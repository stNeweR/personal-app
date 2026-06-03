package shared

import (
	"encoding/json"
	"fmt"
)

// Plugin defines the interface for all plugins.
type Plugin interface {
	Name() string
	Execute(action string, input json.RawMessage) (any, error)
}

var registry = make(map[string]Plugin)

// Register adds a plugin to the registry.
func Register(p Plugin) {
	registry[p.Name()] = p
}

// Execute looks up a plugin by name and runs the given action.
func Execute(name, action string, input json.RawMessage) (any, error) {
	p, ok := registry[name]
	if !ok {
		return nil, fmt.Errorf("plugin %q not found", name)
	}
	return p.Execute(action, input)
}
