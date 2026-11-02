import pytest
from modules.Favorites.favorites_manager import FavoritesManager

class TestFavoritesManager:
    def setup_method(self):
        self.manager = FavoritesManager()
    
    def test_add_favorite(self):
        user_id = 123
        item_id = 456
        item_type = "product"
        
        result = self.manager.add_favorite(user_id, item_id, item_type)
        
        assert result is not None
    
    def test_remove_favorite(self):
        user_id = 123
        item_id = 456
        
        result = self.manager.remove_favorite(user_id, item_id)
        
        assert result is not None
    
    def test_get_favorites(self):
        user_id = 123
        
        favorites = self.manager.get_favorites(user_id)
        
        assert isinstance(favorites, list)
    
    def test_is_favorite(self):
        user_id = 123
        item_id = 456
        
        result = self.manager.is_favorite(user_id, item_id)
        
        assert isinstance(result, bool)
    
    def test_get_favorites_by_type(self):
        user_id = 123
        item_type = "product"
        
        favorites = self.manager.get_favorites_by_type(user_id, item_type)
        
        assert isinstance(favorites, list)
    
    def test_clear_favorites(self):
        user_id = 123
        
        result = self.manager.clear_favorites(user_id)
        
        assert result is not None
