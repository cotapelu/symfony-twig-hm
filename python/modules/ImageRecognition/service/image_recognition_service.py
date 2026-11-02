"""
Image Recognition Service - Computer vision and image analysis service
"""
import json
import os
from datetime import datetime, timezone
from typing import List, Dict, Optional, Any
from dataclasses import dataclass, asdict
from enum import Enum
import uuid

from app.validation import Validator, ValidationError


class RecognitionType(str, Enum):
    OBJECT_DETECTION = "object_detection"
    FACE_DETECTION = "face_detection"
    OCR = "ocr"
    LANDMARK = "landmark"
    LOGO = "logo"
    TEXT_DETECTION = "text_detection"
    SCENE_CLASSIFICATION = "scene_classification"


class ProcessingStatus(str, Enum):
    PENDING = "pending"
    PROCESSING = "processing"
    COMPLETED = "completed"
    FAILED = "failed"


@dataclass
class BoundingBox:
    x: float
    y: float
    width: float
    height: float
    confidence: float


@dataclass
class RecognitionResult:
    label: str
    confidence: float
    bounding_box: Optional[BoundingBox] = None
    metadata: Dict[str, Any] = None

    def __post_init__(self):
        if self.metadata is None:
            self.metadata = {}


@dataclass
class ImageAnalysis:
    id: str
    image_url: str
    recognition_type: str
    status: str
    results: List[RecognitionResult]
    processing_time_ms: int = 0
    error_message: Optional[str] = None
    created_at: Optional[datetime] = None
    completed_at: Optional[datetime] = None

    def __post_init__(self):
        if self.created_at is None:
            self.created_at = datetime.now(timezone.utc)
        if isinstance(self.created_at, str):
            self.created_at = datetime.fromisoformat(self.created_at)
        if isinstance(self.completed_at, str) and self.completed_at:
            self.completed_at = datetime.fromisoformat(self.completed_at)


@dataclass
class RecognitionRequest:
    image_url: str
    recognition_type: str
    options: Dict[str, Any] = None

    def __post_init__(self):
        if self.options is None:
            self.options = {}


