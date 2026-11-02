// QRPaymentService handles QR payment business logic.
package qrpayment

import (
	"context"
	"database/sql"
	"fmt"
	"math/rand"
	"strconv"
	"time"

	"community.local/modules/QRPayment/internal/models"
	"community.local/modules/QRPayment/repository"
)

// EventHandler is interface for emitting events.
type EventHandler interface {
	EmitEvent(ctx context.Context, eventType string, data interface{}) error
}

// QRCRepositoryInterface defines the contract for QR code repository operations.
type QRCRepositoryInterface interface {
	CreateQRCode(ctx context.Context, qr *models.QRCode) error
	GetQRCodeByCode(ctx context.Context, code string) (*models.QRCode, error)
	GetQRCodeByID(ctx context.Context, id int64) (*models.QRCode, error)
	MarkQRCodeAsUsed(ctx context.Context, id int64) error
}

// PaymentRepositoryInterface defines the contract for payment repository operations.
type PaymentRepositoryInterface interface {
	CreatePayment(ctx context.Context, payment *models.QRCPayment) error
	GetPaymentByID(ctx context.Context, id int64) (*models.QRCPayment, error)
	GetPaymentByReference(ctx context.Context, referenceID string) (*models.QRCPayment, error)
	GetPaymentsByUser(ctx context.Context, userID string, limit, offset int) ([]*models.QRCPayment, error)
}

// QRPaymentService handles QR payment business logic.
type QRPaymentService struct {
	db           *sql.DB
	eventHandler EventHandler
	qrRepo       QRCRepositoryInterface
	paymentRepo  PaymentRepositoryInterface
}

// NewQRPaymentService creates a new QRPaymentService.
// If db is provided, it uses PostgreSQL repositories; otherwise, in-memory (for tests).
func NewQRPaymentService(db *sql.DB, eventHandler EventHandler) *QRPaymentService {
	svc := &QRPaymentService{
		db:           db,
		eventHandler: eventHandler,
	}
	if db != nil {
		svc.qrRepo = repository.NewQRCPostgresRepository(db)
		svc.paymentRepo = repository.NewPaymentPostgresRepository(db)
	} else {
		svc.qrRepo = repository.NewInMemoryQRCRepository()
		svc.paymentRepo = repository.NewInMemoryPaymentRepository()
	}
	return svc
}

// NewQRPaymentServiceFromDB creates a new QRPaymentService with PostgreSQL repositories and no event handler.
func NewQRPaymentServiceFromDB(db *sql.DB) *QRPaymentService {
	return NewQRPaymentService(db, nil)
}

// GenerateQRCode generates a QR code for payment.
func (s *QRPaymentService) GenerateQRCode(ctx context.Context, req *models.GenerateQRRequest) (*models.QRCode, error) {
	if req.MerchantID == "" {
		return nil, fmt.Errorf("merchant_id is required")
	}
	if req.MerchantName == "" {
		return nil, fmt.Errorf("merchant_name is required")
	}
	if req.Currency == "" {
		req.Currency = "VND"
	}
	if req.ExpiryMinutes <= 0 {
		req.ExpiryMinutes = 30
	}

	qr := &models.QRCode{
		Code:         generateQRCodeString(),
		CodeType:     "dynamic",
		MerchantID:   req.MerchantID,
		MerchantName: req.MerchantName,
		Amount:       req.Amount,
		Currency:     req.Currency,
		Description:  req.Description,
		Status:       "active",
		ExpiresAt:    time.Now().Add(time.Duration(req.ExpiryMinutes) * time.Minute),
		CreatedAt:    time.Now(),
	}

	if err := s.qrRepo.CreateQRCode(ctx, qr); err != nil {
		return nil, err
	}

	return qr, nil
}

// GetStaticQRCode retrieves or creates a static QR code for a merchant.
func (s *QRPaymentService) GetStaticQRCode(ctx context.Context, merchantID, merchantName string) (*models.QRCode, error) {
	if merchantID == "" {
		return nil, fmt.Errorf("merchant_id is required")
	}
	if merchantName == "" {
		merchantName = merchantID
	}

	// Try to find existing static QR for this merchant
	qr, err := s.qrRepo.GetQRCodeByCode(ctx, merchantID) // using merchantID as code for static? Not ideal. Better to have index.
	if err == nil && qr != nil && qr.Status == "active" && qr.CodeType == "static" {
		return qr, nil
	}

	// Create new static QR
	qr = &models.QRCode{
		Code:         merchantID,
		CodeType:     "static",
		MerchantID:   merchantID,
		MerchantName: merchantName,
		Amount:       0, // variable amount
		Currency:     "VND",
		Description:  "Static QR for merchant",
		Status:       "active",
		ExpiresAt:    time.Now().AddDate(1, 0, 0), // 1 year
		CreatedAt:    time.Now(),
	}

	if err := s.qrRepo.CreateQRCode(ctx, qr); err != nil {
		return nil, err
	}

	return qr, nil
}

