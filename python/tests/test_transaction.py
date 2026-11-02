"""Unit tests for app.appcore.transaction (transaction management)."""
import pytest

from app.appcore.transaction import (
    Transaction,
    TransactionManager,
    SimpleTransaction,
    SimpleTransactionManager,
)


# ==== SimpleTransaction ====

def test_simple_transaction_init():
    tx = SimpleTransaction()
    assert tx.committed is False
    assert tx.rolled_back is False


def test_simple_transaction_commit_succeeds():
    tx = SimpleTransaction()
    tx.commit()
    assert tx.committed is True
    assert tx.rolled_back is False


def test_simple_transaction_rollback_succeeds():
    tx = SimpleTransaction()
    tx.rollback()
    assert tx.rolled_back is True
    assert tx.committed is False


def test_simple_transaction_commit_after_rollback_raises():
    tx = SimpleTransaction()
    tx.rollback()
    with pytest.raises(Exception, match="already rolled back"):
        tx.commit()


def test_simple_transaction_rollback_after_commit_raises():
    tx = SimpleTransaction()
    tx.commit()
    with pytest.raises(Exception, match="already committed"):
        tx.rollback()


def test_simple_transaction_commit_only_once():
    """First commit succeeds; second commit doesn't raise (idempotent)."""
    tx = SimpleTransaction()
    tx.commit()
    tx.commit()  # No raise
    assert tx.committed is True


def test_simple_transaction_rollback_only_once():
    """First rollback succeeds; second rollback doesn't raise (idempotent)."""
    tx = SimpleTransaction()
    tx.rollback()
    tx.rollback()  # No raise
    assert tx.rolled_back is True


# ==== SimpleTransactionManager ====

def test_manager_begin_returns_transaction():
    mgr = SimpleTransactionManager()
    tx = mgr.begin()
    # Protocol isinstance check at runtime requires @runtime_checkable;
    # just verify the object has the expected methods.
    assert hasattr(tx, 'commit')
    assert hasattr(tx, 'rollback')


def test_manager_begin_creates_fresh_transaction():
    """Each begin() returns a new transaction."""
    mgr = SimpleTransactionManager()
    tx1 = mgr.begin()
    tx2 = mgr.begin()
    assert tx1 is not tx2
    assert tx1.committed is False
    assert tx2.committed is False


def test_manager_begin_returns_simple_transaction():
    mgr = SimpleTransactionManager()
    tx = mgr.begin()
    assert isinstance(tx, SimpleTransaction)


def test_manager_full_lifecycle():
    mgr = SimpleTransactionManager()
    tx = mgr.begin()
    tx.commit()
    assert tx.committed is True


def test_manager_full_lifecycle_with_rollback():
    mgr = SimpleTransactionManager()
    tx = mgr.begin()
    tx.rollback()
    assert tx.rolled_back is True


# ==== Protocol conformance ====

def test_simple_transaction_implements_transaction_protocol():
    """SimpleTransaction should implement Transaction protocol methods."""
    tx = SimpleTransaction()
    assert hasattr(tx, 'commit')
    assert hasattr(tx, 'rollback')
    assert callable(tx.commit)
    assert callable(tx.rollback)


def test_simple_transaction_manager_implements_manager_protocol():
    mgr = SimpleTransactionManager()
    assert hasattr(mgr, 'begin')
    assert callable(mgr.begin)


# ==== Integration scenarios ====

def test_begin_commit_begin_commit():
    """Sequential transactions work independently."""
    mgr = SimpleTransactionManager()
    tx1 = mgr.begin()
    tx1.commit()
    tx2 = mgr.begin()
    tx2.commit()
    assert tx1.committed and tx2.committed


def test_begin_rollback_begin_rollback():
    """Sequential rollbacks work independently."""
    mgr = SimpleTransactionManager()
    tx1 = mgr.begin()
    tx1.rollback()
    tx2 = mgr.begin()
    tx2.rollback()
    assert tx1.rolled_back and tx2.rolled_back


def test_begin_commit_begin_rollback():
    """Mixed commit/rollback in sequence."""
    mgr = SimpleTransactionManager()
    tx1 = mgr.begin()
    tx1.commit()
    tx2 = mgr.begin()
    tx2.rollback()
    assert tx1.committed and tx2.rolled_back
