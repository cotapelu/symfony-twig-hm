'''Unit tests for ImageRecognitionService'''
import pytest
from unittest.mock import patch, MagicMock
from datetime import datetime, timezone, timedelta
import os
import json

from modules.ImageRecognition.service.image_recognition_service import (
    ImageRecognitionService,
    RecognitionType,
    ProcessingStatus,
    ImageAnalysis,
    RecognitionResult,
    BoundingBox
)

@pytest.fixture
def temp_storage(tmp_path):
    return str(tmp_path / 'image_recognition.json')

@pytest.fixture
def ir_service(temp_storage):
    return ImageRecognitionService(storage_path=temp_storage)

def test_init_creates_storage_dir_if_not_exists(tmp_path):
    storage_path = str(tmp_path / 'subdir' / 'nested' / 'data.json')
    parent_dir = os.path.dirname(storage_path)
    if os.path.exists(parent_dir):
        os.rmdir(parent_dir)
    service = ImageRecognitionService(storage_path=storage_path)
    assert os.path.isdir(parent_dir)

def test_init_loads_existing_data(temp_storage):
    now = datetime.now(timezone.utc).isoformat()
    data = {
        'analyses': {
            'aid1': {
                'id': 'aid1',
                'image_url': 'http://example.com/img.jpg',
                'recognition_type': 'object_detection',
                'status': 'completed',
                'results': [],
                'created_at': now,
                'completed_at': None,
                'processing_time_ms': 0,
                'error_message': None
            }
        }
    }
    with open(temp_storage, 'w') as f:
        json.dump(data, f)
    service = ImageRecognitionService(storage_path=temp_storage)
    assert 'aid1' in service.analyses
    assert service.analyses['aid1'].image_url == 'http://example.com/img.jpg'

def test_load_from_storage_missing_file(temp_storage):
    if os.path.exists(temp_storage):
        os.remove(temp_storage)
    service = ImageRecognitionService(storage_path=temp_storage)
    assert service.analyses == {}

def test_load_from_storage_invalid_json(temp_storage):
    with open(temp_storage, 'w') as f:
        f.write('{ invalid')
    service = ImageRecognitionService(storage_path=temp_storage)
    assert service.analyses == {}

def test_save_to_storage(ir_service, tmp_path):
    analysis = ImageAnalysis(
        id='test_id',
        image_url='http://example.com/img.jpg',
        recognition_type=RecognitionType.OBJECT_DETECTION.value,
        status=ProcessingStatus.COMPLETED.value,
        results=[],
        created_at=datetime.now(timezone.utc)
    )
    ir_service.analyses['test_id'] = analysis
    ir_service.save_to_storage()
    assert os.path.exists(ir_service.storage_path)
    with open(ir_service.storage_path, 'r') as f:
        data = json.load(f)
    assert 'analyses' in data
    assert 'test_id' in data['analyses']
    saved = data['analyses']['test_id']
    assert saved['id'] == 'test_id'
    assert saved['image_url'] == 'http://example.com/img.jpg'

def test_validate_recognition_type(ir_service):
    for t in RecognitionType:
        assert ir_service._validate_recognition_type(t.value) is True
    assert ir_service._validate_recognition_type('invalid') is False

def test_mock_recognition(ir_service):
    results = ir_service._mock_recognition('url', RecognitionType.OBJECT_DETECTION.value)
    assert isinstance(results, list)
    for r in results:
        assert isinstance(r, RecognitionResult)
        assert r.bounding_box is not None
        assert isinstance(r.bounding_box, BoundingBox)
    results = ir_service._mock_recognition('url', RecognitionType.FACE_DETECTION.value)
    assert isinstance(results, list)
    for r in results:
        assert r.bounding_box is not None
        assert isinstance(r.metadata, dict) and 'age_estimate' in r.metadata
    results = ir_service._mock_recognition('url', RecognitionType.OCR.value)
    assert len(results) == 1
    assert 'text' in results[0].metadata
    results = ir_service._mock_recognition('url', RecognitionType.SCENE_CLASSIFICATION.value)
    assert len(results) == 1
    assert results[0].label in ['indoor', 'outdoor', 'city', 'nature', 'office', 'restaurant']
    results = ir_service._mock_recognition('url', RecognitionType.LANDMARK.value)
    assert len(results) == 1
    assert results[0].label in [' Eiffel Tower', ' Taj Mahal', ' Statue of Liberty', ' Great Wall']
    results = ir_service._mock_recognition('url', RecognitionType.LOGO.value)
    assert len(results) == 1
    assert results[0].bounding_box is not None
    results = ir_service._mock_recognition('url', 'unknown_type')
    assert len(results) == 1
    assert results[0].label == 'unknown'

