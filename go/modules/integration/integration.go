package integration

import (
	"bytes"
	"crypto/rand"
	"encoding/json"
	"net/http"
	"sync"
	"time"
)

type Webhook struct {
	ID        string    `json:"id"`
	URL       string    `json:"url"`
	Events    []string  `json:"events"`
	Secret    string    `json:"secret"`
	Active    bool      `json:"active"`
	CreatedAt time.Time `json:"createdAt"`
}

type WebhookPayload struct {
	WebhookID string      `json:"webhookId"`
	Event     string      `json:"event"`
	Data      interface{} `json:"data"`
	Timestamp time.Time   `json:"timestamp"`
}

type IntegrationService struct {
	mu       sync.RWMutex
	webhooks map[string]*Webhook
}

func NewIntegrationService() *IntegrationService {
	return &IntegrationService{
		webhooks: make(map[string]*Webhook),
	}
}

func (s *IntegrationService) RegisterWebhook(url string, events []string, secret string) *Webhook {
	s.mu.Lock()
	defer s.mu.Unlock()

	webhook := &Webhook{
		ID:        generateWebhookID(),
		URL:       url,
		Events:    events,
		Secret:    secret,
		Active:    true,
		CreatedAt: time.Now(),
	}
	s.webhooks[webhook.ID] = webhook
	return webhook
}

func (s *IntegrationService) TriggerWebhooks(event string, data interface{}) []error {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var errors []error

	for _, webhook := range s.webhooks {
		if !webhook.Active {
			continue
		}

		matches := false
		for _, e := range webhook.Events {
			if e == event {
				matches = true
				break
			}
		}

		if !matches {
			continue
		}

		payload := WebhookPayload{
			WebhookID: webhook.ID,
			Event:     event,
			Data:      data,
			Timestamp: time.Now(),
		}

		body, err := json.Marshal(payload)
		if err != nil {
			errors = append(errors, err)
			continue
		}

		req, err := http.NewRequest("POST", webhook.URL, bytes.NewBuffer(body))
		if err != nil {
			errors = append(errors, err)
			continue
		}

		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-Webhook-Secret", webhook.Secret)

		client := &http.Client{Timeout: 30 * time.Second}
		_, err = client.Do(req)
		if err != nil {
			errors = append(errors, err)
		}
	}

	return errors
}

func generateWebhookID() string {
	return "wh-" + time.Now().Format("20060102150405") + "-" + randomString(8)
}

func randomString(n int) string {
	const letters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
	b := make([]byte, n)
	rand.Read(b)
	for i := range b {
		b[i] = letters[int(b[i])%len(letters)]
	}
	return string(b)
}
