"""
Database connection utilities for testing and development
"""
import os
import sqlite3
from contextlib import contextmanager
from typing import Optional


@contextmanager
def get_db_connection():
    """
    Get database connection for testing
    Uses SQLite for test environment
    """
    db_path = os.path.join(os.path.dirname(__file__), '..', '..', 'test.db')
    conn = sqlite3.connect(db_path, check_same_thread=False)
    conn.row_factory = sqlite3.Row
    try:
        yield conn
    finally:
        conn.close()


def init_db():
    """Initialize database schema if needed"""
    db_path = os.path.join(os.path.dirname(__file__), '..', '..', 'test.db')
    with sqlite3.connect(db_path) as conn:
        cursor = conn.cursor()
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS user_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT NOT NULL,
                action TEXT NOT NULL,
                resource TEXT,
                details TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ''')
        conn.commit()


# Auto-initialize database on import
try:
    init_db()
except Exception:
    pass
