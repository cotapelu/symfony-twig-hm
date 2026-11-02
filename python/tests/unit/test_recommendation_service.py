"""Unit tests for RecommendationService."""
import pytest
from unittest.mock import Mock
from uuid import UUID, uuid4
from datetime import datetime, timedelta

from internal.service.recommendation_service import RecommendationService


@pytest.fixture
def mock_repo():
    """Mock RecommendationRepository."""
    repo = Mock()
    repo.create = Mock()
    repo.get_by_id = Mock()
    repo.get_by_user = Mock()
    repo.get_latest_by_user = Mock()
    repo.delete = Mock()
    return repo


def test_create_recommendation_success(mock_repo):
    """Test creating a recommendation."""
    service = RecommendationService(mock_repo)
    user_id = uuid4()
    model_type = "collaborative_filtering"
    items = ["item1", "item2"]
    mock_rec = Mock()
    mock_rec.id = uuid4()
    mock_repo.create.return_value = mock_rec

    result = service.create_recommendation(user_id, model_type, items)

    assert result is mock_rec
    mock_repo.create.assert_called_once()
    rec = mock_repo.create.call_args[0][0]
    assert rec.user_id == user_id
    assert rec.model_type == model_type
    assert rec.items == items


def test_create_recommendation_missing_fields(mock_repo):
    """Test missing required fields raise ValueError."""
    service = RecommendationService(mock_repo)
    with pytest.raises(ValueError, match="user_id is required"):
        service.create_recommendation(None, "type", [])
    with pytest.raises(ValueError, match="model_type is required"):
        service.create_recommendation(uuid4(), "", ["a"])
    with pytest.raises(ValueError, match="items cannot be empty"):
        service.create_recommendation(uuid4(), "type", [])


def test_get_by_user(mock_repo):
    """Test getting recommendations by user."""
    service = RecommendationService(mock_repo)
    user_id = uuid4()
    mock_recs = [Mock(), Mock()]
    mock_repo.get_by_user.return_value = mock_recs

    result = service.get_by_user(user_id, limit=50)

    assert result == mock_recs
    mock_repo.get_by_user.assert_called_once_with(user_id, None, 50)


def test_get_latest(mock_repo):
    """Test getting latest recommendation."""
    service = RecommendationService(mock_repo)
    user_id = uuid4()
    mock_rec = Mock()
    mock_repo.get_latest_by_user.return_value = mock_rec

    result = service.get_latest(user_id, model_type="popularity")

    assert result is mock_rec
    mock_repo.get_latest_by_user.assert_called_once_with(user_id, "popularity")


def test_upsert_recommendation(mock_repo):
    """Test upserting replaces old recommendations."""
    service = RecommendationService(mock_repo)
    user_id = uuid4()
    model_type = "hybrid"
    items = ["new1"]
    old_recs = [Mock(id=uuid4()), Mock(id=uuid4())]
    mock_repo.get_by_user.return_value = old_recs
    mock_rec = Mock()
    mock_repo.create.return_value = mock_rec

    result = service.upsert_recommendation(user_id, model_type, items)

    assert result is mock_rec
    assert mock_repo.delete.call_count == 2
    mock_repo.delete.assert_any_call(old_recs[0].id)
    mock_repo.delete.assert_any_call(old_recs[1].id)


def test_delete_recommendation(mock_repo):
    """Test deleting a recommendation."""
    service = RecommendationService(mock_repo)
    rec_id = uuid4()
    result = service.delete_recommendation(rec_id)
    assert result is True
    mock_repo.delete.assert_called_once_with(rec_id)


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
