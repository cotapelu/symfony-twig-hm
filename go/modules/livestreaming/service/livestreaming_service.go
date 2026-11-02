package service

import (
	"crypto/rand"
	"encoding/json"
	"fmt"
	"sync"
	"time"

	"community.local/modules/livestreaming/entity"
)

type LiveStreamingService struct {
	mu          sync.RWMutex
	streams     map[string]*entity.LiveStream
	messages    map[string][]*entity.LiveMessage
	gifts       map[string]*entity.LiveGift
	subscribers map[string][]chan *entity.LiveMessage
	rateLimit   map[string]int
	nextID      int
}

func NewLiveStreamingService() *LiveStreamingService {
	return &LiveStreamingService{
		streams:     make(map[string]*entity.LiveStream),
		messages:    make(map[string][]*entity.LiveMessage),
		gifts:       make(map[string]*entity.LiveGift),
		subscribers: make(map[string][]chan *entity.LiveMessage),
		rateLimit:   make(map[string]int),
		nextID:      1,
	}
}

func (s *LiveStreamingService) CreateStream(userID, title, description string) (*entity.LiveStream, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	stream := &entity.LiveStream{
		ID:          fmt.Sprintf("stream_%d", s.nextID),
		UserID:      userID,
		Title:       title,
		Description: description,
		Status:      "live",
		RoomID:      generateRoomID(),
		ViewerCount: 0,
		Metadata:    make(map[string]interface{}),
		StartedAt:   time.Now(),
		CreatedAt:   time.Now(),
		UpdatedAt:   time.Now(),
	}

	s.streams[stream.ID] = stream
	s.messages[stream.ID] = make([]*entity.LiveMessage, 0)
	s.nextID++

	return stream, nil
}

func (s *LiveStreamingService) GetStream(streamID string) (*entity.LiveStream, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	stream, exists := s.streams[streamID]
	if !exists {
		return nil, fmt.Errorf("stream not found")
	}
	return stream, nil
}

func (s *LiveStreamingService) EndStream(streamID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	stream, exists := s.streams[streamID]
	if !exists {
		return fmt.Errorf("stream not found")
	}

	now := time.Now()
	stream.Status = "ended"
	stream.EndedAt = &now
	stream.UpdatedAt = now

	return nil
}

func (s *LiveStreamingService) SendMessage(streamID, userID, username, content, msgType string) (*entity.LiveMessage, error) {
	if err := s.checkRateLimit(streamID); err != nil {
		return nil, err
	}

	s.mu.Lock()
	defer s.mu.Unlock()

	msg := &entity.LiveMessage{
		ID:        fmt.Sprintf("msg_%d", s.nextID),
		StreamID:  streamID,
		UserID:    userID,
		Username:  username,
		Content:   content,
		Type:      msgType,
		Timestamp: time.Now(),
	}

	s.messages[streamID] = append(s.messages[streamID], msg)
	s.nextID++

	for _, ch := range s.subscribers[streamID] {
		go func(c chan *entity.LiveMessage) {
			c <- msg
		}(ch)
	}

	return msg, nil
}

func (s *LiveStreamingService) GetMessages(streamID string, limit int) ([]*entity.LiveMessage, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	messages := s.messages[streamID]
	if len(messages) == 0 {
		return []*entity.LiveMessage{}, nil
	}

	if limit <= 0 || limit > 100 {
		limit = 50
	}

	start := len(messages) - limit
	if start < 0 {
		start = 0
	}

	return messages[start:], nil
}

func (s *LiveStreamingService) SendGift(streamID, senderID, receiverID, name string, value float64, currency string) (*entity.LiveGift, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	gift := &entity.LiveGift{
		ID:         fmt.Sprintf("gift_%d", s.nextID),
		StreamID:   streamID,
		SenderID:   senderID,
		ReceiverID: receiverID,
		Name:       name,
		Value:      value,
		Currency:   currency,
		Timestamp:  time.Now(),
	}

	s.gifts[gift.ID] = gift
	s.nextID++

	msg := &entity.LiveMessage{
		ID:        fmt.Sprintf("msg_%d", s.nextID),
		StreamID:  streamID,
		UserID:    senderID,
		Username:  "",
		Content:   fmt.Sprintf("sent a %s (%.2f %s)", name, value, currency),
		Type:      "gift",
		Timestamp: time.Now(),
	}
	s.messages[streamID] = append(s.messages[streamID], msg)

	for _, ch := range s.subscribers[streamID] {
		go func(c chan *entity.LiveMessage) {
			c <- msg
		}(ch)
	}

	return gift, nil
}

func (s *LiveStreamingService) Subscribe(streamID string) (<-chan *entity.LiveMessage, func()) {
	s.mu.Lock()
	defer s.mu.Unlock()

	ch := make(chan *entity.LiveMessage, 100)
	s.subscribers[streamID] = append(s.subscribers[streamID], ch)

	unsubscribe := func() {
		s.mu.Lock()
		defer s.mu.Unlock()
		for i, c := range s.subscribers[streamID] {
			if c == ch {
				s.subscribers[streamID] = append(s.subscribers[streamID][:i], s.subscribers[streamID][i+1:]...)
				break
			}
		}
		close(ch)
	}

	return ch, unsubscribe
}

func (s *LiveStreamingService) IncrementViewer(streamID string) {
	s.mu.Lock()
	defer s.mu.Unlock()

	if stream, exists := s.streams[streamID]; exists {
		stream.ViewerCount++
	}
}

func (s *LiveStreamingService) GetLiveStreams() ([]*entity.LiveStream, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	streams := make([]*entity.LiveStream, 0)
	for _, stream := range s.streams {
		if stream.Status == "live" {
			streams = append(streams, stream)
		}
	}
	return streams, nil
}

func (s *LiveStreamingService) checkRateLimit(streamID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	s.rateLimit[streamID]++
	if s.rateLimit[streamID] > 100 {
		return fmt.Errorf("rate limit exceeded")
	}
	return nil
}

func generateRoomID() string {
	b := make([]byte, 8)
	rand.Read(b)
	return fmt.Sprintf("room_%x", b)
}

func generateID() string {
	return time.Now().Format("20060102150405") + "-" + randomString(8)
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

func (s *LiveStreamingService) GetStats(streamID string) (*entity.StreamStats, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	stream, exists := s.streams[streamID]
	if !exists {
		return nil, fmt.Errorf("stream not found")
	}

	stats := &entity.StreamStats{
		StreamID:    streamID,
		PeakViewers: stream.ViewerCount,
		TotalViews:  stream.ViewerCount * 10,
		TotalLikes:  0,
		TotalGifts:  0,
		GiftValue:   0,
	}

	for _, gift := range s.gifts {
		if gift.StreamID == streamID {
			stats.TotalGifts++
			stats.GiftValue += gift.Value
		}
	}

	if stream.EndedAt != nil {
		stats.DurationSec = int(stream.EndedAt.Sub(stream.StartedAt).Seconds())
	}

	return stats, nil
}

func (s *LiveStreamingService) MarshalJSON() ([]byte, error) {
	type Alias LiveStreamingService
	return json.Marshal(&struct {
		*Alias
	}{
		Alias: (*Alias)(s),
	})
}
