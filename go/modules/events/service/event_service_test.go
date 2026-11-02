package service

import (
	"os"
	"testing"
	"time"

	"community.local/modules/events/entity"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestEventsService_CreateEvent(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	event := svc.CreateEvent(
		"user_1",
		"Team Meeting",
		"Weekly sync",
		time.Now().Add(time.Hour),
		time.Now().Add(2*time.Hour),
		[]string{"user_1", "user_2"},
		map[string]string{"priority": "high"},
	)

	assert.NotEmpty(t, event.ID)
	assert.Equal(t, "user_1", event.UserID)
	assert.Equal(t, "Team Meeting", event.Title)
	assert.Equal(t, "Weekly sync", event.Description)
}

func TestEventsService_GetEvent(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	event := svc.CreateEvent("user_1", "Test Event", "Description", time.Now(), time.Now().Add(time.Hour), nil, nil)

	retrieved, err := svc.GetEvent(event.ID)
	require.NoError(t, err)
	assert.Equal(t, event.ID, retrieved.ID)
	assert.Equal(t, "Test Event", retrieved.Title)
}

func TestEventsService_GetEvent_NotFound(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	_, err := svc.GetEvent("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "not found")
}

func TestEventsService_UpdateEvent(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	event := svc.CreateEvent("user_1", "Original Title", "Original Desc", time.Now(), time.Now().Add(time.Hour), nil, nil)

	updates := map[string]interface{}{
		"title":       "Updated Title",
		"description": "Updated Desc",
	}

	updated, err := svc.UpdateEvent(event.ID, updates)
	require.NoError(t, err)
	assert.Equal(t, "Updated Title", updated.Title)
	assert.Equal(t, "Updated Desc", updated.Description)
}

func TestEventsService_UpdateEvent_NotFound(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	_, err := svc.UpdateEvent("nonexistent", map[string]interface{}{"title": "Test"})
	assert.Error(t, err)
}

func TestEventsService_DeleteEvent(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	event := svc.CreateEvent("user_1", "To Delete", "Will be deleted", time.Now(), time.Now().Add(time.Hour), nil, nil)

	err := svc.DeleteEvent(event.ID)
	require.NoError(t, err)

	_, err = svc.GetEvent(event.ID)
	assert.Error(t, err)
}

func TestEventsService_DeleteEvent_NotFound(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	err := svc.DeleteEvent("nonexistent")
	assert.Error(t, err)
}

func TestEventsService_ListEvents(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	svc.CreateEvent("user_1", "Event 1", "Desc 1", time.Now(), time.Now().Add(time.Hour), nil, nil)
	svc.CreateEvent("user_1", "Event 2", "Desc 2", time.Now().Add(2*time.Hour), time.Now().Add(3*time.Hour), nil, nil)
	svc.CreateEvent("user_2", "Event 3", "Desc 3", time.Now(), time.Now().Add(time.Hour), nil, nil)

	events := svc.ListEvents("user_1", nil)
	assert.Len(t, events, 2)
}

func TestEventsService_ListEvents_WithDateRange(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	now := time.Now()
	svc.CreateEvent("user_1", "Event 1", "Desc", now.Add(24*time.Hour), now.Add(25*time.Hour), nil, nil)
	svc.CreateEvent("user_1", "Event 2", "Desc", now.Add(48*time.Hour), now.Add(49*time.Hour), nil, nil)
	svc.CreateEvent("user_1", "Event 3", "Desc", now.Add(72*time.Hour), now.Add(73*time.Hour), nil, nil)

	dateRange := &struct{ Start, End time.Time }{
		Start: now,
		End:   now.Add(50 * time.Hour),
	}

	events := svc.ListEvents("user_1", dateRange)
	assert.Len(t, events, 2)
}

func TestEventsService_RSVPEvent(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	event := svc.CreateEvent("user_1", "RSVP Event", "Please respond", time.Now(), time.Now().Add(time.Hour), nil, nil)

	updated, err := svc.RSVPEvent(event.ID, "user_2", "going")
	require.NoError(t, err)
	assert.Equal(t, "going", updated.RSVPs["user_2"])
}

func TestEventsService_RSVPEvent_NotFound(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	_, err := svc.RSVPEvent("nonexistent", "user_1", "going")
	assert.Error(t, err)
}

func TestEventsService_CreateReminder(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	event := svc.CreateEvent("user_1", "Event with Reminder", "Desc", time.Now(), time.Now().Add(time.Hour), nil, nil)

	reminder := svc.CreateReminder("user_1", event.ID, time.Now().Add(30*time.Minute), "Meeting starting soon")

	assert.NotEmpty(t, reminder.ID)
	assert.Equal(t, "user_1", reminder.UserID)
	assert.Equal(t, event.ID, reminder.EventID)
	assert.False(t, reminder.Sent)
}

func TestEventsService_SyncWithGoogleCalendar(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	result, err := svc.SyncWithGoogleCalendar("user_1", "calendar_123")
	require.NoError(t, err)
	assert.Equal(t, "synced", result)
}

func TestEventsService_SyncWithOutlook(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"
	svc := NewEventsService(tmpFile)
	defer svc.Terminate()

	result, err := svc.SyncWithOutlook("user_1", "outlook_123")
	require.NoError(t, err)
	assert.Equal(t, "synced", result)
}

func TestEventsService_Persistence(t *testing.T) {
	tmpFile := t.TempDir() + "/test_events.json"

	svc1 := NewEventsService(tmpFile)
	svc1.CreateEvent("user_1", "Persistent Event", "Will survive", time.Now(), time.Now().Add(time.Hour), nil, nil)
	svc1.Terminate()

	svc2 := NewEventsService(tmpFile)
	events := svc2.ListEvents("user_1", nil)
	assert.Len(t, events, 1)
	assert.Equal(t, "Persistent Event", events[0].Title)
	svc2.Terminate()

	os.Remove(tmpFile)
}

func TestEventEntity(t *testing.T) {
	event := entity.Event{
		ID:          "evt_test",
		UserID:      "user_1",
		Title:       "Test Event",
		Description: "Test Description",
		StartTime:   time.Now(),
		EndTime:     time.Now().Add(time.Hour),
		Attendees:   []string{"user_1", "user_2"},
		RSVPs:       map[string]string{"user_1": "going", "user_2": "maybe"},
		Metadata:    map[string]string{"location": "Room A"},
		CreatedAt:   time.Now(),
		UpdatedAt:   time.Now(),
	}

	assert.Equal(t, "evt_test", event.ID)
	assert.Equal(t, "user_1", event.UserID)
	assert.Equal(t, "Test Event", event.Title)
	assert.Len(t, event.Attendees, 2)
	assert.Equal(t, "going", event.RSVPs["user_1"])
}

func TestReminderEntity(t *testing.T) {
	reminder := entity.Reminder{
		ID:           "rem_test",
		UserID:       "user_1",
		EventID:      "evt_1",
		ReminderTime: time.Now().Add(30 * time.Minute),
		Message:      "Don't forget!",
		Sent:         false,
		CreatedAt:    time.Now(),
	}

	assert.Equal(t, "rem_test", reminder.ID)
	assert.Equal(t, "user_1", reminder.UserID)
	assert.False(t, reminder.Sent)
}
