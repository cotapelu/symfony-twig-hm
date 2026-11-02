"""Unit tests for FinanceManager."""
import pytest
from modules.Finance.finance_manager import FinanceManager

class TestFinanceManager:
    """Test suite for FinanceManager."""

    @pytest.fixture
    def fm(self):
        return FinanceManager()

    def test_create_transaction_credit(self, fm):
        txn = fm.create_transaction(user_id=1, amount=100.0, transaction_type='credit')
        assert txn['id'] is not None
        assert txn['user_id'] == 1
        assert txn['type'] == 'credit'
        assert txn['amount'] == 100.0
        assert txn['status'] == 'completed'
        assert fm.get_balance(1) == 100.0

    def test_create_transaction_debit(self, fm):
        fm.create_transaction(user_id=1, amount=50.0, transaction_type='debit')
        assert fm.get_balance(1) == -50.0

    def test_create_transaction_invalid_amount(self, fm):
        with pytest.raises(ValueError):
            fm.create_transaction(user_id=1, amount=0, transaction_type='credit')
        with pytest.raises(ValueError):
            fm.create_transaction(user_id=1, amount=-10, transaction_type='debit')

    def test_create_transaction_invalid_type(self, fm):
        with pytest.raises(ValueError):
            fm.create_transaction(user_id=1, amount=100, transaction_type='invalid')

    def test_get_balance_default(self, fm):
        assert fm.get_balance(999) == 0.0

    def test_get_transaction_history(self, fm):
        fm.create_transaction(user_id=1, amount=100, transaction_type='credit')
        fm.create_transaction(user_id=1, amount=50, transaction_type='debit')
        fm.create_transaction(user_id=2, amount=30, transaction_type='credit')
        history = fm.get_transaction_history(1)
        assert len(history) == 2
        for txn in history:
            assert txn['user_id'] == 1

    def test_transfer_funds_success(self, fm):
        # Setup balances if not exist (fm uses default 0.0)
        result = fm.transfer_funds(from_user=1, to_user=2, amount=30.0)
        assert result['from_user'] == 1
        assert result['to_user'] == 2
        assert result['amount'] == 30.0
        assert 'transaction_id' in result
        # Balances: from_user -30, to_user +30
        assert fm.get_balance(1) == -30.0
        assert fm.get_balance(2) == 30.0
        # Two transactions recorded
        history1 = fm.get_transaction_history(1)
        history2 = fm.get_transaction_history(2)
        assert len(history1) == 1
        assert len(history2) == 1

    def test_transfer_funds_insufficient_balance(self, fm):
        with pytest.raises(ValueError) as excinfo:
            fm.transfer_funds(from_user=1, to_user=2, amount=100.0)
        assert "Insufficient balance" in str(excinfo.value)

    def test_get_pending_payments_empty(self, fm):
        payments = fm.get_pending_payments(user_id=1)
        assert payments == []

    def test_process_payment_creates_if_missing(self, fm):
        payment_id = "pay_123"
        result = fm.process_payment(payment_id)
        assert result['id'] == payment_id
        assert result['status'] == 'completed'
        assert 'processed_at' in result
        # After processing, pending payments dict may still contain it? The method sets status completed; it doesn't remove.
        pending = fm.get_pending_payments(result['user_id'])
        # Since we created a payment with user_id 123 and status 'pending' initially but then set to 'completed', get_pending_payments should not include completed ones because filter is .get('status') == 'pending'
        assert len(pending) == 0

    def test_process_payment_existing_pending(self, fm):
        # Manually add a pending payment
        fm.pending_payments['pay_456'] = {
            'id': 'pay_456',
            'user_id': 1,
            'amount': 50.0,
            'status': 'pending'
        }
        result = fm.process_payment('pay_456')
        assert result['status'] == 'completed'
        # Check that it's no longer pending
        pending = fm.get_pending_payments(1)
        assert not any(p['id'] == 'pay_456' for p in pending)

if __name__ == "__main__":
    pytest.main([__file__, "-v"])