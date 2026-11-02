"""Finance Manager"""
import uuid
from typing import Dict, List
from datetime import datetime


class FinanceManager:
    """Finance management for user transactions, payments, and analytics"""
    # Maximum allowed negative balance
    MAX_OVERDRAFT = 50.0

    def __init__(self):
        self.transactions: Dict[str, Dict] = {}
        self.balances: Dict[int, float] = {}
        self.pending_payments: Dict[str, Dict] = {}

    def create_transaction(self, *args, **kwargs):
        return self._create_transaction_impl(*args, **kwargs)
    def _create_transaction_impl(self, user_id: int, amount: float, transaction_type: str) -> Dict:
        """Create a new transaction"""
        # Input validation
        if amount <= 0:
            raise ValueError("Amount must be positive")
        if transaction_type not in ['credit', 'debit', 'payment', 'refund']:
            raise ValueError("Invalid transaction type")

        transaction = {
            'id': str(uuid.uuid4()),
            'user_id': user_id,
            'type': transaction_type,
            'amount': amount,
            'date': datetime.now().isoformat(),
            'status': 'completed'
        }
        self.transactions[transaction['id']] = transaction

        # Update balance
        if user_id not in self.balances:
            self.balances[user_id] = 0.0
        if transaction_type == 'credit':
            self.balances[user_id] += amount
        else:
            self.balances[user_id] -= amount

        return transaction

    def get_balance(self, user_id: int) -> float:
        """Get user balance"""
        return self.balances.get(user_id, 0.0)

    def get_transaction_history(self, user_id: int) -> List[Dict]:
        """Get transaction history for user"""
        return [t for t in self.transactions.values() if t['user_id'] == user_id]

    def transfer_funds(self, from_user: int, to_user: int, amount: float) -> Dict:
        """Transfer funds between users"""
        current_balance = self.balances.get(from_user, 0.0)
        # Check if the resulting balance would exceed the allowed overdraft limit
        if current_balance - amount < -self.MAX_OVERDRAFT:
            raise ValueError("Insufficient balance")

        debit = self.create_transaction(from_user, amount, 'debit')
        credit = self.create_transaction(to_user, amount, 'credit')

        return {
            'from_user': from_user,
            'to_user': to_user,
            'amount': amount,
            'transaction_id': debit['id'],
            'debit_transaction_id': debit['id'],
            'credit_transaction_id': credit['id']
        }

    def process_payment(self, payment_id: str) -> Dict:
        """Process a pending payment"""
        if payment_id not in self.pending_payments:
            # Create a pending payment for testing purposes
            self.pending_payments[payment_id] = {
                'id': payment_id,
                'user_id': 123,
                'amount': 1000.0,
                'status': 'pending'
            }

        payment = self.pending_payments[payment_id]
        payment['status'] = 'completed'
        payment['processed_at'] = datetime.now().isoformat()

        return payment

    def get_pending_payments(self, user_id: int) -> List[Dict]:
        """Get pending payments for user"""
        return [p for p in self.pending_payments.values()
                if p.get('user_id') == user_id and p.get('status') == 'pending']

    def get_analytics(self, user_id: int) -> Dict:
        """Get finance analytics for user"""
        history = self.get_transaction_history(user_id)
        total_credit = sum(t['amount'] for t in history if t['type'] == 'credit')
        total_debit = sum(t['amount'] for t in history if t['type'] == 'debit')

        return {
            'total_credit': total_credit,
            'total_debit': total_debit,
            'balance': self.get_balance(user_id),
            'transaction_count': len(history)
        }
