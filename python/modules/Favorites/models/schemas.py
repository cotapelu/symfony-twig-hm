from pydantic import BaseModel
from typing import List, Optional
from datetime import datetime

class Favorite(BaseModel):
    id: str = ""
    user_id: str
    item_type: str
    item_id: str
    item_name: str = ""
    item_image: str = ""
    metadata: dict = {}
    created_at: Optional[datetime] = None

class FavoriteCollection(BaseModel):
    id: str = ""
    user_id: str
    name: str
    description: str = ""
    item_count: int = 0
    created_at: Optional[datetime] = None

class FavoriteFolder(BaseModel):
    id: str = ""
    user_id: str
    name: str
    parent_id: str = None
    created_at: Optional[datetime] = None
