package billspayment

import (
	"context"
	"database/sql"
	"errors"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

// MockBillRepository mocks BillRepository
type MockBillRepository struct {
	mock.Mock
}

func (m *MockBillRepository) CreateBill(ctx context.Context, b *Bill) error {
	args := m.Called(ctx, b)
	return args.Error(0)
}

func (m *MockBillRepository) GetBill(ctx context.Context, id int64) (*Bill, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*Bill), args.Error(1)
}

func (m *MockBillRepository) GetBillsByAccount(ctx context.Context, accNum string) ([]*Bill, error) {
	args := m.Called(ctx, accNum)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*Bill), args.Error(1)
}

func (m *MockBillRepository) GetUpcomingBills(ctx context.Context, days int) ([]*Bill, error) {
	args := m.Called(ctx, days)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*Bill), args.Error(1)
}

func (m *MockBillRepository) MarkAsPaid(ctx context.Context, id int64) error {
	args := m.Called(ctx, id)
	return args.Error(0)
}

// DB satisfies BillRepositoryInterface (not used in tests)
func (m *MockBillRepository) DB() *sql.DB {
	return nil
}

// MockPaymentRepository mocks PaymentRepository
type MockPaymentRepository struct {
	mock.Mock
}

func (m *MockPaymentRepository) CreatePayment(ctx context.Context, p *BillPayment) error {
	args := m.Called(ctx, p)
	return args.Error(0)
}

func (m *MockPaymentRepository) GetPayment(ctx context.Context, id int64) (*BillPayment, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*BillPayment), args.Error(1)
}

func (m *MockPaymentRepository) GetPaymentsByUser(ctx context.Context, userID string) ([]*BillPayment, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*BillPayment), args.Error(1)
}

func (m *MockPaymentRepository) GetPaymentByTransaction(ctx context.Context, txID string) (*BillPayment, error) {
	args := m.Called(ctx, txID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*BillPayment), args.Error(1)
}

// MockProviderRepository mocks ProviderRepository
type MockProviderRepository struct {
	mock.Mock
}

func (m *MockProviderRepository) GetAll(ctx context.Context) ([]*BillProvider, error) {
	args := m.Called(ctx)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*BillProvider), args.Error(1)
}

func (m *MockProviderRepository) GetByCategory(ctx context.Context, category string) ([]*BillProvider, error) {
	args := m.Called(ctx, category)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*BillProvider), args.Error(1)
}

// MockEventHandler mocks EventHandler
type MockEventHandler struct {
	mock.Mock
}

func (m *MockEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	args := m.Called(ctx, eventType, data)
	return args.Error(0)
}

func TestBillsPaymentService_CreateBill(t *testing.T) {
	ctx := context.Background()
	now := time.Now()
	dueDate := now.Add(24 * time.Hour)

	svc := &BillsPaymentService{
		billRepo: &MockBillRepository{},
	}
	mockRepo := svc.billRepo.(*MockBillRepository)
	mockRepo.On("CreateBill", ctx, mock.Anything).Run(func(args mock.Arguments) {
		b := args.Get(1).(*Bill)
		b.ID = 1
		b.CreatedAt = now
	}).Return(nil).Once()

	bill, err := svc.CreateBill(ctx, "electricity", "EVN", "ACC123", "Nguyen Van A", 500000.0, "2025-03", dueDate)

	assert.NoError(t, err)
	assert.NotNil(t, bill)
	assert.Equal(t, "electricity", bill.BillType)
	assert.Equal(t, "EVN", bill.Provider)
	assert.Equal(t, "ACC123", bill.AccountNumber)
	assert.Equal(t, "Nguyen Van A", bill.CustomerName)
	assert.Equal(t, 500000.0, bill.Amount)
	assert.Equal(t, "2025-03", bill.Period)
	assert.Equal(t, "unpaid", bill.Status)
	assert.WithinDuration(t, now, bill.CreatedAt, 1*time.Second)
}

