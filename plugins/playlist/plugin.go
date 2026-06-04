package playlist

import (
	"encoding/json"
	"fmt"
	"net/url"
	"sync"

	"personal-app/plugins/shared"
)

type Plugin struct {
	mu   sync.RWMutex
	urls map[int64]string
}

func (p *Plugin) Name() string {
	return "playlist"
}

func (p *Plugin) Execute(action string, input json.RawMessage) (any, error) {
	switch action {
	case "set_url":
		return p.setURL(input)
	case "get_url":
		return p.getURL(input)
	case "delete_url":
		return p.deleteURL(input)
	default:
		return nil, fmt.Errorf("unknown action %q for playlist", action)
	}
}

type setRequest struct {
	UserID int64  `json:"user_id"`
	URL    string `json:"url"`
}

type getRequest struct {
	UserID int64 `json:"user_id"`
}

func (p *Plugin) setURL(input json.RawMessage) (any, error) {
	var req setRequest
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.UserID == 0 {
		return nil, fmt.Errorf("user_id is required")
	}
	if req.URL == "" {
		return nil, fmt.Errorf("url is required")
	}
	parsed, err := url.ParseRequestURI(req.URL)
	if err != nil {
		return nil, fmt.Errorf("invalid url: %w", err)
	}
	if parsed.Scheme != "http" && parsed.Scheme != "https" {
		return nil, fmt.Errorf("url must be http or https")
	}
	if parsed.Host == "" {
		return nil, fmt.Errorf("url must contain a host")
	}

	p.mu.Lock()
	p.urls[req.UserID] = parsed.String()
	p.mu.Unlock()

	return map[string]any{"user_id": req.UserID, "url": parsed.String()}, nil
}

func (p *Plugin) getURL(input json.RawMessage) (any, error) {
	var req getRequest
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.UserID == 0 {
		return nil, fmt.Errorf("user_id is required")
	}

	p.mu.RLock()
	url, ok := p.urls[req.UserID]
	p.mu.RUnlock()

	return map[string]any{"user_id": req.UserID, "url": url, "set": ok}, nil
}

func (p *Plugin) deleteURL(input json.RawMessage) (any, error) {
	var req getRequest
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.UserID == 0 {
		return nil, fmt.Errorf("user_id is required")
	}

	p.mu.Lock()
	delete(p.urls, req.UserID)
	p.mu.Unlock()

	return map[string]any{"user_id": req.UserID, "deleted": true}, nil
}

func init() {
	shared.Register(&Plugin{urls: make(map[int64]string)})
}
