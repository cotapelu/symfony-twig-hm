"""Messaging Service"""
from .engine import MessagingEngine

MessagingService = MessagingEngine  # Alias for compatibility

__all__ = ['MessagingService', 'MessagingEngine']
