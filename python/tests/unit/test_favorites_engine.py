"""Unit tests for FavoritesEngine."""
import pytest
from datetime import datetime
from modules.Favorites.service.engine import FavoritesEngine
from modules.Favorites.models.schemas import Favorite, FavoriteCollection, FavoriteFolder

class TestFavoritesEngine:
    """Test suite for FavoritesEngine."""

    def test_add_favorite_object(self):
        """Test adding a Favorite object."""
        engine = FavoritesEngine()
        fav = Favorite(user_id="u1", item_type="product", item_id="p123")
        result = engine.add_favorite(fav)
        assert result.id is not None
        assert result.created_at is not None
        key = f"u1:product:p123"
        assert key in engine.favorites
        assert engine.favorites[key] == result

    def test_add_favorite_wrapper(self):
        """Test wrapper add_favorite with primitive args."""
        engine = FavoritesEngine()
        result = engine.add_favorite(user_id=1, item_id=100, item_type="article")
        assert result.id is not None
        assert result.user_id == "1"
        assert result.item_id == "100"
        assert result.item_type == "article"

    def test_get_user_favorites_empty(self):
        engine = FavoritesEngine()
        favs = engine.get_user_favorites("u1")
        assert favs == []

    def test_get_user_favorites_multiple(self):
        engine = FavoritesEngine()
        now = datetime.now()
        fav1 = Favorite(user_id="u1", item_type="product", item_id="p1", created_at=now)
        fav2 = Favorite(user_id="u1", item_type="article", item_id="a1", created_at=now)
        fav3 = Favorite(user_id="u2", item_type="product", item_id="p2", created_at=now)
        engine.favorites = {
            "u1:product:p1": fav1,
            "u1:article:a1": fav2,
            "u2:product:p2": fav3
        }
        result = engine.get_user_favorites("u1")
        assert len(result) == 2
        # Should contain favorites for u1 (order not strictly needed)

    def test_get_user_favorites_filter_by_type(self):
        engine = FavoritesEngine()
        fav1 = Favorite(user_id="u1", item_type="product", item_id="p1")
        fav2 = Favorite(user_id="u1", item_type="article", item_id="a1")
        fav3 = Favorite(user_id="u1", item_type="product", item_id="p2")
        engine.favorites = {
            "u1:product:p1": fav1,
            "u1:article:a1": fav2,
            "u1:product:p2": fav3
        }
        products = engine.get_user_favorites("u1", item_type="product")
        assert len(products) == 2
        assert all(f.item_type == "product" for f in products)

    def test_is_favorite(self):
        engine = FavoritesEngine()
        fav = Favorite(user_id="u1", item_type="product", item_id="p1")
        engine.add_favorite(fav)
        assert engine.is_favorite("u1", "product", "p1") is True
        assert engine.is_favorite("u1", "product", "nonexistent") is False

    def test_remove_favorite(self):
        engine = FavoritesEngine()
        fav = Favorite(user_id="u1", item_type="product", item_id="p1")
        engine.add_favorite(fav)
        key = f"u1:product:p1"
        assert key in engine.favorites
        result = engine.remove_favorite("u1", "product", "p1")
        assert result is True
        assert key not in engine.favorites

    def test_remove_favorite_not_exists(self):
        engine = FavoritesEngine()
        result = engine.remove_favorite("u1", "product", "p1")
        assert result is False

    def test_create_collection(self):
        engine = FavoritesEngine()
        coll = FavoriteCollection(user_id="u1", name="My Coll")
        result = engine.create_collection(coll)
        assert result.id is not None
        assert result.user_id == "u1"
        assert result.name == "My Coll"
        assert result.created_at is not None
        assert result.id in engine.collections

    def test_get_user_collections(self):
        engine = FavoritesEngine()
        coll1 = FavoriteCollection(user_id="u1", name="C1")
        coll2 = FavoriteCollection(user_id="u1", name="C2")
        coll3 = FavoriteCollection(user_id="u2", name="C3")
        engine.create_collection(coll1)
        engine.create_collection(coll2)
        engine.create_collection(coll3)
        result = engine.get_user_collections("u1")
        assert len(result) == 2
        assert all(c.user_id == "u1" for c in result)

    def test_create_folder(self):
        engine = FavoritesEngine()
        folder = FavoriteFolder(user_id="u1", name="My Folder")
        result = engine.create_folder(folder)
        assert result.id is not None
        assert result.user_id == "u1"
        assert result.name == "My Folder"
        assert result.created_at is not None
        assert result.id in engine.folders

    def test_get_user_folders(self):
        engine = FavoritesEngine()
        f1 = FavoriteFolder(user_id="u1", name="F1")
        f2 = FavoriteFolder(user_id="u1", name="F2")
        f3 = FavoriteFolder(user_id="u2", name="F3")
        engine.create_folder(f1)
        engine.create_folder(f2)
        engine.create_folder(f3)
        result = engine.get_user_folders("u1")
        assert len(result) == 2
        assert all(f.user_id == "u1" for f in result)

    def test_get_suggestions(self):
        engine = FavoritesEngine()
        fav1 = Favorite(user_id="u1", item_type="product", item_id="p1")
        fav2 = Favorite(user_id="u1", item_type="article", item_id="a1")
        fav3 = Favorite(user_id="u1", item_type="product", item_id="p2")
        engine.favorites = {
            "u1:product:p1": fav1,
            "u1:article:a1": fav2,
            "u1:product:p2": fav3
        }
        suggestions = engine.get_suggestions("u1")
        assert "suggested_categories" in suggestions
        assert "product" in suggestions["suggested_categories"]
        assert "article" in suggestions["suggested_categories"]

    def test_clear_favorites(self):
        engine = FavoritesEngine()
        fav1 = Favorite(user_id="u1", item_type="product", item_id="p1")
        fav2 = Favorite(user_id="u1", item_type="article", item_id="a1")
        engine.favorites = {
            "u1:product:p1": fav1,
            "u1:article:a1": fav2
        }
        result = engine.clear_favorites("u1")
        assert result is True
        assert len(engine.favorites) == 0

    # Wrapper tests removed - focusing on core functionality

if __name__ == "__main__":
    pytest.main([__file__, "-v"])