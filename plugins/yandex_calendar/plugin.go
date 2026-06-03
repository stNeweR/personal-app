package yandex_calendar

import (
	"encoding/base64"
	"encoding/json"
	"encoding/xml"
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
	return "yandex_calendar"
}

func (p *Plugin) Execute(action string, input json.RawMessage) (any, error) {
	switch action {
	case "list_events":
		return p.listEvents(input)
	default:
		return nil, fmt.Errorf("unknown action %q for yandex_calendar", action)
	}
}

func (p *Plugin) listEvents(input json.RawMessage) (any, error) {
	var req struct {
		Email       string `json:"email"`
		AppPassword string `json:"app_password"`
		TimeMin     string `json:"time_min"`
		TimeMax     string `json:"time_max"`
	}
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}
	if req.Email == "" || req.AppPassword == "" {
		return nil, fmt.Errorf("email and app_password are required")
	}

	auth := base64.StdEncoding.EncodeToString([]byte(req.Email + ":" + req.AppPassword))

	// 1. Discover calendars
	calendars, err := p.discoverCalendars(req.Email, auth)
	if err != nil {
		return nil, err
	}
	if len(calendars) == 0 {
		return nil, fmt.Errorf("no calendars found for user")
	}

	// 2. Query events from all calendars
	reportBody := fmt.Sprintf(`<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:prop>
    <C:calendar-data />
  </D:prop>
  <C:filter>
    <C:comp-filter name="VCALENDAR">
      <C:comp-filter name="VEVENT">
        <C:time-range start="%s" end="%s"/>
      </C:comp-filter>
    </C:comp-filter>
  </C:filter>
</C:calendar-query>`, req.TimeMin, req.TimeMax)

	var allEvents []map[string]string
	seen := make(map[string]bool)

	for _, calURL := range calendars {
		events, err := p.queryCalendar(calURL, auth, reportBody)
		if err != nil {
			continue // skip failed calendars
		}
		for _, ev := range events {
			if !seen[ev["id"]] {
				seen[ev["id"]] = true
				allEvents = append(allEvents, ev)
			}
		}
	}

	return allEvents, nil
}

func (p *Plugin) discoverCalendars(email, auth string) ([]string, error) {
	baseURL := fmt.Sprintf("https://caldav.yandex.ru/calendars/%s/", email)

	propfindBody := `<?xml version="1.0" encoding="utf-8"?>
<D:propfind xmlns:D="DAV:">
  <D:prop>
    <D:resourcetype />
  </D:prop>
</D:propfind>`

	httpReq, err := http.NewRequest("PROPFIND", baseURL, strings.NewReader(propfindBody))
	if err != nil {
		return nil, err
	}
	httpReq.Header.Set("Authorization", "Basic "+auth)
	httpReq.Header.Set("Content-Type", "text/xml; charset=utf-8")
	httpReq.Header.Set("Depth", "1")

	resp, err := p.client.Do(httpReq)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	body, _ := io.ReadAll(resp.Body)
	if resp.StatusCode != http.StatusOK && resp.StatusCode != http.StatusMultiStatus {
		return nil, fmt.Errorf("caldav propfind error %d", resp.StatusCode)
	}

	var calendars []string
	decoder := xml.NewDecoder(strings.NewReader(string(body)))
	var currentHref string
	inCalendar := false
	for {
		tok, err := decoder.Token()
		if err != nil {
			break
		}
		switch se := tok.(type) {
		case xml.StartElement:
			if se.Name.Local == "href" {
				var href string
				if err := decoder.DecodeElement(&href, &se); err == nil {
					currentHref = href
				}
			}
			if se.Name.Local == "calendar" {
				inCalendar = true
			}
		case xml.EndElement:
			if se.Name.Local == "response" {
				if inCalendar && currentHref != "" {
					calendars = append(calendars, "https://caldav.yandex.ru"+currentHref)
				}
				currentHref = ""
				inCalendar = false
			}
		}
	}

	return calendars, nil
}

func (p *Plugin) queryCalendar(calURL, auth, reportBody string) ([]map[string]string, error) {
	httpReq, err := http.NewRequest("REPORT", calURL, strings.NewReader(reportBody))
	if err != nil {
		return nil, err
	}
	httpReq.Header.Set("Authorization", "Basic "+auth)
	httpReq.Header.Set("Content-Type", "text/xml; charset=utf-8")
	httpReq.Header.Set("Depth", "1")

	resp, err := p.client.Do(httpReq)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	body, _ := io.ReadAll(resp.Body)
	if resp.StatusCode != http.StatusOK && resp.StatusCode != http.StatusMultiStatus {
		return nil, fmt.Errorf("caldav report error %d", resp.StatusCode)
	}

	return parseCaldavEvents(string(body))
}

func parseCaldavEvents(xmlBody string) ([]map[string]string, error) {
	type PropStat struct {
		Prop struct {
			CalendarData string `xml:"calendar-data"`
		} `xml:"prop"`
	}
	type Response struct {
		PropStat PropStat `xml:"propstat"`
	}
	type MultiStatus struct {
		Responses []Response `xml:"response"`
	}

	var ms MultiStatus
	if err := xml.Unmarshal([]byte(xmlBody), &ms); err != nil {
		return nil, fmt.Errorf("failed to parse caldav xml: %w", err)
	}

	var events []map[string]string
	for _, r := range ms.Responses {
		ics := r.PropStat.Prop.CalendarData
		if ics == "" {
			continue
		}
		ev := parseICSEvent(ics)
		if ev["id"] != "" {
			events = append(events, ev)
		}
	}

	return events, nil
}

func parseICSEvent(ics string) map[string]string {
	event := map[string]string{}
	inEvent := false
	lines := strings.Split(ics, "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "BEGIN:VEVENT" {
			inEvent = true
			continue
		}
		if line == "END:VEVENT" {
			break
		}
		if !inEvent {
			continue
		}
		if strings.HasPrefix(line, "SUMMARY:") {
			event["summary"] = strings.TrimPrefix(line, "SUMMARY:")
		} else if strings.HasPrefix(line, "UID:") {
			event["id"] = strings.TrimPrefix(line, "UID:")
		} else if strings.HasPrefix(line, "DESCRIPTION:") {
			event["description"] = strings.TrimPrefix(line, "DESCRIPTION:")
		} else if strings.HasPrefix(line, "DTSTART") {
			event["start"] = extractValue(line)
			event["start_tz"] = extractTZID(line)
		} else if strings.HasPrefix(line, "DTEND") {
			event["end"] = extractValue(line)
			event["end_tz"] = extractTZID(line)
		}
	}
	return event
}

func extractValue(line string) string {
	idx := strings.Index(line, ":")
	if idx == -1 {
		return line
	}
	return line[idx+1:]
}

func extractTZID(line string) string {
	// DTSTART;TZID=Europe/Moscow:20251222T110000
	const prefix = "TZID="
	idx := strings.Index(line, prefix)
	if idx == -1 {
		return ""
	}
	rest := line[idx+len(prefix):]
	endIdx := strings.Index(rest, ":")
	if endIdx == -1 {
		return rest
	}
	return rest[:endIdx]
}

func init() {
	shared.Register(&Plugin{client: &http.Client{Timeout: 15 * time.Second}})
}