def test_analyze_image_success(ir_service, monkeypatch):
    mock_result = RecognitionResult(
        label='test_label',
        confidence=0.99,
        bounding_box=BoundingBox(x=10, y=20, width=30, height=40, confidence=0.99),
        metadata={'key': 'value'}
    )
    monkeypatch.setattr(ir_service, '_mock_recognition', lambda url, rec_type: [mock_result])
    analysis = ir_service.analyze_image(
        image_url='http://example.com/img.jpg',
        recognition_type=RecognitionType.OBJECT_DETECTION.value
    )
    assert isinstance(analysis, ImageAnalysis)
    assert analysis.image_url == 'http://example.com/img.jpg'
    assert analysis.recognition_type == RecognitionType.OBJECT_DETECTION.value
    assert analysis.status == ProcessingStatus.COMPLETED.value
    assert analysis.results == [mock_result]
    assert analysis.processing_time_ms >= 0
    assert analysis.completed_at is not None
    assert analysis.id in ir_service.analyses
    assert os.path.exists(ir_service.storage_path)

def test_analyze_image_validation_error_invalid_url(ir_service):
    long_url = 'a' * 3000
    with pytest.raises(ValueError):
        ir_service.analyze_image(image_url=long_url, recognition_type=RecognitionType.OBJECT_DETECTION.value)

def test_analyze_image_validation_error_invalid_type(ir_service):
    with pytest.raises(ValueError):
        ir_service.analyze_image(image_url='http://example.com/img.jpg', recognition_type='invalid_type')

def test_analyze_image_exception_during_recognition(ir_service, monkeypatch):
    monkeypatch.setattr(ir_service, '_mock_recognition', lambda url, rec_type: (_ for _ in ()).throw(RuntimeError('mock error')))
    analysis = ir_service.analyze_image(
        image_url='http://example.com/img.jpg',
        recognition_type=RecognitionType.OBJECT_DETECTION.value
    )
    assert analysis.status == ProcessingStatus.FAILED.value
    assert 'mock error' in analysis.error_message

def test_get_analysis_existing(ir_service):
    analysis = ImageAnalysis(
        id='existing_id',
        image_url='http://example.com/img.jpg',
        recognition_type=RecognitionType.OBJECT_DETECTION.value,
        status=ProcessingStatus.COMPLETED.value,
        results=[]
    )
    ir_service.analyses['existing_id'] = analysis
    fetched = ir_service.get_analysis('existing_id')
    assert fetched is analysis

def test_get_analysis_nonexistent(ir_service):
    fetched = ir_service.get_analysis('nonexistent')
    assert fetched is None

def test_get_user_analyses(ir_service):
    base_time = datetime.now(timezone.utc)
    a_old = ImageAnalysis(id='old', image_url='url', recognition_type=RecognitionType.OBJECT_DETECTION.value, status=ProcessingStatus.COMPLETED.value, results=[], created_at=base_time - timedelta(minutes=2))
    a_mid = ImageAnalysis(id='mid', image_url='url', recognition_type=RecognitionType.OBJECT_DETECTION.value, status=ProcessingStatus.COMPLETED.value, results=[], created_at=base_time - timedelta(minutes=1))
    a_new = ImageAnalysis(id='new', image_url='url', recognition_type=RecognitionType.OBJECT_DETECTION.value, status=ProcessingStatus.COMPLETED.value, results=[], created_at=base_time)
    ir_service.analyses = {'old': a_old, 'mid': a_mid, 'new': a_new}
    results = ir_service.get_user_analyses(user_id='any', limit=2)
    assert len(results) == 2
    assert results[0].id == 'new'
    assert results[1].id == 'mid'

