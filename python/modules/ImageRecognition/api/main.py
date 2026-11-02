from fastapi import FastAPI, UploadFile, File
from typing import Dict
from service.engine import ImageRecognitionEngine
from observability.setup import setup_observability, health_checker

app = FastAPI(title="Image Recognition API", version="1.0.0")
engine = ImageRecognitionEngine()

# Setup observability
setup_observability(app)

# Health check for ML model
health_checker.add_check("image_recognition_engine", lambda: {"status": "healthy", "model_loaded": True})

@app.post("/api/image/analyze")
async def analyze_image(file: UploadFile = File(...)):
    content = await file.read()
    result = engine.analyze_image(content)
    return {
        "labels": [{"name": l.name, "confidence": l.confidence} for l in result.labels],
        "objects": [{"name": o.name, "confidence": o.confidence, "box": o.bounding_box} for o in result.objects],
        "text": result.text,
        "colors": [{"color": c.color, "hex": c.hex, "percentage": c.percentage} for c in result.colors],
        "faces": len(result.faces),
        "quality_score": result.quality_score
    }

@app.post("/api/image/ocr")
async def ocr_image(file: UploadFile = File(...)):
    content = await file.read()
    result = engine.perform_ocr(content)
    return {
        "full_text": result.full_text,
        "words": result.words,
        "language": result.language,
        "confidence": result.confidence
    }

@app.post("/api/image/product")
async def recognize_product(file: UploadFile = File(...)):
    content = await file.read()
    result = engine.recognize_product(content)
    return {
        "product_name": result.product_name,
        "brand": result.brand,
        "category": result.category,
        "confidence": result.confidence,
        "similar_products": result.similar_products
    }

@app.post("/api/image/moderate")
async def moderate_image(file: UploadFile = File(...)):
    content = await file.read()
    result = engine.moderate_image(content)
    return {
        "is_safe": result.is_safe,
        "adult_score": result.adult_content_score,
        "violence_score": result.violence_score,
        "racy_score": result.racy_score,
        "warnings": result.warnings
    }

@app.post("/api/image/compare")
async def compare_images(file1: UploadFile = File(...), file2: UploadFile = File(...)):
    content1 = await file1.read()
    content2 = await file2.read()
    result = engine.compare_images(content1, content2)
    return result
