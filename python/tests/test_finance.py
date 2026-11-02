import pytest
from modules.Finance.finance_manager import FinanceManager

class TestFinanceManager:
    def setup_method(self):
        self.manager = FinanceManager()
        # Initialize some accounts with balances for transfer tests
        self.manager.balances[123] = 100000
        self.manager.balances[456] = 50000
    
    def test_create_transaction(self):
        user_id = 123
        amount = 100000
        transaction_type = "credit"
        
        result = self.manager.create_transaction(user_id, amount, transaction_type)
        
        assert result is not None
    
    def test_get_balance(self):
        user_id = 123
        
        balance = self.manager.get_balance(user_id)
        
        assert isinstance(balance, (int, float))
    
    def test_get_transaction_history(self):
        user_id = 123
        
        history = self.manager.get_transaction_history(user_id)
        
        assert isinstance(history, list)
    
    def test_transfer_funds(self):
        from_user = 123
        to_user = 456
        amount = 50000
        
        result = self.manager.transfer_funds(from_user, to_user, amount)
        
        assert result is not None
    
    def test_get_pending_payments(self):
        user_id = 123
        
        payments = self.manager.get_pending_payments(user_id)
        
        assert isinstance(payments, list)
    
    def test_process_payment(self):
        payment_id = 789
        
        result = self.manager.process_payment(payment_id)
        
        assert result is not None
    
    def test_get_analytics(self):
        user_id = 123
        
        analytics = self.manager.get_analytics(user_id)
        
        assert isinstance(analytics, dict)
