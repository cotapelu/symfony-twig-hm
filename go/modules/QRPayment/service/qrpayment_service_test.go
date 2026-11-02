package qrpayment

import (
	"context"
	"errors"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
	"github.com/stretchr/testify/require"

	qrpaymentmodels "community.local/modules/QRPayment/internal/models"
)

func newNow() time.Time {
	return time.Now().Truncate(time.Microsecond)
}

// ==================== Mock Repositories ====================

type MockQRCRepository struct {
	mock.Mock
}

func (m *MockQRCRepository) CreateQRCode(ctx context.Context, qr *qrpaymentmodels.QRCode) error {
	args := m.Called(ctx, qr)
	return args.Error(0)
}

func (m *MockQRCRepository) GetQRCodeByCode(ctx context.Context, code string) (*qrpaymentmodels.QRCode, error) {
	args := m.Called(ctx, code)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*qrpaymentmodels.QRCode), args.Error(1)
}

func (m *MockQRCRepository) GetQRCodeByID(ctx context.Context, id int64) (*qrpaymentmodels.QRCode, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*qrpaymentmodels.QRCode), args.Error(1)
}

func (m *MockQRCRepository) MarkQRCodeAsUsed(ctx context.Context, id int64) error {
	args := m.Called(ctx, id)
	return args.Error(0)
}

func (m *MockQRCRepository) GetActiveQRCodeForMerchant(ctx context.Context, merchantID string) (*qrpaymentmodels.QRCode, error) {
	args := m.Called(ctx, merchantID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*qrpaymentmodels.QRCode), args.Error(1)
}

type MockPaymentRepository struct {
	mock.Mock
}

func (m *MockPaymentRepository) CreatePayment(ctx context.Context, p *qrpaymentmodels.QRCPayment) error {
	args := m.Called(ctx, p)
	return args.Error(0)
}

func (m *MockPaymentRepository) GetPaymentByID(ctx context.Context, id int64) (*qrpaymentmodels.QRCPayment, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*qrpaymentmodels.QRCPayment), args.Error(1)
}

func (m *MockPaymentRepository) GetPaymentByReference(ctx context.Context, refID string) (*qrpaymentmodels.QRCPayment, error) {
	args := m.Called(ctx, refID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*qrpaymentmodels.QRCPayment), args.Error(1)
}

func (m *MockPaymentRepository) GetPaymentsByUser(ctx context.Context, userID string, limit, offset int) ([]*qrpaymentmodels.QRCPayment, error) {
	args := m.Called(ctx, userID, limit, offset)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*qrpaymentmodels.QRCPayment), args.Error(1)
}

type MockEventHandler struct {
	mock.Mock
}

func (m *MockEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	args := m.Called(ctx, eventType, data)
	return args.Error(0)
}

// ==================== QRPaymentService Tests ====================

func TestNewQRPaymentService(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	// We need to construct service using a custom constructor? The service is created via NewQRPaymentService which sets repos based on db.
	// Since we are mocking, we'll directly instantiate the struct for tests, but we need to ensure fields are set.
	svc := &QRPaymentService{
		qrRepo:       repo,
		paymentRepo:  payRepo,
		eventHandler: nil,
	}
	assert.NotNil(t, svc)
}

