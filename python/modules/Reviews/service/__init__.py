"""Reviews Service"""
from .engine import ReviewsEngine

ReviewsService = ReviewsEngine  # Alias for compatibility

__all__ = ['ReviewsService', 'ReviewsEngine']
