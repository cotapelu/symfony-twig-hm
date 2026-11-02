package entity

import "time"

// Role represents a participant's role in a conference room.
type Role string

const (
	RoleHost     Role = "host"
	RoleCoHost   Role = "co-host"
	RoleSpeaker  Role = "speaker"
	RoleAttendee Role = "attendee"
)

// MediaState tracks the on/off state of a participant's media streams.
type MediaState struct {
	AudioEnabled  bool `json:"audio_enabled"`
	VideoEnabled  bool `json:"video_enabled"`
	ScreenSharing bool `json:"screen_sharing"`
}

// Participant represents a user connected to a conference room.
type Participant struct {
	ID          string     `json:"id"`
	UserID      string     `json:"user_id"`
	DisplayName string     `json:"display_name"`
	RoomID      string     `json:"room_id"`
	Role        Role       `json:"role"`
	Media       MediaState `json:"media"`
	JoinedAt    time.Time  `json:"joined_at"`
	LeftAt      *time.Time `json:"left_at,omitempty"`
}

// NewParticipant creates a new participant with default media state.
func NewParticipant(id, userID, displayName, roomID string, role Role) *Participant {
	return &Participant{
		ID:          id,
		UserID:      userID,
		DisplayName: displayName,
		RoomID:      roomID,
		Role:        role,
		Media: MediaState{
			AudioEnabled:  true,
			VideoEnabled:  true,
			ScreenSharing: false,
		},
		JoinedAt: time.Now().UTC(),
	}
}

// Leave marks the participant as having left the room.
func (p *Participant) Leave() {
	now := time.Now().UTC()
	p.LeftAt = &now
}

// IsActive returns true if the participant has not left.
func (p *Participant) IsActive() bool {
	return p.LeftAt == nil
}

// ToggleAudio flips the audio state.
func (p *Participant) ToggleAudio() {
	p.Media.AudioEnabled = !p.Media.AudioEnabled
}

// ToggleVideo flips the video state.
func (p *Participant) ToggleVideo() {
	p.Media.VideoEnabled = !p.Media.VideoEnabled
}

// StartScreenShare enables screen sharing.
func (p *Participant) StartScreenShare() {
	p.Media.ScreenSharing = true
}

// StopScreenShare disables screen sharing.
func (p *Participant) StopScreenShare() {
	p.Media.ScreenSharing = false
}