func TestGenerateQRCode_Validation(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{
		qrRepo:       repo,
		paymentRepo:  payRepo,
		eventHandler: nil,
	}

	tests := []struct {
		name        string
		req         *qrpaymentmodels.GenerateQRRequest
		wantErr     bool
		wantErrMsg  string
	}{
		{
			name: "merchant_id empty",
			req: &qrpaymentmodels.GenerateQRRequest{
				MerchantID:    "",
				MerchantName:  "Store",
				Amount:        1000,
				Currency:      "VND",
				Description:   "Test",
				ExpiryMinutes: 30,
			},
			wantErr:    true,
			wantErrMsg: "merchant_id is required",
		},
		{
			name: "merchant_name empty",
			req: &qrpaymentmodels.GenerateQRRequest{
				MerchantID:    "merch1",
				MerchantName:  "",
				Amount:        1000,
				Currency:      "VND",
				Description:   "Test",
				ExpiryMinutes: 30,
			},
			wantErr:    true,
			wantErrMsg: "merchant_name is required",
		},
		{
			name: "valid request default currency",
			req: &qrpaymentmodels.GenerateQRRequest{
				MerchantID:    "merch1",
				MerchantName:  "Store",
				Amount:        1000,
				Currency:     "", // empty, should default to VND
				Description:   "Test",
				ExpiryMinutes: 0, // should default to 30
			},
			wantErr: false,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			// Set up mock expectation only for success case
			if !tt.wantErr {
				repo.On("CreateQRCode", mock.Anything, mock.Anything).Run(func(args mock.Arguments) {
					qrArg := args[1].(*qrpaymentmodels.QRCode)
					qrArg.ID = 123
					qrArg.CreatedAt = newNow()
				}).Return(nil).Once()
			}
			qr, err := svc.GenerateQRCode(context.Background(), tt.req)
			if tt.wantErr {
				assert.Error(t, err)
				assert.Contains(t, err.Error(), tt.wantErrMsg)
				assert.Nil(t, qr)
			} else {
				require.NoError(t, err)
				assert.NotNil(t, qr)
				assert.Equal(t, "merch1", qr.MerchantID)
				assert.Equal(t, "Store", qr.MerchantName)
				assert.Equal(t, tt.req.Amount, qr.Amount)
				assert.Equal(t, "VND", qr.Currency) // default
				assert.Equal(t, "dynamic", qr.CodeType)
				assert.Equal(t, "active", qr.Status)
				assert.NotEmpty(t, qr.Code)
				assert.NotZero(t, qr.ID)
				assert.WithinDuration(t, time.Now(), qr.ExpiresAt, 31*time.Minute) // default 30min, allow 1min tolerance
				assert.WithinDuration(t, time.Now(), qr.CreatedAt, 1*time.Second)
			}
		})
	}
}

func TestGenerateQRCode_Success(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	req := &qrpaymentmodels.GenerateQRRequest{
		MerchantID:    "merch2",
		MerchantName:  "Store2",
		Amount:        5000,
		Currency:      "USD",
		Description:   "USD payment",
		ExpiryMinutes: 60,
	}

	repo.On("CreateQRCode", mock.Anything, mock.Anything).Run(func(args mock.Arguments) {
		qr := args[1].(*qrpaymentmodels.QRCode)
		assert.NotEmpty(t, qr.Code)
		assert.Zero(t, qr.ID) // will be set by repo, but mock doesn't modify
	}).Return(nil)

	qr, err := svc.GenerateQRCode(context.Background(), req)
	require.NoError(t, err)
	assert.NotNil(t, qr)
	assert.Equal(t, "merch2", qr.MerchantID)
	assert.Equal(t, "Store2", qr.MerchantName)
	assert.Equal(t, req.Amount, qr.Amount)
	assert.Equal(t, "USD", qr.Currency)
	assert.Equal(t, "dynamic", qr.CodeType)
	assert.Equal(t, "active", qr.Status)

	repo.AssertExpectations(t)
}

func TestGetStaticQRCode_CreatesNewWhenNoneExists(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	// No existing static QR
	repo.On("GetQRCodeByCode", mock.Anything, "merchantX").Return(nil, nil).Once()
	repo.On("CreateQRCode", mock.Anything, mock.Anything).Run(func(args mock.Arguments) {
		qr := args[1].(*qrpaymentmodels.QRCode)
		assert.Equal(t, "merchantX", qr.Code)
		assert.Equal(t, "static", qr.CodeType)
		assert.Equal(t, float64(0), qr.Amount)
		assert.Equal(t, "active", qr.Status)
	}).Return(nil).Once()

	qr, err := svc.GetStaticQRCode(context.Background(), "merchantX", "Merchant X")
	require.NoError(t, err)
	assert.NotNil(t, qr)
	assert.Equal(t, "merchantX", qr.Code)
	assert.Equal(t, "static", qr.CodeType)
}

