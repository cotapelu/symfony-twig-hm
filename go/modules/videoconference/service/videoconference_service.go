package service

import (
	"context"
	"crypto/rand"
	"errors"
	"fmt"
	"time"

	"community.local/modules/videoconference/entity"
)

var (
	ErrRoomNotFound        = errors.New("room not found")
	ErrParticipantNotFound = errors.New("participant not found")
	ErrRoomFull            = errors.New("room is full")
	ErrInvalidRoomName     = errors.New("invalid room name")
	ErrInvalidUserID       = errors.New("invalid user ID")
	ErrInvalidDisplayName  = errors.New("invalid display name")
)

const (
	MaxRoomNameLength    = 100
	MaxDisplayNameLength = 50
	MaxRoomDuration      = 24 * time.Hour
)

type VideoConferenceService interface {
	CreateRoom(ctx context.Context, name string) (*entity.Room, error)
	GetRoom(ctx context.Context, id string) (*entity.Room, error)
	DeleteRoom(ctx context.Context, id string) error
	ListRooms(ctx context.Context) ([]*entity.Room, error)

	JoinRoom(ctx context.Context, roomID, userID, displayName string) (*entity.Participant, error)
	LeaveRoom(ctx context.Context, roomID, participantID string) error
	GetParticipants(ctx context.Context, roomID string) ([]*entity.Participant, error)

	ToggleAudio(ctx context.Context, roomID, participantID string) error
	ToggleVideo(ctx context.Context, roomID, participantID string) error
	StartScreenShare(ctx context.Context, roomID, participantID string) error
	StopScreenShare(ctx context.Context, roomID, participantID string) error
}

type videoConferenceService struct {
	repo   entity.VideoConferenceRepository
	config *Config
}

type Config struct {
	MaxParticipants int
}

func NewVideoConferenceService(repo entity.VideoConferenceRepository) VideoConferenceService {
	return &videoConferenceService{
		repo: repo,
		config: &Config{
			MaxParticipants: 100,
		},
	}
}

func (s *videoConferenceService) CreateRoom(ctx context.Context, name string) (*entity.Room, error) {
	if err := validateRoomName(name); err != nil {
		return nil, err
	}

	room := &entity.Room{
		ID:        generateRoomID(),
		Name:      name,
		CreatedAt: time.Now(),
		ExpiresAt: time.Now().Add(MaxRoomDuration),
		Active:    true,
	}

	if err := s.repo.CreateRoom(ctx, room); err != nil {
		return nil, err
	}

	return room, nil
}

func (s *videoConferenceService) GetRoom(ctx context.Context, id string) (*entity.Room, error) {
	room, err := s.repo.GetRoom(ctx, id)
	if err != nil {
		return nil, err
	}
	if room == nil {
		return nil, ErrRoomNotFound
	}
	if !room.Active || time.Now().After(room.ExpiresAt) {
		return nil, ErrRoomNotFound
	}
	return room, nil
}

func (s *videoConferenceService) DeleteRoom(ctx context.Context, id string) error {
	return s.repo.DeleteRoom(ctx, id)
}

func (s *videoConferenceService) ListRooms(ctx context.Context) ([]*entity.Room, error) {
	rooms, err := s.repo.ListRooms(ctx)
	if err != nil {
		return nil, err
	}

	var activeRooms []*entity.Room
	now := time.Now()
	for _, room := range rooms {
		if room.Active && !now.After(room.ExpiresAt) {
			activeRooms = append(activeRooms, room)
		}
	}
	return activeRooms, nil
}

func (s *videoConferenceService) JoinRoom(ctx context.Context, roomID, userID, displayName string) (*entity.Participant, error) {
	if err := validateUserID(userID); err != nil {
		return nil, err
	}
	if err := validateDisplayName(displayName); err != nil {
		return nil, err
	}

	_, err := s.GetRoom(ctx, roomID)
	if err != nil {
		return nil, err
	}

	participants, err := s.repo.GetParticipants(ctx, roomID)
	if err != nil {
		return nil, err
	}

	if len(participants) >= s.config.MaxParticipants {
		return nil, ErrRoomFull
	}

	for _, p := range participants {
		if p.UserID == userID {
			return p, nil
		}
	}

	participant := entity.NewParticipant(
		generateParticipantID(),
		userID,
		displayName,
		roomID,
		entity.RoleAttendee,
	)

	if len(participants) == 0 {
		participant.Role = entity.RoleHost
	}

	if err := s.repo.AddParticipant(ctx, roomID, participant); err != nil {
		return nil, err
	}

	return participant, nil
}

func (s *videoConferenceService) LeaveRoom(ctx context.Context, roomID, participantID string) error {
	return s.repo.RemoveParticipant(ctx, roomID, participantID)
}

func (s *videoConferenceService) GetParticipants(ctx context.Context, roomID string) ([]*entity.Participant, error) {
	return s.repo.GetParticipants(ctx, roomID)
}

func (s *videoConferenceService) ToggleAudio(ctx context.Context, roomID, participantID string) error {
	participant, err := s.repo.GetParticipant(ctx, roomID, participantID)
	if err != nil {
		return err
	}
	if participant == nil {
		return ErrParticipantNotFound
	}

	participant.ToggleAudio()
	return s.repo.UpdateParticipant(ctx, roomID, participant)
}

func (s *videoConferenceService) ToggleVideo(ctx context.Context, roomID, participantID string) error {
	participant, err := s.repo.GetParticipant(ctx, roomID, participantID)
	if err != nil {
		return err
	}
	if participant == nil {
		return ErrParticipantNotFound
	}

	participant.ToggleVideo()
	return s.repo.UpdateParticipant(ctx, roomID, participant)
}

func (s *videoConferenceService) StartScreenShare(ctx context.Context, roomID, participantID string) error {
	participant, err := s.repo.GetParticipant(ctx, roomID, participantID)
	if err != nil {
		return err
	}
	if participant == nil {
		return ErrParticipantNotFound
	}

	participant.StartScreenShare()
	return s.repo.UpdateParticipant(ctx, roomID, participant)
}

func (s *videoConferenceService) StopScreenShare(ctx context.Context, roomID, participantID string) error {
	participant, err := s.repo.GetParticipant(ctx, roomID, participantID)
	if err != nil {
		return err
	}
	if participant == nil {
		return ErrParticipantNotFound
	}

	participant.StopScreenShare()
	return s.repo.UpdateParticipant(ctx, roomID, participant)
}

func validateRoomName(name string) error {
	if name == "" {
		return ErrInvalidRoomName
	}
	if len(name) > MaxRoomNameLength {
		return ErrInvalidRoomName
	}
	return nil
}

func validateUserID(userID string) error {
	if userID == "" {
		return ErrInvalidUserID
	}
	return nil
}

func validateDisplayName(name string) error {
	if name == "" {
		return ErrInvalidDisplayName
	}
	if len(name) > MaxDisplayNameLength {
		return ErrInvalidDisplayName
	}
	return nil
}

func generateRoomID() string {
	return fmt.Sprintf("room-%d-%s", time.Now().UnixNano(), randomString(8))
}

func generateParticipantID() string {
	return fmt.Sprintf("p-%d-%s", time.Now().UnixNano(), randomString(6))
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
