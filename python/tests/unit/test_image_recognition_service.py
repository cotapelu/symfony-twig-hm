"""Unit tests for ImageRecognitionResultService"""
import pytest
from unittest.mock import MagicMock, AsyncMock
from uuid import UUID, uuid4
from datetime import datetime
from internal.service.image_recognition_service import ImageRecognitionResultService
from models.optimized_schema import ImageRecognitionResult

@pytest.fixture
def mock_repo():
    return MagicMock()

@pytest.fixture
def service(mock_repo):
    return ImageRecognitionResultService(repo=mock_repo)

def test_create_result_validation_empty_image_url(service):
    with pytest.raises(ValueError, match="image_url is required"):
        service.create_result(user_id=None, image_url="", model_type="test", labels=["label"])

def test_create_result_validation_empty_model_type(service):
    with pytest.raises(ValueError, match="model_type is required"):
        service.create_result(user_id=None, image_url="url", model_type="", labels=["label"])

def test_create_result_validation_empty_labels(service):
    with pytest.raises(ValueError, match="labels cannot be empty"):
        service.create_result(user_id=None, image_url="url", model_type="test", labels=[])

def test_create_result_success(service, mock_repo):
    user_id = uuid4()
    result = ImageRecognitionResult(
        id=uuid4(),
        user_id=user_id,
        image_url="http://example.com/image.jpg",
        model_type="object_detection",
        labels=["person", "car"],
        confidence=0.95,
        processing_time_ms=120,
        meta={"source": "test"},
        created_at=datetime.utcnow()
    )
    mock_repo.create.return_value = result

    created = service.create_result(
        user_id=user_id,
        image_url="http://example.com/image.jpg",
        model_type="object_detection",
        labels=["person", "car"],
        confidence=0.95,
        processing_time_ms=120,
        metadata={"source": "test"}
    )

    assert created == result
    mock_repo.create.assert_called_once()
    call_arg = mock_repo.create.call_args[0][0]
    assert call_arg.image_url == "http://example.com/image.jpg"
    assert call_arg.model_type == "object_detection"
    assert call_arg.labels == ["person", "car"]
    assert call_arg.confidence == 0.95
    assert call_arg.meta == {"source": "test"}

def test_get_result(service, mock_repo):
    result_id = uuid4()
    result = ImageRecognitionResult(id=result_id)
    mock_repo.get_by_id.return_value = result

    fetched = service.get_result(result_id)
    assert fetched == result
    mock_repo.get_by_id.assert_called_once_with(result_id)

def test_get_by_user(service, mock_repo):
    user_id = uuid4()
    mock_results = [ImageRecognitionResult(id=uuid4())]
    mock_repo.get_by_user.return_value = mock_results

    results = service.get_by_user(user_id, limit=50)
    assert results == mock_results
    mock_repo.get_by_user.assert_called_once_with(user_id, 50)

def test_get_by_model_type(service, mock_repo):
    model_type = "face_detection"
    mock_results = [ImageRecognitionResult(id=uuid4())]
    mock_repo.get_by_model_type.return_value = mock_results

    results = service.get_by_model_type(model_type, limit=30)
    assert results == mock_results
    mock_repo.get_by_model_type.assert_called_once_with(model_type, 30)

def test_get_by_image_url(service, mock_repo):
    image_url = "http://example.com/img.jpg"
    mock_results = [ImageRecognitionResult(id=uuid4())]
    mock_repo.get_by_image_url.return_value = mock_results

    results = service.get_by_image_url(image_url)
    assert results == mock_results
    mock_repo.get_by_image_url.assert_called_once_with(image_url)

def test_get_average_confidence(service, mock_repo):
    model_type = "object_detection"
    mock_repo.get_average_confidence_by_model.return_value = 0.87

    avg = service.get_average_confidence(model_type)
    assert avg == 0.87
    mock_repo.get_average_confidence_by_model.assert_called_once_with(model_type)

def test_get_average_processing_time(service, mock_repo):
    model_type = "ocr"
    mock_repo.get_average_processing_time_by_model.return_value = 150.5

    avg = service.get_average_processing_time(model_type)
    assert avg == 150.5
    mock_repo.get_average_processing_time_by_model.assert_called_once_with(model_type)

def test_count_by_user(service, mock_repo):
    user_id = uuid4()
    mock_repo.count_by_user.return_value = 42

    count = service.count_by_user(user_id)
    assert count == 42
    mock_repo.count_by_user.assert_called_once_with(user_id)