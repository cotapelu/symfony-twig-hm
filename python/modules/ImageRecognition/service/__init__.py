"""Image Recognition Service"""
from .engine import ImageRecognitionEngine

ImageRecognitionService = ImageRecognitionEngine  # Alias for compatibility

__all__ = ['ImageRecognitionService', 'ImageRecognitionEngine']
