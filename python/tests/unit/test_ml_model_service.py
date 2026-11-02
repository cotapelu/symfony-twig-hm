"""Unit tests for MLModelService."""
import pytest
from unittest.mock import Mock
from uuid import UUID, uuid4

from internal.service.ml_model_service import MLModelService


@pytest.fixture
def mock_repo():
    """Mock MLModelRepository."""
    repo = Mock()
    repo.create = Mock()
    repo.get_by_id = Mock()
    repo.get_by_type = Mock()
    repo.get_active_models = Mock()
    repo.update_status = Mock()
    repo.update_accuracy = Mock()
    repo.delete = Mock()
    return repo


def test_create_model_success(mock_repo):
    """Test creating a model."""
    service = MLModelService(mock_repo)
    name = "Test Model"
    model_type = "classification"
    mock_model = Mock()
    mock_model.id = uuid4()
    mock_repo.create.return_value = mock_model

    result = service.create_model(name, model_type=model_type)

    assert result is mock_model
    mock_repo.create.assert_called_once()
    model = mock_repo.create.call_args[0][0]
    assert model.name == name
    assert model.model_type == model_type


def test_create_model_missing_name(mock_repo):
    """Test that missing name raises ValueError."""
    service = MLModelService(mock_repo)
    with pytest.raises(ValueError, match="name is required"):
        service.create_model("")


def test_get_model(mock_repo):
    """Test getting a model."""
    service = MLModelService(mock_repo)
    model_id = uuid4()
    mock_model = Mock()
    mock_repo.get_by_id.return_value = mock_model

    result = service.get_model(model_id)

    assert result is mock_model
    mock_repo.get_by_id.assert_called_once_with(model_id)


def test_update_status(mock_repo):
    """Test updating model status."""
    service = MLModelService(mock_repo)
    model_id = uuid4()
    status = "deployed"
    mock_model = Mock()
    mock_repo.update_status.return_value = mock_model

    result = service.update_status(model_id, status)

    assert result is mock_model
    mock_repo.update_status.assert_called_once_with(model_id, status)


def test_get_active_models(mock_repo):
    """Test getting active models."""
    service = MLModelService(mock_repo)
    mock_models = [Mock(), Mock()]
    mock_repo.get_active_models.return_value = mock_models

    results = service.get_active_models(limit=50)

    assert results == mock_models
    mock_repo.get_active_models.assert_called_once_with(None, 50)


def test_delete_model(mock_repo):
    """Test deleting a model."""
    service = MLModelService(mock_repo)
    model_id = uuid4()

    result = service.delete_model(model_id)

    assert result is True
    mock_repo.delete.assert_called_once_with(model_id)


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
