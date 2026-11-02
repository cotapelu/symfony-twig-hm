"""Optimized Database Schema Models - Python (Analytics & ML)
Optimized from 17 tables to 8 tables (-53% reduction)

This module provides unified models for analytics and ML workloads.
"""

from datetime import datetime
from typing import Optional, Any
from uuid import UUID, uuid4
from pydantic import BaseModel, Field
from sqlalchemy import Column, String, Integer, Float, DateTime, Text, JSON, Index, UniqueConstraint, func
from sqlalchemy.dialects.postgresql import UUID as PGUUID, JSONB
from sqlalchemy.ext.declarative import declarative_base

Base = declarative_base()


# ============== SQLAlchemy Models ==============

class AnalyticsEvent(Base):
    __tablename__ = 'analytics_events'
    id = Column(PGUUID(as_uuid=True), primary_key=True, default=uuid4)
    user_id = Column(PGUUID(as_uuid=True), nullable=True, index=True)
    event_type = Column(String(50), nullable=False, index=True)
    properties = Column(JSONB, default={})
    timestamp = Column(DateTime, default=datetime.utcnow, index=True)
    __table_args__ = (Index('idx_analytics_events_user_type', 'user_id', 'event_type'),)


class AnalyticsAggregate(Base):
    __tablename__ = 'analytics_aggregates'
    id = Column(PGUUID(as_uuid=True), primary_key=True, default=uuid4)
    metric_type = Column(String(50), nullable=False, index=True)
    dimensions = Column(JSONB, default={})
    value = Column(Integer, default=0)
    date = Column(DateTime, nullable=False, index=True)
    created_at = Column(DateTime, default=datetime.utcnow)
    __table_args__ = (Index('idx_analytics_aggregates_metric_date', 'metric_type', 'date'),)


class MLModel(Base):
    __tablename__ = 'ml_models'
    id = Column(PGUUID(as_uuid=True), primary_key=True, default=uuid4)
    name = Column(String(255), nullable=False)
    model_type = Column(String(50), nullable=False, index=True)
    version = Column(String(20), nullable=True)
    accuracy = Column(Float, nullable=True)
    status = Column(String(20), default='training', index=True)
    config = Column(JSONB, default={})
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)


class MLInference(Base):
    __tablename__ = 'ml_inferences'
    id = Column(PGUUID(as_uuid=True), primary_key=True, default=uuid4)
    model_id = Column(PGUUID(as_uuid=True), nullable=True, index=True)
    user_id = Column(PGUUID(as_uuid=True), nullable=True, index=True)
    input = Column(JSONB, default={})
    output = Column(JSONB, default={})
    latency_ms = Column(Integer, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow, index=True)
    __table_args__ = (Index('idx_ml_inferences_model_user', 'model_id', 'user_id'),)


class Recommendation(Base):
    __tablename__ = 'recommendations'
    id = Column(PGUUID(as_uuid=True), primary_key=True, default=uuid4)
    user_id = Column(PGUUID(as_uuid=True), nullable=False, index=True)
    model_type = Column(String(50), nullable=False, index=True)
    items = Column(JSONB, default=[])
    score = Column(Float, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow, index=True)
    __table_args__ = (
        UniqueConstraint('user_id', 'model_type', name='uq_recommendations_user_model'),
        Index('idx_recommendations_user_created', 'user_id', 'created_at'),
    )


class ChatbotConversation(Base):
    __tablename__ = 'chatbot_conversations'
    id = Column(PGUUID(as_uuid=True), primary_key=True, default=uuid4)
    user_id = Column(PGUUID(as_uuid=True), nullable=True, index=True)
    session_id = Column(String(255), nullable=False, unique=True, index=True)
    status = Column(String(20), default='active', index=True)
    meta = Column('metadata', JSONB, default={})
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    __table_args__ = (Index('idx_chatbot_conversations_user_status', 'user_id', 'status'),)


class ChatbotInteraction(Base):
    __tablename__ = 'chatbot_interactions'
    id = Column(PGUUID(as_uuid=True), primary_key=True, default=uuid4)
    conversation_id = Column(PGUUID(as_uuid=True), nullable=False, index=True)
    user_id = Column(PGUUID(as_uuid=True), nullable=True, index=True)
    role = Column(String(20), nullable=False, index=True)
    content = Column(Text, nullable=False)
    meta = Column('metadata', JSONB, default={})
    created_at = Column(DateTime, default=datetime.utcnow, index=True)
    __table_args__ = (
        Index('idx_chatbot_interactions_conversation_created', 'conversation_id', 'created_at'),
        Index('idx_chatbot_interactions_user_created', 'user_id', 'created_at'),
    )


