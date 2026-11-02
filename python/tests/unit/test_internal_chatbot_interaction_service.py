"""Unit tests for ChatbotInteractionService (internal)."""
import pytest
from unittest.mock import MagicMock
from uuid import UUID, uuid4

from internal.service.chatbot_interaction_service import ChatbotInteractionService
from models.optimized_schema import ChatbotInteraction

@pytest.fixture
def mock_repo():
    return MagicMock()

@pytest.fixture
def service(mock_repo):
    return ChatbotInteractionService(repo=mock_repo)

def test_create_interaction_validation_missing_conversation_id(service):
    with pytest.raises(ValueError, match="conversation_id is required"):
        service.create_interaction(conversation_id=None, user_id=uuid4(), role="user", content="hello")

def test_create_interaction_validation_missing_role(service):
    with pytest.raises(ValueError, match="role is required"):
        service.create_interaction(conversation_id=uuid4(), user_id=uuid4(), role="", content="hello")

def test_create_interaction_validation_missing_content(service):
    with pytest.raises(ValueError, match="content is required"):
        service.create_interaction(conversation_id=uuid4(), user_id=uuid4(), role="user", content="")

def test_create_interaction_success(service, mock_repo):
    conversation_id = uuid4()
    user_id = uuid4()
    interaction = ChatbotInteraction(
        id=uuid4(),
        conversation_id=conversation_id,
        user_id=user_id,
        role="assistant",
        content="response",
        meta={}
    )
    mock_repo.create.return_value = interaction

    result = service.create_interaction(
        conversation_id=conversation_id,
        user_id=user_id,
        role="assistant",
        content="response"
    )

    assert result == interaction
    mock_repo.create.assert_called_once()
    arg = mock_repo.create.call_args[0][0]
    assert arg.conversation_id == conversation_id
    assert arg.user_id == user_id
    assert arg.role == "assistant"
    assert arg.content == "response"
    assert arg.meta == {}

def test_create_interaction_with_metadata(service, mock_repo):
    conversation_id = uuid4()
    meta = {"source": "test"}
    interaction = ChatbotInteraction(
        id=uuid4(),
        conversation_id=conversation_id,
        user_id=None,
        role="user",
        content="hi",
        meta=meta
    )
    mock_repo.create.return_value = interaction

    result = service.create_interaction(
        conversation_id=conversation_id,
        user_id=None,
        role="user",
        content="hi",
        metadata=meta
    )

    assert result == interaction
    arg = mock_repo.create.call_args[0][0]
    assert arg.meta == meta

def test_get_interaction(service, mock_repo):
    inter_id = uuid4()
    interaction = ChatbotInteraction(
        id=inter_id,
        conversation_id=uuid4(),
        user_id=uuid4(),
        role="user",
        content="test",
        meta={}
    )
    mock_repo.get_by_id.return_value = interaction

    result = service.get_interaction(inter_id)
    assert result == interaction
    mock_repo.get_by_id.assert_called_once_with(inter_id)

def test_get_interaction_not_found(service, mock_repo):
    mock_repo.get_by_id.return_value = None
    result = service.get_interaction(uuid4())
    assert result is None

def test_get_by_conversation(service, mock_repo):
    conversation_id = uuid4()
    interactions = [
        ChatbotInteraction(id=uuid4(), conversation_id=conversation_id, user_id=uuid4(), role="user", content="hi", meta={}),
        ChatbotInteraction(id=uuid4(), conversation_id=conversation_id, user_id=uuid4(), role="assistant", content="hello", meta={})
    ]
    mock_repo.get_by_conversation.return_value = interactions

    result = service.get_by_conversation(conversation_id, limit=50)
    assert result == interactions
    mock_repo.get_by_conversation.assert_called_once_with(conversation_id, 50)

def test_get_by_user(service, mock_repo):
    user_id = uuid4()
    interactions = [ChatbotInteraction(id=uuid4(), conversation_id=uuid4(), user_id=user_id, role="user", content="hi", meta={})]
    mock_repo.get_by_user.return_value = interactions

    result = service.get_by_user(user_id, limit=30)
    assert result == interactions
    mock_repo.get_by_user.assert_called_once_with(user_id, 30)

def test_get_by_role(service, mock_repo):
    conversation_id = uuid4()
    role = "assistant"
    interactions = [ChatbotInteraction(id=uuid4(), conversation_id=conversation_id, user_id=uuid4(), role=role, content="hi", meta={})]
    mock_repo.get_by_role.return_value = interactions

    result = service.get_by_role(conversation_id, role, limit=20)
    assert result == interactions
    mock_repo.get_by_role.assert_called_once_with(conversation_id, role, 20)

def test_count_by_conversation(service, mock_repo):
    conversation_id = uuid4()
    mock_repo.count_by_conversation.return_value = 15

    count = service.count_by_conversation(conversation_id)
    assert count == 15
    mock_repo.count_by_conversation.assert_called_once_with(conversation_id)