// GetQRCodeByCode retrieves a QR code by its code string.
func (s *QRPaymentService) GetQRCodeByCode(ctx context.Context, code string) (*models.QRCode, error) {
	qr, err := s.qrRepo.GetQRCodeByCode(ctx, code)
	if err != nil {
		return nil, err
	}
	if qr == nil {
		return nil, fmt.Errorf("qr code not found") }
	return qr, nil
}

// ProcessPayment processes a payment using a QR code.
func (s *QRPaymentService) ProcessPayment(ctx context.Context, req *models.PaymentRequest) (*models.QRCPayment, error) {
	// Validate amount
	if req.Amount <= 0 {
		return nil, fmt.Errorf("amount must be positive")
	}
	if req.Amount > 1000000000 {
		return nil, fmt.Errorf("amount exceeds maximum limit")
	}
	if req.PaymentMethod == "" {
		return nil, fmt.Errorf("payment method is required")
	}

	// Look up QR code
	qr, err := s.qrRepo.GetQRCodeByCode(ctx, req.QRCode)
	if err != nil || qr == nil {
		return nil, fmt.Errorf("qr code not found")
	}

	// Check QR is active and not expired
	if qr.Status != "active" {
		return nil, fmt.Errorf("qr code is not active")
	}
	if time.Now().After(qr.ExpiresAt) {
		return nil, fmt.Errorf("qr code has expired")
	}

	// Validate amount against QR
	if qr.Amount > 0 && req.Amount != qr.Amount {
		return nil, fmt.Errorf("amount does not match the qr code")
	}

	amount := req.Amount
	// For static QR with preset amount? Usually static has amount 0, but if merchant set amount, respect? We'll use QR's amount if >0 and req amount matches.
	if qr.Amount > 0 {
		amount = qr.Amount
	}

	// Calculate fee: 1% min 1000
	fee := amount * 0.01
	if fee < 1000 {
		fee = 1000
	}
	netAmount := amount - fee

	// Generate reference ID
	refID := generatePaymentReferenceID()

	// Create payment record
	payment := &models.QRCPayment{
		QRCodeID:      qr.ID,
		PayerID:       req.PayerID,
		Amount:        amount,
		Fee:           fee,
		NetAmount:     netAmount,
		Currency:      qr.Currency,
		PaymentMethod: req.PaymentMethod,
		ReferenceID:   refID,
		BankCode:      req.BankCode,
		Status:        "completed",
		CreatedAt:     time.Now(),
		CompletedAt:  func() *time.Time { t := time.Now(); return &t }(),
	}

	if err := s.paymentRepo.CreatePayment(ctx, payment); err != nil {
		return nil, err
	}

	// If dynamic QR, mark as used
	if qr.CodeType == "dynamic" {
		if err := s.qrRepo.MarkQRCodeAsUsed(ctx, qr.ID); err != nil {
		// Log but not fail
		}
	}

	// Emit event
	if s.eventHandler != nil {
		event := models.PaymentProcessedEvent{
			EventType: "payment.processed",
			PaymentID: payment.ID,
			QRCodeID:  qr.ID,
			PayerID:   req.PayerID,
			Amount:    amount,
			Currency:  qr.Currency,
			Status:    "completed",
			Timestamp: time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "qr.payment.processed", event)
	}

	return payment, nil
}

// GetPayment retrieves a payment by ID.
func (s *QRPaymentService) GetPayment(ctx context.Context, id int64) (*models.QRCPayment, error) {
	payment, err := s.paymentRepo.GetPaymentByID(ctx, id)
	if err != nil || payment == nil {
		return nil, fmt.Errorf("payment not found")
	}
	return payment, nil
}

// GetPaymentByReference retrieves a payment by reference ID.
func (s *QRPaymentService) GetPaymentByReference(ctx context.Context, refID string) (*models.QRCPayment, error) {
	payment, err := s.paymentRepo.GetPaymentByReference(ctx, refID)
	if err != nil || payment == nil {
		return nil, fmt.Errorf("payment not found")
	}
	return payment, nil
}

// GetUserPayments retrieves all payments for a user.
func (s *QRPaymentService) GetUserPayments(ctx context.Context, userID string, limit, offset int) ([]*models.QRCPayment, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}
	payments, err := s.paymentRepo.GetPaymentsByUser(ctx, userID, limit, offset)
	if err != nil {
		return nil, err
	}
	return payments, nil
}

// generateQRCodeString generates a random QR code string.
func generateQRCodeString() string {
	const bytes = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"
	b := make([]byte, 16)
	for i := range b {
		b[i] = bytes[rand.Intn(len(bytes))]
	}
	return "QR-" + string(b)
}

// generatePaymentReferenceID generates a unique reference ID for payments.
func generatePaymentReferenceID() string {
	return "QRPAY-" + time.Now().Format("20060102150405") + "-" + strconv.FormatInt(time.Now().UnixNano(), 10)
}
