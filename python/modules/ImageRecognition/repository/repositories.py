from datetime import datetime, timedelta
from typing import Optional, List
import uuid
from models.entities import ImageRecognitionResult

class ImageRecognitionResultRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.image_recognition_results

    async def create(self, result: ImageRecognitionResult) -> ImageRecognitionResult:
        result.id = str(uuid.uuid4())
        result.created_at = datetime.now()
        await self.collection.insert_one(result.dict())
        return result

    async def get_by_id(self, id: str) -> Optional[ImageRecognitionResult]:
        data = await self.collection.find_one({"id": id})
        if data:
            return ImageRecognitionResult(**data)
        return None

    async def get_by_user_id(self, user_id: str, limit: int = 100) -> List[ImageRecognitionResult]:
        cursor = self.collection.find({"user_id": user_id}).sort("created_at", -1).limit(limit)
        results = []
        async for doc in cursor:
            results.append(ImageRecognitionResult(**doc))
        return results

    async def get_by_image_id(self, image_id: str) -> Optional[ImageRecognitionResult]:
        data = await self.collection.find_one({"image_id": image_id})
        if data:
            return ImageRecognitionResult(**data)
        return None

    async def get_by_model_id(self, model_id: str, limit: int = 100) -> List[ImageRecognitionResult]:
        cursor = self.collection.find({"model_id": model_id}).sort("created_at", -1).limit(limit)
        results = []
        async for doc in cursor:
            results.append(ImageRecognitionResult(**doc))
        return results

    async def get_by_date_range(self, start_date: datetime, end_date: datetime) -> List[ImageRecognitionResult]:
        cursor = self.collection.find({
            "created_at": {"$gte": start_date, "$lte": end_date}
        }).sort("created_at", -1)
        results = []
        async for doc in cursor:
            results.append(ImageRecognitionResult(**doc))
        return results

    async def delete_old_results(self, days: int) -> int:
        cutoff_date = datetime.now() - timedelta(days=days)
        result = await self.collection.delete_many({"created_at": {"$lt": cutoff_date}})
        return result.deleted_count