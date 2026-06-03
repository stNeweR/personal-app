package calendar

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"time"

	"personal-app/plugins/shared"
)

type Event struct {
	ID          string `json:"id"`
	Summary     string `json:"summary"`
	Description string `json:"description"`
	Start       string `json:"start"`
	End         string `json:"end"`
}

type Plugin struct {
	client *http.Client
}

func (p *Plugin) Name() string {
	return "calendar"
}

func (p *Plugin) Execute(action string, input json.RawMessage) (any, error) {
	switch action {
	case "list_events":
		return p.listEvents(input)
	default:
		return nil, fmt.Errorf("unknown action %q for calendar", action)
	}
}

func (p *Plugin) listEvents(input json.RawMessage) (any, error) {
	var req struct {
		AccessToken string `json:"access_token"`
		TimeMin     string `json:"time_min"`
		TimeMax     string `json:"time_max"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.AccessToken == "" {
		return nil, fmt.Errorf("access_token is required")
	}

	u, _ := url.Parse("https://www.googleapis.com/calendar/v3/calendars/primary/events")
	q := u.Query()
	q.Set("timeMin", req.TimeMin)
	q.Set("timeMax", req.TimeMax)
	q.Set("orderBy", "startTime")
	q.Set("singleEvents", "true")
	u.RawQuery = q.Encode()

	httpReq, err := http.NewRequest("GET", u.String(), nil)
	if err != nil {
		return nil, err
	}
	httpReq.Header.Set("Authorization", "Bearer "+req.AccessToken)

	resp, err := p.client.Do(httpReq)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	body, _ := io.ReadAll(resp.Body)

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("google calendar api error %d: %s", resp.StatusCode, string(body))
	}

	var data struct {
		Items []struct {
			ID          string `json:"id"`
			Summary     string `json:"summary"`
			Description string `json:"description"`
			Start       struct {
				DateTime string `json:"dateTime"`
				Date     string `json:"date"`
			} `json:"start"`
			End struct {
				DateTime string `json:"dateTime"`
				Date     string `json:"date"`
			} `json:"end"`
		} `json:"items"`
	}
	if err := json.Unmarshal(body, &data); err != nil {
		return nil, err
	}

	events := make([]Event, 0, len(data.Items))
	for _, item := range data.Items {
		start := item.Start.DateTime
		if start == "" {
			start = item.Start.Date
		}
		end := item.End.DateTime
		if end == "" {
			end = item.End.Date
		}
		events = append(events, Event{
			ID:          item.ID,
			Summary:     item.Summary,
			Description: item.Description,
			Start:       start,
			End:         end,
		})
	}

	return events, nil
}

func init() {
	shared.Register(&Plugin{client: &http.Client{Timeout: 15 * time.Second}})
}
