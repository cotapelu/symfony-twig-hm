"""Telemedicine Manager - re-exports from service"""
from modules.Telemedicine.service.service import TelemedicineService

TelemedicineManager = TelemedicineService  # Alias for compatibility

__all__ = ['TelemedicineManager', 'TelemedicineService']