func TestBillsPaymentService_GetBill(t *testing.T) {
	ctx := context.Background()
	now := time.Now()
	bill := &Bill{
		ID:            1,
		BillType:      "water",
		Provider:      "WATERCO",
		AccountNumber: "ACC999",
		CustomerName:  "Tran Thi B",
		Amount:        200000.0,
		Period:        "2025-03",
		Status:        "unpaid",
		DueDate:       now.Add(48 * time.Hour),
		CreatedAt:     now,
	}

	svc := &BillsPaymentService{
		billRepo: &MockBillRepository{},
	}

	mockRepo := svc.billRepo.(*MockBillRepository)
	mockRepo.On("GetBill", ctx, int64(1)).Return(bill, nil)

	result, err := svc.GetBill(ctx, 1)

	assert.NoError(t, err)
	assert.Equal(t, bill, result)
	mockRepo.AssertExpectations(t)
}

func TestBillsPaymentService_GetBill_NotFound(t *testing.T) {
	ctx := context.Background()
	svc := &BillsPaymentService{
		billRepo: &MockBillRepository{},
	}
	mockRepo := svc.billRepo.(*MockBillRepository)
	mockRepo.On("GetBill", ctx, int64(999)).Return((*Bill)(nil), errors.New("not found"))

	result, err := svc.GetBill(ctx, 999)

	assert.Error(t, err)
	assert.Nil(t, result)
}

func TestBillsPaymentService_GetBillsByAccount(t *testing.T) {
	ctx := context.Background()
	now := time.Now()
	bills := []*Bill{
		{ID: 1, BillType: "electricity", Provider: "EVN", AccountNumber: "ACC123", Amount: 500000.0, DueDate: now.Add(24 * time.Hour), Status: "unpaid", CreatedAt: now},
		{ID: 2, BillType: "water", Provider: "WATERCO", AccountNumber: "ACC123", Amount: 200000.0, DueDate: now.Add(48 * time.Hour), Status: "unpaid", CreatedAt: now},
	}

	svc := &BillsPaymentService{
		billRepo: &MockBillRepository{},
	}
	mockRepo := svc.billRepo.(*MockBillRepository)
	mockRepo.On("GetBillsByAccount", ctx, "ACC123").Return(bills, nil)

	result, err := svc.GetBillsByAccount(ctx, "ACC123")

	assert.NoError(t, err)
	assert.Len(t, result, 2)
	assert.Equal(t, bills[0].ID, result[0].ID)
}

func TestBillsPaymentService_PayBill_Success_SKIPPED(t *testing.T) {
	t.Skip("requires real DB transaction")
	ctx := context.Background()
	now := time.Now()
	bill := &Bill{
		ID:            1,
		BillType:      "electricity",
		Provider:      "EVN",
		AccountNumber: "ACC123",
		CustomerName:  "Nguyen Van A",
		Amount:        500000.0,
		Period:        "2025-03",
		Status:        "unpaid",
		DueDate:       now.Add(24 * time.Hour),
		CreatedAt:     now,
	}

	payment := &BillPayment{
		ID:             1,
		BillID:         1,
		UserID:         "user123",
		Amount:         500000.0,
		PaymentMethod:  "wallet",
		TransactionID:  "BILLPAY-1-1234567890",
		Status:         "completed",
		CreatedAt:      now,
	}

	svc := &BillsPaymentService{
		billRepo:     &MockBillRepository{},
		paymentRepo:  &MockPaymentRepository{},
		eventHandler: &MockEventHandler{},
	}

	billRepo := svc.billRepo.(*MockBillRepository)
	paymentRepo := svc.paymentRepo.(*MockPaymentRepository)
	eventHandler := svc.eventHandler.(*MockEventHandler)

	// Expectations in order
	billRepo.On("GetBill", ctx, int64(1)).Return(bill, nil).Once()
	paymentRepo.On("CreatePayment", ctx, mock.AnythingOfType("*billspayment.BillPayment")).Return(nil).Once()
	billRepo.On("MarkAsPaid", ctx, int64(1)).Return(nil).Once()
	eventHandler.On("EmitEvent", ctx, "bill.payment", mock.Anything).Return(nil).Once()

	// Mock QueryRow to return payment ID and CreatedAt
	paymentRepo.On("CreatePayment", mock.Anything, mock.Anything).Run(func(args mock.Arguments) {
		p := args.Get(1).(*BillPayment)
		p.ID = payment.ID
		p.CreatedAt = payment.CreatedAt
	}).Return(nil)

	result, err := svc.PayBill(ctx, 1, "user123", "wallet")

	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, payment.BillID, result.BillID)
	assert.Equal(t, payment.UserID, result.UserID)
	assert.Equal(t, payment.TransactionID, result.TransactionID)
	assert.Equal(t, "completed", result.Status)

	billRepo.AssertExpectations(t)
	paymentRepo.AssertExpectations(t)
	eventHandler.AssertExpectations(t)
}

