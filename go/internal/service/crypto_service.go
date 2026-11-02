package service

import (
	"context"
	"database/sql"
	"errors"
	"time"

	"community.local/internal/models"
)

type CryptoService struct {
	repo models.CryptoRepository
}

func NewCryptoService(repo models.CryptoRepository) *CryptoService {
	return &CryptoService{repo: repo}
}

func (s *CryptoService) CreateWallet(ctx context.Context, userID, currency string) (*models.CryptoWallet, error) {
	if userID == "" {
		return nil, errors.New("user ID is required")
	}
	if currency == "" {
		return nil, errors.New("currency is required")
	}
	wallet := &models.CryptoWallet{
		UserID:    userID,
		Currency:  currency,
		Balance:   0,
		Status:    "ACTIVE",
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}
	err := s.repo.CreateWallet(ctx, wallet)
	if err != nil {
		return nil, err
	}
	return wallet, nil
}

func (s *CryptoService) GetWallet(ctx context.Context, id string) (*models.CryptoWallet, error) {
	wallet, err := s.repo.GetWallet(ctx, id)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, errors.New("wallet not found")
		}
		return nil, err
	}
	if wallet.Status == "CLOSED" {
		return nil, errors.New("wallet is closed")
	}
	return wallet, nil
}

func (s *CryptoService) GetWalletByUserCurrency(ctx context.Context, userID, currency string) (*models.CryptoWallet, error) {
	wallet, err := s.repo.GetWalletByUser(ctx, userID, currency)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, errors.New("wallet not found")
		}
		return nil, err
	}
	if wallet.Status == "CLOSED" {
		return nil, errors.New("wallet is closed")
	}
	return wallet, nil
}

func (s *CryptoService) Deposit(ctx context.Context, walletID string, amount float64, txHash string) error {
	if amount <= 0 {
		return errors.New("amount must be positive")
	}
	_, err := s.GetWallet(ctx, walletID)
	if err != nil {
		return err
	}
	err = s.repo.UpdateBalance(ctx, walletID, amount)
	if err != nil {
		return err
	}
	tx := &models.CryptoTransaction{
		WalletID:  walletID,
		Type:      "DEPOSIT",
		Amount:    amount,
		TxHash:    txHash,
		Status:    "CONFIRMED",
	}
	return s.repo.CreateTransaction(ctx, tx)
}

func (s *CryptoService) Withdraw(ctx context.Context, walletID string, amount float64, txHash string) error {
	if amount <= 0 {
		return errors.New("amount must be positive")
	}
	wallet, err := s.GetWallet(ctx, walletID)
	if err != nil {
		return err
	}
	if wallet.Balance < amount {
		return errors.New("insufficient funds")
	}
	err = s.repo.UpdateBalance(ctx, walletID, -amount)
	if err != nil {
		return err
	}
	tx := &models.CryptoTransaction{
		WalletID: walletID,
		Type:     "WITHDRAW",
		Amount:   amount,
		TxHash:   txHash,
		Status:   "CONFIRMED",
	}
	return s.repo.CreateTransaction(ctx, tx)
}

func (s *CryptoService) Transfer(ctx context.Context, fromWalletID, toWalletID string, amount float64, txHash string) error {
	if amount <= 0 {
		return errors.New("amount must be positive")
	}
	if fromWalletID == toWalletID {
		return errors.New("cannot transfer to same wallet")
	}
	fromWallet, err := s.GetWallet(ctx, fromWalletID)
	if err != nil {
		return err
	}
	_, err = s.GetWallet(ctx, toWalletID)
	if err != nil {
		return err
	}
	if fromWallet.Balance < amount {
		return errors.New("insufficient funds")
	}
	err = s.repo.UpdateBalance(ctx, fromWalletID, -amount)
	if err != nil {
		return err
	}
	err = s.repo.UpdateBalance(ctx, toWalletID, amount)
	if err != nil {
		s.repo.UpdateBalance(ctx, fromWalletID, amount)
		return err
	}
	now := time.Now()
	s.repo.CreateTransaction(ctx, &models.CryptoTransaction{
		WalletID:  fromWalletID,
		Type:      "TRANSFER_OUT",
		Amount:    amount,
		TxHash:    txHash + "-out",
		Status:    "CONFIRMED",
		CreatedAt: now,
	})
	s.repo.CreateTransaction(ctx, &models.CryptoTransaction{
		WalletID:  toWalletID,
		Type:      "TRANSFER_IN",
		Amount:    amount,
		TxHash:    txHash + "-in",
		Status:    "CONFIRMED",
		CreatedAt: now,
	})
	return nil
}
