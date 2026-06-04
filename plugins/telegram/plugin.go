package telegram

import (
	"bytes"
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"

	"personal-app/plugins/shared"
)

type Plugin struct {
	client *http.Client
}

func (p *Plugin) Name() string {
	return "telegram"
}

func (p *Plugin) Execute(action string, input json.RawMessage) (any, error) {
	switch action {
	case "send_message":
		return p.sendMessage(input)
	case "get_me":
		return p.getMe(input)
	case "set_webhook":
		return p.setWebhook(input)
	case "set_commands":
		return p.setCommands(input)
	case "generate_token":
		return p.generateToken(input)
	default:
		return nil, fmt.Errorf("unknown action %q for telegram", action)
	}
}

type authRequest struct {
	BotToken string `json:"bot_token"`
	APIURL   string `json:"api_url"`
}

func (p *Plugin) baseURL(req authRequest) (string, error) {
	if req.BotToken == "" {
		return "", fmt.Errorf("bot_token is required")
	}
	base := strings.TrimRight(req.APIURL, "/")
	if base == "" {
		base = "https://api.telegram.org"
	}
	return base + "/bot" + req.BotToken, nil
}

func (p *Plugin) doJSON(req *http.Request) (map[string]any, error) {
	resp, err := p.client.Do(req)
	if err != nil {
		return nil, fmt.Errorf("telegram request failed: %w", err)
	}
	defer resp.Body.Close()

	body, _ := io.ReadAll(resp.Body)

	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		return nil, fmt.Errorf("telegram api error %d: %s", resp.StatusCode, strings.TrimSpace(string(body)))
	}

	var parsed map[string]any
	if err := json.Unmarshal(body, &parsed); err != nil {
		return nil, fmt.Errorf("failed to parse telegram response: %w", err)
	}

	if ok, _ := parsed["ok"].(bool); !ok {
		return nil, fmt.Errorf("telegram api returned not-ok: %s", strings.TrimSpace(string(body)))
	}

	return parsed, nil
}

func (p *Plugin) sendMessage(input json.RawMessage) (any, error) {
	var req struct {
		authRequest
		ChatID    int64  `json:"chat_id"`
		Text      string `json:"text"`
		ParseMode string `json:"parse_mode"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}

	base, err := p.baseURL(req.authRequest)
	if err != nil {
		return nil, err
	}

	if req.Text == "" {
		return nil, fmt.Errorf("text is required")
	}

	payload := map[string]any{
		"chat_id": req.ChatID,
		"text":    req.Text,
	}
	if req.ParseMode != "" {
		payload["parse_mode"] = req.ParseMode
	}

	body, err := json.Marshal(payload)
	if err != nil {
		return nil, err
	}

	httpReq, err := http.NewRequest(http.MethodPost, base+"/sendMessage", bytes.NewReader(body))
	if err != nil {
		return nil, err
	}
	httpReq.Header.Set("Content-Type", "application/json")

	parsed, err := p.doJSON(httpReq)
	if err != nil {
		return nil, err
	}

	result, _ := parsed["result"].(map[string]any)
	chatID := int64(0)
	if chat, ok := result["chat"].(map[string]any); ok {
		if id, ok := chat["id"].(float64); ok {
			chatID = int64(id)
		}
	}
	return map[string]any{
		"ok":          true,
		"message_id":  result["message_id"],
		"chat_id":     chatID,
		"date":        result["date"],
		"description": parsed["description"],
	}, nil
}

func (p *Plugin) getMe(input json.RawMessage) (any, error) {
	var req authRequest
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}

	base, err := p.baseURL(req)
	if err != nil {
		return nil, err
	}

	httpReq, err := http.NewRequest(http.MethodGet, base+"/getMe", nil)
	if err != nil {
		return nil, err
	}

	parsed, err := p.doJSON(httpReq)
	if err != nil {
		return nil, err
	}

	result, _ := parsed["result"].(map[string]any)
	return map[string]any{
		"id":         result["id"],
		"username":   result["username"],
		"first_name": result["first_name"],
		"is_bot":     result["is_bot"],
	}, nil
}

func (p *Plugin) setWebhook(input json.RawMessage) (any, error) {
	var req struct {
		authRequest
		URL string `json:"url"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}

	base, err := p.baseURL(req.authRequest)
	if err != nil {
		return nil, err
	}

	if req.URL == "" {
		return nil, fmt.Errorf("url is required")
	}

	payload, _ := json.Marshal(map[string]any{"url": req.URL})

	httpReq, err := http.NewRequest(http.MethodPost, base+"/setWebhook", bytes.NewReader(payload))
	if err != nil {
		return nil, err
	}
	httpReq.Header.Set("Content-Type", "application/json")

	parsed, err := p.doJSON(httpReq)
	if err != nil {
		return nil, err
	}

	return map[string]any{
		"ok":          true,
		"description": parsed["description"],
	}, nil
}

func (p *Plugin) setCommands(input json.RawMessage) (any, error) {
	var req struct {
		authRequest
		Commands []map[string]string `json:"commands"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}

	base, err := p.baseURL(req.authRequest)
	if err != nil {
		return nil, err
	}

	if len(req.Commands) == 0 {
		return nil, fmt.Errorf("commands is required")
	}

	payload, _ := json.Marshal(map[string]any{"commands": req.Commands})

	httpReq, err := http.NewRequest(http.MethodPost, base+"/setMyCommands", bytes.NewReader(payload))
	if err != nil {
		return nil, err
	}
	httpReq.Header.Set("Content-Type", "application/json")

	parsed, err := p.doJSON(httpReq)
	if err != nil {
		return nil, err
	}

	return map[string]any{
		"ok":          true,
		"description": parsed["description"],
	}, nil
}

func (p *Plugin) generateToken(input json.RawMessage) (any, error) {
	buf := make([]byte, 16)
	if _, err := rand.Read(buf); err != nil {
		return nil, fmt.Errorf("failed to generate token: %w", err)
	}
	return map[string]any{
		"token": hex.EncodeToString(buf),
	}, nil
}

func init() {
	shared.Register(&Plugin{client: &http.Client{Timeout: 15 * time.Second}})
}