class ImageRecognitionService:
    """
    Service for image recognition and computer vision tasks
    """

    def __init__(self, storage_path: str = "/dev/shm/data/image_recognition.json"):
        self.storage_path = storage_path
        self.analyses: Dict[str, ImageAnalysis] = {}
        self.model_configs: Dict[str, Dict] = {
            RecognitionType.OBJECT_DETECTION.value: {
                "name": "YOLO",
                "classes": 80,
                "threshold": 0.5
            },
            RecognitionType.FACE_DETECTION.value: {
                "name": "RetinaFace",
                "max_faces": 10,
                "threshold": 0.5
            },
            RecognitionType.OCR.value: {
                "name": "Tesseract",
                "languages": ["eng", "vie"]
            },
            RecognitionType.SCENE_CLASSIFICATION.value: {
                "name": "ResNet",
                "classes": 365
            }
        }

        self.ensure_storage_directory()
        self.load_from_storage()

    def ensure_storage_directory(self):
        dir_path = os.path.dirname(self.storage_path)
        if dir_path and dir_path != "." and not os.path.exists(dir_path):
            os.makedirs(dir_path, exist_ok=True)

    def load_from_storage(self):
        return self._load_from_storage_impl()

    def _load_from_storage_impl(self):
        if not os.path.exists(self.storage_path):
            return
        try:
            with open(self.storage_path, 'r') as f:
                data = json.load(f)
                if 'analyses' in data:
                    for analysis_id, analysis_data in data['analyses'].items():
                        analysis_data['created_at'] = datetime.fromisoformat(analysis_data['created_at']) if analysis_data.get('created_at') else datetime.now(timezone.utc)
                        if analysis_data.get('completed_at'):
                            analysis_data['completed_at'] = datetime.fromisoformat(analysis_data['completed_at'])
                        analysis_data['results'] = [
                            RecognitionResult(
                                label=r['label'],
                                confidence=r['confidence'],
                                bounding_box=BoundingBox(**r['bounding_box']) if r.get('bounding_box') else None,
                                metadata=r.get('metadata', {})
                            ) for r in analysis_data.get('results', [])
                        ]
                        self.analyses[analysis_id] = ImageAnalysis(**analysis_data)
        except Exception as e:
            print(f"Failed to load image recognition data: {e}")

    def save_to_storage(self):
        return self._save_to_storage_impl()

    def _save_to_storage_impl(self):
        try:
            data = {
                'analyses': {
                    k: {
                        'id': v.id,
                        'image_url': v.image_url,
                        'recognition_type': v.recognition_type,
                        'status': v.status,
                        'results': [
                            {
                                'label': r.label,
                                'confidence': r.confidence,
                                'bounding_box': {
                                    'x': r.bounding_box.x,
                                    'y': r.bounding_box.y,
                                    'width': r.bounding_box.width,
                                    'height': r.bounding_box.height,
                                    'confidence': r.bounding_box.confidence
                                } if r.bounding_box else None,
                                'metadata': r.metadata
                            } for r in v.results
                        ],
                        'processing_time_ms': v.processing_time_ms,
                        'error_message': v.error_message,
                        'created_at': v.created_at.isoformat() if v.created_at else None,
                        'completed_at': v.completed_at.isoformat() if v.completed_at else None
                    } for k, v in self.analyses.items()
                }
            }
            with open(self.storage_path, 'w') as f:
                json.dump(data, f, indent=2)
        except Exception as e:
            print(f"Failed to save image recognition data: {e}")

    def _validate_recognition_type(self, recognition_type: str) -> bool:
        """Validate recognition type"""
        valid_types = [t.value for t in RecognitionType]
        return recognition_type in valid_types

    def _mock_recognition(
        self,
        image_url: str,
        recognition_type: str
    ) -> List[RecognitionResult]:
        """
        Mock recognition results - in production, this would call actual ML models
        """
        import random
        import time

        # Simulate processing time
        time.sleep(0.1)

        results = []

        if recognition_type == RecognitionType.OBJECT_DETECTION.value:
            mock_objects = [
                ("person", 0.95), ("car", 0.88), ("bicycle", 0.82),
                ("dog", 0.90), ("cat", 0.87), ("chair", 0.75),
                ("table", 0.78), ("laptop", 0.85), ("phone", 0.92)
            ]
            selected = random.sample(mock_objects, random.randint(2, 5))
            for label, conf in selected:
                results.append(RecognitionResult(
                    label=label,
                    confidence=conf,
                    bounding_box=BoundingBox(
                        x=random.uniform(0, 300),
                        y=random.uniform(0, 300),
                        width=random.uniform(50, 150),
                        height=random.uniform(50, 150),
                        confidence=conf
                    )
                ))

        elif recognition_type == RecognitionType.FACE_DETECTION.value:
            num_faces = random.randint(0, 3)
            for i in range(num_faces):
                results.append(RecognitionResult(
                    label=f"face_{i+1}",
                    confidence=random.uniform(0.85, 0.99),
                    bounding_box=BoundingBox(
                        x=random.uniform(50, 250),
                        y=random.uniform(50, 250),
                        width=random.uniform(80, 120),
                        height=random.uniform(100, 140),
                        confidence=random.uniform(0.85, 0.99)
                    ),
                    metadata={"age_estimate": random.randint(20, 60)}
                ))

        elif recognition_type == RecognitionType.OCR.value:
            mock_texts = [
                "Welcome to Vietnam",
                "Order #12345",
                "Product Name",
                "Price: 100,000 VND"
            ]
            results.append(RecognitionResult(
                label="text",
                confidence=0.92,
                metadata={"text": random.choice(mock_texts)}
            ))

        elif recognition_type == RecognitionType.SCENE_CLASSIFICATION.value:
            scenes = ["indoor", "outdoor", "city", "nature", "office", "restaurant"]
            results.append(RecognitionResult(
                label=random.choice(scenes),
                confidence=random.uniform(0.70, 0.95)
            ))

        elif recognition_type == RecognitionType.LANDMARK.value:
            landmarks = [" Eiffel Tower", " Taj Mahal", " Statue of Liberty", " Great Wall"]
            results.append(RecognitionResult(
                label=random.choice(landmarks),
                confidence=random.uniform(0.75, 0.95)
            ))

        elif recognition_type == RecognitionType.LOGO.value:
            logos = ["Nike", "Apple", "Samsung", "Adidas", "Google"]
            results.append(RecognitionResult(
                label=random.choice(logos),
                confidence=random.uniform(0.80, 0.95),
                bounding_box=BoundingBox(
                    x=random.uniform(100, 200),
                    y=random.uniform(100, 200),
                    width=100,
                    height=50,
                    confidence=random.uniform(0.80, 0.95)
                )
            ))

        else:
            results.append(RecognitionResult(
                label="unknown",
                confidence=0.5
            ))

        return results

    def analyze_image(
        self,
        image_url: str,
        recognition_type: str,
        options: Optional[Dict[str, Any]] = None
    ) -> ImageAnalysis:
        return self._analyze_image_impl(image_url, recognition_type, options)

    def _analyze_image_impl(
        self,
        image_url: str,
        recognition_type: str,
        options: Optional[Dict[str, Any]] = None
    ) -> ImageAnalysis:
        """Analyze an image"""
        # Validate inputs using validation module (TODO.md requirements)
        try:
            image_url = Validator.validate_string_length(image_url, 2048, "Image URL")
            recognition_type = Validator.validate_type(
                recognition_type,
                [t.value for t in RecognitionType]
            )
        except ValidationError as e:
            raise ValueError(str(e))

        # Validate recognition type
        if not self._validate_recognition_type(recognition_type):
            raise ValueError(f"Invalid recognition type: {recognition_type}")

        if options is None:
            options = {}

        # Create analysis record
        analysis = ImageAnalysis(
            id=str(uuid.uuid4()),
            image_url=image_url,
            recognition_type=recognition_type,
            status=ProcessingStatus.PROCESSING.value,
            results=[],
            created_at=datetime.now(timezone.utc)
        )

        self.analyses[analysis.id] = analysis

        try:
            # Run recognition (mock in production, would be real ML model)
            start_time = datetime.now()
            results = self._mock_recognition(image_url, recognition_type)
            end_time = datetime.now()

            # Calculate processing time
            processing_time = int((end_time - start_time).total_seconds() * 1000)

            # Update analysis
            analysis.results = results
            analysis.status = ProcessingStatus.COMPLETED.value
            analysis.processing_time_ms = processing_time
            analysis.completed_at = datetime.now(timezone.utc)

        except Exception as e:
            analysis.status = ProcessingStatus.FAILED.value
            analysis.error_message = str(e)
            analysis.completed_at = datetime.now(timezone.utc)

        self.save_to_storage()
        return analysis

    def get_analysis(self, analysis_id: str) -> Optional[ImageAnalysis]:
        """Get analysis by ID"""
        return self.analyses.get(analysis_id)

    def get_user_analyses(
        self,
        user_id: str,
        limit: int = 20
    ) -> List[ImageAnalysis]:
        """Get all analyses for a user"""
        analyses = list(self.analyses.values())
        analyses.sort(key=lambda a: a.created_at, reverse=True)
        return analyses[:limit]

    def get_analyses_by_type(
        self,
        recognition_type: str,
        limit: int = 20
    ) -> List[ImageAnalysis]:
        """Get analyses by recognition type"""
        if not self._validate_recognition_type(recognition_type):
            return []

        filtered = [
            a for a in self.analyses.values()
            if a.recognition_type == recognition_type
        ]
        filtered.sort(key=lambda a: a.created_at, reverse=True)
        return filtered[:limit]

    def delete_analysis(self, analysis_id: str) -> bool:
        """Delete an analysis"""
        if analysis_id in self.analyses:
            del self.analyses[analysis_id]
            self.save_to_storage()
            return True
        return False

    def get_model_config(self, recognition_type: str) -> Optional[Dict]:
        """Get model configuration for a recognition type"""
        return self.model_configs.get(recognition_type)

    def update_model_config(
        self,
        recognition_type: str,
        config: Dict[str, Any]
    ) -> bool:
        """Update model configuration"""
        if not self._validate_recognition_type(recognition_type):
            return False

        self.model_configs[recognition_type] = config
        self.save_to_storage()
        return True

    def get_image_recognition_statistics(self) -> Dict[str, Any]:
        return self._get_image_recognition_statistics_impl()

    def _get_image_recognition_statistics_impl(self) -> Dict[str, Any]:
        """Get image recognition statistics"""
        total_analyses = len(self.analyses)

        by_status = {}
        by_type = {}

        for analysis in self.analyses.values():
            by_status[analysis.status] = by_status.get(analysis.status, 0) + 1
            by_type[analysis.recognition_type] = by_type.get(analysis.recognition_type, 0) + 1

        completed_analyses = [
            a for a in self.analyses.values()
            if a.status == ProcessingStatus.COMPLETED.value
        ]

        avg_processing_time = 0
        if completed_analyses:
            avg_processing_time = sum(
                a.processing_time_ms for a in completed_analyses
            ) / len(completed_analyses)

        return {
            "total_analyses": total_analyses,
            "by_status": by_status,
            "by_type": by_type,
            "average_processing_time_ms": round(avg_processing_time, 2),
            "success_rate": round(
                by_status.get(ProcessingStatus.COMPLETED.value, 0) / max(total_analyses, 1) * 100,
                2
            )
        }