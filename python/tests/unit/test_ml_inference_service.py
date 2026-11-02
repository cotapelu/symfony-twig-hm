"""Unit tests for MLInferenceService."""
import pytest
from unittest.mock import Mock
from uuid import UUID, uuid4

from internal.service.ml_inference_service import MLInferenceService


@pytest.fixture
def mock_repo():
    """Mock MLInferenceRepository."""
    repo = Mock()
    repo.create = Mock()
    repo.get_by_id = Mock()
    repo.get_by_model = Mock()
    repo.get_by_user = Mock()
    repo.get_average_latency_for_model = Mock()
    return repo


def test_record_inference_success(mock_repo):
    """Test recording an inference."""
    service = MLInferenceService(mock_repo)
    model_id = uuid4()
    user_id = uuid4()
    input_data = {"text": "hello"}
    output_data = {"sentiment": "positive"}
    mock_inf = Mock()
    mock_inf.id = uuid4()
    mock_repo.create.return_value = mock_inf

    result = service.record_inference(model_id, user_id, input_data, output_data, latency_ms=50)

    assert result is mock_inf
    mock_repo.create.assert_called_once()
    inf = mock_repo.create.call_args[0][0]
    assert inf.model_id == model_id
    assert inf.user_id == user_id
    assert inf.latency_ms == 50


def test_record_inference_missing_model_id(mock_repo):
    """Test missing model_id raises ValueError."""
    service = MLInferenceService(mock_repo)
    with pytest.raises(ValueError, match="model_id is required"):
        service.record_inference(None, None, {}, {})


def test_get_by_model(mock_repo):
    """Test getting inferences by model."""
    service = MLInferenceService(mock_repo)
    model_id = uuid4()
    mock_list = [Mock(), Mock()]
    mock_repo.get_by_model.return_value = mock_list

    result = service.get_by_model(model_id, limit=50)

    assert result == mock_list
    mock_repo.get_by_model.assert_called_once_with(model_id, 50)


def test_get_average_latency(mock_repo):
    """Test getting average latency."""
    service = MLInferenceService(mock_repo)
    model_id = uuid4()
    mock_repo.get_average_latency_for_model.return_value = 75.5

    avg = service.get_average_latency(model_id)

    assert avg == 75.5
    mock_repo.get_average_latency_for_model.assert_called_once_with(model_id)


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
