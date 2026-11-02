"""
Settings API - FastAPI endpoints for user settings management
"""

from datetime import datetime
from typing import Dict, Any, List
from fastapi import APIRouter, Depends, HTTPException, BackgroundTasks
from fastapi.responses import JSONResponse, StreamingResponse
import json
import io

from app.core.auth import get_current_user
from app.core.database import get_db_connection

from .schemas import (
    UserSettings,
    ProfileSettings,
    NotificationSettings,
    PrivacySettings,
    AppearanceSettings,
    AccessibilitySettings,
    SecuritySettings,
    SettingsUpdateRequest,
    NotificationPreferencesUpdate,
    PrivacyUpdateRequest,
    AppearanceUpdateRequest,
    SecurityUpdateRequest,
    BlockUserRequest,
    ExportDataRequest,
    DeleteAccountRequest,
)
from .settings_service import settings_service


router = APIRouter(prefix="/api/settings", tags=["Settings"])


@router.get("/user/{user_id}", response_model=UserSettings)
async def get_user_settings(
    user_id: str,
    current_user: dict = Depends(get_current_user)
):
    """
    Get user settings.
    """
    if user_id != current_user["user_id"] and current_user.get("role") != "admin":
        raise HTTPException(status_code=403, detail="Cannot view other user's settings")
    
    return await settings_service.get_settings(user_id)


@router.put("/user/{user_id}")
async def update_user_settings(
    user_id: str,
    request: SettingsUpdateRequest,
    current_user: dict = Depends(get_current_user)
):
    """
    Update user settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot update other user's settings")
    
    settings = await settings_service.update_settings(
        user_id,
        request.settings,
        request.section
    )
    
    return {"status": "success", "settings": settings}


@router.get("/user/{user_id}/profile", response_model=ProfileSettings)
async def get_profile_settings(
    user_id: str,
    current_user: dict = Depends(get_current_user)
):
    """
    Get profile settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot view other user's profile settings")
    
    settings = await settings_service.get_settings(user_id)
    return settings.profile


@router.put("/user/{user_id}/profile")
async def update_profile_settings(
    user_id: str,
    updates: Dict[str, Any],
    current_user: dict = Depends(get_current_user)
):
    """
    Update profile settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot update other user's profile")
    
    profile = await settings_service.update_profile(user_id, updates)
    return {"status": "success", "profile": profile}


@router.get("/user/{user_id}/notifications", response_model=NotificationSettings)
async def get_notification_settings(
    user_id: str,
    current_user: dict = Depends(get_current_user)
):
    """
    Get notification settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot view other user's notifications")
    
    settings = await settings_service.get_settings(user_id)
    return settings.notifications


@router.put("/user/{user_id}/notifications")
async def update_notification_settings(
    user_id: str,
    updates: Dict[str, Any],
    current_user: dict = Depends(get_current_user)
):
    """
    Update notification settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot update other user's notifications")
    
    notifications = await settings_service.update_notifications(user_id, updates)
    return {"status": "success", "notifications": notifications}


@router.get("/user/{user_id}/privacy", response_model=PrivacySettings)
async def get_privacy_settings(
    user_id: str,
    current_user: dict = Depends(get_current_user)
):
    """
    Get privacy settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot view other user's privacy settings")
    
    settings = await settings_service.get_settings(user_id)
    return settings.privacy


@router.put("/user/{user_id}/privacy")
async def update_privacy_settings(
    user_id: str,
    updates: Dict[str, Any],
    current_user: dict = Depends(get_current_user)
):
    """
    Update privacy settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot update other user's privacy")
    
    privacy = await settings_service.update_privacy(user_id, updates)
    return {"status": "success", "privacy": privacy}


@router.get("/user/{user_id}/appearance", response_model=AppearanceSettings)
async def get_appearance_settings(
    user_id: str,
    current_user: dict = Depends(get_current_user)
):
    """
    Get appearance settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot view other user's appearance settings")
    
    settings = await settings_service.get_settings(user_id)
    return settings.appearance


@router.put("/user/{user_id}/appearance")
async def update_appearance_settings(
    user_id: str,
    updates: Dict[str, Any],
    current_user: dict = Depends(get_current_user)
):
    """
    Update appearance settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot update other user's appearance")
    
    appearance = await settings_service.update_appearance(user_id, updates)
    return {"status": "success", "appearance": appearance}


@router.get("/user/{user_id}/security", response_model=SecuritySettings)
async def get_security_settings(
    user_id: str,
    current_user: dict = Depends(get_current_user)
):
    """
    Get security settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot view other user's security settings")
    
    settings = await settings_service.get_settings(user_id)
    return settings.security


@router.put("/user/{user_id}/security")
async def update_security_settings(
    user_id: str,
    updates: Dict[str, Any],
    current_user: dict = Depends(get_current_user)
):
    """
    Update security settings.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot update other user's security")
    
    security = await settings_service.update_security(user_id, updates)
    return {"status": "success", "security": security}


@router.post("/user/{user_id}/block")
async def block_user(
    user_id: str,
    request: BlockUserRequest,
    current_user: dict = Depends(get_current_user)
):
    """
    Block or unblock a user.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot modify other user's block list")
    
    privacy = await settings_service.block_user(user_id, request)
    return {"status": "success", "privacy": privacy}


@router.get("/user/{user_id}/export")
async def export_user_data(
    user_id: str,
    include_settings: bool = True,
    include_history: bool = False,
    format: str = "json",
    current_user: dict = Depends(get_current_user)
):
    """
    Export user data (GDPR).
    """
    if user_id != current_user["user_id"] and current_user.get("role") != "admin":
        raise HTTPException(status_code=403, detail="Cannot export other user's data")
    
    request = ExportDataRequest(
        include_settings=include_settings,
        include_history=include_history,
        format=format
    )
    
    data = await settings_service.export_user_data(user_id, request)
    
    if format == "json":
        return JSONResponse(content=data)
    else:
        # CSV export would go here
        return JSONResponse(content=data)


@router.delete("/user/{user_id}")
async def delete_account(
    user_id: str,
    request: DeleteAccountRequest,
    background_tasks: BackgroundTasks,
    current_user: dict = Depends(get_current_user)
):
    """
    Delete user account (GDPR right to erasure).
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot delete other user's account")
    
    success = await settings_service.delete_account(user_id, request)
    
    if not success:
        raise HTTPException(status_code=400, detail="Invalid confirmation")
    
    return {"status": "success", "message": "Account deleted"}


@router.post("/user/{user_id}/reset")
async def reset_to_defaults(
    user_id: str,
    current_user: dict = Depends(get_current_user)
):
    """
    Reset settings to defaults.
    """
    if user_id != current_user["user_id"]:
        raise HTTPException(status_code=403, detail="Cannot reset other user's settings")
    
    settings = await settings_service.reset_to_defaults(user_id)
    return {"status": "success", "settings": settings}