func TestGetStaticQRCode_ReturnsExistingActive(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	existing := &qrpaymentmodels.QRCode{
		ID:       5,
		Code:     "merchantY",
		CodeType: "static",
		MerchantID:  "merchantY",
		MerchantName: "Merchant Y",
		Amount:   0,
		Status:   "active",
		CreatedAt: newNow(),
		ExpiresAt: time.Now().AddDate(1, 0, 0),
	}

	repo.On("GetQRCodeByCode", mock.Anything, "merchantY").Return(existing, nil).Once()
	// CreateQRCode should not be called


	qr, err := svc.GetStaticQRCode(context.Background(), "merchantY", "")
	require.NoError(t, err)
	assert.Equal(t, existing, qr)
	repo.AssertExpectations(t)
}

func TestGetQRCodeByCode_NotFound(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	repo.On("GetQRCodeByCode", mock.Anything, "unknown").Return(nil, nil).Once()

	qr, err := svc.GetQRCodeByCode(context.Background(), "unknown")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "not found")
	assert.Nil(t, qr)
}

func TestGetQRCodeByCode_Found(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	expected := &qrpaymentmodels.QRCode{
		ID: 10, Code: "code10", CodeType: "dynamic", MerchantID: "m10", Amount: 100, Currency: "VND", Status: "active", CreatedAt: newNow(), ExpiresAt: time.Now().Add(1 * time.Hour),
	}
	repo.On("GetQRCodeByCode", mock.Anything, "code10").Return(expected, nil).Once()

	qr, err := svc.GetQRCodeByCode(context.Background(), "code10")
	require.NoError(t, err)
	assert.Equal(t, expected, qr)
}

func disabledTestProcessPayment_ValidationErrors(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	tests := []struct {
		name        string
		req         *qrpaymentmodels.PaymentRequest
		mockQR      *qrpaymentmodels.QRCode
		wantErr     bool
		wantErrMsg  string
	}{
		{
			name: "amount <= 0",
			req:  &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 0, PaymentMethod: "card"},
			wantErr:    true,
			wantErrMsg: "amount must be positive",
		},
		{
			name: "amount > max",
			req:  &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 2000000000, PaymentMethod: "card"},
			wantErr:    true,
			wantErrMsg: "amount exceeds maximum limit",
		},
		{
			name: "missing payment method",
			req:  &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 100, PaymentMethod: ""},
			wantErr:    true,
			wantErrMsg: "payment method is required",
		},
		{
			name: "qr not found",
			req:  &qrpaymentmodels.PaymentRequest{QRCode: "missing", Amount: 100, PaymentMethod: "card"},
			mockQR: nil,
			wantErr:    true,
			wantErrMsg: "qr code not found",
		},
		{
			name: "qr inactive",
			req:  &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 100, PaymentMethod: "card"},
			mockQR: &qrpaymentmodels.QRCode{ID: 1, Code: "code", Status: "inactive", CodeType: "dynamic", Amount: 0, Currency: "VND", MerchantID: "m"},
			wantErr:    true,
			wantErrMsg: "qr code is not active",
		},
		{
			name: "qr expired",
			req:  &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 100, PaymentMethod: "card"},
			mockQR: &qrpaymentmodels.QRCode{ID: 1, Code: "code", Status: "active", CodeType: "dynamic", Amount: 0, Currency: "VND", MerchantID: "m", ExpiresAt: time.Now().Add(-1 * time.Hour)},
			wantErr:    true,
			wantErrMsg: "qr code has expired",
		},
		{
			name: "amount mismatch for fixed QR",
			req:  &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 150, PaymentMethod: "card"},
			mockQR: &qrpaymentmodels.QRCode{ID: 1, Code: "code", Status: "active", CodeType: "dynamic", Amount: 200, Currency: "VND", MerchantID: "m", ExpiresAt: time.Now().Add(1 * time.Hour)},
			wantErr:    true,
			wantErrMsg: "amount does not match the qr code",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			if tt.mockQR != nil {
				repo.On("GetQRCodeByCode", mock.Anything, tt.req.QRCode).Return(tt.mockQR, nil)
			} else {
				repo.On("GetQRCodeByCode", mock.Anything, tt.req.QRCode).Return(nil, nil)
			}

			payment, err := svc.ProcessPayment(context.Background(), tt.req)
			if tt.wantErr {
				assert.Error(t, err)
				assert.Contains(t, err.Error(), tt.wantErrMsg)
				assert.Nil(t, payment)
			} else {
				require.NoError(t, err)
				assert.NotNil(t, payment)
			}
		})
	}
}

