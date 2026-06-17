package notifier

import (
	"encoding/json"
	"fmt"
	"sync"
	"time"

	"github.com/google/uuid"
	"personal-app/plugins/shared"
)

type Notification struct {
	ID      string    `json:"id"`
	Channel string    `json:"channel"`
	Message string    `json:"message"`
	SentAt  time.Time `json:"sent_at"`
}

type Plugin struct {
	mu            sync.RWMutex
	notifications []Notification
}

func (p *Plugin) Name() string {
	return "notifier"
}

func (p *Plugin) Execute(action string, input json.RawMessage) (any, error) {
	switch action {
	case "send":
		return p.send(input)
	case "history":
		return p.history(), nil
	default:
		return nil, fmt.Errorf("unknown action %q for notifier", action)
	}
}

func (p *Plugin) send(input json.RawMessage) (Notification, error) {
	var req struct {
		Channel string `json:"channel"`
		Message string `json:"message"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return Notification{}, err
	}
	if req.Channel == "" || req.Message == "" {
		return Notification{}, fmt.Errorf("channel and message are required")
	}
	n := Notification{
		ID:      uuid.NewString(),
		Channel: req.Channel,
		Message: req.Message,
		SentAt:  time.Now().UTC(),
	}
	p.mu.Lock()
	p.notifications = append(p.notifications, n)
	p.mu.Unlock()

	// In a real scenario this would call Telegram API or SMTP.
	fmt.Printf("[NOTIFIER:%s] %s\n", req.Channel, req.Message)
	return n, nil
}

func (p *Plugin) history() []Notification {
	p.mu.RLock()
	defer p.mu.RUnlock()
	out := make([]Notification, len(p.notifications))
	copy(out, p.notifications)
	return out
}

func init() {
	shared.Register(&Plugin{notifications: make([]Notification, 0)})
}
