package entity

import "time"

type LiveStream struct {
	ID          string                 `json:"id"`
	UserID      string                 `json:"user_id"`
	Title       string                 `json:"title"`
	Description string                 `json:"description"`
	Thumbnail   string                 `json:"thumbnail"`
	Status      string                 `json:"status"`
	RoomID      string                 `json:"room_id"`
	ViewerCount int                    `json:"viewer_count"`
	Metadata    map[string]interface{} `json:"metadata"`
	StartedAt   time.Time              `json:"started_at"`
	EndedAt     *time.Time             `json:"ended_at,omitempty"`
	CreatedAt   time.Time              `json:"created_at"`
	UpdatedAt   time.Time              `json:"updated_at"`
}

type LiveMessage struct {
	ID        string                 `json:"id"`
	StreamID  string                 `json:"stream_id"`
	UserID    string                 `json:"user_id"`
	Username  string                 `json:"username"`
	Content   string                 `json:"content"`
	Type      string                 `json:"type"`
	Metadata  map[string]interface{} `json:"metadata,omitempty"`
	Timestamp time.Time              `json:"timestamp"`
}

type LiveGift struct {
	ID         string    `json:"id"`
	StreamID   string    `json:"stream_id"`
	SenderID   string    `json:"sender_id"`
	ReceiverID string    `json:"receiver_id"`
	Name       string    `json:"name"`
	Value      float64   `json:"value"`
	Currency   string    `json:"currency"`
	Timestamp  time.Time `json:"timestamp"`
}

type StreamStats struct {
	StreamID    string  `json:"stream_id"`
	PeakViewers int     `json:"peak_viewers"`
	TotalViews  int     `json:"total_views"`
	TotalLikes  int     `json:"total_likes"`
	TotalGifts  int     `json:"total_gifts"`
	GiftValue   float64 `json:"gift_value"`
	DurationSec int     `json:"duration_sec"`
}
