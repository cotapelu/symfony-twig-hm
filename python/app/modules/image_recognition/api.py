"""Image Recognition API using FastAPI"""
from fastapi import APIRouter, File, UploadFile, HTTPException
from typing import List, Dict, Any
import base64
from .service import ImageRecognitionService

router = APIRouter(prefix="/api/image-recognition", tags=["image-recognition"])
service = ImageRecognitionService()

@router.post("/classify")
async def classify_image(file: UploadFile = File(...)):
    """Classify image content"""
    try:
        contents = await file.read()
        result = await service.classify_image(contents)
        return {
            "filename": file.filename,
            "classification": result,
            "confidence": 0.95
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/detect-objects")
async def detect_objects(file: UploadFile = File(...)):
    """Detect objects in image"""
    try:
        contents = await file.read()
        objects = await service.detect_objects(contents)
        return {
            "filename": file.filename,
            "objects": objects,
            "count": len(objects)
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/face-detection")
async def detect_faces(file: UploadFile = File(...)):
    """Detect faces in image"""
    try:
        contents = await file.read()
        faces = await service.detect_faces(contents)
        return {
            "filename": file.filename,
            "faces": faces,
            "count": len(faces)
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/ocr")
async def extract_text(file: UploadFile = File(...)):
    """Extract text from image (OCR)"""
    try:
        contents = await file.read()
        text = await service.extract_text(contents)
        return {
            "filename": file.filename,
            "text": text,
            "language": "auto"
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/similarity")
async def compare_images(image1: UploadFile = File(...), image2: UploadFile = File(...)):
    """Compare two images for similarity"""
    try:
        contents1 = await image1.read()
        contents2 = await image2.read()
        similarity = await service.compare_images(contents1, contents2)
        return {
            "similarity": similarity,
            "is_match": similarity > 0.85
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
