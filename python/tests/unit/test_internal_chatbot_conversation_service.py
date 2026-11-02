"""Unit tests for ChatbotConversationService (internal)."""
import pytest
from unittest.mock import MagicMock
from uuid import UUID, uuid4

from internal.service.chatbot_conversation_service import ChatbotConversationService
from models.optimized_schema import ChatbotConversation

@pytest.fixture
def mock_repo():
    return MagicMock()

@pytest.fixture
def service(mock_repo):
    return ChatbotConversationService(repo=mock_repo)

def test_start_conversation_success(service, mock_repo):
    user_id = uuid4()
    session_id = uuid4()
    conv = ChatbotConversation(
        id=uuid4(),
        user_id=user_id,
        session_id=session_id,
        status="active",
        meta={}
    )
    mock_repo.create.return_value = conv

    result = service.start_conversation(user_id=user_id, session_id=session_id)

    assert result == conv
    mock_repo.create.assert_called_once()
    arg = mock_repo.create.call_args[0][0]
    assert arg.user_id == user_id
    assert arg.session_id == session_id
    assert arg.status == "active"

def test_start_conversation_custom_status(service, mock_repo):
    user_id = uuid4()
    session_id = uuid4()
    conv = ChatbotConversation(
        id=uuid4(),
        user_id=user_id,
        session_id=session_id,
        status="paused",
        meta={}
    )
    mock_repo.create.return_value = conv

    result = service.start_conversation(user_id=user_id, session_id=session_id, status="paused")

    assert result == conv
    arg = mock_repo.create.call_args[0][0]
    assert arg.status == "paused"

def test_get_conversation(service, mock_repo):
    conv_id = uuid4()
    conv = ChatbotConversation(id=conv_id, user_id=uuid4(), session_id=uuid4(), status="active")
    mock_repo.get_by_id.return_value = conv

    result = service.get_conversation(conv_id)
    assert result == conv
    mock_repo.get_by_id.assert_called_once_with(conv_id)

def test_get_conversation_not_found(service, mock_repo):
    mock_repo.get_by_id.return_value = None
    result = service.get_conversation(uuid4())
    assert result is None

def test_get_by_session(service, mock_repo):
    session_id = uuid4()
    convs = [
        ChatbotConversation(id=uuid4(), user_id=uuid4(), session_id=session_id, status="active"),
        ChatbotConversation(id=uuid4(), user_id=uuid4(), session_id=session_id, status="ended")
    ]
    mock_repo.get_by_session.return_value = convs

    result = service.get_by_session(session_id)
    assert result == convs
    mock_repo.get_by_session.assert_called_once_with(session_id)

def test_get_by_user(service, mock_repo):
    user_id = uuid4()
    convs = [ChatbotConversation(id=uuid4(), user_id=user_id, session_id=uuid4(), status="active")]
    mock_repo.get_by_user.return_value = convs

    result = service.get_by_user(user_id, limit=50)
    assert result == convs
    mock_repo.get_by_user.assert_called_once_with(user_id, 50)

def test_get_active_without_user(service, mock_repo):
    convs = [ChatbotConversation(id=uuid4(), user_id=uuid4(), session_id=uuid4(), status="active")]
    mock_repo.get_active.return_value = convs

    result = service.get_active(limit=100)
    assert result == convs
    mock_repo.get_active.assert_called_once_with(None, 100)

def test_get_active_with_user(service, mock_repo):
    user_id = uuid4()
    convs = [ChatbotConversation(id=uuid4(), user_id=user_id, session_id=uuid4(), status="active")]
    mock_repo.get_active.return_value = convs

    result = service.get_active(user_id=user_id, limit=30)
    assert result == convs
    mock_repo.get_active.assert_called_once_with(user_id, 30)

def test_update_status_validation(service):
    with pytest.raises(ValueError, match="status is required"):
        service.update_status(uuid4(), "")

def test_update_status_success(service, mock_repo):
    conv_id = uuid4()
    conv = ChatbotConversation(
        id=conv_id,
        user_id=uuid4(),
        session_id=uuid4(),
        status="paused",
        meta={}
    )
    mock_repo.update_status.return_value = conv

    result = service.update_status(conv_id, "paused")

    assert result == conv
    assert result.status == "paused"
    mock_repo.update_status.assert_called_once_with(conv_id, "paused")

def test_update_status_not_found(service, mock_repo):
    conv_id = uuid4()
    mock_repo.update_status.return_value = None

    result = service.update_status(conv_id, "ended")

    assert result is None
    mock_repo.update_status.assert_called_once_with(conv_id, "ended")

def test_end_conversation(service, mock_repo):
    conv_id = uuid4()
    conv = ChatbotConversation(id=conv_id, user_id=uuid4(), session_id=uuid4(), status="active")
    mock_repo.update_status.return_value = conv

    result = service.end_conversation(conv_id)

    assert result is not None
    mock_repo.update_status.assert_called_once_with(conv_id, "ended")


def test_start_conversation_missing_session_id(service):
    with pytest.raises(ValueError, match="session_id is required"):
        service.start_conversation(user_id=None, session_id=None)