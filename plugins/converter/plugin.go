package converter

import (
	"encoding/json"
	"fmt"
	"math"

	"personal-app/plugins/shared"
)

// Hardcoded rates to USD for demo purposes (no external API key needed).
var rates = map[string]float64{
	"USD": 1.0,
	"EUR": 0.92,
	"GBP": 0.79,
	"JPY": 150.0,
	"RUB": 92.5,
	"KZT": 500.0,
}

var units = map[string]float64{
	"m":    1.0,
	"km":   1000.0,
	"cm":   0.01,
	"mm":   0.001,
	"ft":   0.3048,
	"in":   0.0254,
	"mi":   1609.34,
	"kg":   1.0,
	"g":    0.001,
	"lb":   0.453592,
	"oz":   0.0283495,
}

type Plugin struct{}

func (p *Plugin) Name() string {
	return "converter"
}

func (p *Plugin) Execute(action string, input json.RawMessage) (any, error) {
	switch action {
	case "currency":
		return p.currency(input)
	case "unit":
		return p.unit(input)
	default:
		return nil, fmt.Errorf("unknown action %q for converter", action)
	}
}

func (p *Plugin) currency(input json.RawMessage) (map[string]any, error) {
	var req struct {
		Amount float64 `json:"amount"`
		From   string  `json:"from"`
		To     string  `json:"to"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	fromRate, ok := rates[req.From]
	if !ok {
		return nil, fmt.Errorf("unknown currency %q", req.From)
	}
	toRate, ok := rates[req.To]
	if !ok {
		return nil, fmt.Errorf("unknown currency %q", req.To)
	}
	usd := req.Amount / fromRate
	result := usd * toRate
	return map[string]any{
		"amount":   math.Round(result*100) / 100,
		"from":     req.From,
		"to":       req.To,
		"original": req.Amount,
	}, nil
}

func (p *Plugin) unit(input json.RawMessage) (map[string]any, error) {
	var req struct {
		Amount float64 `json:"amount"`
		From   string  `json:"from"`
		To     string  `json:"to"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	fromFactor, ok := units[req.From]
	if !ok {
		return nil, fmt.Errorf("unknown unit %q", req.From)
	}
	toFactor, ok := units[req.To]
	if !ok {
		return nil, fmt.Errorf("unknown unit %q", req.To)
	}
	base := req.Amount * fromFactor
	result := base / toFactor
	return map[string]any{
		"amount":   math.Round(result*10000) / 10000,
		"from":     req.From,
		"to":       req.To,
		"original": req.Amount,
	}, nil
}

func init() {
	shared.Register(&Plugin{})
}
