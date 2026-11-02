from pydantic import BaseModel
from typing import List, Dict, Optional
from datetime import datetime, timezone

class ImageUpload(BaseModel):
    id: str
    user_id: str
    filename: str
    content_type: str
    size: int
    url: str
    metadata: Optional[Dict] = None
    created_at: datetime = datetime.now(timezone.utc)

class ImageAnalysis(BaseModel):
    image_id: str
    labels: List['Label']
    objects: List['DetectedObject']
    text: List[str] = []
    faces: List['FaceAnnotation'] = []
    colors: List['ColorInfo'] = []
    safe_search: Optional[Dict] = None
    quality_score: float = 0.0
class Label(BaseModel):
    name: str
    confidence: float

class DetectedObject(BaseModel):
    name: str
    confidence: float
    bounding_box: Dict
    attributes: Dict = {}

class FaceAnnotation(BaseModel):
    bounding_box: Dict
    confidence: float
    emotions: Dict[str, float] = {}
    age_range: Optional[Dict] = None
    gender: Optional[Dict] = None

class ColorInfo(BaseModel):
    color: str
    hex: str
    percentage: float
    pixel_fraction: float

class ProductRecognition(BaseModel):
    product_name: str
    brand: Optional[str] = None
    category: str
    confidence: float
    similar_products: List[Dict] = []

class OCRResult(BaseModel):
    full_text: str
    words: List[Dict]
    language: str
    confidence: float

class ImageModeration(BaseModel):
    is_safe: bool
    categories: Dict[str, str]
    adult_content_score: float
    violence_score: float
    racy_score: float
    warnings: List[str] = []
