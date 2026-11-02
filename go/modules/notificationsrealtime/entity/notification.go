package entity

import "time"

type Notification struct {
	ID     string                 `json:"id"`
	UserID string                 `json:"user_id"`
	Title  string                 `json:"title"`
	Body   string                 `json:"body"`
	Type   string                 `json:"type"`
	Data   map[string]interface{} `json:"data"`
	Read   bool                   `json:"read"`
	SentAt time.Time              `json:"sent_at"`
	ReadAt *time.Time             `json:"read_at,omitempty"`
}

type Subscription struct {
	UserID       string    `json:"user_id"`
	Channel      string    `json:"channel"`
	SubscribedAt time.Time `json:"subscribed_at"`
}

type PushToken struct {
	UserID       string    `json:"user_id"`
	Token        string    `json:"token"`
	Platform     string    `json:"platform"`
	RegisteredAt time.Time `json:"registered_at"`
}
