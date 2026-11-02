from fastapi import FastAPI
from typing import List, Dict, Optional
from models.schemas import RecommendationRequest, RecommendationResponse, RecommendedItem
from service.engine import RecommendationEngine

app = FastAPI(title="Recommendations API", version="1.0.0")
engine = RecommendationEngine()

@app.post("/api/recommendations", response_model=RecommendationResponse)
async def get_recommendations(request: RecommendationRequest):
    items = engine.hybrid_recommend(
        request.user_id,
        request.item_type,
        request.count,
        request.exclude_items,
        request.context
    )
    
    recommendations = [
        RecommendedItem(
            item_id=item.id,
            item_type=item.type,
            score=item.popularity_score,
            reason="Recommended for you",
            metadata={"categories": item.categories, "attributes": item.attributes}
        )
        for item in items
    ]
    
    return RecommendationResponse(
        user_id=request.user_id,
        recommendations=recommendations,
        model_used="hybrid"
    )

@app.get("/api/recommendations/popular/{item_type}")
async def get_popular(item_type: str, count: int = 10):
    items = engine.get_popular_items(item_type, count)
    return {"items": [{"id": i.id, "type": i.type, "score": i.popularity_score} for i in items]}

@app.get("/api/recommendations/trending/{item_type}")
async def get_trending(item_type: str, count: int = 10):
    items = engine.trending_now(item_type, count)
    return {"items": [{"id": i.id, "type": i.type, "trending_score": i.popularity_score + i.recency_score} for i in items]}

@app.get("/api/recommendations/similar/{item_id}")
async def get_similar(item_id: str, count: int = 5):
    items = engine.similar_items(item_id, count)
    return {"items": [{"id": i.id, "type": i.type, "similarity": 1.0} for i in items]}

@app.post("/api/recommendations/user/{user_id}/profile")
async def update_profile(user_id: str, profile: dict):
    from models.schemas import UserProfile
    up = UserProfile(user_id=user_id, **profile)
    engine.update_user_profile(user_id, up)
    return {"status": "updated"}

@app.post("/api/recommendations/items")
async def add_items(items: List[dict]):
    from models.schemas import Item
    for item_data in items:
        item = Item(**item_data)
        engine.add_item(item)
    return {"status": "added", "count": len(items)}
