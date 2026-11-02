package entity

import "time"

type Event struct {
	ID          string            `json:"id"`
	UserID      string            `json:"user_id"`
	Title       string            `json:"title"`
	Description string            `json:"description"`
	StartTime   time.Time         `json:"start_time"`
	EndTime     time.Time         `json:"end_time"`
	Attendees   []string          `json:"attendees"`
	RSVPs       map[string]string `json:"rsvps"`
	Metadata    map[string]string `json:"metadata"`
	CreatedAt   time.Time         `json:"created_at"`
	UpdatedAt   time.Time         `json:"updated_at"`
}

type Reminder struct {
	ID           string    `json:"id"`
	UserID       string    `json:"user_id"`
	EventID      string    `json:"event_id"`
	ReminderTime time.Time `json:"reminder_time"`
	Message      string    `json:"message"`
	Sent         bool      `json:"sent"`
	CreatedAt    time.Time `json:"created_at"`
}
