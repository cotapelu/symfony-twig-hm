package service

import (
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"sort"
	"sync"
	"time"

	"community.local/modules/events/entity"
)

type EventsService struct {
	mu        sync.RWMutex
	events    map[string]entity.Event
	reminders map[string]entity.Reminder
	nextID    int
	etsFile   string
}

func NewEventsService(etsFile string) *EventsService {
	s := &EventsService{
		events:    make(map[string]entity.Event),
		reminders: make(map[string]entity.Reminder),
		nextID:    1,
		etsFile:   etsFile,
	}
	s.ensureDataDirectory(etsFile)
	s.restoreFromETS()
	return s
}

func (s *EventsService) ensureDataDirectory(filePath string) {
	dir := filepath.Dir(filePath)
	if dir != "" && dir != "." {
		os.MkdirAll(dir, 0755)
	}
}

func (s *EventsService) restoreFromETS() {
	data, err := os.ReadFile(s.etsFile)
	if err != nil {
		if os.IsNotExist(err) {
			return
		}
		return
	}

	var saved struct {
		Events    map[string]entity.Event    `json:"events"`
		Reminders map[string]entity.Reminder `json:"reminders"`
		NextID    int                        `json:"next_id"`
	}

	if err := json.Unmarshal(data, &saved); err != nil {
		return
	}

	s.events = saved.Events
	s.reminders = saved.Reminders
	s.nextID = saved.NextID
}

func (s *EventsService) persistToETS() {
	data, _ := json.MarshalIndent(map[string]interface{}{
		"events":    s.events,
		"reminders": s.reminders,
		"next_id":   s.nextID,
	}, "", "  ")
	os.WriteFile(s.etsFile, data, 0644)
}

func (s *EventsService) CreateEvent(userID, title, description string, startTime, endTime time.Time, attendees []string, metadata map[string]string) entity.Event {
	s.mu.Lock()
	defer s.mu.Unlock()

	eventID := fmt.Sprintf("evt_%d", s.nextID)
	s.nextID++

	event := entity.Event{
		ID:          eventID,
		UserID:      userID,
		Title:       title,
		Description: description,
		StartTime:   startTime,
		EndTime:     endTime,
		Attendees:   attendees,
		RSVPs:       map[string]string{userID: "going"},
		Metadata:    metadata,
		CreatedAt:   time.Now(),
		UpdatedAt:   time.Now(),
	}

	s.events[eventID] = event
	return event
}

func (s *EventsService) UpdateEvent(eventID string, updates map[string]interface{}) (*entity.Event, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	event, exists := s.events[eventID]
	if !exists {
		return nil, fmt.Errorf("event not found")
	}

	for k, v := range updates {
		switch k {
		case "title":
			event.Title = v.(string)
		case "description":
			event.Description = v.(string)
		case "start_time":
			event.StartTime = v.(time.Time)
		case "end_time":
			event.EndTime = v.(time.Time)
		case "attendees":
			event.Attendees = v.([]string)
		case "metadata":
			event.Metadata = v.(map[string]string)
		}
	}
	event.UpdatedAt = time.Now()

	s.events[eventID] = event
	return &event, nil
}

func (s *EventsService) DeleteEvent(eventID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	if _, exists := s.events[eventID]; !exists {
		return fmt.Errorf("event not found")
	}

	delete(s.events, eventID)
	return nil
}

func (s *EventsService) GetEvent(eventID string) (*entity.Event, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	event, exists := s.events[eventID]
	if !exists {
		return nil, fmt.Errorf("event not found")
	}
	return &event, nil
}

func (s *EventsService) ListEvents(userID string, dateRange *struct{ Start, End time.Time }) []entity.Event {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var events []entity.Event
	for _, event := range s.events {
		if event.UserID != userID {
			continue
		}
		if dateRange != nil {
			if event.StartTime.Before(dateRange.Start) || event.EndTime.After(dateRange.End) {
				continue
			}
		}
		events = append(events, event)
	}

	sort.Slice(events, func(i, j int) bool {
		return events[i].StartTime.Before(events[j].StartTime)
	})

	return events
}

func (s *EventsService) RSVPEvent(eventID, userID, status string) (*entity.Event, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	event, exists := s.events[eventID]
	if !exists {
		return nil, fmt.Errorf("event not found")
	}

	event.RSVPs[userID] = status
	event.UpdatedAt = time.Now()
	s.events[eventID] = event

	return &event, nil
}

func (s *EventsService) CreateReminder(userID, eventID string, reminderTime time.Time, message string) entity.Reminder {
	s.mu.Lock()
	defer s.mu.Unlock()

	reminderID := fmt.Sprintf("rem_%d", s.nextID)
	s.nextID++

	reminder := entity.Reminder{
		ID:           reminderID,
		UserID:       userID,
		EventID:      eventID,
		ReminderTime: reminderTime,
		Message:      message,
		Sent:         false,
		CreatedAt:    time.Now(),
	}

	s.reminders[reminderID] = reminder
	return reminder
}

func (s *EventsService) SyncWithGoogleCalendar(userID, calendarID string) (string, error) {
	return "synced", nil
}

func (s *EventsService) SyncWithOutlook(userID, calendarID string) (string, error) {
	return "synced", nil
}

func (s *EventsService) Terminate() {
	s.persistToETS()
}
