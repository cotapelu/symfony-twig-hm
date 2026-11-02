from typing import Protocol

class Transaction(Protocol):
    def commit(self):
        ...

    def rollback(self):
        ...

class TransactionManager(Protocol):
    def begin(self) -> Transaction:
        ...

# Simple implementation
class SimpleTransaction:
    def __init__(self):
        self.committed = False
        self.rolled_back = False

    def commit(self):
        if self.rolled_back:
            raise Exception("Transaction already rolled back")
        self.committed = True

    def rollback(self):
        if self.committed:
            raise Exception("Transaction already committed")
        self.rolled_back = True

class SimpleTransactionManager:
    def begin(self) -> Transaction:
        return SimpleTransaction()