package videoconference

import (
	"testing"
)

func TestNewVideoConferenceService(t *testing.T) {
	svc := NewVideoConferenceService()
	if svc == nil {
		t.Fatal("expected non-nil service")
	}
}

func TestCreateRoom(t *testing.T) {
	svc := NewVideoConferenceService()
	room := svc.CreateRoom("Test Room")
	if room == nil {
		t.Fatal("expected non-nil room")
	}
	if room.Name != "Test Room" {
		t.Errorf("expected name %q, got %q", "Test Room", room.Name)
	}
	if !room.Active {
		t.Error("room should be active")
	}
	if len(room.Users) != 0 {
		t.Error("room users should be empty")
	}
	if room.ID == "" {
		t.Error("room ID should be generated")
	}
	// Verify via GetRoom
	retrieved, err := svc.GetRoom(room.ID)
	if err != nil {
		t.Fatalf("GetRoom error: %v", err)
	}
	if retrieved.ID != room.ID {
		t.Errorf("retrieved room ID mismatch")
	}
}

func TestJoinRoom(t *testing.T) {
	svc := NewVideoConferenceService()
	room := svc.CreateRoom("Test Room")
	userID := "user1"
	userName := "Alice"
	joinedRoom, err := svc.JoinRoom(room.ID, userID, userName)
	if err != nil {
		t.Fatalf("JoinRoom error: %v", err)
	}
	if joinedRoom.ID != room.ID {
		t.Errorf("joined room ID mismatch")
	}
	if _, ok := joinedRoom.Users[userID]; !ok {
		t.Error("user not added to room")
	}
	// Join same user again (should be idempotent? It overwrites)
	joinedRoom2, err := svc.JoinRoom(room.ID, userID, userName)
	if err != nil {
		t.Fatalf("JoinRoom second time error: %v", err)
	}
	if _, ok := joinedRoom2.Users[userID]; !ok {
		t.Error("user not present after second join")
	}
	// Join non-existent room
	_, err = svc.JoinRoom("nonexistent", userID, userName)
	if err != ErrRoomNotFound {
		t.Errorf("expected ErrRoomNotFound, got %v", err)
	}
}

func TestLeaveRoom(t *testing.T) {
	svc := NewVideoConferenceService()
	room := svc.CreateRoom("Test Room")
	userID := "user1"
	svc.JoinRoom(room.ID, userID, "Alice")
	err := svc.LeaveRoom(room.ID, userID)
	if err != nil {
		t.Fatalf("LeaveRoom error: %v", err)
	}
	// Verify user removed by checking room's users via GetRoom (but GetRoom returns a copy? It returns the pointer, so safe)
	r, err := svc.GetRoom(room.ID)
	if err != nil {
		t.Fatalf("GetRoom after LeaveRoom: %v", err)
	}
	if _, ok := r.Users[userID]; ok {
		t.Error("user still present after leave")
	}
	// Leave non-existent room
	err = svc.LeaveRoom("nonexistent", userID)
	if err != ErrRoomNotFound {
		t.Errorf("expected ErrRoomNotFound, got %v", err)
	}
	// Leave from room that exists but user not in room (should also be okay? The code just deletes from map, which is fine even if not present; it does not return error). Let's check: if room exists but userID not in room. Currently LeaveRoom does not check if user existed; it just deletes. That's fine, no error. So we don't need to test error for that.
}

func TestGetRoom(t *testing.T) {
	svc := NewVideoConferenceService()
	room := svc.CreateRoom("Test Room")
	retrieved, err := svc.GetRoom(room.ID)
	if err != nil {
		t.Fatalf("GetRoom error: %v", err)
	}
	if retrieved.ID != room.ID {
		t.Errorf("ID mismatch")
	}
	// Non-existent
	_, err = svc.GetRoom("nonexistent")
	if err != ErrRoomNotFound {
		t.Errorf("expected ErrRoomNotFound, got %v", err)
	}
}
