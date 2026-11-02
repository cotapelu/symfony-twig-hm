import pytest
from datetime import datetime
from modules.Favorites.service.engine import FavoritesEngine
from modules.Favorites.models.schemas import Favorite, FavoriteCollection, FavoriteFolder

@pytest.fixture
def engine():
    return FavoritesEngine()

def test_init(engine):
    assert engine is not None
    assert engine.favorites == {}
    assert engine.collections == {}
    assert engine.folders == {}

def test_add_favorite(engine):
    favorite = Favorite(id="", user_id="user123", item_id="item456", item_type="product")
    added = engine.add_favorite(favorite)
    
    assert added is not None
    assert added.item_type == "product"

def test_get_user_favorites(engine):
    favorite1 = Favorite(id="", user_id="user123", item_id="item1", item_type="product")
    favorite2 = Favorite(id="", user_id="user123", item_id="item2", item_type="service")
    
    engine.add_favorite(favorite1)
    engine.add_favorite(favorite2)
    
    favorites = engine.get_user_favorites("user123")
    assert len(favorites) == 2

def test_get_user_favorites_filtered(engine):
    favorite1 = Favorite(id="", user_id="user123", item_id="item1", item_type="product")
    favorite2 = Favorite(id="", user_id="user123", item_id="item2", item_type="service")
    
    engine.add_favorite(favorite1)
    engine.add_favorite(favorite2)
    
    favorites = engine.get_user_favorites("user123", item_type="product")
    assert len(favorites) == 1
    assert favorites[0].item_type == "product"

def test_is_favorite(engine):
    favorite = Favorite(id="", user_id="user123", item_id="item456", item_type="product")
    engine.add_favorite(favorite)
    
    result = engine.is_favorite("user123", "product", "item456")
    assert result is True

def test_is_favorite_not_added(engine):
    result = engine.is_favorite("user123", "product", "item456")
    assert result is False

def test_remove_favorite(engine):
    favorite = Favorite(id="", user_id="user123", item_id="item456", item_type="product")
    engine.add_favorite(favorite)
    
    result = engine.remove_favorite("user123", "product", "item456")
    assert result is True
    
    is_fav = engine.is_favorite("user123", "product", "item456")
    assert is_fav is False

def test_remove_favorite_not_found(engine):
    result = engine.remove_favorite("user123", "product", "item456")
    assert result is False

def test_create_collection(engine):
    collection = FavoriteCollection(id="", user_id="user123", name="Wishlist")
    created = engine.create_collection(collection)
    
    assert created is not None
    assert created.name == "Wishlist"

def test_get_user_collections(engine):
    collection1 = FavoriteCollection(id="", user_id="user123", name="Wishlist")
    collection2 = FavoriteCollection(id="", user_id="user123", name="Saved")
    
    engine.create_collection(collection1)
    engine.create_collection(collection2)
    
    collections = engine.get_user_collections("user123")
    assert len(collections) == 2

def test_create_folder(engine):
    folder = FavoriteFolder(id="", user_id="user123", name="Products")
    created = engine.create_folder(folder)
    
    assert created is not None
    assert created.name == "Products"

def test_get_user_folders(engine):
    folder1 = FavoriteFolder(id="", user_id="user123", name="Products")
    folder2 = FavoriteFolder(id="", user_id="user123", name="Services")
    
    engine.create_folder(folder1)
    engine.create_folder(folder2)
    
    folders = engine.get_user_folders("user123")
    assert len(folders) == 2

def test_get_suggestions(engine):
    favorite1 = Favorite(id="", user_id="user123", item_id="item1", item_type="product")
    favorite2 = Favorite(id="", user_id="user123", item_id="item2", item_type="service")
    
    engine.add_favorite(favorite1)
    engine.add_favorite(favorite2)
    
    suggestions = engine.get_suggestions("user123")
    assert "suggested_categories" in suggestions
    assert "product" in suggestions["suggested_categories"]

def test_get_favorites_wrapper(engine):
    favorite = Favorite(id="", user_id="123", item_id="item456", item_type="product")
    engine.add_favorite(favorite)
    
    favorites = engine.get_favorites(123)
    assert len(favorites) == 1
