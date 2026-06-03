package todoist

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"slices"
	"strings"
	"time"

	"github.com/google/uuid"
	"personal-app/plugins/shared"
)

const apiBase = "https://api.todoist.com/api/v1"
const syncPath = "/sync"

type Plugin struct {
	client *http.Client
}

func (p *Plugin) Name() string {
	return "todoist"
}

func (p *Plugin) Execute(action string, input json.RawMessage) (any, error) {
	switch action {
	case "list_tasks":
		return p.listTasks(input)
	case "create_task":
		return p.createTask(input)
	case "update_task":
		return p.updateTask(input)
	case "complete_task":
		return p.completeTask(input)
	case "reopen_task":
		return p.reopenTask(input)
	case "delete_task":
		return p.deleteTask(input)
	default:
		return nil, fmt.Errorf("unknown action %q for todoist", action)
	}
}

type Task struct {
	ID          string   `json:"id"`
	UserID      string   `json:"user_id"`
	ProjectID   string   `json:"project_id"`
	SectionID   string   `json:"section_id"`
	ParentID    string   `json:"parent_id"`
	AddedByUID  string   `json:"added_by_uid"`
	Labels      []string `json:"labels"`
	Checked     bool     `json:"checked"`
	IsDeleted   bool     `json:"is_deleted"`
	AddedAt     string   `json:"added_at"`
	CompletedAt string   `json:"completed_at"`
	Due         *Due     `json:"due,omitempty"`
	Priority    int      `json:"priority"`
	ChildOrder  int      `json:"child_order"`
	Content     string   `json:"content"`
	Description string   `json:"description"`
	NoteCount   int      `json:"note_count"`
	DayOrder    int      `json:"day_order"`
	URL         string   `json:"url"`
}

type Due struct {
	Date        string `json:"date"`
	Datetime    string `json:"datetime"`
	String      string `json:"string"`
	Timezone    string `json:"timezone"`
	IsRecurring bool   `json:"is_recurring"`
	Lang        string `json:"lang"`
}

func doSync(client *http.Client, token string, form url.Values) ([]byte, error) {
	req, err := http.NewRequest(http.MethodPost, apiBase+syncPath, strings.NewReader(form.Encode()))
	if err != nil {
		return nil, err
	}
	req.Header.Set("Authorization", "Bearer "+token)
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	body, _ := io.ReadAll(resp.Body)
	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		return nil, fmt.Errorf("todoist api error %d: %s", resp.StatusCode, strings.TrimSpace(string(body)))
	}
	return body, nil
}