class ImageRecognitionResult(Base):
    __tablename__ = 'image_recognition_results'
    id = Column(PGUUID(as_uuid=True), primary_key=True, default=uuid4)
    user_id = Column(PGUUID(as_uuid=True), nullable=True, index=True)
    image_url = Column(String(500), nullable=False)
    model_type = Column(String(50), nullable=False, index=True)
    labels = Column(JSONB, default=[])
    confidence = Column(Float, nullable=True)
    processing_time_ms = Column(Integer, nullable=True)
    meta = Column('metadata', JSONB, default={})
    created_at = Column(DateTime, default=datetime.utcnow, index=True)
    __table_args__ = (
        Index('idx_image_recognition_user_model', 'user_id', 'model_type'),
        Index('idx_image_recognition_created', 'created_at'),
    )


# ============== Pydantic Schemas ==============

class AnalyticsEventCreate(BaseModel):
    user_id: Optional[UUID] = None
    event_type: str
    properties: dict = Field(default_factory=dict)
    timestamp: Optional[datetime] = None


class AnalyticsEventResponse(BaseModel):
    id: UUID
    user_id: Optional[UUID]
    event_type: str
    properties: dict
    timestamp: datetime

    class Config:
        from_attributes = True


class AnalyticsAggregateCreate(BaseModel):
    metric_type: str
    dimensions: dict = Field(default_factory=dict)
    value: int = 0
    date: datetime


class AnalyticsAggregateResponse(BaseModel):
    id: UUID
    metric_type: str
    dimensions: dict
    value: int
    date: datetime

    class Config:
        from_attributes = True


class MLModelCreate(BaseModel):
    name: str
    model_type: str
    version: Optional[str] = None
    accuracy: Optional[float] = None
    status: str = 'training'
    config: dict = Field(default_factory=dict)


class MLModelResponse(BaseModel):
    id: UUID
    name: str
    model_type: str
    version: Optional[str]
    accuracy: Optional[float]
    status: str
    config: dict
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class MLInferenceCreate(BaseModel):
    model_id: UUID
    user_id: Optional[UUID] = None
    input: dict = Field(default_factory=dict)
    output: dict = Field(default_factory=dict)
    latency_ms: Optional[int] = None


class MLInferenceResponse(BaseModel):
    id: UUID
    model_id: UUID
    user_id: Optional[UUID]
    input: dict
    output: dict
    latency_ms: Optional[int]
    created_at: datetime

    class Config:
        from_attributes = True


class RecommendationCreate(BaseModel):
    user_id: UUID
    model_type: str
    items: list = Field(default_factory=list)
    score: Optional[float] = None
    expires_at: Optional[datetime] = None


class RecommendationResponse(BaseModel):
    id: UUID
    user_id: UUID
    model_type: str
    items: list
    score: Optional[float]
    created_at: datetime
    expires_at: Optional[datetime]

    class Config:
        from_attributes = True


class ChatbotConversationCreate(BaseModel):
    user_id: Optional[UUID] = None
    session_id: str
    status: str = 'active'
    meta: dict = Field(default_factory=dict)


class ChatbotConversationResponse(BaseModel):
    id: UUID
    user_id: Optional[UUID]
    session_id: str
    status: str
    meta: dict
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class ChatbotInteractionCreate(BaseModel):
    conversation_id: UUID
    user_id: Optional[UUID] = None
    role: str
    content: str
    meta: dict = Field(default_factory=dict)


class ChatbotInteractionResponse(BaseModel):
    id: UUID
    conversation_id: UUID
    user_id: Optional[UUID]
    role: str
    content: str
    meta: dict
    created_at: datetime

    class Config:
        from_attributes = True


class ImageRecognitionResultCreate(BaseModel):
    user_id: Optional[UUID] = None
    image_url: str
    model_type: str
    labels: list = Field(default_factory=list)
    confidence: Optional[float] = None
    processing_time_ms: Optional[int] = None
    meta: dict = Field(default_factory=dict)


