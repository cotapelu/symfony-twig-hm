"""Favorites Manager - re-exports from service engine"""
from modules.Favorites.service.engine import FavoritesEngine

FavoritesManager = FavoritesEngine  # Alias for backward compatibility

__all__ = ['FavoritesEngine', 'FavoritesManager']
