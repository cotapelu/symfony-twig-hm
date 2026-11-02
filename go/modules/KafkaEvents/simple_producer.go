package kafkasevents

import (
	"encoding/json"
	"fmt"
	"log"
	"sync"
	"time"
)

type SimpleProducer struct {
	buffer []byte
	mu     sync.Mutex
}

func NewSimpleProducer() *SimpleProducer {
	return &SimpleProducer{}
}

func (p *SimpleProducer) Produce(key string, eventData []byte) error {
	p.mu.Lock()
	defer p.mu.Unlock()
	log.Printf("Event produced for key: %s, size: %d bytes", key, len(eventData))
	p.buffer = append(p.buffer, eventData...)
	return nil
}

func (p *SimpleProducer) Close() error {
	return nil
}

func (p *SimpleProducer) EmitPaymentCreated(paymentID, userID string, amount float64, transactionID string) error {
	// Input validation
	if paymentID == "" {
		return fmt.Errorf("payment ID is required")
	}
	if userID == "" {
		return fmt.Errorf("user ID is required")
	}
	if amount <= 0 {
		return fmt.Errorf("amount must be positive")
	}

	event := map[string]interface{}{
		"event_type": "payment.created",
		"timestamp":  time.Now().Unix(),
		"payload": map[string]interface{}{
			"payment_id":     paymentID,
			"user_id":        userID,
			"amount":         amount,
			"transaction_id": transactionID,
			"status":         "created",
		},
	}
	key := fmt.Sprintf("evt_%d", time.Now().UnixNano())
	data, _ := json.Marshal(event)
	return p.Produce(key, data)
}

func (p *SimpleProducer) EmitPaymentCompleted(paymentID, transactionID string, amount float64) error {
	event := map[string]interface{}{
		"event_type": "payment.completed",
		"timestamp":  time.Now().Unix(),
		"payload": map[string]interface{}{
			"payment_id":     paymentID,
			"transaction_id": transactionID,
			"amount":         amount,
			"completed_at":   time.Now().Format(time.RFC3339),
		},
	}
	key := fmt.Sprintf("evt_%d", time.Now().UnixNano())
	data, _ := json.Marshal(event)
	return p.Produce(key, data)
}

func (p *SimpleProducer) EmitPaymentFailed(paymentID, transactionID string, amount float64, reason string) error {
	event := map[string]interface{}{
		"event_type": "payment.failed",
		"timestamp":  time.Now().Unix(),
		"payload": map[string]interface{}{
			"payment_id":     paymentID,
			"transaction_id": transactionID,
			"amount":         amount,
			"reason":         reason,
			"timestamp":      time.Now().Format(time.RFC3339),
		},
	}
	key := fmt.Sprintf("evt_%d", time.Now().UnixNano())
	data, _ := json.Marshal(event)
	return p.Produce(key, data)
}

func (p *SimpleProducer) EmitWalletBalanceChanged(walletID int64, userID string, oldBalance, newBalance, amount float64, changeType, reason string) error {
	event := map[string]interface{}{
		"event_type": "wallet.balance_changed",
		"timestamp":  time.Now().Unix(),
		"payload": map[string]interface{}{
			"wallet_id":   walletID,
			"user_id":     userID,
			"old_balance": oldBalance,
			"new_balance": newBalance,
			"amount":      amount,
			"change_type": changeType,
			"reason":      reason,
		},
	}
	key := fmt.Sprintf("evt_%d", time.Now().UnixNano())
	data, _ := json.Marshal(event)
	return p.Produce(key, data)
}

func (p *SimpleProducer) EmitBankAccountLinked(userID, bankCode, accountNo, accountName string) error {
	event := map[string]interface{}{
		"event_type": "bank_account.linked",
		"timestamp":  time.Now().Unix(),
		"payload": map[string]interface{}{
			"user_id":      userID,
			"bank_code":    bankCode,
			"account_no":   accountNo,
			"account_name": accountName,
			"linked_at":    time.Now().Format(time.RFC3339),
		},
	}
	key := fmt.Sprintf("evt_%d", time.Now().UnixNano())
	data, _ := json.Marshal(event)
	return p.Produce(key, data)
}

func (p *SimpleProducer) EmitBillPaid(billID, userID, transactionID string, amount float64) error {
	event := map[string]interface{}{
		"event_type": "bill.paid",
		"timestamp":  time.Now().Unix(),
		"payload": map[string]interface{}{
			"bill_id":        billID,
			"user_id":        userID,
			"transaction_id": transactionID,
			"amount":         amount,
			"paid_at":        time.Now().Format(time.RFC3339),
		},
	}
	key := fmt.Sprintf("evt_%d", time.Now().UnixNano())
	data, _ := json.Marshal(event)
	return p.Produce(key, data)
}
