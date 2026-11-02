package repository

import (
	"context"
	"sync"
	"time"

	qrpayment "community.local/modules/QRPayment/internal/models"
)

type InMemoryQRCRepository struct {
	mu      sync.RWMutex
	codes   map[int64]*qrpayment.QRCode
	codeIdx map[string]int64
	nextID  int64
}

func NewInMemoryQRCRepository() *InMemoryQRCRepository {
	return &InMemoryQRCRepository{
		codes:   make(map[int64]*qrpayment.QRCode),
		codeIdx: make(map[string]int64),
		nextID:  1,
	}
}

func (r *InMemoryQRCRepository) CreateQRCode(ctx context.Context, qr *qrpayment.QRCode) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	qr.ID = r.nextID
	qr.CreatedAt = time.Now()
	r.nextID++

	r.codes[qr.ID] = qr
	r.codeIdx[qr.Code] = qr.ID
	return nil
}

func (r *InMemoryQRCRepository) GetQRCodeByCode(ctx context.Context, code string) (*qrpayment.QRCode, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	if id, ok := r.codeIdx[code]; ok {
		return r.codes[id], nil
	}
	return nil, nil
}

func (r *InMemoryQRCRepository) GetQRCodeByID(ctx context.Context, id int64) (*qrpayment.QRCode, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	return r.codes[id], nil
}

func (r *InMemoryQRCRepository) MarkQRCodeAsUsed(ctx context.Context, id int64) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	if qr, ok := r.codes[id]; ok {
		qr.Status = "used"
		now := time.Now()
		qr.UsedAt = &now
	}
	return nil
}

func (r *InMemoryQRCRepository) GetActiveQRCodeForMerchant(ctx context.Context, merchantID string) (*qrpayment.QRCode, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	for _, qr := range r.codes {
		if qr.MerchantID == merchantID && qr.CodeType == "static" && qr.Status == "active" {
			return qr, nil
		}
	}
	return nil, nil
}

type InMemoryPaymentRepository struct {
	mu       sync.RWMutex
	payments map[int64]*qrpayment.QRCPayment
	userIdx  map[string][]int64
	nextID   int64
}

func NewInMemoryPaymentRepository() *InMemoryPaymentRepository {
	return &InMemoryPaymentRepository{
		payments: make(map[int64]*qrpayment.QRCPayment),
		userIdx:  make(map[string][]int64),
		nextID:   1,
	}
}

func (r *InMemoryPaymentRepository) CreatePayment(ctx context.Context, p *qrpayment.QRCPayment) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	p.ID = r.nextID
	p.CreatedAt = time.Now()
	r.nextID++

	r.payments[p.ID] = p
	r.userIdx[p.PayerID] = append(r.userIdx[p.PayerID], p.ID)
	return nil
}

func (r *InMemoryPaymentRepository) GetPaymentByID(ctx context.Context, id int64) (*qrpayment.QRCPayment, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	return r.payments[id], nil
}

func (r *InMemoryPaymentRepository) GetPaymentByReference(ctx context.Context, refID string) (*qrpayment.QRCPayment, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	for _, p := range r.payments {
		if p.ReferenceID == refID {
			return p, nil
		}
	}
	return nil, nil
}

func (r *InMemoryPaymentRepository) GetPaymentsByUser(ctx context.Context, userID string, limit, offset int) ([]*qrpayment.QRCPayment, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	paymentIDs, ok := r.userIdx[userID]
	if !ok || offset >= len(paymentIDs) {
		return nil, nil
	}

	end := offset + limit
	if end > len(paymentIDs) {
		end = len(paymentIDs)
	}

	result := make([]*qrpayment.QRCPayment, 0, end-offset)
	for i := offset; i < end; i++ {
		if p, ok := r.payments[paymentIDs[i]]; ok {
			result = append(result, p)
		}
	}
	return result, nil
}

func (r *InMemoryPaymentRepository) UpdatePaymentStatus(ctx context.Context, id int64, status string, completedAt *time.Time) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	if p, ok := r.payments[id]; ok {
		p.Status = status
		p.CompletedAt = completedAt
	}
	return nil
}

// EventHandler interface
type EventHandler interface {
	EmitEvent(ctx context.Context, eventType string, data interface{}) error
}

// NullEventHandler implements EventHandler with no-op
type NullEventHandler struct{}

func (n *NullEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	return nil
}
