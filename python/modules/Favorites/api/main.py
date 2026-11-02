from fastapi import FastAPI, HTTPException
from typing import List, Optional
from models.schemas import Favorite, FavoriteCollection, FavoriteFolder
from service.engine import FavoritesEngine

app = FastAPI(title="Favorites API", version="1.0.0")
engine = FavoritesEngine()

@app.post("/api/favorites", response_model=Favorite)
async def add_favorite(favorite: Favorite):
    return engine.add_favorite(favorite)

@app.get("/api/favorites/{user_id}", response_model=List[Favorite])
async def get_user_favorites(user_id: str, item_type: Optional[str] = None):
    return engine.get_user_favorites(user_id, item_type)

@app.get("/api/favorites/{user_id}/{item_type}/{item_id}")
async def check_favorite(user_id: str, item_type: str, item_id: str):
    exists = engine.is_favorite(user_id, item_type, item_id)
    return {"is_favorite": exists}

@app.delete("/api/favorites/{user_id}/{item_type}/{item_id}")
async def remove_favorite(user_id: str, item_type: str, item_id: str):
    success = engine.remove_favorite(user_id, item_type, item_id)
    if success:
        return {"status": "removed"}
    raise HTTPException(status_code=404, detail="Favorite not found")

@app.post("/api/favorites/collections", response_model=FavoriteCollection)
async def create_collection(collection: FavoriteCollection):
    return engine.create_collection(collection)

@app.get("/api/favorites/collections/{user_id}", response_model=List[FavoriteCollection])
async def get_user_collections(user_id: str):
    return engine.get_user_collections(user_id)

@app.post("/api/favorites/folders", response_model=FavoriteFolder)
async def create_folder(folder: FavoriteFolder):
    return engine.create_folder(folder)

@app.get("/api/favorites/folders/{user_id}", response_model=List[FavoriteFolder])
async def get_user_folders(user_id: str):
    return engine.get_user_folders(user_id)

@app.get("/api/favorites/{user_id}/suggestions")
async def get_favorites_suggestions(user_id: str):
    return engine.get_suggestions(user_id)
