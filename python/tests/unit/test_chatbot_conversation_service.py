"""Unit tests for ChatbotConversationService."""
import pytest
from unittest.mock import Mock
from uuid import UUID, uuid4

from internal.service.chatbot_conversation_service import ChatbotConversationService


@pytest.fixture
def mock_repo():
    """Mock ChatbotConversationRepository."""
    repo = Mock()
    repo.create = Mock()
    repo.get_by_id = Mock()
    repo.get_by_session = Mock()
    repo.get_by_user = Mock()
    repo.get_active = Mock()
    repo.update_status = Mock()
    return repo


def test_start_conversation_success(mock_repo):
    """Test starting a conversation."""
    service = ChatbotConversationService(mock_repo)
    session_id = uuid4()
    mock_conv = Mock()
    mock_conv.id = uuid4()
    mock_repo.create.return_value = mock_conv

    result = service.start_conversation(None, session_id)

    assert result is mock_conv
    mock_repo.create.assert_called_once()
    conv = mock_repo.create.call_args[0][0]
    assert conv.session_id == session_id
    assert conv.status == "active"


def test_start_conversation_missing_session_id(mock_repo):
    """Test missing session_id raises ValueError."""
    service = ChatbotConversationService(mock_repo)
    with pytest.raises(ValueError, match="session_id is required"):
        service.start_conversation(None, None)


def test_get_by_session(mock_repo):
    """Test getting conversations by session."""
    service = ChatbotConversationService(mock_repo)
    session_id = uuid4()
    mock_list = [Mock(), Mock()]
    mock_repo.get_by_session.return_value = mock_list

    result = service.get_by_session(session_id)

    assert result == mock_list
    mock_repo.get_by_session.assert_called_once_with(session_id)


def test_get_active(mock_repo):
    """Test getting active conversations."""
    service = ChatbotConversationService(mock_repo)
    mock_list = [Mock()]
    mock_repo.get_active.return_value = mock_list

    result = service.get_active(limit=50)

    assert result == mock_list
    mock_repo.get_active.assert_called_once_with(None, 50)


def test_update_status_missing_status(mock_repo):
    """Test missing status raises ValueError."""
    service = ChatbotConversationService(mock_repo)
    with pytest.raises(ValueError, match="status is required"):
        service.update_status(uuid4(), "")


def test_end_conversation(mock_repo):
    """Test ending a conversation."""
    service = ChatbotConversationService(mock_repo)
    conv_id = uuid4()
    mock_conv = Mock()
    mock_repo.update_status.return_value = mock_conv

    result = service.end_conversation(conv_id)

    assert result is mock_conv
    mock_repo.update_status.assert_called_once_with(conv_id, "ended")


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
