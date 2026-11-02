"""
History API - FastAPI endpoints for user history management
"""

from datetime import datetime
from typing import Optional, List
from fastapi import APIRouter, Depends, HTTPException, Header

from app.core.auth import get_current_user
from app.core.database import get_db_connection

from .schemas import (
    ActivityCreate,
    ActivityResponse,
    HistoryEntry,
    HistorySearchRequest,
    HistoryStats,
    SessionSummary,
    ActivityType,
    DeviceType,
)
from .history_service import history_service


router = APIRouter(prefix="/api/history", tags=["History"])


@router.post("/activities", response_model=ActivityResponse)
async def log_activity(
    activity: ActivityCreate,
    current_user: dict = Depends(get_current_user)
):
    """
    Log a new user activity.
    """
    # Ensure user can only log activities for themselves
    if activity.user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot log activities for other users")
    
    return await history_service.log_activity(activity)


@router.get("/user/{user_id}", response_model=List[HistoryEntry])
async def get_user_history(
    user_id: str,
    activity_type: Optional[List[ActivityType]] = None,
    start_date: Optional[datetime] = None,
    end_date: Optional[datetime] = None,
    resource_type: Optional[str] = None,
    resource_id: Optional[str] = None,
    limit: int = 50,
    offset: int = 0,
    current_user: dict = Depends(get_current_user)
):
    """
    Get user history with filters.
    """
    # Users can only view their own history unless admin
    if user_id != current_user["user_id"] and current_user.get("role") != "admin":
        raise HTTPException(status_code=403, detail="Cannot view other user's history")
    
    return await history_service.get_user_history(
        user_id=user_id,
        activity_types=activity_type,
        start_date=start_date,
        end_date=end_date,
        resource_type=resource_type,
        resource_id=resource_id,
        limit=limit,
        offset=offset
    )


@router.get("/user/{user_id}/stats", response_model=HistoryStats)
async def get_history_stats(
    user_id: str,
    current_user: dict = Depends(get_current_user)
):
    """
    Get statistics for user's history.
    """
    if user_id != current_user["user_id"] and current_user.get("role") != "admin":
        raise HTTPException(status_code=403, detail="Cannot view other user's history stats")
    
    return await history_service.get_history_stats(user_id)


@router.get("/user/{user_id}/sessions", response_model=List[SessionSummary])
async def get_user_sessions(
    user_id: str,
    limit: int = 20,
    offset: int = 0,
    current_user: dict = Depends(get_current_user)
):
    """
    Get user's session history.
    """
    if user_id != current_user["user_id"] and current_user.get("role") != "admin":
        raise HTTPException(status_code=403, detail="Cannot view other user's sessions")
    
    return await history_service.get_user_sessions(user_id, limit, offset)


@router.post("/sessions", response_model=SessionSummary)
async def create_session(
    device_type: DeviceType,
    x_forwarded_for: Optional[str] = Header(None),
    current_user: dict = Depends(get_current_user)
):
    """
    Create a new user session.
    """
    ip_address = x_forwarded_for.split(",")[0].strip() if x_forwarded_for else "unknown"
    
    return await history_service.create_session(
        user_id=current_user["user_id"],
        device_type=device_type,
        ip_address=ip_address
    )


@router.post("/sessions/{session_id}/end")
async def end_session(
    session_id: str,
    current_user: dict = Depends(get_current_user)
):
    """
    End a user session.
    """
    success = await history_service.end_session(session_id)
    if not success:
        raise HTTPException(status_code=404, detail="Session not found or already ended")
    return {"status": "success"}


@router.post("/page-visit")
async def log_page_visit(
    session_id: str,
    path: str,
    title: Optional[str] = None,
    referrer: Optional[str] = None,
    duration: int = 0,
    current_user: dict = Depends(get_current_user)
):
    """
    Log a page visit.
    """
    return {"id": await history_service.log_page_visit(
        session_id=session_id,
        user_id=current_user["user_id"],
        path=path,
        title=title,
        referrer=referrer,
        duration=duration
    )}


@router.delete("/user/{user_id}")
async def delete_user_history(
    user_id: str,
    before_date: Optional[datetime] = None,
    current_user: dict = Depends(get_current_user)
):
    """
    Delete user history (for GDPR compliance).
    """
    if user_id != current_user["user_id"] and current_user.get("role") != "admin":
        raise HTTPException(status_code=403, detail="Cannot delete other user's history")
    
    count = await history_service.delete_user_history(user_id, before_date)
    return {"deleted_count": count}


# Admin endpoints
@router.get("/admin/all", response_model=List[HistoryEntry])
async def get_all_history(
    user_id: Optional[str] = None,
    activity_type: Optional[List[ActivityType]] = None,
    start_date: Optional[datetime] = None,
    end_date: Optional[datetime] = None,
    limit: int = 100,
    offset: int = 0,
    current_user: dict = Depends(get_current_user)
):
    """
    Get all history entries (admin only).
    """
    if current_user.get("role") != "admin":
        raise HTTPException(status_code=403, detail="Admin access required")
    
    conn = await get_db_connection()
    try:
        query = {}
        if user_id:
            query["user_id"] = user_id
        if activity_type:
            query["activity_type"] = {"$in": [at.value for at in activity_type]}
        if start_date or end_date:
            query["timestamp"] = {}
            if start_date:
                query["timestamp"]["$gte"] = start_date
            if end_date:
                query["timestamp"]["$lte"] = end_date
        
        cursor = conn["user_history"].find(query).sort(
            "timestamp", -1
        ).skip(offset).limit(limit)
        
        entries = []
        async for doc in cursor:
            entries.append(HistoryEntry(
                id=doc["_id"],
                user_id=doc["user_id"],
                activity_type=ActivityType(doc["activity_type"]),
                category="N/A",
                title=doc.get("description", "")[:50],
                description=doc.get("description", ""),
                resource_type=doc.get("resource_type"),
                resource_id=doc.get("resource_id"),
                metadata=doc.get("metadata"),
                timestamp=doc["timestamp"],
                location=doc.get("location"),
                device_type=DeviceType(doc["device_type"]) if doc.get("device_type") else None
            ))
        
        return entries
    finally:
        await conn.close()
