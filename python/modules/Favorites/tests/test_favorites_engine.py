"""Unit tests for FavoritesEngine service.

Tests favorite CRUD with multiple signatures, collections, folders, suggestions, and clear.
"""
import pytest
from datetime import datetime
from modules.Favorites.service.engine import FavoritesEngine, FavoritesManager
from modules.Favorites.models.schemas import (
    Favorite,
    FavoriteCollection,
    FavoriteFolder,
)


@pytest.fixture
def engine():
    return FavoritesEngine()


# === Initialization ===

def test_init_creates_empty_state(engine):
    assert engine.favorites == {}
    assert engine.collections == {}
    assert engine.folders == {}


def test_favorites_manager_alias_exists():
    assert FavoritesManager is FavoritesEngine


# === add_favorite: 1-arg (Favorite object) signature ===

def test_add_favorite_with_object(engine):
    fav = Favorite(user_id="u1", item_id="i1", item_type="product")
    result = engine.add_favorite(fav)
    assert result.id != ""


def test_add_favorite_with_object_generates_id_when_empty(engine):
    fav = Favorite(user_id="u1", item_id="i1", item_type="product")
    fav.id = ""
    result = engine.add_favorite(fav)
    assert result.id != ""


def test_add_favorite_preserves_existing_id(engine):
    fav = Favorite(user_id="u1", item_id="i1", item_type="product")
    fav.id = "custom-id"
    result = engine.add_favorite(fav)
    assert result.id == "custom-id"


def test_add_favorite_sets_created_at(engine):
    fav = Favorite(user_id="u1", item_id="i1", item_type="product")
    result = engine.add_favorite(fav)
    assert result.created_at is not None


# === add_favorite: 3-arg positional signature ===

def test_add_favorite_with_positional_args(engine):
    result = engine.add_favorite("u1", "i1", "product")
    assert result.id != ""
    assert result.user_id == "u1"
    assert result.item_id == "i1"
    assert result.item_type == "product"


def test_add_favorite_with_kwargs(engine):
    result = engine.add_favorite(user_id="u1", item_id="i1", item_type="product")
    assert result.user_id == "u1"


def test_add_favorite_with_kwargs_missing_raises(engine):
    with pytest.raises(ValueError):
        engine.add_favorite(user_id="u1", item_id="i1")


def test_add_favorite_with_wrong_arg_count_raises(engine):
    with pytest.raises(TypeError):
        engine.add_favorite("u1", "i1")  # missing item_type


# === Duplicate handling ===

def test_add_duplicate_favorite_uses_composite_key(engine):
    fav1 = Favorite(user_id="u1", item_id="i1", item_type="product")
    fav2 = Favorite(user_id="u1", item_id="i1", item_type="product")
    engine.add_favorite(fav1)
    engine.add_favorite(fav2)
    # Same user_id:item_type:item_id key — only one entry
    key = "u1:product:i1"
    assert len([k for k in engine.favorites if k == key]) == 1


def test_add_different_users_same_item(engine):
    fav1 = Favorite(user_id="u1", item_id="i1", item_type="product")
    fav2 = Favorite(user_id="u2", item_id="i1", item_type="product")
    engine.add_favorite(fav1)
    engine.add_favorite(fav2)
    assert len(engine.favorites) == 2


def test_add_same_user_different_types(engine):
    fav1 = Favorite(user_id="u1", item_id="i1", item_type="product")
    fav2 = Favorite(user_id="u1", item_id="i1", item_type="service")
    engine.add_favorite(fav1)
    engine.add_favorite(fav2)
    assert len(engine.favorites) == 2


# === get_user_favorites ===

