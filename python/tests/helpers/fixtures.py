"""Common test fixtures and helpers for Python backend."""
import pytest
from datetime import datetime, timedelta
from typing import Dict, Any

class FixtureFactory:
    """Factory for creating test data."""

    @staticmethod
    def user(user_id: str = "test-user", email: str = None, roles: list = None) -> Dict[str, Any]:
        return {
            "id": user_id,
            "email": email or f"{user_id}@example.com",
            "roles": roles or ["user"],
            "is_active": True,
            "created_at": datetime.utcnow().isoformat(),
        }

    @staticmethod
    def admin(user_id: str = "admin-user") -> Dict[str, Any]:
        return FixtureFactory.user(user_id, roles=["admin"])

    @staticmethod
    def citizen(user_id: str = "citizen-1", name: str = "Nguyen Van A") -> Dict[str, Any]:
        base = FixtureFactory.user(user_id)
        base.update({
            "name": name,
            "roles": ["citizen"],
            "citizen_id": f"ID{user_id}",
        })
        return base

    @staticmethod
    def conversation(conv_id: str = "conv-1", participant_ids: list = None) -> Dict[str, Any]:
        return {
            "id": conv_id,
            "participants": participant_ids or ["user1", "user2"],
            "type": "direct",
            "created_at": datetime.utcnow().isoformat(),
        }

    @staticmethod
    def message(msg_id: str = "msg-1", sender_id: str = "user1", content: str = "Hello") -> Dict[str, Any]:
        return {
            "id": msg_id,
            "conversation_id": "conv-1",
            "sender_id": sender_id,
            "content": content,
            "created_at": datetime.utcnow().isoformat(),
        }

@pytest.fixture
def factory():
    return FixtureFactory

@pytest.fixture
def sample_user(factory):
    return factory.user()

@pytest.fixture
def sample_admin(factory):
    return factory.admin()

@pytest.fixture
def sample_citizen(factory):
    return factory.citizen()
