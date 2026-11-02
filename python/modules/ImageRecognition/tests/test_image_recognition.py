import pytest
import os
import tempfile
from datetime import datetime, timezone
from modules.ImageRecognition.service.image_recognition_service import (
    ImageRecognitionService, ImageAnalysis, RecognitionResult, 
    BoundingBox, RecognitionType, ProcessingStatus
)

try:
    from app.validation import ValidationError
except ImportError:
    class ValidationError(Exception):
        pass

@pytest.fixture
def temp_storage():
    with tempfile.NamedTemporaryFile(mode='w', suffix='.json', delete=False) as f:
        yield f.name
    os.unlink(f.name)

@pytest.fixture
def service(temp_storage):
    return ImageRecognitionService(storage_path=temp_storage)

def test_init(service):
    assert service is not None
    assert service.analyses == {}

def test_analyze_image_object_detection(service):
    analysis = service.analyze_image(
        "https://example.com/image.jpg",
        RecognitionType.OBJECT_DETECTION.value
    )
    
    assert analysis.status == ProcessingStatus.COMPLETED.value
    assert len(analysis.results) > 0
    assert analysis.processing_time_ms >= 0

def test_analyze_image_face_detection(service):
    analysis = service.analyze_image(
        "https://example.com/image.jpg",
        RecognitionType.FACE_DETECTION.value
    )
    
    assert analysis.status == ProcessingStatus.COMPLETED.value

def test_analyze_image_ocr(service):
    analysis = service.analyze_image(
        "https://example.com/image.jpg",
        RecognitionType.OCR.value
    )
    
    assert analysis.status == ProcessingStatus.COMPLETED.value
    assert len(analysis.results) > 0

def test_analyze_image_scene_classification(service):
    analysis = service.analyze_image(
        "https://example.com/image.jpg",
        RecognitionType.SCENE_CLASSIFICATION.value
    )
    
    assert analysis.status == ProcessingStatus.COMPLETED.value

def test_analyze_image_landmark(service):
    analysis = service.analyze_image(
        "https://example.com/image.jpg",
        RecognitionType.LANDMARK.value
    )
    
    assert analysis.status == ProcessingStatus.COMPLETED.value

def test_analyze_image_logo(service):
    analysis = service.analyze_image(
        "https://example.com/image.jpg",
        RecognitionType.LOGO.value
    )
    
    assert analysis.status == ProcessingStatus.COMPLETED.value

def test_analyze_image_invalid_type(service):
    with pytest.raises(ValueError):
        service.analyze_image(
            "https://example.com/image.jpg",
            "invalid_type"
        )

def test_get_analysis(service):
    analysis = service.analyze_image(
        "https://example.com/image.jpg",
        RecognitionType.OBJECT_DETECTION.value
    )
    
    retrieved = service.get_analysis(analysis.id)
    
    assert retrieved is not None
    assert retrieved.id == analysis.id

def test_get_analysis_not_found(service):
    result = service.get_analysis("nonexistent")
    assert result is None

def test_get_user_analyses(service):
    for i in range(3):
        service.analyze_image(
            f"https://example.com/image{i}.jpg",
            RecognitionType.OBJECT_DETECTION.value
        )
    
    analyses = service.get_user_analyses("user123", limit=10)
    assert len(analyses) == 3

def test_get_analyses_by_type(service):
    for i in range(2):
        service.analyze_image(
            f"https://example.com/image{i}.jpg",
            RecognitionType.OBJECT_DETECTION.value
        )
    
    object_analyses = service.get_analyses_by_type(RecognitionType.OBJECT_DETECTION.value)
    assert len(object_analyses) == 2

def test_delete_analysis(service):
    analysis = service.analyze_image(
        "https://example.com/image.jpg",
        RecognitionType.OBJECT_DETECTION.value
    )
    
    result = service.delete_analysis(analysis.id)
    assert result is True
    
    retrieved = service.get_analysis(analysis.id)
    assert retrieved is None

def test_delete_analysis_not_found(service):
    result = service.delete_analysis("nonexistent")
    assert result is False

def test_get_model_config(service):
    config = service.get_model_config(RecognitionType.OBJECT_DETECTION.value)
    assert config is not None
    assert "name" in config

def test_get_model_config_not_found(service):
    config = service.get_model_config("invalid_type")
    assert config is None

def test_update_model_config(service):
    new_config = {"name": "TestModel", "threshold": 0.6}
    result = service.update_model_config(RecognitionType.OBJECT_DETECTION.value, new_config)
    assert result is True
    
    updated = service.get_model_config(RecognitionType.OBJECT_DETECTION.value)
    assert updated["name"] == "TestModel"

def test_update_model_config_invalid_type(service):
    result = service.update_model_config("invalid_type", {})
    assert result is False

def test_get_statistics(service):
    for i in range(3):
        service.analyze_image(
            f"https://example.com/image{i}.jpg",
            RecognitionType.OBJECT_DETECTION.value
        )
    
    stats = service.get_image_recognition_statistics()
    
    assert stats["total_analyses"] == 3
    assert "by_status" in stats
    assert "average_processing_time_ms" in stats
    assert "success_rate" in stats

def test_bounding_box():
    bbox = BoundingBox(x=10, y=20, width=100, height=150, confidence=0.9)
    assert bbox.x == 10
    assert bbox.confidence == 0.9

def test_recognition_result():
    result = RecognitionResult(
        label="test",
        confidence=0.95,
        metadata={"key": "value"}
    )
    assert result.label == "test"
    assert result.confidence == 0.95
    assert result.metadata["key"] == "value"

def test_image_analysis():
    analysis = ImageAnalysis(
        id="test123",
        image_url="https://example.com/image.jpg",
        recognition_type=RecognitionType.OBJECT_DETECTION.value,
        status=ProcessingStatus.PENDING.value,
        results=[]
    )
    assert analysis.id == "test123"
    assert analysis.status == ProcessingStatus.PENDING.value

def test_recognition_type_enum():
    assert RecognitionType.OBJECT_DETECTION.value == "object_detection"
    assert RecognitionType.FACE_DETECTION.value == "face_detection"
    assert RecognitionType.OCR.value == "ocr"

def test_processing_status_enum():
    assert ProcessingStatus.PENDING.value == "pending"
    assert ProcessingStatus.COMPLETED.value == "completed"
    assert ProcessingStatus.FAILED.value == "failed"