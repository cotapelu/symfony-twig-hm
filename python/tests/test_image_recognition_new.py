"""Tests for ImageRecognition Engine"""
import pytest
from modules.ImageRecognition.service.engine import ImageRecognitionEngine


@pytest.fixture
def engine():
    """Create an ImageRecognitionEngine instance"""
    return ImageRecognitionEngine()


@pytest.fixture
def sample_image_data():
    """Sample image data for testing"""
    return b"fake_image_data"


def test_engine_import():
    """Test that ImageRecognitionEngine can be imported"""
    from modules.ImageRecognition.service.engine import ImageRecognitionEngine
    assert ImageRecognitionEngine is not None


def test_analyze_image(engine, sample_image_data):
    """Test analyzing an image"""
    result = engine.analyze_image(sample_image_data)
    assert result is not None
    assert len(result.labels) > 0
    assert len(result.objects) > 0


def test_detect_labels(engine, sample_image_data):
    """Test label detection"""
    labels = engine._detect_labels(sample_image_data)
    assert len(labels) > 0
    assert labels[0].name == "object"
    assert labels[0].confidence > 0


def test_detect_objects(engine, sample_image_data):
    """Test object detection"""
    objects = engine._detect_objects(sample_image_data)
    assert len(objects) > 0
    assert objects[0].name == "person"
    assert objects[0].confidence > 0


def test_extract_text(engine, sample_image_data):
    """Test text extraction (OCR)"""
    text = engine._extract_text(sample_image_data)
    assert isinstance(text, list)
    assert len(text) > 0


def test_extract_colors(engine, sample_image_data):
    """Test color extraction"""
    colors = engine._extract_colors(sample_image_data)
    assert len(colors) > 0
    assert colors[0].color is not None
    assert colors[0].hex is not None


def test_detect_faces(engine, sample_image_data):
    """Test face detection"""
    faces = engine._detect_faces(sample_image_data)
    assert len(faces) > 0
    assert faces[0].confidence > 0


def test_safe_search(engine, sample_image_data):
    """Test safe search detection"""
    safe = engine._check_safe_search(sample_image_data)
    assert "adult" in safe
    assert "violence" in safe
    assert safe["adult"] == "VERY_UNLIKELY"


def test_assess_quality(engine, sample_image_data):
    """Test quality assessment"""
    quality = engine._assess_quality(sample_image_data)
    assert 0.0 <= quality <= 1.0


def test_recognize_product(engine, sample_image_data):
    """Test product recognition"""
    result = engine.recognize_product(sample_image_data)
    assert result.product_name == "Premium Coffee Cup"
    assert result.brand == "Starbucks"
    assert result.confidence > 0


def test_perform_ocr(engine, sample_image_data):
    """Test OCR"""
    result = engine.perform_ocr(sample_image_data)
    assert result.full_text is not None
    assert len(result.words) > 0
    assert result.language == "en"


def test_moderate_image_safe(engine, sample_image_data):
    """Test image moderation - safe image"""
    result = engine.moderate_image(sample_image_data)
    assert result.is_safe == True
    assert len(result.warnings) == 0


def test_safe_to_score():
    """Test safe level to score conversion"""
    engine = ImageRecognitionEngine()
    assert engine._safe_to_score("VERY_UNLIKELY") == 0.0
    assert engine._safe_to_score("UNLIKELY") == 0.2
    assert engine._safe_to_score("POSSIBLE") == 0.5
    assert engine._safe_to_score("LIKELY") == 0.8
    assert engine._safe_to_score("VERY_LIKELY") == 1.0


def test_compare_images(engine, sample_image_data):
    """Test image comparison"""
    result = engine.compare_images(sample_image_data, sample_image_data)
    assert "similarity_score" in result
    assert "is_same" in result
    assert 0.0 <= result["similarity_score"] <= 1.0


def test_find_similar(engine, sample_image_data):
    """Test finding similar images"""
    database = [b"img1", b"img2", b"img3"]
    results = engine.find_similar(sample_image_data, database)
    assert len(results) > 0
    assert "image_id" in results[0]
    assert "similarity" in results[0]


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
