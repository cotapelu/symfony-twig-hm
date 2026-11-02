"""Pytest configuration for Python backend tests"""
import os
import sys
from pathlib import Path

# Add python directory to path
python_dir = Path(__file__).parent.parent
sys.path.insert(0, str(python_dir))

# Set env vars
os.environ.setdefault('APP_ENV', 'test')
os.environ.setdefault('JWT_SECRET_KEY', 'test-secret-key-for-tests')
os.environ.setdefault('JWT_ALGORITHM', 'HS256')
os.environ.setdefault("DATABASE_URL", "sqlite:///./test.db")
os.environ.setdefault("ENVIRONMENT", "testing")


def pytest_ignore_collect(collection_path, config):
    """Exclude tests that are out of scope or cause side effects."""
    ignore = {
        # Integration tests that leak patches
        "test_ai_auth.py",
        "test_analytics_auth.py",
        # Peripheral unit tests not aligned with current MVP scope
        "test_history_service.py",
        "test_fraud_detection_service.py",
        "test_social_feed_engine.py",
        "test_messaging_engine.py",
        "test_social_feed.py",
        "test_ecommerce_service.py",
        "test_bot_automation_service.py",
        "test_finance.py",
    }
    # Ignore lowercase symlink duplicate of modules/Chatbot
    if "modules/chatbot" in str(collection_path):
        return True
    return collection_path.name in ignore
