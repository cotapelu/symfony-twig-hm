"""Unit tests for FinanceManager"""
import pytest
from modules.Finance.finance_manager import FinanceManager

@pytest.fixture
def finance_manager():
    return FinanceManager()

def test_init(finance_manager):
    assert finance_manager.transactions == {}
    assert finance_manager.balances == {}
    assert finance_manager.pending_payments == {}

def test_create_transaction_credit(finance_manager):
    tx = finance_manager.create_transaction(user_id=1, amount=100.0, transaction_type='credit')
    assert tx['user_id'] == 1
    assert tx['amount'] == 100.0
    assert tx['type'] == 'credit'
    assert tx['id'] in finance_manager.transactions
    assert finance_manager.balances[1] == 100.0

def test_create_transaction_debit(finance_manager):
    tx = finance_manager.create_transaction(user_id=2, amount=50.0, transaction_type='debit')
    assert finance_manager.balances[2] == -50.0

def test_create_transaction_invalid_amount_zero(finance_manager):
    with pytest.raises(ValueError, match="Amount must be positive"):
        finance_manager.create_transaction(user_id=1, amount=0.0, transaction_type='credit')

def test_create_transaction_invalid_amount_negative(finance_manager):
    with pytest.raises(ValueError, match="Amount must be positive"):
        finance_manager.create_transaction(user_id=1, amount=-10.0, transaction_type='credit')

def test_create_transaction_invalid_type(finance_manager):
    with pytest.raises(ValueError, match="Invalid transaction type"):
        finance_manager.create_transaction(user_id=1, amount=100.0, transaction_type='invalid')

def test_get_balance_no_entries(finance_manager):
    assert finance_manager.get_balance(1) == 0.0

def test_get_balance_existing(finance_manager):
    finance_manager.create_transaction(user_id=1, amount=100.0, transaction_type='credit')
    assert finance_manager.get_balance(1) == 100.0

def test_get_transaction_history_empty(finance_manager):
    history = finance_manager.get_transaction_history(1)
    assert history == []

def test_get_transaction_history_returns_user_transactions(finance_manager):
    fm = finance_manager
    tx1 = fm.create_transaction(user_id=1, amount=100.0, transaction_type='credit')
    tx2 = fm.create_transaction(user_id=2, amount=50.0, transaction_type='debit')
    tx3 = fm.create_transaction(user_id=1, amount=25.0, transaction_type='debit')
    history = fm.get_transaction_history(1)
    assert len(history) == 2
    ids = {t['id'] for t in history}
    assert tx1['id'] in ids
    assert tx3['id'] in ids

def test_transfer_funds_success(finance_manager):
    fm = finance_manager
    # Set initial balances
    fm.create_transaction(user_id=1, amount=200.0, transaction_type='credit')
    fm.create_transaction(user_id=2, amount=50.0, transaction_type='credit')
    # Transfer 100 from user1 to user2
    result = fm.transfer_funds(from_user=1, to_user=2, amount=100.0)
    assert result['from_user'] == 1
    assert result['to_user'] == 2
    assert result['amount'] == 100.0
    # Balances: user1: 200-100=100, user2: 50+100=150
    assert fm.get_balance(1) == 100.0
    assert fm.get_balance(2) == 150.0
    # Two new transactions: debit from 1, credit to 2
    assert len(fm.transactions) == 4  # initial two + two new
    assert result['debit_transaction_id'] in fm.transactions
    assert result['credit_transaction_id'] in fm.transactions

def test_transfer_funds_insufficient_balance(finance_manager):
    fm = finance_manager
    fm.create_transaction(user_id=1, amount=50.0, transaction_type='credit')
    # Try transfer 101 which would result in -51 < -50 (overdraft limit)
    with pytest.raises(ValueError, match="Insufficient balance"):
        fm.transfer_funds(from_user=1, to_user=2, amount=101.0)

def test_process_payment_creates_if_missing(finance_manager):
    fm = finance_manager
    # No pending payment with ID 'pay1' exists
    result = fm.process_payment('pay1')
    assert result['id'] == 'pay1'
    assert result['status'] == 'completed'
    assert 'processed_at' in result
    # It should have been added to pending_payments with default data (as per code)
    assert 'pay1' in fm.pending_payments
    assert fm.pending_payments['pay1']['user_id'] == 123
    assert fm.pending_payments['pay1']['status'] == 'completed'

def test_process_payment_existing(finance_manager):
    fm = finance_manager
    fm.pending_payments['pay2'] = {'id': 'pay2', 'user_id': 456, 'amount': 200.0, 'status': 'pending'}
    result = fm.process_payment('pay2')
    assert result['id'] == 'pay2'
    assert result['status'] == 'completed'
    assert fm.pending_payments['pay2']['status'] == 'completed'

def test_get_pending_payments_empty(finance_manager):
    fm = finance_manager
    assert fm.get_pending_payments(1) == []

def test_get_pending_payments_returns_only_pending_for_user(finance_manager):
    fm = finance_manager
    fm.pending_payments['p1'] = {'id': 'p1', 'user_id': 1, 'amount': 100, 'status': 'pending'}
    fm.pending_payments['p2'] = {'id': 'p2', 'user_id': 1, 'amount': 200, 'status': 'completed'}
    fm.pending_payments['p3'] = {'id': 'p3', 'user_id': 2, 'amount': 300, 'status': 'pending'}
    pending_user1 = fm.get_pending_payments(1)
    assert len(pending_user1) == 1
    assert pending_user1[0]['id'] == 'p1'
    pending_user2 = fm.get_pending_payments(2)
    assert len(pending_user2) == 1
    assert pending_user2[0]['id'] == 'p3'

def test_get_analytics(finance_manager):
    fm = finance_manager
    fm.create_transaction(user_id=1, amount=100.0, transaction_type='credit')
    fm.create_transaction(user_id=1, amount=30.0, transaction_type='debit')
    fm.create_transaction(user_id=1, amount=20.0, transaction_type='debit')
    analytics = fm.get_analytics(1)
    assert analytics['total_credit'] == 100.0
    assert analytics['total_debit'] == 50.0
    assert analytics['balance'] == 50.0
    assert analytics['transaction_count'] == 3