func TestProcessPayment_SuccessDynamicQR(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	eventMock := new(MockEventHandler)
	svc := &QRPaymentService{
		qrRepo:       repo,
		paymentRepo:  payRepo,
		eventHandler: eventMock,
	}

	now := newNow()
	qr := &qrpaymentmodels.QRCode{
		ID:          101,
		Code:        "dyn101",
		CodeType:    "dynamic",
		MerchantID:  "merch101",
		MerchantName: "Merchant 101",
		Amount:      1000,
		Currency:    "VND",
		Status:      "active",
		ExpiresAt:   now.Add(30 * time.Minute),
		CreatedAt:   now,
	}
	req := &qrpaymentmodels.PaymentRequest{
		QRCode:        "dyn101",
		PayerID:       "payer123",
		Amount:        1000,
		PaymentMethod: "card",
		BankCode:      "VCB",
	}

	repo.On("GetQRCodeByCode", mock.Anything, "dyn101").Return(qr, nil).Once()
	payRepo.On("CreatePayment", mock.Anything, mock.Anything).Run(func(args mock.Arguments) {
		p := args[1].(*qrpaymentmodels.QRCPayment)
		assert.Equal(t, int64(0), p.ID) // ID set by repo, but mock doesn't hold
		assert.Equal(t, qr.ID, p.QRCodeID)
		assert.Equal(t, "payer123", p.PayerID)
		assert.Equal(t, qr.Amount, p.Amount)
		// fee check: 1% of 1000 = 10, but min 1000 -> should be 1000? Actually fee = amount*0.01 => 10, min 1000 => fee 1000, net 0? That seems off. Let's compute: For 1000, 1% = 10, less than min 1000 -> fee 1000. That would result negative net. That seems unrealistic but per code: fee := amount*0.01; if fee < 1000 { fee = 1000 }. For small amounts, fee may exceed amount. That's okay for test. We'll assert algorithm later.
	}).Return(nil).Once()
	repo.On("MarkQRCodeAsUsed", mock.Anything, qr.ID).Return(nil).Once()
	eventMock.On("EmitEvent", mock.Anything, "qr.payment.processed", mock.Anything).Return(nil).Once()

	payment, err := svc.ProcessPayment(context.Background(), req)
	require.NoError(t, err)
	assert.NotNil(t, payment)
	assert.Equal(t, qr.ID, payment.QRCodeID)
	assert.Equal(t, "completed", payment.Status)
	// Fee and NetAmount checks.
	expectedFee := qr.Amount * 0.01
	if expectedFee < 1000 {
		expectedFee = 1000
	}
	assert.Equal(t, expectedFee, payment.Fee)
	assert.Equal(t, qr.Amount-expectedFee, payment.NetAmount)
	assert.Equal(t, "card", payment.PaymentMethod)
	assert.NotEmpty(t, payment.ReferenceID)
	assert.NotZero(t, payment.CreatedAt)
	assert.NotNil(t, payment.CompletedAt)

	// Verify QR marked as used
	repo.AssertExpectations(t)
	eventMock.AssertExpectations(t)
}

