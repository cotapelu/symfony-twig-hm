"""Unit tests for MLInferenceService (internal)."""
import pytest
from unittest.mock import MagicMock
from uuid import UUID, uuid4

from internal.service.ml_inference_service import MLInferenceService
from models.optimized_schema import MLInference

@pytest.fixture
def mock_repo():
    return MagicMock()

@pytest.fixture
def service(mock_repo):
    return MLInferenceService(repo=mock_repo)

def test_record_inference_validation_missing_model_id(service):
    with pytest.raises(ValueError, match="model_id is required"):
        service.record_inference(model_id=None, user_id=uuid4(), input_data={}, output_data={})

def test_record_inference_success(service, mock_repo):
    model_id = uuid4()
    user_id = uuid4()
    inference = MLInference(
        id=uuid4(),
        model_id=model_id,
        user_id=user_id,
        input={"text": "hello"},
        output={"intent": "greeting"},
        latency_ms=120
    )
    mock_repo.create.return_value = inference

    result = service.record_inference(
        model_id=model_id,
        user_id=user_id,
        input_data={"text": "hello"},
        output_data={"intent": "greeting"},
        latency_ms=120
    )

    assert result == inference
    mock_repo.create.assert_called_once()
    arg = mock_repo.create.call_args[0][0]
    assert arg.model_id == model_id
    assert arg.user_id == user_id
    assert arg.input == {"text": "hello"}
    assert arg.output == {"intent": "greeting"}
    assert arg.latency_ms == 120

def test_record_inference_without_latency(service, mock_repo):
    model_id = uuid4()
    inference = MLInference(
        id=uuid4(),
        model_id=model_id,
        user_id=None,
        input={},
        output={},
        latency_ms=None
    )
    mock_repo.create.return_value = inference

    result = service.record_inference(
        model_id=model_id,
        user_id=None,
        input_data={},
        output_data={}
    )

    assert result == inference
    arg = mock_repo.create.call_args[0][0]
    assert arg.latency_ms is None

def test_get_inference(service, mock_repo):
    inf_id = uuid4()
    inference = MLInference(
        id=inf_id,
        model_id=uuid4(),
        user_id=uuid4(),
        input={},
        output={},
        latency_ms=50
    )
    mock_repo.get_by_id.return_value = inference

    result = service.get_inference(inf_id)
    assert result == inference
    mock_repo.get_by_id.assert_called_once_with(inf_id)

def test_get_inference_not_found(service, mock_repo):
    mock_repo.get_by_id.return_value = None
    result = service.get_inference(uuid4())
    assert result is None

def test_get_by_model(service, mock_repo):
    model_id = uuid4()
    inferences = [
        MLInference(id=uuid4(), model_id=model_id, user_id=uuid4(), input={}, output={}, latency_ms=100),
        MLInference(id=uuid4(), model_id=model_id, user_id=uuid4(), input={}, output={}, latency_ms=200)
    ]
    mock_repo.get_by_model.return_value = inferences

    result = service.get_by_model(model_id, limit=50)
    assert result == inferences
    mock_repo.get_by_model.assert_called_once_with(model_id, 50)

def test_get_by_user(service, mock_repo):
    user_id = uuid4()
    inferences = [MLInference(id=uuid4(), model_id=uuid4(), user_id=user_id, input={}, output={})]
    mock_repo.get_by_user.return_value = inferences

    result = service.get_by_user(user_id, limit=30)
    assert result == inferences
    mock_repo.get_by_user.assert_called_once_with(user_id, 30)

def test_get_average_latency(service, mock_repo):
    model_id = uuid4()
    mock_repo.get_average_latency_for_model.return_value = 150.5

    avg = service.get_average_latency(model_id)
    assert avg == 150.5
    mock_repo.get_average_latency_for_model.assert_called_once_with(model_id)