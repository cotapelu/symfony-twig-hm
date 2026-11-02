"""Unit tests for ChatbotInteractionService."""
import pytest
from unittest.mock import Mock
from uuid import UUID, uuid4

from internal.service.chatbot_interaction_service import ChatbotInteractionService


@pytest.fixture
def mock_repo():
    """Mock ChatbotInteractionRepository."""
    repo = Mock()
    repo.create = Mock()
    repo.get_by_id = Mock()
    repo.get_by_conversation = Mock()
    repo.get_by_user = Mock()
    repo.get_by_role = Mock()
    repo.count_by_conversation = Mock(return_value=0)
    return repo


def test_create_interaction_success(mock_repo):
    """Test creating an interaction."""
    service = ChatbotInteractionService(mock_repo)
    conv_id = uuid4()
    role = "bot"
    content = "Hello"
    mock_inter = Mock()
    mock_inter.id = uuid4()
    mock_repo.create.return_value = mock_inter

    result = service.create_interaction(conv_id, None, role, content)

    assert result is mock_inter
    mock_repo.create.assert_called_once()
    inter = mock_repo.create.call_args[0][0]
    assert inter.conversation_id == conv_id
    assert inter.role == role
    assert inter.content == content


def test_create_interaction_missing_fields(mock_repo):
    """Test missing required fields raise ValueError."""
    service = ChatbotInteractionService(mock_repo)
    with pytest.raises(ValueError, match="conversation_id is required"):
        service.create_interaction(None, None, "bot", "hi")
    with pytest.raises(ValueError, match="role is required"):
        service.create_interaction(uuid4(), None, "", "hi")
    with pytest.raises(ValueError, match="content is required"):
        service.create_interaction(uuid4(), None, "bot", "")


def test_get_by_conversation(mock_repo):
    """Test getting interactions by conversation."""
    service = ChatbotInteractionService(mock_repo)
    conv_id = uuid4()
    mock_list = [Mock(), Mock()]
    mock_repo.get_by_conversation.return_value = mock_list

    result = service.get_by_conversation(conv_id, limit=50)

    assert result == mock_list
    mock_repo.get_by_conversation.assert_called_once_with(conv_id, 50)


def test_count_by_conversation(mock_repo):
    """Test counting interactions in a conversation."""
    service = ChatbotInteractionService(mock_repo)
    conv_id = uuid4()
    mock_repo.count_by_conversation.return_value = 5

    count = service.count_by_conversation(conv_id)

    assert count == 5
    mock_repo.count_by_conversation.assert_called_once_with(conv_id)


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
