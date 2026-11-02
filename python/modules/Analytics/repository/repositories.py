from datetime import datetime, timedelta
from typing import Optional, List
import uuid
from models.entities import AnalyticsEvent, AnalyticsAggregate

class AnalyticsEventRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.analytics_events

    async def create(self, event: AnalyticsEvent) -> AnalyticsEvent:
        event.id = str(uuid.uuid4())
        event.created_at = datetime.now()
        await self.collection.insert_one(event.dict())
        return event

    async def get_by_id(self, id: str) -> Optional[AnalyticsEvent]:
        data = await self.collection.find_one({"id": id})
        if data:
            return AnalyticsEvent(**data)
        return None

    async def get_by_user_id(self, user_id: str, limit: int = 100) -> List[AnalyticsEvent]:
        cursor = self.collection.find({"user_id": user_id}).sort("created_at", -1).limit(limit)
        events = []
        async for doc in cursor:
            events.append(AnalyticsEvent(**doc))
        return events

    async def get_by_event_type(self, event_type: str, limit: int = 100) -> List[AnalyticsEvent]:
        cursor = self.collection.find({"event_type": event_type}).sort("created_at", -1).limit(limit)
        events = []
        async for doc in cursor:
            events.append(AnalyticsEvent(**doc))
        return events

    async def get_by_date_range(self, start_date: datetime, end_date: datetime) -> List[AnalyticsEvent]:
        cursor = self.collection.find({
            "created_at": {"$gte": start_date, "$lte": end_date}
        }).sort("created_at", -1)
        events = []
        async for doc in cursor:
            events.append(AnalyticsEvent(**doc))
        return events

    async def count_by_event_type(self, event_type: str) -> int:
        return await self.collection.count_documents({"event_type": event_type})

class AnalyticsAggregateRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.analytics_aggregates

    async def create(self, aggregate: AnalyticsAggregate) -> AnalyticsAggregate:
        aggregate.id = str(uuid.uuid4())
        aggregate.created_at = datetime.now()
        await self.collection.insert_one(aggregate.dict())
        return aggregate

    async def get_by_id(self, id: str) -> Optional[AnalyticsAggregate]:
        data = await self.collection.find_one({"id": id})
        if data:
            return AnalyticsAggregate(**data)
        return None

    async def get_by_event_type(self, event_type: str) -> List[AnalyticsAggregate]:
        cursor = self.collection.find({"event_type": event_type}).sort("created_at", -1)
        aggregates = []
        async for doc in cursor:
            aggregates.append(AnalyticsAggregate(**doc))
        return aggregates

    async def get_by_date_range(self, start_date: datetime, end_date: datetime) -> List[AnalyticsAggregate]:
        cursor = self.collection.find({
            "created_at": {"$gte": start_date, "$lte": end_date}
        }).sort("created_at", -1)
        aggregates = []
        async for doc in cursor:
            aggregates.append(AnalyticsAggregate(**doc))
        return aggregates

    async def update_aggregate(self, id: str, data: dict) -> Optional[AnalyticsAggregate]:
        await self.collection.update_one({"id": id}, {"$set": data})
        return await self.get_by_id(id)

    async def delete_old_aggregates(self, days: int) -> int:
        cutoff_date = datetime.now() - timedelta(days=days)
        result = await self.collection.delete_many({"created_at": {"$lt": cutoff_date}})
        return result.deleted_count