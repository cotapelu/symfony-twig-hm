package kafkasevents

import (
	"encoding/json"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestSimpleProducer_Produce(t *testing.T) {
	sp := NewSimpleProducer()

	// Append two events
	key1 := "key1"
	data1 := []byte("payload1")
	err := sp.Produce(key1, data1)
	assert.NoError(t, err)
	assert.Len(t, sp.buffer, len(data1))

	key2 := "key2"
	data2 := []byte("payload2")
	err = sp.Produce(key2, data2)
	assert.NoError(t, err)
	assert.Len(t, sp.buffer, len(data1)+len(data2))

	// Buffer should contain concatenated payloads
	assert.Equal(t, append(data1, data2...), sp.buffer)
}

func TestSimpleProducer_Close(t *testing.T) {
	sp := NewSimpleProducer()
	// Close is a no-op; just ensure it doesn't panic/error
	err := sp.Close()
	assert.NoError(t, err)
}

func TestSimpleProducer_EmitPaymentCreated(t *testing.T) {
	sp := NewSimpleProducer()

	t.Run("validation errors", func(t *testing.T) {
		err := sp.EmitPaymentCreated("", "user1", 100, "txn1")
		assert.Error(t, err)
		assert.Contains(t, err.Error(), "payment ID is required")

		err = sp.EmitPaymentCreated("pay1", "", 100, "txn1")
		assert.Error(t, err)
		assert.Contains(t, err.Error(), "user ID is required")

		err = sp.EmitPaymentCreated("pay1", "user1", 0, "txn1")
		assert.Error(t, err)
		assert.Contains(t, err.Error(), "amount must be positive")
	})

	t.Run("success", func(t *testing.T) {
		initialLen := len(sp.buffer)
		err := sp.EmitPaymentCreated("pay123", "user456", 99.95, "txn789")
		assert.NoError(t, err)
		// Buffer should have grown
		assert.Greater(t, len(sp.buffer), initialLen)

		// Extract the last event from buffer (it's appended; we need to parse)
		// Since buffer is concatenated JSON, we can't easily split; but we can assert that the new part is valid JSON and contains expected fields.
		// Instead, we'll test the payload by unmarshaling the entire buffer's last entry by reconstructing from event format.
		// Simpler: check that buffer contains a valid JSON object with expected keys (by unmarshaling into interface{}).
		var event map[string]interface{}
		// Find the end of the previous buffer to isolate the new event.
		// Since we appended after initialLen, the new bytes are sp.buffer[initialLen:]
		part := sp.buffer[initialLen:]
		err = json.Unmarshal(part, &event)
		assert.NoError(t, err)
		assert.Equal(t, "payment.created", event["event_type"])
		payload, ok := event["payload"].(map[string]interface{})
		assert.True(t, ok)
		assert.Equal(t, "pay123", payload["payment_id"])
		assert.Equal(t, "user456", payload["user_id"])
		assert.InDelta(t, 99.95, payload["amount"], 0.01)
		assert.Equal(t, "txn789", payload["transaction_id"])
		assert.Equal(t, "created", payload["status"])
	})
}

func TestSimpleProducer_EmitPaymentCompleted(t *testing.T) {
	sp := NewSimpleProducer()
	initialLen := len(sp.buffer)
	err := sp.EmitPaymentCompleted("pay1", "txn1", 50.0)
	assert.NoError(t, err)
	part := sp.buffer[initialLen:]
	var event map[string]interface{}
	err = json.Unmarshal(part, &event)
	assert.NoError(t, err)
	assert.Equal(t, "payment.completed", event["event_type"])
}

func TestSimpleProducer_EmitPaymentFailed(t *testing.T) {
	sp := NewSimpleProducer()
	initialLen := len(sp.buffer)
	err := sp.EmitPaymentFailed("pay1", "txn1", 100, "insufficient funds")
	assert.NoError(t, err)
	part := sp.buffer[initialLen:]
	var event map[string]interface{}
	err = json.Unmarshal(part, &event)
	assert.NoError(t, err)
	assert.Equal(t, "payment.failed", event["event_type"])
	payload := event["payload"].(map[string]interface{})
	assert.Equal(t, "insufficient funds", payload["reason"])
}

func TestSimpleProducer_EmitWalletBalanceChanged(t *testing.T) {
	sp := NewSimpleProducer()
	initialLen := len(sp.buffer)
	err := sp.EmitWalletBalanceChanged(12345, "user1", 100.0, 150.0, 50.0, "deposit", "top-up")
	assert.NoError(t, err)
	part := sp.buffer[initialLen:]
	var event map[string]interface{}
	err = json.Unmarshal(part, &event)
	assert.NoError(t, err)
	assert.Equal(t, "wallet.balance_changed", event["event_type"])
	payload := event["payload"].(map[string]interface{})
	assert.Equal(t, float64(12345), payload["wallet_id"])
	assert.Equal(t, "deposit", payload["change_type"])
}

func TestSimpleProducer_EmitBankAccountLinked(t *testing.T) {
	sp := NewSimpleProducer()
	initialLen := len(sp.buffer)
	err := sp.EmitBankAccountLinked("user1", "BIDV", "1234567890", "John Doe")
	assert.NoError(t, err)
	part := sp.buffer[initialLen:]
	var event map[string]interface{}
	err = json.Unmarshal(part, &event)
	assert.NoError(t, err)
	assert.Equal(t, "bank_account.linked", event["event_type"])
	payload := event["payload"].(map[string]interface{})
	assert.Equal(t, "BIDV", payload["bank_code"])
	assert.Equal(t, "John Doe", payload["account_name"])
}

func TestSimpleProducer_EmitBillPaid(t *testing.T) {
	sp := NewSimpleProducer()
	initialLen := len(sp.buffer)
	err := sp.EmitBillPaid("bill1", "user1", "txn1", 200.0)
	assert.NoError(t, err)
	part := sp.buffer[initialLen:]
	var event map[string]interface{}
	err = json.Unmarshal(part, &event)
	assert.NoError(t, err)
	assert.Equal(t, "bill.paid", event["event_type"])
	payload := event["payload"].(map[string]interface{})
	assert.Equal(t, "bill1", payload["bill_id"])
}
