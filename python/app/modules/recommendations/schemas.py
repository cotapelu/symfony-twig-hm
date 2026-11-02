from pydantic import BaseModel
from typing import List

class RecommendationResponse(BaseModel):
    user_id: str
    items: List[str]  # list of item IDs
