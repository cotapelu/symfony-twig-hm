package service

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestLiveStreamingService_CreateStream(t *testing.T) {
	svc := NewLiveStreamingService()

	stream, err := svc.CreateStream("user_123", "Test Stream", "Test Description")
	require.NoError(t, err)
	assert.NotEmpty(t, stream.ID)
	assert.Equal(t, "Test Stream", stream.Title)
	assert.Equal(t, "live", stream.Status)
	assert.Equal(t, "user_123", stream.UserID)
}

func TestLiveStreamingService_GetStream(t *testing.T) {
	svc := NewLiveStreamingService()

	created, err := svc.CreateStream("user_123", "Test Stream", "Test Description")
	require.NoError(t, err)

	stream, err := svc.GetStream(created.ID)
	require.NoError(t, err)
	assert.Equal(t, created.ID, stream.ID)
	assert.Equal(t, "Test Stream", stream.Title)
}

func TestLiveStreamingService_GetStream_NotFound(t *testing.T) {
	svc := NewLiveStreamingService()

	_, err := svc.GetStream("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "not found")
}

func TestLiveStreamingService_EndStream(t *testing.T) {
	svc := NewLiveStreamingService()

	created, err := svc.CreateStream("user_123", "Test Stream", "Test Description")
	require.NoError(t, err)

	err = svc.EndStream(created.ID)
	require.NoError(t, err)

	stream, err := svc.GetStream(created.ID)
	require.NoError(t, err)
	assert.Equal(t, "ended", stream.Status)
	assert.NotNil(t, stream.EndedAt)
}

func TestLiveStreamingService_SendMessage(t *testing.T) {
	svc := NewLiveStreamingService()

	stream, err := svc.CreateStream("user_123", "Test Stream", "Test Description")
	require.NoError(t, err)

	msg, err := svc.SendMessage(stream.ID, "user_456", "TestUser", "Hello world!", "text")
	require.NoError(t, err)
	assert.NotEmpty(t, msg.ID)
	assert.Equal(t, "Hello world!", msg.Content)
	assert.Equal(t, "text", msg.Type)
}

func TestLiveStreamingService_GetMessages(t *testing.T) {
	svc := NewLiveStreamingService()

	stream, err := svc.CreateStream("user_123", "Test Stream", "Test Description")
	require.NoError(t, err)

	_, err = svc.SendMessage(stream.ID, "user_456", "User1", "Message 1", "text")
	require.NoError(t, err)
	_, err = svc.SendMessage(stream.ID, "user_789", "User2", "Message 2", "text")
	require.NoError(t, err)

	messages, err := svc.GetMessages(stream.ID, 10)
	require.NoError(t, err)
	assert.Len(t, messages, 2)
}

func TestLiveStreamingService_GetMessages_Limit(t *testing.T) {
	svc := NewLiveStreamingService()

	stream, err := svc.CreateStream("user_123", "Test Stream", "Test Description")
	require.NoError(t, err)

	for i := 0; i < 10; i++ {
		_, err = svc.SendMessage(stream.ID, "user_456", "User", "Message", "text")
		require.NoError(t, err)
	}

	messages, err := svc.GetMessages(stream.ID, 5)
	require.NoError(t, err)
	assert.Len(t, messages, 5)
}

func TestLiveStreamingService_SendGift(t *testing.T) {
	svc := NewLiveStreamingService()

	stream, err := svc.CreateStream("user_123", "Test Stream", "Test Description")
	require.NoError(t, err)

	gift, err := svc.SendGift(stream.ID, "sender_456", "receiver_123", "Rose", 100.0, "VND")
	require.NoError(t, err)
	assert.NotEmpty(t, gift.ID)
	assert.Equal(t, "Rose", gift.Name)
	assert.Equal(t, 100.0, gift.Value)
}

func TestLiveStreamingService_GetLiveStreams(t *testing.T) {
	svc := NewLiveStreamingService()

	_, err := svc.CreateStream("user_123", "Stream 1", "Description")
	require.NoError(t, err)
	_, err = svc.CreateStream("user_456", "Stream 2", "Description")
	require.NoError(t, err)

	// End one stream
	stream2, _ := svc.CreateStream("user_789", "Stream 3", "Description")
	svc.EndStream(stream2.ID)

	streams, err := svc.GetLiveStreams()
	require.NoError(t, err)
	assert.Len(t, streams, 2)
}

func TestLiveStreamingService_IncrementViewer(t *testing.T) {
	svc := NewLiveStreamingService()

	stream, err := svc.CreateStream("user_123", "Test Stream", "Test Description")
	require.NoError(t, err)

	initialCount := stream.ViewerCount
	svc.IncrementViewer(stream.ID)

	updated, err := svc.GetStream(stream.ID)
	require.NoError(t, err)
	assert.Equal(t, initialCount+1, updated.ViewerCount)
}

func TestLiveStreamingService_GetStats(t *testing.T) {
	svc := NewLiveStreamingService()

	stream, err := svc.CreateStream("user_123", "Test Stream", "Test Description")
	require.NoError(t, err)

	// Send a gift
	_, err = svc.SendGift(stream.ID, "sender_456", "receiver_123", "Rose", 100.0, "VND")
	require.NoError(t, err)

	// End stream to get duration
	svc.EndStream(stream.ID)

	stats, err := svc.GetStats(stream.ID)
	require.NoError(t, err)
	assert.Equal(t, stream.ID, stats.StreamID)
	assert.Equal(t, 1, stats.TotalGifts)
	assert.Equal(t, 100.0, stats.GiftValue)
}
