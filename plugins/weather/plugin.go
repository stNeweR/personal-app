package weather

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"time"

	"personal-app/plugins/shared"
)

const apiBase = "https://api.open-meteo.com/v1/forecast"

type Plugin struct {
	client *http.Client
}

func (p *Plugin) Name() string {
	return "weather"
}

func (p *Plugin) Execute(action string, input json.RawMessage) (any, error) {
	switch action {
	case "current":
		return p.current(input)
	case "forecast":
		return p.forecast(input)
	default:
		return nil, fmt.Errorf("unknown action %q for weather", action)
	}
}

func (p *Plugin) current(input json.RawMessage) (any, error) {
	var req struct {
		Latitude  float64 `json:"latitude"`
		Longitude float64 `json:"longitude"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}

	u, _ := url.Parse(apiBase)
	q := u.Query()
	q.Set("latitude", fmt.Sprintf("%f", req.Latitude))
	q.Set("longitude", fmt.Sprintf("%f", req.Longitude))
	q.Set("current", "temperature_2m,relative_humidity_2m,weather_code")
	u.RawQuery = q.Encode()

	resp, err := p.client.Get(u.String())
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	body, _ := io.ReadAll(resp.Body)

	var data any
	if err := json.Unmarshal(body, &data); err != nil {
		return nil, err
	}
	return data, nil
}

func (p *Plugin) forecast(input json.RawMessage) (any, error) {
	var req struct {
		Latitude  float64 `json:"latitude"`
		Longitude float64 `json:"longitude"`
		Days      int     `json:"days"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.Days <= 0 || req.Days > 16 {
		req.Days = 7
	}

	u, _ := url.Parse(apiBase)
	q := u.Query()
	q.Set("latitude", fmt.Sprintf("%f", req.Latitude))
	q.Set("longitude", fmt.Sprintf("%f", req.Longitude))
	q.Set("daily", "temperature_2m_max,temperature_2m_min,weather_code")
	q.Set("forecast_days", fmt.Sprintf("%d", req.Days))
	u.RawQuery = q.Encode()

	resp, err := p.client.Get(u.String())
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	body, _ := io.ReadAll(resp.Body)

	var data any
	if err := json.Unmarshal(body, &data); err != nil {
		return nil, err
	}
	return data, nil
}

func init() {
	shared.Register(&Plugin{client: &http.Client{Timeout: 10 * time.Second}})
}