def test_get_user_favorites_returns_matching(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    engine.add_favorite(Favorite(user_id="u1", item_id="i2", item_type="product"))
    engine.add_favorite(Favorite(user_id="u2", item_id="i3", item_type="product"))
    favs = engine.get_user_favorites("u1")
    assert len(favs) == 2


def test_get_user_favorites_empty_for_no_favorites(engine):
    assert engine.get_user_favorites("u1") == []


def test_get_user_favorites_filter_by_type(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    engine.add_favorite(Favorite(user_id="u1", item_id="i2", item_type="service"))
    favs = engine.get_user_favorites("u1", item_type="product")
    assert len(favs) == 1
    assert favs[0].item_type == "product"


def test_get_user_favorites_sorted_descending_by_created_at(engine):
    fav1 = Favorite(user_id="u1", item_id="i1", item_type="product")
    fav1.created_at = datetime(2024, 1, 1)
    fav2 = Favorite(user_id="u1", item_id="i2", item_type="product")
    fav2.created_at = datetime(2024, 6, 1)
    engine.add_favorite(fav1)
    engine.add_favorite(fav2)
    favs = engine.get_user_favorites("u1")
    assert favs[0].item_id == "i2"  # newer first


def test_get_user_favorites_does_not_leak_other_users(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    favs = engine.get_user_favorites("u2")
    assert favs == []


# === is_favorite: 3-arg signature ===

def test_is_favorite_3arg_returns_true(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    assert engine.is_favorite("u1", "product", "i1") is True


def test_is_favorite_3arg_returns_false_for_missing(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    assert engine.is_favorite("u1", "product", "i999") is False


def test_is_favorite_3arg_returns_false_for_wrong_user(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    assert engine.is_favorite("u2", "product", "i1") is False


# === is_favorite: 2-arg signature (any type) ===

def test_is_favorite_2arg_returns_true(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    assert engine.is_favorite("u1", "i1") is True


def test_is_favorite_2arg_returns_false_for_missing(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    assert engine.is_favorite("u1", "i999") is False


def test_is_favorite_2arg_wrong_args_raises(engine):
    with pytest.raises(TypeError):
        engine.is_favorite("u1")


# === remove_favorite: 3-arg signature ===

def test_remove_favorite_3arg_removes(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    result = engine.remove_favorite("u1", "product", "i1")
    assert result is True
    assert engine.is_favorite("u1", "product", "i1") is False


def test_remove_favorite_3arg_returns_false_for_missing(engine):
    result = engine.remove_favorite("u1", "product", "i999")
    assert result is False


# === remove_favorite: 2-arg signature ===

def test_remove_favorite_2arg_removes_first_match(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    result = engine.remove_favorite("u1", "i1")
    assert result is True
    assert engine.is_favorite("u1", "i1") is False


def test_remove_favorite_2arg_returns_false_for_missing(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    result = engine.remove_favorite("u1", "i999")
    assert result is False


def test_remove_favorite_wrong_args_raises(engine):
    with pytest.raises(TypeError):
        engine.remove_favorite("u1")


# === create_collection ===

def test_create_collection_generates_id(engine):
    coll = FavoriteCollection(user_id="u1", name="My Collection")
    result = engine.create_collection(coll)
    assert result.id != ""


def test_create_collection_sets_created_at(engine):
    coll = FavoriteCollection(user_id="u1", name="My Collection")
    result = engine.create_collection(coll)
    assert result.created_at is not None


def test_create_collection_preserves_id(engine):
    coll = FavoriteCollection(user_id="u1", name="My Collection")
    coll.id = "custom-coll"
    result = engine.create_collection(coll)
    assert result.id == "custom-coll"


def test_create_collection_with_description(engine):
    coll = FavoriteCollection(user_id="u1", name="X", description="Best items")
    result = engine.create_collection(coll)
    assert result.description == "Best items"


# === get_user_collections ===

def test_get_user_collections_returns_matching(engine):
    engine.create_collection(FavoriteCollection(user_id="u1", name="A"))
    engine.create_collection(FavoriteCollection(user_id="u1", name="B"))
    engine.create_collection(FavoriteCollection(user_id="u2", name="C"))
    cols = engine.get_user_collections("u1")
    assert len(cols) == 2


def test_get_user_collections_empty(engine):
    assert engine.get_user_collections("u1") == []


# === create_folder ===

def test_create_folder_generates_id(engine):
    folder = FavoriteFolder(user_id="u1", name="Work")
    result = engine.create_folder(folder)
    assert result.id != ""


def test_create_folder_with_parent_id(engine):
    folder = FavoriteFolder(user_id="u1", name="Subfolder", parent_id="parent-id")
    result = engine.create_folder(folder)
    assert result.parent_id == "parent-id"


# === get_user_folders ===

def test_get_user_folders_returns_matching(engine):
    engine.create_folder(FavoriteFolder(user_id="u1", name="A"))
    engine.create_folder(FavoriteFolder(user_id="u1", name="B"))
    engine.create_folder(FavoriteFolder(user_id="u2", name="C"))
    folders = engine.get_user_folders("u1")
    assert len(folders) == 2


def test_get_user_folders_empty(engine):
    assert engine.get_user_folders("u1") == []


# === get_suggestions ===

def test_get_suggestions_empty_for_no_favorites(engine):
    result = engine.get_suggestions("u1")
    assert result["suggested_categories"] == []
    assert result["recommended_items"] == []


def test_get_suggestions_returns_unique_categories(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    engine.add_favorite(Favorite(user_id="u1", item_id="i2", item_type="product"))
    engine.add_favorite(Favorite(user_id="u1", item_id="i3", item_type="service"))
    result = engine.get_suggestions("u1")
    # Should deduplicate item_types
    assert len(result["suggested_categories"]) == 2
    assert "product" in result["suggested_categories"]
    assert "service" in result["suggested_categories"]


def test_get_suggestions_limits_to_5(engine):
    for i in range(10):
        engine.add_favorite(Favorite(user_id="u1", item_id=f"i{i}", item_type=f"type{i}"))
    result = engine.get_suggestions("u1")
    assert len(result["suggested_categories"]) <= 5


# === clear_favorites ===

def test_clear_favorites_removes_all_for_user(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    engine.add_favorite(Favorite(user_id="u1", item_id="i2", item_type="service"))
    engine.add_favorite(Favorite(user_id="u2", item_id="i3", item_type="product"))
    engine.clear_favorites("u1")
    assert engine.get_user_favorites("u1") == []
    assert engine.get_user_favorites("u2") != []


def test_clear_favorites_returns_true(engine):
    engine.add_favorite(Favorite(user_id="u1", item_id="i1", item_type="product"))
    assert engine.clear_favorites("u1") is True


def test_clear_favorites_no_favorites_returns_true(engine):
    assert engine.clear_favorites("u1") is True


# === Backward-compat wrappers ===

def test_get_favorites_int_user_id(engine):
    engine.add_favorite(Favorite(user_id="42", item_id="i1", item_type="product"))
    favs = engine.get_favorites(42)  # int
    assert len(favs) == 1


def test_get_favorites_by_type_int_user_id(engine):
    engine.add_favorite(Favorite(user_id="42", item_id="i1", item_type="product"))
    engine.add_favorite(Favorite(user_id="42", item_id="i2", item_type="service"))
    favs = engine.get_favorites_by_type(42, "product")
    assert len(favs) == 1


# === Integration scenarios ===

def test_full_favorite_flow(engine):
    """Add -> Is -> Remove -> Is workflow."""
    engine.add_favorite("u1", "i1", "product")
    assert engine.is_favorite("u1", "i1") is True
    engine.remove_favorite("u1", "product", "i1")
    assert engine.is_favorite("u1", "i1") is False


def test_favorite_then_collection_then_folder(engine):
    """Composite workflow."""
    engine.add_favorite("u1", "i1", "product")
    coll = engine.create_collection(FavoriteCollection(user_id="u1", name="Wishlist"))
    folder = engine.create_folder(FavoriteFolder(user_id="u1", name="2024"))
    assert engine.is_favorite("u1", "i1") is True
    assert coll.id in engine.collections
    assert folder.id in engine.folders
