"""
History Service - User Activity History Management
Handles storage, retrieval, and analysis of user activity history.
"""

import uuid
from datetime import datetime, timedelta
from typing import Optional, List, Dict, Any
from collections import defaultdict

import sqlite3

from app.appcore.database import get_db_connection
from app.appcore.redis import get_redis_cache

from ..models.schemas import (
    ActivityCreate,
    ActivityResponse,
    HistoryEntry,
    HistorySearchRequest,
    HistoryStats,
    SessionSummary,
    ActivityType,
    DeviceType,
)


class HistoryService:
    """Service for managing user history and activity tracking"""
    
    def __init__(self):
        self.collection = "user_history"
        self.sessions_collection = "user_sessions"
        self.page_visits_collection = "page_visits"
    
    async def log_activity(self, activity: ActivityCreate) -> ActivityResponse:
        """
        Log a new user activity.
        
        Args:
            activity: Activity creation request
            
        Returns:
            Created activity response
        """
        conn = await get_db_connection()
        try:
            timestamp = activity.timestamp or datetime.utcnow()
            
            doc = {
                "_id": str(uuid.uuid4()),
                "user_id": activity.user_id,
                "activity_type": activity.activity_type.value,
                "description": activity.description,
                "metadata": activity.metadata or {},
                "session_id": activity.session_id,
                "device_type": activity.device_type.value if activity.device_type else None,
                "ip_address": activity.ip_address,
                "user_agent": activity.user_agent,
                "location": activity.location,
                "timestamp": timestamp,
                "created_at": datetime.utcnow()
            }
            
            await conn[self.collection].insert_one(doc)
            
            # Cache recent activity for quick access
            redis = await get_redis_cache()
            if redis:
                key = f"recent_activity:{activity.user_id}"
                await redis.lpush(key, str(doc["_id"]))
                await redis.ltrim(key, 0, 99)  # Keep last 100
                await redis.expire(key, 86400)  # 24 hours
            
            return ActivityResponse(
                id=doc["_id"],
                user_id=doc["user_id"],
                activity_type=ActivityType(doc["activity_type"]),
                description=doc["description"],
                metadata=doc.get("metadata"),
                session_id=doc.get("session_id"),
                device_type=DeviceType(doc["device_type"]) if doc.get("device_type") else None,
                ip_address=doc.get("ip_address"),
                user_agent=doc.get("user_agent"),
                location=doc.get("location"),
                timestamp=doc["timestamp"],
                created_at=doc["created_at"]
            )
        finally:
            await conn.close()
    
    async def get_user_history(
        self,
        user_id: str,
        activity_types: Optional[List[ActivityType]] = None,
        start_date: Optional[datetime] = None,
        end_date: Optional[datetime] = None,
        resource_type: Optional[str] = None,
        resource_id: Optional[str] = None,
        limit: int = 50,
        offset: int = 0
    ) -> List[HistoryEntry]:
        """
        Get user history with filters.
        
        Args:
            user_id: User ID
            activity_types: Filter by activity types
            start_date: Filter by start date
            end_date: Filter by end date
            resource_type: Filter by resource type
            resource_id: Filter by resource ID
            limit: Maximum number of entries
            offset: Pagination offset
            
        Returns:
            List of history entries
        """
        conn = await get_db_connection()
        try:
            query: Dict[str, Any] = self._build_history_query(
                user_id, activity_types, start_date, end_date, resource_type, resource_id
            )
            cursor = conn[self.collection].find(query).sort("timestamp", -1).skip(offset).limit(limit)
            docs = await cursor.to_list(length=limit)
            return [self._doc_to_history_entry(doc) for doc in docs]
        finally:
            await conn.close()
    
    async def get_history_stats(self, user_id: str) -> HistoryStats:
        """
        Get statistics for user's history.
        
        Args:
            user_id: User ID
            
        Returns:
            History statistics
        """
        conn = await get_db_connection()
        try:
            total = await self._get_total_activities(conn, user_id)
            activities_by_type = await self._get_activities_by_type(conn, user_id)
            most_active_day = await self._get_most_active_day(conn, user_id)
            avg_duration = await self._get_average_session_duration(conn, user_id)
            top_locations = await self._get_top_locations(conn, user_id)
            recent_activity_types = await self._get_recent_activity_types(conn, user_id)

            return HistoryStats(
                user_id=user_id,
                total_activities=total,
                activities_by_type=activities_by_type,
                most_active_day=most_active_day,
                average_session_duration=avg_duration,
                top_locations=top_locations,
                recent_activity_types=recent_activity_types
            )
        finally:
            await conn.close()
    
    async def get_user_sessions(
        self,
        user_id: str,
        limit: int = 20,
        offset: int = 0
    ) -> List[SessionSummary]:
        """
        Get user's session history.
        
        Args:
            user_id: User ID
            limit: Maximum number of sessions
            offset: Pagination offset
            
        Returns:
            List of session summaries
        """
        conn = await get_db_connection()
        try:
            cursor = conn[self.sessions_collection].find(
                {"user_id": user_id}
            ).sort("start_time", -1).skip(offset).limit(limit)
            
            sessions = []
            async for doc in cursor:
                duration = 0
                if doc.get("end_time"):
                    duration = int((doc["end_time"] - doc["start_time"]).total_seconds())
                elif doc.get("start_time"):
                    duration = int((datetime.utcnow() - doc["start_time"]).total_seconds())
                
                # Get pages visited in session
                pages_cursor = conn[self.page_visits_collection].find(
                    {"session_id": doc["_id"]}
                ).sort("timestamp", 1)
                
                pages_visited = []
                async for page in pages_cursor:
                    pages_visited.append(page.get("path", "/"))
                
                sessions.append(SessionSummary(
                    session_id=doc["_id"],
                    user_id=doc["user_id"],
                    start_time=doc["start_time"],
                    end_time=doc.get("end_time"),
                    duration=duration,
                    device_type=DeviceType(doc.get("device_type", "other")),
                    ip_address=doc.get("ip_address", ""),
                    location=doc.get("location"),
                    activities_count=doc.get("activities_count", 0),
                    pages_visited=pages_visited
                ))
            
            return sessions
        finally:
            await conn.close()
    
    async def create_session(
        self,
        user_id: str,
        device_type: DeviceType,
        ip_address: str,
        location: Optional[str] = None,
        user_agent: Optional[str] = None
    ) -> SessionSummary:
        """
        Create a new user session.
        
        Args:
            user_id: User ID
            device_type: Type of device
            ip_address: IP address
            location: Geographic location
            user_agent: User agent string
            
        Returns:
            Created session summary
        """
        conn = await get_db_connection()
        try:
            now = datetime.utcnow()
            doc = {
                "_id": str(uuid.uuid4()),
                "user_id": user_id,
                "start_time": now,
                "end_time": None,
                "device_type": device_type.value,
                "ip_address": ip_address,
                "location": location,
                "user_agent": user_agent,
                "activities_count": 0
            }
            
            await conn[self.sessions_collection].insert_one(doc)
            
            return SessionSummary(
                session_id=doc["_id"],
                user_id=doc["user_id"],
                start_time=doc["start_time"],
                end_time=None,
                duration=0,
                device_type=device_type,
                ip_address=ip_address,
                location=location,
                activities_count=0,
                pages_visited=[]
            )
        finally:
            await conn.close()
    
    async def end_session(self, session_id: str) -> bool:
        """
        End a user session.
        
        Args:
            session_id: Session ID
            
        Returns:
            True if successful
        """
        conn = await get_db_connection()
        try:
            from bson import ObjectId
            
            result = await conn[self.sessions_collection].update_one(
                {"_id": session_id, "end_time": None},
                {"$set": {"end_time": datetime.utcnow()}}
            )
            
            return result.modified_count > 0
        finally:
            await conn.close()
    
    async def log_page_visit(
        self,
        session_id: str,
        user_id: str,
        path: str,
        title: Optional[str] = None,
        referrer: Optional[str] = None,
        duration: int = 0
    ) -> str:
        """
        Log a page visit.
        
        Args:
            session_id: Session ID
            user_id: User ID
            path: Page path
            title: Page title
            referrer: Referrer URL
            duration: Time spent on page
            
        Returns:
            Created document ID
        """
        conn = await get_db_connection()
        try:
            doc = {
                "_id": str(uuid.uuid4()),
                "session_id": session_id,
                "user_id": user_id,
                "path": path,
                "title": title,
                "referrer": referrer,
                "duration": duration,
                "timestamp": datetime.utcnow()
            }
            
            await conn[self.page_visits_collection].insert_one(doc)
            
            # Update session activity count
            await conn[self.sessions_collection].update_one(
                {"_id": session_id},
                {"$inc": {"activities_count": 1}}
            )
            
            return doc["_id"]
        finally:
            await conn.close()
    
    async def delete_user_history(self, user_id: str, before_date: Optional[datetime] = None) -> int:
        """
        Delete user history (for GDPR compliance).
        
        Args:
            user_id: User ID
            before_date: Delete history before this date
            
        Returns:
            Number of deleted documents
        """
        conn = await get_db_connection()
        try:
            query: Dict[str, Any] = {"user_id": user_id}
            
            if before_date:
                query["timestamp"] = {"$lt": before_date}
            
            result = await conn[self.collection].delete_many(query)
            
            # Also delete sessions and page visits
            if before_date:
                await conn[self.sessions_collection].delete_many({
                    "user_id": user_id,
                    "start_time": {"$lt": before_date}
                })
                await conn[self.page_visits_collection].delete_many({
                    "user_id": user_id,
                    "timestamp": {"$lt": before_date}
                })
            else:
                await conn[self.sessions_collection].delete_many({"user_id": user_id})
                await conn[self.page_visits_collection].delete_many({"user_id": user_id})
            
            return result.deleted_count
        finally:
            await conn.close()

    async def get_recent_activity(self, user_id: str, limit: int = 100) -> List[str]:
        """
        Get recent activity IDs from cache.
        """
        redis = await get_redis_cache()
        if not redis:
            return []
        key = f"recent_activity:{user_id}"
        # lrange start=0, end=limit-1
        result = await redis.lrange(key, 0, limit-1)
        return result or []

    async def clear_user_history(self, user_id: str) -> bool:
        """
        Clear all user history and related cache.
        """
        await self.delete_user_history(user_id)
        redis = await get_redis_cache()
        if redis:
            await redis.delete(f"recent_activity:{user_id}")
        return True

    async def get_statistics(self, user_id: str) -> HistoryStats:
        """
        Get user history statistics.
        """
        return await self.get_history_stats(user_id)
    
    def _get_category(self, activity_type: str) -> str:
        """Get category for activity type"""
        categories = {
            "login": "authentication",
            "logout": "authentication",
            "page_view": "browsing",
            "search": "search",
            "message_sent": "messaging",
            "message_received": "messaging",
            "file_upload": "files",
            "file_download": "files",
            "payment": "transactions",
            "purchase": "transactions",
            "booking": "bookings",
            "appointment": "appointments",
            "review": "engagement",
            "rating": "engagement",
            "comment": "engagement",
            "share": "engagement",
            "follow": "social",
            "unfollow": "social",
            "like": "engagement",
            "unlike": "engagement",
            "profile_update": "account",
            "settings_change": "account",
            "device_change": "security",
            "password_change": "security",
            "two_factor": "security",
            "api_call": "system"
        }
        return categories.get(activity_type, "other")
    
    def _get_title(self, doc: Dict[str, Any]) -> str:
        """Generate title for history entry"""
        activity_type = doc.get("activity_type", "")
        titles = {
            "login": "User logged in",
            "logout": "User logged out",
            "page_view": f"Viewed {doc.get('resource_type', 'page')}",
            "search": f"Searched for '{doc.get('metadata', {}).get('query', '')}'",
            "message_sent": "Sent a message",
            "message_received": "Received a message",
            "file_upload": f"Uploaded {doc.get('resource_type', 'file')}",
            "file_download": f"Downloaded {doc.get('resource_type', 'file')}",
            "payment": f"Payment of {doc.get('metadata', {}).get('amount', 'N/A')}",
            "purchase": f"Purchased {doc.get('resource_type', 'item')}",
            "booking": f"Created {doc.get('resource_type', 'booking')}",
            "appointment": f"Booked appointment",
            "review": f"Left review",
            "rating": f"Rated {doc.get('resource_type', 'item')}",
            "comment": f"Commented on {doc.get('resource_type', 'post')}",
            "share": f"Shared {doc.get('resource_type', 'content')}",
            "follow": f"Followed {doc.get('metadata', {}).get('target_user', 'user')}",
            "unfollow": f"Unfollowed user",
            "like": f"Liked {doc.get('resource_type', 'content')}",
            "unlike": f"Unliked content",
            "profile_update": "Updated profile",
            "settings_change": "Changed settings",
            "device_change": "Changed device",
            "password_change": "Changed password",
            "two_factor": "Updated 2FA settings",
            "api_call": "API request"
        }
        return titles.get(activity_type, activity_type.replace("_", " ").title())


    def _build_history_query(
        self,
        user_id: str,
        activity_types: Optional[List[ActivityType]],
        start_date: Optional[datetime],
        end_date: Optional[datetime],
        resource_type: Optional[str],
        resource_id: Optional[str]
    ) -> Dict[str, Any]:
        query: Dict[str, Any] = {"user_id": user_id}
        if activity_types:
            query["activity_type"] = {"$in": [at.value for at in activity_types]}
        if start_date or end_date:
            query["timestamp"] = {}
            if start_date:
                query["timestamp"]["$gte"] = start_date
            if end_date:
                query["timestamp"]["$lte"] = end_date
        if resource_type or resource_id:
            if resource_type:
                query["resource_type"] = resource_type
            if resource_id:
                query["resource_id"] = resource_id
        return query

    def _doc_to_history_entry(self, doc: Dict) -> HistoryEntry:
        return HistoryEntry(
            id=doc["_id"],
            user_id=doc["user_id"],
            activity_type=ActivityType(doc["activity_type"]),
            category=self._get_category(doc["activity_type"]),
            title=self._get_title(doc),
            description=doc["description"],
            resource_type=doc.get("resource_type"),
            resource_id=doc.get("resource_id"),
            metadata=doc.get("metadata"),
            timestamp=doc["timestamp"],
            duration=doc.get("duration"),
            location=doc.get("location"),
            device_type=DeviceType(doc["device_type"]) if doc.get("device_type") else None
        )

    async def _get_total_activities(self, conn, user_id: str) -> int:
        return await conn[self.collection].count_documents({"user_id": user_id})

    async def _get_activities_by_type(self, conn, user_id: str) -> Dict[str, int]:
        type_cursor = conn[self.collection].aggregate([
            {"$match": {"user_id": user_id}},
            {"$group": {"_id": "$activity_type", "count": {"$sum": 1}}}
        ])
        activities_by_type: Dict[str, int] = {}
        async for doc in type_cursor:
            activities_by_type[doc["_id"]] = doc["count"]
        return activities_by_type

    async def _get_most_active_day(self, conn, user_id: str) -> Optional[datetime]:
        day_cursor = conn[self.collection].aggregate([
            {"$match": {"user_id": user_id}},
            {"$group": {
                "_id": {"$dateToString": {"format": "%Y-%m-%d", "date": "$timestamp"}},
                "count": {"$sum": 1}
            }},
            {"$sort": {"count": -1}},
            {"$limit": 1}
        ])
        most_active_day = None
        async for doc in day_cursor:
            most_active_day = datetime.fromisoformat(doc["_id"])
        return most_active_day

    async def _get_average_session_duration(self, conn, user_id: str) -> float:
        sessions = await conn[self.sessions_collection].find(
            {"user_id": user_id, "end_time": {"$exists": True}}
        ).to_list(length=100)
        avg_duration = 0.0
        if sessions:
            durations = [
                (s["end_time"] - s["start_time"]).total_seconds()
                for s in sessions if s.get("end_time")
            ]
            avg_duration = sum(durations) / len(durations) if durations else 0
        return avg_duration

    async def _get_top_locations(self, conn, user_id: str) -> List[Dict]:
        location_cursor = conn[self.collection].aggregate([
            {"$match": {"user_id": user_id, "location": {"$exists": True, "$ne": None}}},
            {"$group": {"_id": "$location", "count": {"$sum": 1}}},
            {"$sort": {"count": -1}},
            {"$limit": 5}
        ])
        top_locations = []
        async for doc in location_cursor:
            top_locations.append({"location": doc["_id"], "count": doc["count"]})
        return top_locations

    async def _get_recent_activity_types(self, conn, user_id: str) -> List[Dict]:
        recent_cursor = conn[self.collection].aggregate([
            {"$match": {"user_id": user_id}},
            {"$sort": {"timestamp": -1}},
            {"$limit": 100},
            {"$group": {
                "_id": "$activity_type",
                "last_occurrence": {"$max": "$timestamp"},
                "count_in_recent": {"$sum": 1}
            }},
            {"$sort": {"last_occurrence": -1}}
        ])
        recent_activity_types = []
        async for doc in recent_cursor:
            recent_activity_types.append({
                "type": doc["_id"],
                "last_occurrence": doc["last_occurrence"],
                "count_in_recent": doc["count_in_recent"]
            })
        return recent_activity_types

# Singleton instance
history_service = HistoryService()