class ImageRecognitionResultResponse(BaseModel):
    id: UUID
    user_id: Optional[UUID]
    image_url: str
    model_type: str
    labels: list
    confidence: Optional[float]
    processing_time_ms: Optional[int]
    meta: dict
    created_at: datetime

    class Config:
        from_attributes = True


# ============== Repository Classes ==============

class AnalyticsEventRepository:
    def __init__(self, session):
        self.session = session

    def create(self, event: AnalyticsEvent) -> AnalyticsEvent:
        self.session.add(event)
        self.session.commit()
        return event

    def get_by_id(self, event_id: UUID) -> Optional[AnalyticsEvent]:
        return self.session.query(AnalyticsEvent).filter(AnalyticsEvent.id == event_id).first()

    def get_by_user(self, user_id: UUID, limit: int = 100) -> list[AnalyticsEvent]:
        return self.session.query(AnalyticsEvent).filter(AnalyticsEvent.user_id == user_id).order_by(AnalyticsEvent.timestamp.desc()).limit(limit).all()

    def get_by_type(self, event_type: str, limit: int = 100) -> list[AnalyticsEvent]:
        return self.session.query(AnalyticsEvent).filter(AnalyticsEvent.event_type == event_type).order_by(AnalyticsEvent.timestamp.desc()).limit(limit).all()

    def get_by_user_and_type(self, user_id: UUID, event_type: str, limit: int = 100) -> list[AnalyticsEvent]:
        return self.session.query(AnalyticsEvent).filter(AnalyticsEvent.user_id == user_id, AnalyticsEvent.event_type == event_type).order_by(AnalyticsEvent.timestamp.desc()).limit(limit).all()

    def count_by_user(self, user_id: UUID) -> int:
        return self.session.query(func.count(AnalyticsEvent.id)).filter(AnalyticsEvent.user_id == user_id).scalar() or 0

    def count_by_type(self, event_type: str) -> int:
        return self.session.query(func.count(AnalyticsEvent.id)).filter(AnalyticsEvent.event_type == event_type).scalar() or 0

    def delete_old_events(self, before_date: datetime) -> int:
        deleted = self.session.query(AnalyticsEvent).filter(AnalyticsEvent.timestamp < before_date).delete()
        self.session.commit()
        return deleted


class AnalyticsAggregateRepository:
    def __init__(self, session):
        self.session = session

    def create(self, agg: AnalyticsAggregate) -> AnalyticsAggregate:
        self.session.add(agg)
        self.session.commit()
        return agg

    def get_by_id(self, agg_id: UUID) -> Optional[AnalyticsAggregate]:
        return self.session.query(AnalyticsAggregate).filter(AnalyticsAggregate.id == agg_id).first()

    def get_by_metric_type(self, metric_type: str, limit: int = 100) -> list[AnalyticsAggregate]:
        return self.session.query(AnalyticsAggregate).filter(AnalyticsAggregate.metric_type == metric_type).order_by(AnalyticsAggregate.date.desc()).limit(limit).all()

    def get_by_date_range(self, metric_type: str, start: datetime, end: datetime) -> list[AnalyticsAggregate]:
        return self.session.query(AnalyticsAggregate).filter(
            AnalyticsAggregate.metric_type == metric_type,
            AnalyticsAggregate.date >= start,
            AnalyticsAggregate.date <= end
        ).order_by(AnalyticsAggregate.date.asc()).all()

    def get_latest_by_metric(self, metric_type: str) -> Optional[AnalyticsAggregate]:
        return self.session.query(AnalyticsAggregate).filter(AnalyticsAggregate.metric_type == metric_type).order_by(AnalyticsAggregate.date.desc()).first()

    def upsert(self, metric_type: str, dimensions: dict, value: int, date: datetime) -> AnalyticsAggregate:
        existing = self.session.query(AnalyticsAggregate).filter(
            AnalyticsAggregate.metric_type == metric_type,
            AnalyticsAggregate.dimensions == dimensions,
            AnalyticsAggregate.date == date
        ).first()
        if existing:
            existing.value = value
            self.session.commit()
            return existing
        agg = AnalyticsAggregate(metric_type=metric_type, dimensions=dimensions, value=value, date=date)
        self.session.add(agg)
        self.session.commit()
        return agg