func TestProcessPayment_StaticQRNotMarkedUsed(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	eventMock := new(MockEventHandler)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo, eventHandler: eventMock}

	now := newNow()
	qr := &qrpaymentmodels.QRCode{
		ID:          202,
		Code:        "static202",
		CodeType:    "static",
		MerchantID:  "merch202",
		MerchantName: "Merchant 202",
		Amount:      0, // variable amount
		Currency:    "VND",
		Status:      "active",
		ExpiresAt:   now.AddDate(1, 0, 0),
		CreatedAt:   now,
	}
	req := &qrpaymentmodels.PaymentRequest{
		QRCode:        "static202",
		PayerID:       "payer456",
		Amount:        5000,
		PaymentMethod: "wallet",
	}

	repo.On("GetQRCodeByCode", mock.Anything, "static202").Return(qr, nil).Once()
	payRepo.On("CreatePayment", mock.Anything, mock.Anything).Return(nil).Once()
	// MarkQRCodeAsUsed should NOT be called for static QR
	repo.On("MarkQRCodeAsUsed", mock.Anything, qr.ID).Return(errors.New("should not be called"))

	eventMock.On("EmitEvent", mock.Anything, "qr.payment.processed", mock.Anything).Return(nil).Once()

	payment, err := svc.ProcessPayment(context.Background(), req)
	require.NoError(t, err)
	assert.NotNil(t, payment)
	// Ensure MarkQRCodeAsUsed not called
	repo.AssertNotCalled(t, "MarkQRCodeAsUsed", mock.Anything, qr.ID)
	eventMock.AssertExpectations(t)
}

func TestGetPayment(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	payment := &qrpaymentmodels.QRCPayment{
		ID:         55,
		QRCodeID:   5,
		PayerID:    "payer",
		Amount:     1000,
		Fee:        100,
		NetAmount:  900,
		Currency:   "VND",
		Status:     "completed",
		CreatedAt:  newNow(),
		CompletedAt: func() *time.Time { t := time.Now(); return &t }(),
	}
	payRepo.On("GetPaymentByID", mock.Anything, int64(55)).Return(payment, nil).Once()

	result, err := svc.GetPayment(context.Background(), 55)
	require.NoError(t, err)
	assert.Equal(t, payment, result)
}

func TestGetPayment_NotFound(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	payRepo.On("GetPaymentByID", mock.Anything, int64(999)).Return(nil, nil).Once()

	result, err := svc.GetPayment(context.Background(), 999)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "not found")
	assert.Nil(t, result)
}

func TestGetPaymentByReference(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	payment := &qrpaymentmodels.QRCPayment{
		ID:          66,
		QRCodeID:    6,
		PayerID:     "payer",
		Amount:      2000,
		Fee:         200,
		NetAmount:   1800,
		ReferenceID: "REF123",
	}
	payRepo.On("GetPaymentByReference", mock.Anything, "REF123").Return(payment, nil).Once()

	result, err := svc.GetPaymentByReference(context.Background(), "REF123")
	require.NoError(t, err)
	assert.Equal(t, payment, result)
}

func TestGetPaymentByReference_NotFound(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	payRepo.On("GetPaymentByReference", mock.Anything, "MISSING").Return(nil, nil).Once()

	result, err := svc.GetPaymentByReference(context.Background(), "MISSING")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "not found")
	assert.Nil(t, result)
}

func TestGetUserPayments(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	payments := []*qrpaymentmodels.QRCPayment{
		{ID: 1, PayerID: "user1", Amount: 100},
		{ID: 2, PayerID: "user1", Amount: 200},
	}
	payRepo.On("GetPaymentsByUser", mock.Anything, "user1", 20, 0).Return(payments, nil).Once()

	result, err := svc.GetUserPayments(context.Background(), "user1", 20, 0)
	require.NoError(t, err)
	assert.Len(t, result, 2)
	assert.Equal(t, int64(1), result[0].ID)
	assert.Equal(t, int64(2), result[1].ID)
}