func TestBillsPaymentService_PayBill_BillNotFound_SKIPPED(t *testing.T) {
	t.Skip("requires real DB transaction")
	ctx := context.Background()
	svc := &BillsPaymentService{
		billRepo: &MockBillRepository{},
	}
	mockRepo := svc.billRepo.(*MockBillRepository)
	mockRepo.On("GetBill", ctx, int64(999)).Return((*Bill)(nil), errors.New("not found"))

	result, err := svc.PayBill(ctx, 999, "user123", "wallet")

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "bill not found")
	assert.Nil(t, result)
}

func TestBillsPaymentService_PayBill_AlreadyPaid_SKIPPED(t *testing.T) {
	t.Skip("requires real DB transaction")
	ctx := context.Background()
	bill := &Bill{
		ID:     1,
		Status: "paid",
	}

	svc := &BillsPaymentService{
		billRepo: &MockBillRepository{},
	}
	mockRepo := svc.billRepo.(*MockBillRepository)
	mockRepo.On("GetBill", ctx, int64(1)).Return(bill, nil)

	result, err := svc.PayBill(ctx, 1, "user123", "wallet")

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "already paid")
	assert.Nil(t, result)
}

func TestBillsPaymentService_GetPayments(t *testing.T) {
	ctx := context.Background()
	now := time.Now()
	payments := []*BillPayment{
		{ID: 1, BillID: 1, UserID: "user123", Amount: 500000.0, PaymentMethod: "wallet", Status: "completed", CreatedAt: now},
		{ID: 2, BillID: 2, UserID: "user123", Amount: 200000.0, PaymentMethod: "card", Status: "completed", CreatedAt: now},
	}

	svc := &BillsPaymentService{
		paymentRepo: &MockPaymentRepository{},
	}
	mockRepo := svc.paymentRepo.(*MockPaymentRepository)
	mockRepo.On("GetPaymentsByUser", ctx, "user123").Return(payments, nil)

	result, err := svc.GetPayments(ctx, "user123")

	assert.NoError(t, err)
	assert.Len(t, result, 2)
}

func TestBillsPaymentService_GetUpcomingBills(t *testing.T) {
	ctx := context.Background()
	now := time.Now()
	bills := []*Bill{
		{ID: 1, DueDate: now.Add(2 * 24 * time.Hour), Status: "unpaid"},
		{ID: 2, DueDate: now.Add(5 * 24 * time.Hour), Status: "unpaid"},
	}

	svc := &BillsPaymentService{
		billRepo: &MockBillRepository{},
	}
	mockRepo := svc.billRepo.(*MockBillRepository)
	mockRepo.On("GetUpcomingBills", ctx, 7).Return(bills, nil)

	result, err := svc.GetUpcomingBills(ctx, 7)

	assert.NoError(t, err)
	assert.Len(t, result, 2)
}

