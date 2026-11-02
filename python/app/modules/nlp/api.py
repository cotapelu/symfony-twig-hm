"""NLP Operations API"""
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import List, Dict, Any
from .service import NLPService

router = APIRouter(prefix="/api/nlp", tags=["nlp"])
service = NLPService()

class TextInput(BaseModel):
    text: str
    language: str = "auto"

class BatchTextInput(BaseModel):
    texts: List[str]

@router.post("/sentiment")
async def analyze_sentiment(input: TextInput):
    """Analyze sentiment of text"""
    try:
        result = await service.analyze_sentiment(input.text)
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/entities")
async def extract_entities(input: TextInput):
    """Extract named entities from text"""
    try:
        entities = await service.extract_entities(input.text)
        return {"entities": entities}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/keywords")
async def extract_keywords(input: TextInput):
    """Extract keywords from text"""
    try:
        keywords = await service.extract_keywords(input.text)
        return {"keywords": keywords}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/translate")
async def translate_text(input: TextInput, target_language: str):
    """Translate text to target language"""
    try:
        translation = await service.translate(input.text, target_language)
        return {
            "original": input.text,
            "translated": translation,
            "source_language": input.language,
            "target_language": target_language
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/summarize")
async def summarize_text(input: TextInput, max_length: int = 100):
    """Summarize text"""
    try:
        summary = await service.summarize(input.text, max_length)
        return {"summary": summary}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/classify")
async def classify_text(input: TextInput):
    """Classify text category"""
    try:
        category = await service.classify(input.text)
        return {"category": category}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/language-detection")
async def detect_language(input: TextInput):
    """Detect language of text"""
    try:
        language = await service.detect_language(input.text)
        return {"language": language}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
