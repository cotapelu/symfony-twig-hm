from pydantic import BaseModel
from typing import List, Dict
from enum import Enum

class SearchEntityType(str, Enum):
    USER = "user"
    MESSAGE = "message"
    STORY = "story"
    MINIPROGRAM = "miniprogram"
    PRODUCT = "product"

class SearchResult(BaseModel):
    entity_id: str
    entity_type: SearchEntityType
    score: float
    snippet: str
    metadata: Dict

class SearchQuery(BaseModel):
    query: str
    entity_types: List[SearchEntityType] = []
    filters: Dict = {}

class SearchAnalytics(BaseModel):
    query: str
    result_count: int
    user_id: str
    clicked_results: List[str]
    search_time_ms: float