def test_get_analyses_by_type(ir_service):
    base_time = datetime.now(timezone.utc)
    a_obj = ImageAnalysis(id='obj1', image_url='url', recognition_type=RecognitionType.OBJECT_DETECTION.value, status=ProcessingStatus.COMPLETED.value, results=[], created_at=base_time)
    a_face = ImageAnalysis(id='face1', image_url='url', recognition_type=RecognitionType.FACE_DETECTION.value, status=ProcessingStatus.COMPLETED.value, results=[], created_at=base_time)
    a_ocr = ImageAnalysis(id='ocr1', image_url='url', recognition_type=RecognitionType.OCR.value, status=ProcessingStatus.COMPLETED.value, results=[], created_at=base_time)
    ir_service.analyses = {'obj1': a_obj, 'face1': a_face, 'ocr1': a_ocr}
    results = ir_service.get_analyses_by_type(RecognitionType.OBJECT_DETECTION.value)
    assert len(results) == 1
    assert results[0].id == 'obj1'
    results = ir_service.get_analyses_by_type('invalid')
    assert results == []
    a_obj2 = ImageAnalysis(id='obj2', image_url='url', recognition_type=RecognitionType.OBJECT_DETECTION.value, status=ProcessingStatus.COMPLETED.value, results=[], created_at=base_time - timedelta(minutes=1))
    ir_service.analyses['obj2'] = a_obj2
    results = ir_service.get_analyses_by_type(RecognitionType.OBJECT_DETECTION.value, limit=1)
    assert len(results) == 1
    assert results[0].id == 'obj1'

def test_delete_analysis(ir_service):
    analysis = ImageAnalysis(
        id='to_delete',
        image_url='url',
        recognition_type=RecognitionType.OBJECT_DETECTION.value,
        status=ProcessingStatus.COMPLETED.value,
        results=[]
    )
    ir_service.analyses['to_delete'] = analysis
    result = ir_service.delete_analysis('to_delete')
    assert result is True
    assert 'to_delete' not in ir_service.analyses
    result = ir_service.delete_analysis('nonexistent')
    assert result is False

def test_get_model_config():
    service = ImageRecognitionService()
    # Only the configured types have a model config
    configured_types = [
        RecognitionType.OBJECT_DETECTION,
        RecognitionType.FACE_DETECTION,
        RecognitionType.OCR,
        RecognitionType.SCENE_CLASSIFICATION
    ]
    for t in configured_types:
        config = service.get_model_config(t.value)
        assert isinstance(config, dict)
        assert 'name' in config
    # Unknown or unconfigured type returns None
    assert service.get_model_config('unknown') is None
    assert service.get_model_config(RecognitionType.LANDMARK.value) is None

def test_update_model_config():
    service = ImageRecognitionService()
    new_config = {'name': 'TestModel', 'threshold': 0.7}
    result = service.update_model_config(RecognitionType.OBJECT_DETECTION.value, new_config)
    assert result is True
    assert service.model_configs[RecognitionType.OBJECT_DETECTION.value] == new_config
    result = service.update_model_config('invalid', new_config)
    assert result is False

def test_get_statistics_empty(ir_service):
    stats = ir_service.get_image_recognition_statistics()
    assert stats['total_analyses'] == 0
    assert stats['by_status'] == {}
    assert stats['by_type'] == {}
    assert stats['average_processing_time_ms'] == 0
    assert stats['success_rate'] == 0

def test_get_statistics_with_data(ir_service):
    base_time = datetime.now(timezone.utc)
    a1 = ImageAnalysis(
        id='c1',
        image_url='url',
        recognition_type=RecognitionType.OBJECT_DETECTION.value,
        status=ProcessingStatus.COMPLETED.value,
        results=[],
        created_at=base_time - timedelta(minutes=2),
        completed_at=base_time - timedelta(minutes=2),
        processing_time_ms=100
    )
    a2 = ImageAnalysis(
        id='c2',
        image_url='url',
        recognition_type=RecognitionType.FACE_DETECTION.value,
        status=ProcessingStatus.COMPLETED.value,
        results=[],
        created_at=base_time - timedelta(minutes=1),
        completed_at=base_time - timedelta(minutes=1),
        processing_time_ms=200
    )
    a3 = ImageAnalysis(
        id='f1',
        image_url='url',
        recognition_type=RecognitionType.OCR.value,
        status=ProcessingStatus.FAILED.value,
        results=[],
        created_at=base_time,
        completed_at=base_time,
        processing_time_ms=0,
        error_message='mock error'
    )
    ir_service.analyses = {'c1': a1, 'c2': a2, 'f1': a3}
    stats = ir_service.get_image_recognition_statistics()
    assert stats['total_analyses'] == 3
    assert stats['by_status'][ProcessingStatus.COMPLETED.value] == 2
    assert stats['by_status'][ProcessingStatus.FAILED.value] == 1
    assert stats['by_type'][RecognitionType.OBJECT_DETECTION.value] == 1
    assert stats['by_type'][RecognitionType.FACE_DETECTION.value] == 1
    assert stats['by_type'][RecognitionType.OCR.value] == 1
    assert stats['average_processing_time_ms'] == 150.0
    assert stats['success_rate'] == round(2/3 * 100, 2)