class MLModelRepository:
    def __init__(self, session):
        self.session = session

    def create(self, model: MLModel) -> MLModel:
        self.session.add(model)
        self.session.commit()
        return model

    def get_by_id(self, model_id: UUID) -> Optional[MLModel]:
        return self.session.query(MLModel).filter(MLModel.id == model_id).first()

    def get_by_type(self, model_type: str) -> list[MLModel]:
        return self.session.query(MLModel).filter(MLModel.model_type == model_type).all()

    def get_active_models(self, model_type: Optional[str] = None, limit: int = 100) -> list[MLModel]:
        query = self.session.query(MLModel).filter(MLModel.status == 'deployed')
        if model_type:
            query = query.filter(MLModel.model_type == model_type)
        return query.order_by(MLModel.updated_at.desc()).limit(limit).all()

    def update_status(self, model_id: UUID, status: str) -> Optional[MLModel]:
        model = self.session.query(MLModel).filter(MLModel.id == model_id).first()
        if model:
            model.status = status
            self.session.commit()
        return model

    def update_accuracy(self, model_id: UUID, accuracy: float) -> Optional[MLModel]:
        model = self.session.query(MLModel).filter(MLModel.id == model_id).first()
        if model:
            model.accuracy = accuracy
            self.session.commit()
        return model

    def delete(self, model_id: UUID):
        model = self.session.query(MLModel).filter(MLModel.id == model_id).first()
        if model:
            self.session.delete(model)
            self.session.commit()


class MLInferenceRepository:
    def __init__(self, session):
        self.session = session

    def create(self, inference: MLInference) -> MLInference:
        self.session.add(inference)
        self.session.commit()
        return inference

    def get_by_id(self, inference_id: UUID) -> Optional[MLInference]:
        return self.session.query(MLInference).filter(MLInference.id == inference_id).first()

    def get_by_model(self, model_id: UUID, limit: int = 100) -> list[MLInference]:
        return self.session.query(MLInference).filter(MLInference.model_id == model_id).order_by(MLInference.created_at.desc()).limit(limit).all()

    def get_by_user(self, user_id: UUID, limit: int = 100) -> list[MLInference]:
        return self.session.query(MLInference).filter(MLInference.user_id == user_id).order_by(MLInference.created_at.desc()).limit(limit).all()

    def get_average_latency_for_model(self, model_id: UUID) -> Optional[float]:
        result = self.session.query(func.avg(MLInference.latency_ms)).filter(MLInference.model_id == model_id).scalar()
        return float(result) if result is not None else None


class RecommendationRepository:
    def __init__(self, session):
        self.session = session

    def create(self, rec: Recommendation) -> Recommendation:
        self.session.add(rec)
        self.session.commit()
        return rec

    def get_by_id(self, rec_id: UUID) -> Optional[Recommendation]:
        return self.session.query(Recommendation).filter(Recommendation.id == rec_id).first()

    def get_by_user(self, user_id: UUID, model_type: Optional[str] = None, limit: int = 100) -> list[Recommendation]:
        query = self.session.query(Recommendation).filter(Recommendation.user_id == user_id)
        if model_type:
            query = query.filter(Recommendation.model_type == model_type)
        return query.order_by(Recommendation.created_at.desc()).limit(limit).all()

    def get_latest_by_user(self, user_id: UUID, model_type: Optional[str] = None) -> Optional[Recommendation]:
        query = self.session.query(Recommendation).filter(Recommendation.user_id == user_id)
        if model_type:
            query = query.filter(Recommendation.model_type == model_type)
        return query.order_by(Recommendation.created_at.desc()).first()

    def delete(self, rec_id: UUID):
        rec = self.session.query(Recommendation).filter(Recommendation.id == rec_id).first()
        if rec:
            self.session.delete(rec)
            self.session.commit()