func TestGetUserPayments_DefaultLimit(t *testing.T) {
	repo := new(MockQRCRepository)
	payRepo := new(MockPaymentRepository)
	svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

	payRepo.On("GetPaymentsByUser", mock.Anything, "userX", 20, 10).Return([]*qrpaymentmodels.QRCPayment{}, nil).Once()

	// Call with limit=0, offset=10 => service should set limit to default 20
	_, err := svc.GetUserPayments(context.Background(), "userX", 0, 10)
	require.NoError(t, err)
	payRepo.AssertExpectations(t)
}

func TestProcessPayment_ValidationErrors(t *testing.T) {
	tests := []struct {
		name       string
		req        *qrpaymentmodels.PaymentRequest
		mockQR     *qrpaymentmodels.QRCode
		wantErr    bool
		wantErrMsg string
		wantRepo   bool // if true, expect GetQRCodeByCode to be called once
	}{
		{
			name:       "amount <= 0",
			req:        &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 0, PaymentMethod: "card"},
			wantErr:    true,
			wantErrMsg: "amount must be positive",
			wantRepo:   false,
		},
		{
			name:       "amount > max",
			req:        &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 2000000000, PaymentMethod: "card"},
			wantErr:    true,
			wantErrMsg: "amount exceeds maximum limit",
			wantRepo:   false,
		},
		{
			name:       "missing payment method",
			req:        &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 100, PaymentMethod: ""},
			wantErr:    true,
			wantErrMsg: "payment method is required",
			wantRepo:   false,
		},
		{
			name:       "qr not found",
			req:        &qrpaymentmodels.PaymentRequest{QRCode: "missing", Amount: 100, PaymentMethod: "card"},
			mockQR:     nil,
			wantErr:    true,
			wantErrMsg: "qr code not found",
			wantRepo:   true,
		},
		{
			name:       "qr inactive",
			req:        &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 100, PaymentMethod: "card"},
			mockQR:     &qrpaymentmodels.QRCode{ID: 1, Code: "code", Status: "inactive", CodeType: "dynamic", Amount: 0, Currency: "VND", MerchantID: "m"},
			wantErr:    true,
			wantErrMsg: "qr code is not active",
			wantRepo:   true,
		},
		{
			name:       "qr expired",
			req:        &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 100, PaymentMethod: "card"},
			mockQR:     &qrpaymentmodels.QRCode{ID: 1, Code: "code", Status: "active", CodeType: "dynamic", Amount: 0, Currency: "VND", MerchantID: "m", ExpiresAt: time.Now().Add(-1 * time.Hour)},
			wantErr:    true,
			wantErrMsg: "qr code has expired",
			wantRepo:   true,
		},
		{
			name:       "amount mismatch for fixed QR",
			req:        &qrpaymentmodels.PaymentRequest{QRCode: "code", Amount: 150, PaymentMethod: "card"},
			mockQR:     &qrpaymentmodels.QRCode{ID: 1, Code: "code", Status: "active", CodeType: "dynamic", Amount: 200, Currency: "VND", MerchantID: "m", ExpiresAt: time.Now().Add(1 * time.Hour)},
			wantErr:    true,
			wantErrMsg: "amount does not match the qr code",
			wantRepo:   true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			repo := new(MockQRCRepository)
			payRepo := new(MockPaymentRepository)
			svc := &QRPaymentService{qrRepo: repo, paymentRepo: payRepo}

			if tt.wantRepo {
				if tt.mockQR != nil {
					repo.On("GetQRCodeByCode", mock.Anything, tt.req.QRCode).Return(tt.mockQR, nil).Once()
				} else {
					repo.On("GetQRCodeByCode", mock.Anything, tt.req.QRCode).Return(nil, nil).Once()
				}
			}

			payment, err := svc.ProcessPayment(context.Background(), tt.req)

			if tt.wantRepo {
				repo.AssertExpectations(t)
			}

			if tt.wantErr {
				assert.Error(t, err)
				assert.Contains(t, err.Error(), tt.wantErrMsg)
				assert.Nil(t, payment)
			} else {
				require.NoError(t, err)
				assert.NotNil(t, payment)
			}
		})
	}
}
