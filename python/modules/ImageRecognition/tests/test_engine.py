"""Unit tests for ImageRecognitionEngine"""
import pytest
from modules.ImageRecognition.service.engine import ImageRecognitionEngine
from modules.ImageRecognition.models.schemas import (
    ImageAnalysis, Label, DetectedObject, FaceAnnotation, ColorInfo,
    ProductRecognition, OCRResult, ImageModeration
)

@pytest.fixture
def engine():
    return ImageRecognitionEngine()

def test_analyze_image(engine):
    image_data = b"fake image bytes"
    result = engine.analyze_image(image_data)
    assert isinstance(result, ImageAnalysis)
    assert result.image_id == ""
    assert isinstance(result.labels, list)
    assert isinstance(result.objects, list)
    assert isinstance(result.text, list)
    assert isinstance(result.colors, list)
    assert isinstance(result.faces, list)
    assert isinstance(result.safe_search, dict)
    assert isinstance(result.quality_score, float)

def test_detect_labels(engine):
    image_data = b"fake bytes"
    labels = engine._detect_labels(image_data)
    assert len(labels) == 5
    assert all(isinstance(l, Label) for l in labels)
    names = [l.name for l in labels]
    for expected in ["object", "indoor", "food", "restaurant", "meal"]:
        assert expected in names

def test_detect_objects(engine):
    image_data = b"fake bytes"
    objects = engine._detect_objects(image_data)
    assert len(objects) == 3
    assert all(isinstance(o, DetectedObject) for o in objects)
    names = [o.name for o in objects]
    for expected in ["person", "cup", "table"]:
        assert expected in names
    # Check bounding_box present
    for o in objects:
        assert isinstance(o.bounding_box, dict)
        assert "x" in o.bounding_box

def test_extract_text(engine):
    image_data = b"fake bytes"
    text = engine._extract_text(image_data)
    assert isinstance(text, list)
    assert "MENU" in text
    assert "Special Offer" in text

def test_extract_colors(engine):
    image_data = b"fake bytes"
    colors = engine._extract_colors(image_data)
    assert len(colors) == 3
    assert all(isinstance(c, ColorInfo) for c in colors)
    color_names = [c.color for c in colors]
    for expected in ["Brown", "White", "Green"]:
        assert expected in color_names

def test_detect_faces(engine):
    image_data = b"fake bytes"
    faces = engine._detect_faces(image_data)
    assert len(faces) == 1
    face = faces[0]
    assert isinstance(face, FaceAnnotation)
    assert isinstance(face.bounding_box, dict)
    assert isinstance(face.confidence, float)
    assert isinstance(face.emotions, dict)
    assert isinstance(face.age_range, dict)
    assert isinstance(face.gender, dict)

def test_check_safe_search(engine):
    image_data = b"fake bytes"
    safe = engine._check_safe_search(image_data)
    assert isinstance(safe, dict)
    for key in ["adult", "violence", "racy", "medical", "spoof"]:
        assert key in safe

def test_assess_quality(engine):
    image_data = b"fake bytes"
    quality = engine._assess_quality(image_data)
    assert isinstance(quality, float)
    assert quality == 0.85

def test_recognize_product(engine):
    image_data = b"fake bytes"
    product = engine.recognize_product(image_data)
    assert isinstance(product, ProductRecognition)
    assert product.product_name == "Premium Coffee Cup"
    assert product.brand == "Starbucks"
    assert product.category == "Food & Beverage"
    assert product.confidence == 0.92
    assert isinstance(product.similar_products, list)
    assert len(product.similar_products) == 2

def test_perform_ocr(engine):
    image_data = b"fake bytes"
    ocr = engine.perform_ocr(image_data)
    assert isinstance(ocr, OCRResult)
    assert "Welcome" in ocr.full_text
    assert isinstance(ocr.words, list)
    assert ocr.language == "en"
    assert ocr.confidence == 0.95

def test_moderate_image(engine):
    image_data = b"fake bytes"
    moderation = engine.moderate_image(image_data)
    assert isinstance(moderation, ImageModeration)
    assert isinstance(moderation.is_safe, bool)
    assert moderation.adult_content_score == 0.0
    assert moderation.violence_score == 0.0
    assert moderation.racy_score == 0.0
    assert isinstance(moderation.warnings, list)
    # Since safe levels are VERY_UNLIKELY, is_safe should be True
    assert moderation.is_safe is True

def test_safe_to_score(engine):
    assert engine._safe_to_score("VERY_UNLIKELY") == 0.0
    assert engine._safe_to_score("UNLIKELY") == 0.2
    assert engine._safe_to_score("POSSIBLE") == 0.5
    assert engine._safe_to_score("LIKELY") == 0.8
    assert engine._safe_to_score("VERY_LIKELY") == 1.0
    assert engine._safe_to_score("unknown") == 0.0

def test_compare_images(engine):
    img1 = b"bytes1"
    img2 = b"bytes2"
    result = engine.compare_images(img1, img2)
    assert isinstance(result, dict)
    assert "similarity_score" in result
    assert "is_same" in result
    assert "differences" in result
    assert "common_elements" in result

def test_find_similar(engine):
    image_data = b"query image"
    database = [b"img1", b"img2", b"img3"]
    results = engine.find_similar(image_data, database)
    assert isinstance(results, list)
    assert len(results) == 3
    for r in results:
        assert "image_id" in r
        assert "similarity" in r
        assert "metadata" in r