class ChatbotConversationRepository:
    def __init__(self, session):
        self.session = session

    def create(self, conv: ChatbotConversation) -> ChatbotConversation:
        self.session.add(conv)
        self.session.commit()
        return conv

    def get_by_id(self, conv_id: UUID) -> Optional[ChatbotConversation]:
        return self.session.query(ChatbotConversation).filter(ChatbotConversation.id == conv_id).first()

    def get_by_session(self, session_id: str) -> list[ChatbotConversation]:
        return self.session.query(ChatbotConversation).filter(ChatbotConversation.session_id == session_id).order_by(ChatbotConversation.created_at.desc()).all()

    def get_by_user(self, user_id: UUID, limit: int = 100) -> list[ChatbotConversation]:
        return self.session.query(ChatbotConversation).filter(ChatbotConversation.user_id == user_id).order_by(ChatbotConversation.created_at.desc()).limit(limit).all()

    def get_active(self, user_id: Optional[UUID] = None, limit: int = 100) -> list[ChatbotConversation]:
        query = self.session.query(ChatbotConversation).filter(ChatbotConversation.status == 'active')
        if user_id:
            query = query.filter(ChatbotConversation.user_id == user_id)
        return query.order_by(ChatbotConversation.created_at.desc()).limit(limit).all()

    def update_status(self, conv_id: UUID, status: str) -> Optional[ChatbotConversation]:
        conv = self.session.query(ChatbotConversation).filter(ChatbotConversation.id == conv_id).first()
        if conv:
            conv.status = status
            self.session.commit()
        return conv


class ChatbotInteractionRepository:
    def __init__(self, session):
        self.session = session

    def create(self, inter: ChatbotInteraction) -> ChatbotInteraction:
        self.session.add(inter)
        self.session.commit()
        return inter

    def get_by_id(self, inter_id: UUID) -> Optional[ChatbotInteraction]:
        return self.session.query(ChatbotInteraction).filter(ChatbotInteraction.id == inter_id).first()

    def get_by_conversation(self, conversation_id: UUID, limit: int = 100) -> list[ChatbotInteraction]:
        return self.session.query(ChatbotInteraction).filter(ChatbotInteraction.conversation_id == conversation_id).order_by(ChatbotInteraction.created_at.asc()).limit(limit).all()

    def get_by_user(self, user_id: UUID, limit: int = 100) -> list[ChatbotInteraction]:
        return self.session.query(ChatbotInteraction).filter(ChatbotInteraction.user_id == user_id).order_by(ChatbotInteraction.created_at.desc()).limit(limit).all()

    def get_by_role(self, conversation_id: UUID, role: str, limit: int = 100) -> list[ChatbotInteraction]:
        return self.session.query(ChatbotInteraction).filter(
            ChatbotInteraction.conversation_id == conversation_id,
            ChatbotInteraction.role == role
        ).order_by(ChatbotInteraction.created_at.asc()).limit(limit).all()

    def count_by_conversation(self, conversation_id: UUID) -> int:
        return self.session.query(ChatbotInteraction).filter(ChatbotInteraction.conversation_id == conversation_id).count()


class ImageRecognitionResultRepository:
    def __init__(self, session):
        self.session = session

    def create(self, result: ImageRecognitionResult) -> ImageRecognitionResult:
        self.session.add(result)
        self.session.commit()
        return result

    def get_by_id(self, result_id: UUID) -> Optional[ImageRecognitionResult]:
        return self.session.query(ImageRecognitionResult).filter(ImageRecognitionResult.id == result_id).first()

    def get_by_user(self, user_id: UUID, limit: int = 100) -> list[ImageRecognitionResult]:
        return self.session.query(ImageRecognitionResult).filter(ImageRecognitionResult.user_id == user_id).order_by(ImageRecognitionResult.created_at.desc()).limit(limit).all()

    def get_by_model_type(self, model_type: str, limit: int = 100) -> list[ImageRecognitionResult]:
        return self.session.query(ImageRecognitionResult).filter(ImageRecognitionResult.model_type == model_type).order_by(ImageRecognitionResult.created_at.desc()).limit(limit).all()

    def get_by_image_url(self, image_url: str) -> list[ImageRecognitionResult]:
        return self.session.query(ImageRecognitionResult).filter(ImageRecognitionResult.image_url == image_url).order_by(ImageRecognitionResult.created_at.desc()).all()

    def get_average_confidence_by_model(self, model_type: str) -> Optional[float]:
        result = self.session.query(func.avg(ImageRecognitionResult.confidence)).filter(ImageRecognitionResult.model_type == model_type).scalar()
        return float(result) if result is not None else None

    def get_average_processing_time_by_model(self, model_type: str) -> Optional[float]:
        result = self.session.query(func.avg(ImageRecognitionResult.processing_time_ms)).filter(ImageRecognitionResult.model_type == model_type).scalar()
        return float(result) if result is not None else None

    def count_by_user(self, user_id: UUID) -> int:
        return self.session.query(ImageRecognitionResult).filter(ImageRecognitionResult.user_id == user_id).count()