func TestBillsPaymentService_GetProviders(t *testing.T) {
	ctx := context.Background()
	providers := []*BillProvider{
		{ID: 1, Name: "EVN", Code: "EVN", Category: "electricity", IsActive: true},
		{ID: 2, Name: "WATERCO", Code: "WATER", Category: "water", IsActive: true},
	}

	svc := &BillsPaymentService{
		providerRepo: &MockProviderRepository{},
	}
	mockRepo := svc.providerRepo.(*MockProviderRepository)
	mockRepo.On("GetAll", ctx).Return(providers, nil)

	result, err := svc.GetProviders(ctx)

	assert.NoError(t, err)
	assert.Len(t, result, 2)
	assert.Equal(t, "EVN", result[0].Name)
}

func TestBillsPaymentService_GetProvidersByCategory(t *testing.T) {
	ctx := context.Background()
	providers := []*BillProvider{
		{ID: 1, Name: "EVN", Code: "EVN", Category: "electricity", IsActive: true},
	}

	svc := &BillsPaymentService{
		providerRepo: &MockProviderRepository{},
	}
	mockRepo := svc.providerRepo.(*MockProviderRepository)
	mockRepo.On("GetByCategory", ctx, "electricity").Return(providers, nil)

	result, err := svc.GetProvidersByCategory(ctx, "electricity")

	assert.NoError(t, err)
	assert.Len(t, result, 1)
	assert.Equal(t, "electricity", result[0].Category)
}

// mockTx is a test double for the Tx interface.
type mockTx struct{}

func (mt *mockTx) Commit() error  { return nil }
func (mt *mockTx) Rollback() error { return nil }

func TestBillsPaymentService_CreateBill_InvalidAmount(t *testing.T) {
	svc := &BillsPaymentService{
		billRepo: &MockBillRepository{},
	}
	_, err := svc.CreateBill(context.Background(), "electricity", "EVN", "ACC123", "John", 0, "2025-03", time.Now())
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "amount must be greater than zero")
}

func TestBillsPaymentService_PayBill_Success(t *testing.T) {
	ctx := context.Background()
	now := time.Now()
	bill := &Bill{
		ID:            1,
		BillType:      "electricity",
		Provider:      "EVN",
		AccountNumber: "ACC123",
		CustomerName:  "John Doe",
		Amount:        500000.0,
		Period:        "2025-03",
		Status:        "unpaid",
		DueDate:       now.Add(24 * time.Hour),
		CreatedAt:     now,
	}

	// Mocks
	billRepo := &MockBillRepository{}
	billRepo.On("GetBill", ctx, int64(1)).Return(bill, nil)
	billRepo.On("MarkAsPaid", ctx, int64(1)).Return(nil)

	paymentRepo := &MockPaymentRepository{}
	paymentRepo.On("CreatePayment", ctx, mock.AnythingOfType("*billspayment.BillPayment")).Run(func(args mock.Arguments) {
		p := args.Get(1).(*BillPayment)
		p.ID = 1
		p.CreatedAt = now
	}).Return(nil)

	svc := &BillsPaymentService{
		billRepo:     billRepo,
		paymentRepo:  paymentRepo,
		eventHandler: nil,
	}
	svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) {
		return &mockTx{}, nil
	}

	result, err := svc.PayBill(ctx, 1, "user123", "wallet")

	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, int64(1), result.BillID)
	assert.Equal(t, "user123", result.UserID)
	assert.Equal(t, 500000.0, result.Amount)
	assert.Equal(t, "wallet", result.PaymentMethod)
	assert.Equal(t, "completed", result.Status)
	assert.NotEmpty(t, result.TransactionID)
}

func TestBillsPaymentService_PayBill_BillNotFound(t *testing.T) {
	ctx := context.Background()
	billRepo := &MockBillRepository{}
	billRepo.On("GetBill", ctx, int64(999)).Return((*Bill)(nil), errors.New("not found"))

	svc := &BillsPaymentService{
		billRepo: billRepo,
	}
	svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

	_, err := svc.PayBill(ctx, 999, "user123", "wallet")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "bill not found")
}

