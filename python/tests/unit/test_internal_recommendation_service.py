"""Unit tests for RecommendationService (internal)."""
import pytest
from unittest.mock import MagicMock
from uuid import UUID, uuid4
from datetime import datetime

from internal.service.recommendation_service import RecommendationService
from models.optimized_schema import Recommendation

@pytest.fixture
def mock_repo():
    return MagicMock()

@pytest.fixture
def service(mock_repo):
    return RecommendationService(repo=mock_repo)

def test_create_recommendation_validation_missing_user_id(service):
    with pytest.raises(ValueError, match="user_id is required"):
        service.create_recommendation(user_id=None, model_type="collab", items=["item1"])

def test_create_recommendation_validation_missing_model_type(service):
    with pytest.raises(ValueError, match="model_type is required"):
        service.create_recommendation(user_id=uuid4(), model_type="", items=["item1"])

def test_create_recommendation_validation_empty_items(service):
    with pytest.raises(ValueError, match="items cannot be empty"):
        service.create_recommendation(user_id=uuid4(), model_type="collab", items=[])

def test_create_recommendation_success(service, mock_repo):
    user_id = uuid4()
    rec_id = uuid4()
    rec = Recommendation(
        id=rec_id,
        user_id=user_id,
        model_type="collab",
        items=["item1", "item2"],
        score=0.95
    )
    mock_repo.create.return_value = rec

    result = service.create_recommendation(
        user_id=user_id,
        model_type="collab",
        items=["item1", "item2"],
        score=0.95
    )

    assert result == rec
    mock_repo.create.assert_called_once()
    arg = mock_repo.create.call_args[0][0]
    assert arg.user_id == user_id
    assert arg.model_type == "collab"
    assert arg.items == ["item1", "item2"]
    assert arg.score == 0.95

def test_get_recommendation(service, mock_repo):
    rec_id = uuid4()
    rec = Recommendation(id=rec_id, user_id=uuid4(), model_type="test", items=[])
    mock_repo.get_by_id.return_value = rec

    result = service.get_recommendation(rec_id)
    assert result == rec
    mock_repo.get_by_id.assert_called_once_with(rec_id)

def test_get_recommendation_not_found(service, mock_repo):
    mock_repo.get_by_id.return_value = None
    result = service.get_recommendation(uuid4())
    assert result is None

def test_get_by_user(service, mock_repo):
    user_id = uuid4()
    recs = [
        Recommendation(id=uuid4(), user_id=user_id, model_type="type1", items=["a"]),
        Recommendation(id=uuid4(), user_id=user_id, model_type="type2", items=["b"])
    ]
    mock_repo.get_by_user.return_value = recs

    result = service.get_by_user(user_id, model_type=None, limit=50)
    assert result == recs
    mock_repo.get_by_user.assert_called_once_with(user_id, None, 50)

def test_get_by_user_with_model_type(service, mock_repo):
    user_id = uuid4()
    recs = [Recommendation(id=uuid4(), user_id=user_id, model_type="collab", items=[])]
    mock_repo.get_by_user.return_value = recs

    result = service.get_by_user(user_id, model_type="collab", limit=100)
    assert result == recs
    mock_repo.get_by_user.assert_called_once_with(user_id, "collab", 100)

def test_get_latest(service, mock_repo):
    user_id = uuid4()
    rec = Recommendation(id=uuid4(), user_id=user_id, model_type="collab", items=[])
    mock_repo.get_latest_by_user.return_value = rec

    result = service.get_latest(user_id)
    assert result == rec
    mock_repo.get_latest_by_user.assert_called_once_with(user_id, None)

def test_get_latest_with_model_type(service, mock_repo):
    user_id = uuid4()
    rec = Recommendation(id=uuid4(), user_id=user_id, model_type="test", items=[])
    mock_repo.get_latest_by_user.return_value = rec

    result = service.get_latest(user_id, model_type="test")
    assert result == rec
    mock_repo.get_latest_by_user.assert_called_once_with(user_id, "test")

def test_upsert_recommendation_creates_new(service, mock_repo):
    user_id = uuid4()
    model_type = "collab"
    mock_repo.get_by_user.return_value = []

    new_rec = Recommendation(id=uuid4(), user_id=user_id, model_type=model_type, items=["item"])
    mock_repo.create.return_value = new_rec

    result = service.upsert_recommendation(user_id, model_type, ["item"])

    assert result == new_rec
    mock_repo.get_by_user.assert_called_once_with(user_id, model_type, limit=100)
    mock_repo.create.assert_called_once()

def test_upsert_recommendation_replaces_existing(service, mock_repo):
    user_id = uuid4()
    model_type = "collab"
    old_recs = [
        Recommendation(id=uuid4(), user_id=user_id, model_type=model_type, items=["old1"]),
        Recommendation(id=uuid4(), user_id=user_id, model_type=model_type, items=["old2"])
    ]
    mock_repo.get_by_user.return_value = old_recs
    new_rec = Recommendation(id=uuid4(), user_id=user_id, model_type=model_type, items=["new"])
    mock_repo.create.return_value = new_rec

    result = service.upsert_recommendation(user_id, model_type, ["new"])

    assert result == new_rec
    assert mock_repo.delete.call_count == 2
    for old in old_recs:
        mock_repo.delete.assert_any_call(old.id)
    mock_repo.create.assert_called_once()

def test_delete_recommendation(service, mock_repo):
    rec_id = uuid4()
    result = service.delete_recommendation(rec_id)
    assert result is True
    mock_repo.delete.assert_called_once_with(rec_id)