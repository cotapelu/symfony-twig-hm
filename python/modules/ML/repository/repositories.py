from datetime import datetime, timedelta
from typing import Optional, List
import uuid
from models.entities import MLModel, MLInference

class MLModelRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.ml_models

    async def create(self, model: MLModel) -> MLModel:
        model.id = str(uuid.uuid4())
        model.created_at = datetime.now()
        await self.collection.insert_one(model.dict())
        return model

    async def get_by_id(self, id: str) -> Optional[MLModel]:
        data = await self.collection.find_one({"id": id})
        if data:
            return MLModel(**data)
        return None

    async def get_by_name(self, name: str) -> Optional[MLModel]:
        data = await self.collection.find_one({"name": name})
        if data:
            return MLModel(**data)
        return None

    async def get_by_type(self, model_type: str, limit: int = 100) -> List[MLModel]:
        cursor = self.collection.find({"type": model_type}).sort("created_at", -1).limit(limit)
        models = []
        async for doc in cursor:
            models.append(MLModel(**doc))
        return models

    async def get_active_models(self, limit: int = 100) -> List[MLModel]:
        cursor = self.collection.find({"status": "active"}).sort("created_at", -1).limit(limit)
        models = []
        async for doc in cursor:
            models.append(MLModel(**doc))
        return models

    async def update_status(self, id: str, status: str) -> Optional[MLModel]:
        await self.collection.update_one({"id": id}, {"$set": {"status": status}})
        return await self.get_by_id(id)

    async def update_model(self, id: str, data: dict) -> Optional[MLModel]:
        await self.collection.update_one({"id": id}, {"$set": data})
        return await self.get_by_id(id)

class MLInferenceRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.ml_inferences

    async def create(self, inference: MLInference) -> MLInference:
        inference.id = str(uuid.uuid4())
        inference.created_at = datetime.now()
        await self.collection.insert_one(inference.dict())
        return inference

    async def get_by_id(self, id: str) -> Optional[MLInference]:
        data = await self.collection.find_one({"id": id})
        if data:
            return MLInference(**data)
        return None

    async def get_by_model_id(self, model_id: str, limit: int = 100) -> List[MLInference]:
        cursor = self.collection.find({"model_id": model_id}).sort("created_at", -1).limit(limit)
        inferences = []
        async for doc in cursor:
            inferences.append(MLInference(**doc))
        return inferences

    async def get_by_user_id(self, user_id: str, limit: int = 100) -> List[MLInference]:
        cursor = self.collection.find({"user_id": user_id}).sort("created_at", -1).limit(limit)
        inferences = []
        async for doc in cursor:
            inferences.append(MLInference(**doc))
        return inferences

    async def get_by_date_range(self, start_date: datetime, end_date: datetime) -> List[MLInference]:
        cursor = self.collection.find({
            "created_at": {"$gte": start_date, "$lte": end_date}
        }).sort("created_at", -1)
        inferences = []
        async for doc in cursor:
            inferences.append(MLInference(**doc))
        return inferences

    async def count_by_model_id(self, model_id: str) -> int:
        return await self.collection.count_documents({"model_id": model_id})

    async def delete_old_inferences(self, days: int) -> int:
        cutoff_date = datetime.now() - timedelta(days=days)
        result = await self.collection.delete_many({"created_at": {"$lt": cutoff_date}})
        return result.deleted_count