from typing import Dict, List, Optional
import uuid
from datetime import datetime
from models.schemas import Favorite, FavoriteCollection, FavoriteFolder

class FavoritesEngine:
    def __init__(self):
        self.favorites: Dict[str, Favorite] = {}
        self.collections: Dict[str, FavoriteCollection] = {}
        self.folders: Dict[str, FavoriteFolder] = {}

    def add_favorite(self, *args, **kwargs) -> Favorite:
        return self._add_favorite_impl(*args, **kwargs)

    def _add_favorite_impl(self, *args, **kwargs) -> Favorite:
        """Add a favorite. Supports two signatures:
        - add_favorite(favorite: Favorite)
        - add_favorite(user_id, item_id, item_type)
        """
        if len(args) == 1 and hasattr(args[0], 'user_id') and hasattr(args[0], 'item_id') and hasattr(args[0], 'item_type'):
            favorite = args[0]
        else:
            if kwargs:
                user_id = kwargs.get('user_id')
                item_id = kwargs.get('item_id')
                item_type = kwargs.get('item_type')
                if not all((user_id, item_id, item_type)):
                    raise ValueError("user_id, item_id, item_type required")
            else:
                if len(args) != 3:
                    raise TypeError("add_favorite requires (favorite) or (user_id, item_id, item_type)")
                user_id, item_id, item_type = args
            favorite = Favorite(user_id=str(user_id), item_id=str(item_id), item_type=item_type)

        if not favorite.id:
            favorite.id = str(uuid.uuid4())
        favorite.created_at = datetime.now()
        key = f"{favorite.user_id}:{favorite.item_type}:{favorite.item_id}"
        self.favorites[key] = favorite
        return favorite

    def get_user_favorites(self, user_id: str, item_type: Optional[str] = None) -> List[Favorite]:
        result = []
        for fav in self.favorites.values():
            if fav.user_id == str(user_id):
                if item_type is None or fav.item_type == item_type:
                    result.append(fav)
        return sorted(result, key=lambda x: x.created_at or datetime.min, reverse=True)

    def is_favorite(self, *args) -> bool:
        """Check if item is favorite. Signatures:
        - is_favorite(user_id, item_type, item_id)
        - is_favorite(user_id, item_id)  # checks any type
        """
        if len(args) == 3:
            user_id, item_type, item_id = args
            key = f"{user_id}:{item_type}:{item_id}"
            return key in self.favorites
        elif len(args) == 2:
            user_id, item_id = args
            favs = self.get_user_favorites(user_id)
            target_id = str(item_id)
            return any(f.item_id == target_id for f in favs)
        else:
            raise TypeError("is_favorite requires (user_id, item_type, item_id) or (user_id, item_id)")

    def remove_favorite(self, *args) -> bool:
        return self._remove_favorite_impl(*args)

    def _remove_favorite_impl(self, *args) -> bool:
        """Remove favorite. Signatures:
        - remove_favorite(user_id, item_type, item_id)
        - remove_favorite(user_id, item_id)  # removes first matching
        """
        if len(args) == 3:
            user_id, item_type, item_id = args
            key = f"{user_id}:{item_type}:{item_id}"
            if key in self.favorites:
                del self.favorites[key]
                return True
            return False
        elif len(args) == 2:
            user_id, item_id = args
            favs = self.get_user_favorites(user_id)
            target_id = str(item_id)
            for f in favs:
                if f.item_id == target_id:
                    key = f"{user_id}:{f.item_type}:{target_id}"
                    if key in self.favorites:
                        del self.favorites[key]
                        return True
            return False
        else:
            raise TypeError("remove_favorite requires (user_id, item_type, item_id) or (user_id, item_id)")

    def create_collection(self, collection: FavoriteCollection) -> FavoriteCollection:
        if not collection.id:
            collection.id = str(uuid.uuid4())
        collection.created_at = datetime.now()
        self.collections[collection.id] = collection
        return collection

    def get_user_collections(self, user_id: str) -> List[FavoriteCollection]:
        return [c for c in self.collections.values() if c.user_id == str(user_id)]

    def create_folder(self, folder: FavoriteFolder) -> FavoriteFolder:
        if not folder.id:
            folder.id = str(uuid.uuid4())
        folder.created_at = datetime.now()
        self.folders[folder.id] = folder
        return folder

    def get_user_folders(self, user_id: str) -> List[FavoriteFolder]:
        return [f for f in self.folders.values() if f.user_id == str(user_id)]

    def get_suggestions(self, user_id: str) -> Dict:
        favorites = self.get_user_favorites(user_id)
        item_types = list(set(f.item_type for f in favorites))
        return {
            "suggested_categories": item_types[:5],
            "recommended_items": []
        }

    def clear_favorites(self, user_id) -> bool:
        """Clear all favorites for user."""
        favs = self.get_user_favorites(user_id)
        for f in favs:
            self.remove_favorite(str(user_id), f.item_type, f.item_id)
        return True

    # Wrapper methods for backward compatibility with int-based tests
    def get_favorites(self, user_id: int) -> List[Favorite]:
        return self.get_user_favorites(str(user_id))

    def get_favorites_by_type(self, user_id: int, item_type: str) -> List[Favorite]:
        return self.get_user_favorites(str(user_id), item_type)

# Alias for compatibility
FavoritesManager = FavoritesEngine
