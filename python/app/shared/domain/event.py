from typing import Any, Dict, Optional
from enum import Enum
from datetime import datetime
from pydantic import BaseModel, Field
import uuid


class EventType(str, Enum):
    MESSAGE_SENT = "MessageSent"
    TYPING_START = "TypingStart"
    TYPING_STOP = "TypingStop"
    PRESENCE_CHANGE = "PresenceChange"
    NOTIFICATION_SEND = "NotificationSend"
    DELIVERY_CONFIRM = "DeliveryConfirm"
    AI_PROCESS_REQUEST = "AIProcessRequest"
    MEDIA_PROCESS_REQUEST = "MediaProcessRequest"
    AI_PROCESSED = "AIProcessed"
    MEDIA_PROCESSED = "MediaProcessed"


class DomainEvent(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    type: EventType
    version: str = "1.0"
    data: Dict[str, Any]
    timestamp: int = Field(default_factory=lambda: int(datetime.now().timestamp()))
    metadata: Optional[Dict[str, Any]] = None
    
    class Config:
        json_encoders = {
            datetime: lambda v: v.isoformat()
        }