func (p *Plugin) listTasks(input json.RawMessage) (any, error) {
	var req struct {
		Token    string `json:"token"`
		Timezone string `json:"timezone"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.Token == "" {
		return nil, fmt.Errorf("token is required")
	}

	form := url.Values{}
	form.Set("resource_types", `["items"]`)
	body, err := doSync(p.client, req.Token, form)
	if err != nil {
		return nil, err
	}

	var response struct {
		Items []Task `json:"items"`
	}
	if err := json.Unmarshal(body, &response); err != nil {
		return nil, fmt.Errorf("failed to parse tasks: %w", err)
	}
	if response.Items == nil {
		return []Task{}, nil
	}

	loc := time.UTC
	if req.Timezone != "" {
		if loaded, err := time.LoadLocation(req.Timezone); err == nil {
			loc = loaded
		}
	}
	today := time.Now().In(loc).Format("2006-01-02")

	filtered := make([]Task, 0, len(response.Items))
	for _, task := range response.Items {
		if task.Due == nil {
			continue
		}
		dueDate := task.Due.Date
		if dueDate == "" && len(task.Due.Datetime) >= 10 {
			dueDate = task.Due.Datetime[:10]
		}
		if dueDate != "" && dueDate <= today {
			filtered = append(filtered, task)
		}
	}

	slices.SortStableFunc(filtered, func(a, b Task) int {
		return b.Priority - a.Priority
	})

	return filtered, nil
}

func (p *Plugin) createTask(input json.RawMessage) (any, error) {
	var req struct {
		Token       string   `json:"token"`
		Content     string   `json:"content"`
		Description string   `json:"description"`
		ProjectID   string   `json:"project_id"`
		SectionID   string   `json:"section_id"`
		ParentID    string   `json:"parent_id"`
		Order       *int     `json:"order"`
		Labels      []string `json:"labels"`
		Priority    int      `json:"priority"`
		AssigneeID  string   `json:"assignee_id"`
		DueString   string   `json:"due_string"`
		DueDate     string   `json:"due_date"`
		DueDatetime string   `json:"due_datetime"`
		DueLang     string   `json:"due_lang"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.Token == "" {
		return nil, fmt.Errorf("token is required")
	}
	if req.Content == "" {
		return nil, fmt.Errorf("content is required")
	}

	args := map[string]any{
		"content": req.Content,
	}
	if req.Description != "" {
		args["description"] = req.Description
	}
	if req.ProjectID != "" {
		args["project_id"] = req.ProjectID
	}
	if req.SectionID != "" {
		args["section_id"] = req.SectionID
	}
	if req.ParentID != "" {
		args["parent_id"] = req.ParentID
	}
	if req.Order != nil {
		args["order"] = *req.Order
	}
	if len(req.Labels) > 0 {
		args["labels"] = req.Labels
	}
	if req.Priority > 0 {
		args["priority"] = req.Priority
	}
	if req.AssigneeID != "" {
		args["assignee_id"] = req.AssigneeID
	}
	if req.DueString != "" {
		args["due_string"] = req.DueString
	}
	if req.DueDate != "" {
		args["due_date"] = req.DueDate
	}
	if req.DueDatetime != "" {
		args["due_datetime"] = req.DueDatetime
	}
	if req.DueLang != "" {
		args["due_lang"] = req.DueLang
	}

	tempID := fmt.Sprintf("tmp-%s-%d", strings.ReplaceAll(uuid.NewString(), "-", ""), time.Now().UnixMilli())
	cmdUUID := strings.ReplaceAll(uuid.NewString(), "-", "")
	cmd := map[string]any{
		"type":    "item_add",
		"temp_id": tempID,
		"uuid":    cmdUUID,
		"args":    args,
	}
	cmdJSON, err := json.Marshal([]map[string]any{cmd})
	if err != nil {
		return nil, err
	}

	form := url.Values{}
	form.Set("commands", string(cmdJSON))
	body, err := doSync(p.client, req.Token, form)
	if err != nil {
		return nil, err
	}

	var response struct {
		SyncStatus    map[string]string `json:"sync_status"`
		TempIDMapping map[string]string `json:"temp_id_mapping"`
	}
	if err := json.Unmarshal(body, &response); err != nil {
		return nil, fmt.Errorf("failed to parse create response: %w", err)
	}
	if status, ok := response.SyncStatus[cmdUUID]; !ok || status != "ok" {
		return nil, fmt.Errorf("todoist create task failed: %v", response.SyncStatus)
	}
	realID, ok := response.TempIDMapping[tempID]
	if !ok {
		return nil, fmt.Errorf("todoist create task: no temp_id mapping returned")
	}

	return Task{
		ID:          realID,
		Content:     req.Content,
		Description: req.Description,
		ProjectID:   req.ProjectID,
		SectionID:   req.SectionID,
		ParentID:    req.ParentID,
		Labels:      req.Labels,
		Priority:    req.Priority,
	}, nil
}

func (p *Plugin) updateTask(input json.RawMessage) (any, error) {
	var req struct {
		Token       string   `json:"token"`
		ID          string   `json:"id"`
		Content     string   `json:"content"`
		Description string   `json:"description"`
		Labels      []string `json:"labels"`
		Priority    int      `json:"priority"`
		AssigneeID  string   `json:"assignee_id"`
		DueString   string   `json:"due_string"`
		DueDate     string   `json:"due_date"`
		DueDatetime string   `json:"due_datetime"`
		DueLang     string   `json:"due_lang"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.Token == "" {
		return nil, fmt.Errorf("token is required")
	}
	if req.ID == "" {
		return nil, fmt.Errorf("id is required")
	}

	args := map[string]any{
		"id": req.ID,
	}
	if req.Content != "" {
		args["content"] = req.Content
	}
	if req.Description != "" {
		args["description"] = req.Description
	}
	if len(req.Labels) > 0 {
		args["labels"] = req.Labels
	}
	if req.Priority > 0 {
		args["priority"] = req.Priority
	}
	if req.AssigneeID != "" {
		args["assignee_id"] = req.AssigneeID
	}
	if req.DueString != "" {
		args["due_string"] = req.DueString
	}
	if req.DueDate != "" {
		args["due_date"] = req.DueDate
	}
	if req.DueDatetime != "" {
		args["due_datetime"] = req.DueDatetime
	}
	if req.DueLang != "" {
		args["due_lang"] = req.DueLang
	}
	if len(args) == 1 {
		return nil, fmt.Errorf("no fields to update")
	}

	if err := p.runCommand(req.Token, "item_update", args); err != nil {
		return nil, err
	}
	return map[string]string{"id": req.ID, "updated": "true"}, nil
}

func (p *Plugin) completeTask(input json.RawMessage) (any, error) {
	var req struct {
		Token string `json:"token"`
		ID    string `json:"id"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.Token == "" {
		return nil, fmt.Errorf("token is required")
	}
	if req.ID == "" {
		return nil, fmt.Errorf("id is required")
	}
	if err := p.runCommand(req.Token, "item_close", map[string]any{"id": req.ID}); err != nil {
		return nil, err
	}
	return map[string]string{"id": req.ID, "completed": "true"}, nil
}

func (p *Plugin) reopenTask(input json.RawMessage) (any, error) {
	var req struct {
		Token string `json:"token"`
		ID    string `json:"id"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.Token == "" {
		return nil, fmt.Errorf("token is required")
	}
	if req.ID == "" {
		return nil, fmt.Errorf("id is required")
	}
	if err := p.runCommand(req.Token, "item_uncomplete", map[string]any{"id": req.ID}); err != nil {
		return nil, err
	}
	return map[string]string{"id": req.ID, "reopened": "true"}, nil
}

func (p *Plugin) deleteTask(input json.RawMessage) (any, error) {
	var req struct {
		Token string `json:"token"`
		ID    string `json:"id"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.Token == "" {
		return nil, fmt.Errorf("token is required")
	}
	if req.ID == "" {
		return nil, fmt.Errorf("id is required")
	}
	if err := p.runCommand(req.Token, "item_delete", map[string]any{"id": req.ID}); err != nil {
		return nil, err
	}
	return map[string]string{"id": req.ID, "deleted": "true"}, nil
}

func (p *Plugin) runCommand(token, commandType string, args map[string]any) error {
	cmdUUID := strings.ReplaceAll(uuid.NewString(), "-", "")
	cmd := map[string]any{
		"type": commandType,
		"uuid": cmdUUID,
		"args": args,
	}
	cmdJSON, err := json.Marshal([]map[string]any{cmd})
	if err != nil {
		return err
	}
	form := url.Values{}
	form.Set("commands", string(cmdJSON))
	body, err := doSync(p.client, token, form)
	if err != nil {
		return err
	}
	var response struct {
		SyncStatus map[string]string `json:"sync_status"`
	}
	if err := json.Unmarshal(body, &response); err != nil {
		return fmt.Errorf("failed to parse sync response: %w", err)
	}
	if status, ok := response.SyncStatus[cmdUUID]; !ok || status != "ok" {
		return fmt.Errorf("todoist %s failed: %v", commandType, response.SyncStatus)
	}
	return nil
}

func init() {
	shared.Register(&Plugin{client: &http.Client{Timeout: 15 * time.Second}})
}
