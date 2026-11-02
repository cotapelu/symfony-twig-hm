from fastapi import APIRouter, Depends
from app.shared.middleware.auth import get_current_user_id
from ..schemas import RecommendationResponse
from ..service import RecommendationService

router = APIRouter(prefix="/recommendations", tags=["recommendations"])

@router.get("/", response_model=RecommendationResponse)
async def get_recommendations(user_id: str = Depends(get_current_user_id)):
    svc = RecommendationService()
    items = svc.get_recommendations(user_id, limit=10)
    return RecommendationResponse(user_id=user_id, items=items)
