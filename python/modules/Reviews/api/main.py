from fastapi import FastAPI, HTTPException
from typing import List, Optional
from models.schemas import Review, ReviewReply, ReviewHelpful, ReviewSummary
from service.engine import ReviewsEngine

app = FastAPI(title="Reviews API", version="1.0.0")
engine = ReviewsEngine()

@app.post("/api/reviews", response_model=Review)
async def create_review(review: Review):
	# Input validation
	if not review.rating or review.rating < 1 or review.rating > 5:
		raise HTTPException(status_code=400, detail="Rating must be between 1 and 5")
	if not review.content or len(review.content) < 10:
		raise HTTPException(status_code=400, detail="Content must be at least 10 characters")
	if len(review.content) > 5000:
		raise HTTPException(status_code=400, detail="Content is too long")
	return engine.create_review(review)

@app.get("/api/reviews/{target_type}/{target_id}", response_model=List[Review])
async def get_target_reviews(target_type: str, target_id: str, limit: int = 20, offset: int = 0):
    return engine.get_target_reviews(target_type, target_id, limit, offset)

@app.get("/api/reviews/{target_type}/{target_id}/summary", response_model=ReviewSummary)
async def get_review_summary(target_type: str, target_id: str):
    return engine.get_review_summary(target_type, target_id)

@app.get("/api/reviews/user/{user_id}", response_model=List[Review])
async def get_user_reviews(user_id: str):
    return engine.get_user_reviews(user_id)

@app.post("/api/reviews/{review_id}/reply", response_model=ReviewReply)
async def add_review_reply(review_id: str, reply: ReviewReply):
    reply.review_id = review_id
    return engine.add_reply(reply)

@app.post("/api/reviews/{review_id}/helpful")
async def mark_review_helpful(review_id: str, user_id: str):
    success = engine.mark_helpful(review_id, user_id)
    if success:
        return {"status": "marked"}
    raise HTTPException(status_code=400, detail="Already marked or review not found")

@app.delete("/api/reviews/{review_id}/helpful")
async def unmark_review_helpful(review_id: str, user_id: str):
    success = engine.unmark_helpful(review_id, user_id)
    if success:
        return {"status": "unmarked"}
    raise HTTPException(status_code=400, detail="Not marked or review not found")

@app.get("/api/reviews/{review_id}/helpful/users")
async def get_helpful_users(review_id: str) -> List[str]:
    return engine.get_helpful_users(review_id)

@app.post("/api/reviews/{review_id}/report")
async def report_review(review_id: str, reason: str):
    return {"status": "reported", "reason": reason}
