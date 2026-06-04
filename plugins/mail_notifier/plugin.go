package mail_notifier

import (
	"crypto/rand"
	"crypto/tls"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"net"
	"net/smtp"
	"strings"
	"time"

	"personal-app/plugins/shared"
)

type Plugin struct{}

func (p *Plugin) Name() string {
	return "mail_notifier"
}

func (p *Plugin) Execute(action string, input json.RawMessage) (any, error) {
	switch action {
	case "send_email":
		return p.sendEmail(input)
	case "generate_token":
		return p.generateToken(input)
	default:
		return nil, fmt.Errorf("unknown action %q for mail_notifier", action)
	}
}

type sendEmailRequest struct {
	Host       string `json:"host"`
	Port       int    `json:"port"`
	Username   string `json:"username"`
	Password   string `json:"password"`
	Encryption string `json:"encryption"`
	FromAddr   string `json:"from_address"`
	FromName   string `json:"from_name"`
	To         string `json:"to"`
	Subject    string `json:"subject"`
	Body       string `json:"body"`
	IsHTML     bool   `json:"is_html"`
}

func (p *Plugin) sendEmail(input json.RawMessage) (any, error) {
	var req sendEmailRequest
	if err := json.Unmarshal(input, &req); err != nil {
		return nil, err
	}

	if req.Host == "" || req.Port == 0 {
		return nil, fmt.Errorf("host and port are required")
	}
	if req.To == "" {
		return nil, fmt.Errorf("to is required")
	}
	if req.FromAddr == "" {
		return nil, fmt.Errorf("from_address is required")
	}
	if req.Subject == "" {
		return nil, fmt.Errorf("subject is required")
	}
	if req.Body == "" {
		return nil, fmt.Errorf("body is required")
	}

	addr := fmt.Sprintf("%s:%d", req.Host, req.Port)
	from := req.FromAddr
	header := buildHeader(from, req.FromName, req.To, req.Subject, req.IsHTML)
	message := header + req.Body

	enc := strings.ToLower(strings.TrimSpace(req.Encryption))

	var auth smtp.Auth
	if req.Username != "" {
		auth = smtp.PlainAuth("", req.Username, req.Password, req.Host)
	}

	if enc == "ssl" || enc == "tls" && req.Port == 465 {
		if err := sendImplicitTLS(addr, req.Host, auth, from, []string{req.To}, []byte(message)); err != nil {
			return nil, err
		}
	} else {
		if err := smtp.SendMail(addr, auth, from, []string{req.To}, []byte(message)); err != nil {
			return nil, fmt.Errorf("smtp send failed: %w", err)
		}
	}

	id, _ := generateID()
	return map[string]any{
		"ok":          true,
		"message_id":  id,
		"to":          req.To,
		"subject":     req.Subject,
		"sent_at":     time.Now().UTC().Format(time.RFC3339),
		"description": "Email sent successfully",
	}, nil
}

func sendImplicitTLS(addr, host string, auth smtp.Auth, from string, to []string, msg []byte) error {
	conn, err := tls.Dial("tcp", addr, &tls.Config{ServerName: host})
	if err != nil {
		return fmt.Errorf("tls dial failed: %w", err)
	}
	defer conn.Close()

	client, err := smtp.NewClient(conn, host)
	if err != nil {
		return fmt.Errorf("smtp client failed: %w", err)
	}
	defer client.Close()

	if auth != nil {
		if ok, _ := client.Extension("AUTH"); ok {
			if err := client.Auth(auth); err != nil {
				return fmt.Errorf("smtp auth failed: %w", err)
			}
		}
	}

	if err := client.Mail(from); err != nil {
		return fmt.Errorf("smtp MAIL failed: %w", err)
	}
	for _, rcpt := range to {
		if err := client.Rcpt(rcpt); err != nil {
			return fmt.Errorf("smtp RCPT failed: %w", err)
		}
	}
	w, err := client.Data()
	if err != nil {
		return fmt.Errorf("smtp DATA failed: %w", err)
	}
	if _, err := w.Write(msg); err != nil {
		return fmt.Errorf("smtp write failed: %w", err)
	}
	if err := w.Close(); err != nil {
		return fmt.Errorf("smtp close failed: %w", err)
	}
	return client.Quit()
}

func buildHeader(fromAddr, fromName, to, subject string, isHTML bool) string {
	from := fromAddr
	if fromName != "" {
		from = fmt.Sprintf("%s <%s>", fromName, fromAddr)
	}
	contentType := "text/plain; charset=UTF-8"
	if isHTML {
		contentType = "text/html; charset=UTF-8"
	}
	var b strings.Builder
	b.WriteString(fmt.Sprintf("From: %s\r\n", from))
	b.WriteString(fmt.Sprintf("To: %s\r\n", to))
	b.WriteString(fmt.Sprintf("Subject: %s\r\n", subject))
	b.WriteString("MIME-Version: 1.0\r\n")
	b.WriteString(fmt.Sprintf("Content-Type: %s\r\n", contentType))
	b.WriteString("Date: " + time.Now().UTC().Format(time.RFC1123Z) + "\r\n")
	b.WriteString("\r\n")
	return b.String()
}

func generateID() (string, error) {
	buf := make([]byte, 8)
	if _, err := rand.Read(buf); err != nil {
		return "", err
	}
	return hex.EncodeToString(buf), nil
}

func (p *Plugin) generateToken(input json.RawMessage) (any, error) {
	buf := make([]byte, 32)
	if _, err := rand.Read(buf); err != nil {
		return nil, fmt.Errorf("failed to generate token: %w", err)
	}
	return map[string]any{
		"token": hex.EncodeToString(buf),
	}, nil
}

func init() {
	_ = net.IPv4len
	shared.Register(&Plugin{})
}