func TestBillsPaymentService_PayBill_AlreadyPaid(t *testing.T) {
	ctx := context.Background()
	bill := &Bill{
		ID:     1,
		Status: "paid",
	}
	billRepo := &MockBillRepository{}
	billRepo.On("GetBill", ctx, int64(1)).Return(bill, nil)

	svc := &BillsPaymentService{
		billRepo: billRepo,
	}
	svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

	_, err := svc.PayBill(ctx, 1, "user123", "wallet")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "bill already paid")
}

func TestBillsPaymentService_GetPaymentByTransaction(t *testing.T) {
	ctx := context.Background()
	payment := &BillPayment{
		ID:             1,
		BillID:         1,
		UserID:         "user123",
		Amount:         500000.0,
		PaymentMethod:  "wallet",
		TransactionID:  "TX123",
		Status:         "completed",
	}
	paymentRepo := &MockPaymentRepository{}
	paymentRepo.On("GetPaymentByTransaction", ctx, "TX123").Return(payment, nil)

	svc := &BillsPaymentService{
		paymentRepo: paymentRepo,
		beginTx:     func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil },
	}
	result, err := svc.GetPaymentByTransaction(ctx, "TX123")
	assert.NoError(t, err)
	assert.Equal(t, payment, result)
}

func TestBillsPaymentService_New(t *testing.T) {
	db := &sql.DB{} // dummy, beginTx will try to call BeginTx on this; but we override after construction.
	event := &MockEventHandler{}
	svc := NewBillsPaymentService(db, event)
	assert.NotNil(t, svc)
	assert.NotNil(t, svc.beginTx)
}

func TestBillsPaymentService_PayBill_CreatePaymentError(t *testing.T) {
	ctx := context.Background()
	now := time.Now()
	bill := &Bill{
		ID:            1,
		BillType:      "electricity",
		Provider:      "EVN",
		AccountNumber: "ACC123",
		CustomerName:  "John Doe",
		Amount:        500000.0,
		Period:        "2025-03",
		Status:        "unpaid",
		DueDate:       now.Add(24 * time.Hour),
		CreatedAt:     now,
	}

	billRepo := &MockBillRepository{}
	billRepo.On("GetBill", ctx, int64(1)).Return(bill, nil)

	paymentRepo := &MockPaymentRepository{}
	paymentRepo.On("CreatePayment", ctx, mock.AnythingOfType("*billspayment.BillPayment")).Return(errors.New("payment failed")).Once()

	svc := &BillsPaymentService{
		billRepo:    billRepo,
		paymentRepo: paymentRepo,
	}
	svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

	_, err := svc.PayBill(ctx, 1, "user123", "wallet")

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "failed to create payment")
}

func TestBillsPaymentService_PayBill_MarkAsPaidError(t *testing.T) {
	ctx := context.Background()
	now := time.Now()
	bill := &Bill{
		ID:            1,
		BillType:      "electricity",
		Provider:      "EVN",
		AccountNumber: "ACC123",
		CustomerName:  "John Doe",
		Amount:        500000.0,
		Period:        "2025-03",
		Status:        "unpaid",
		DueDate:       now.Add(24 * time.Hour),
		CreatedAt:     now,
	}

	billRepo := &MockBillRepository{}
	billRepo.On("GetBill", ctx, int64(1)).Return(bill, nil)

	paymentRepo := &MockPaymentRepository{}
	paymentRepo.On("CreatePayment", ctx, mock.AnythingOfType("*billspayment.BillPayment")).Return(nil).Once()

	// Second call to MarkAsPaid fails
	billRepo.On("MarkAsPaid", ctx, int64(1)).Return(errors.New("update failed")).Once()

	svc := &BillsPaymentService{
		billRepo:    billRepo,
		paymentRepo: paymentRepo,
	}
	svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

	_, err := svc.PayBill(ctx, 1, "user123", "wallet")

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "failed to mark bill as paid")
}
