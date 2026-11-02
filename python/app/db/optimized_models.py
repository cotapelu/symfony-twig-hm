"""
Optimized Python Database Models - Schema: python
Reduces from ~17 tables to 8 tables using JSONB and shared tables
"""

from datetime import datetime
from typing import Optional, List, Dict, Any
from sqlalchemy import (
    Column, String, Integer, Float, DateTime, Boolean, Text, 
    Index, UniqueConstraint, ForeignKey, JSON
)
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import relationship
from sqlalchemy.dialects.postgresql import JSONB

Base = declarative_base()


class AnalyticsEvent(Base):
    """
    Unified analytics events - replaces multiple event tables
    """
    __tablename__ = 'python.analytics_events'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(String(36), nullable=True, index=True)
    event_type = Column(String(50), nullable=False, index=True)
    properties = Column(JSONB, default={})
    timestamp = Column(DateTime, default=datetime.utcnow, index=True)
    
    __table_args__ = (
        Index('idx_analytics_events_user_type', 'user_id', 'event_type'),
    )
    
    def __repr__(self):
        return f"<AnalyticsEvent(id={self.id}, type={self.event_type}, user={self.user_id})>"


class AnalyticsAggregate(Base):
    """
    Pre-aggregated analytics metrics
    """
    __tablename__ = 'python.analytics_aggregates'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    metric_type = Column(String(50), nullable=False, index=True)
    dimensions = Column(JSONB, default={})
    value = Column(Float, nullable=False)
    date = Column(DateTime, nullable=False, index=True)
    created_at = Column(DateTime, default=datetime.utcnow)
    
    __table_args__ = (
        Index('idx_analytics_aggregates_metric_date', 'metric_type', 'date'),
    )
    
    def __repr__(self):
        return f"<AnalyticsAggregate(type={self.metric_type}, date={self.date})>"


class MLModel(Base):
    """
    ML Model registry
    """
    __tablename__ = 'python.ml_models'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(100), nullable=False)
    type = Column(String(50), nullable=False, index=True)  # RECOMMENDATION, CLASSIFICATION, NLP
    version = Column(String(20), nullable=False)
    accuracy = Column(Float, nullable=True)
    status = Column(String(20), default='TRAINING', index=True)  # TRAINING, DEPLOYED, ARCHIVED
    config = Column(JSONB, default={})
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    __table_args__ = (
        UniqueConstraint('name', 'version', name='uq_ml_model_name_version'),
    )
    
    def __repr__(self):
        return f"<MLModel(name={self.name}, version={self.version}, status={self.status})>"


class MLInference(Base):
    """
    ML Inference logs
    """
    __tablename__ = 'python.ml_inferences'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    model_id = Column(Integer, ForeignKey('python.ml_models.id'), nullable=False, index=True)
    user_id = Column(String(36), nullable=True, index=True)
    input_data = Column(JSONB, default={})
    output_data = Column(JSONB, default={})
    latency_ms = Column(Integer, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow, index=True)
    
    model = relationship("MLModel")
    
    __table_args__ = (
        Index('idx_ml_inferences_model_created', 'model_id', 'created_at'),
    )
    
    def __repr__(self):
        return f"<MLInference(model_id={self.model_id}, latency={self.latency_ms}ms)>"


class Recommendation(Base):
    """
    User recommendations - unified for all types
    """
    __tablename__ = 'python.recommendations'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(String(36), nullable=False, index=True)
    model_type = Column(String(50), nullable=False, index=True)  # PRODUCT, CONTENT, USER
    items = Column(JSONB, default=[])  # List of {id, score, metadata}
    score = Column(Float, nullable=True)  # Average score
    created_at = Column(DateTime, default=datetime.utcnow, index=True)
    expires_at = Column(DateTime, nullable=True)
    
    __table_args__ = (
        Index('idx_recommendations_user_model', 'user_id', 'model_type', 'created_at'),
    )
    
    def __repr__(self):
        return f"<Recommendation(user_id={self.user_id}, model_type={self.model_type})>"


class ChatbotConversation(Base):
    """
    Chatbot conversations
    """
    __tablename__ = 'python.chatbot_conversations'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(String(36), nullable=False, index=True)
    status = Column(String(20), default='ACTIVE', index=True)  # ACTIVE, COMPLETED
    context = Column(JSONB, default={})
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def __repr__(self):
        return f"<ChatbotConversation(id={self.id}, user_id={self.user_id})>"


class ChatbotInteraction(Base):
    """
    Chatbot user interactions
    """
    __tablename__ = 'python.chatbot_interactions'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    conversation_id = Column(Integer, ForeignKey('python.chatbot_conversations.id'), nullable=False)
    user_id = Column(String(36), nullable=False, index=True)
    message = Column(Text, nullable=False)
    response = Column(Text, nullable=True)
    intent = Column(String(50), nullable=True)
    confidence = Column(Float, nullable=True)
    metadata = Column(JSONB, default={})
    created_at = Column(DateTime, default=datetime.utcnow, index=True)
    
    conversation = relationship("ChatbotConversation")
    
    __table_args__ = (
        Index('idx_chatbot_interactions_conv', 'conversation_id', 'created_at'),
    )
    
    def __repr__(self):
        return f"<ChatbotInteraction(id={self.id}, intent={self.intent})>"


class ImageRecognitionResult(Base):
    """
    Image recognition results
    """
    __tablename__ = 'python.image_recognition_results'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(String(36), nullable=True, index=True)
    image_url = Column(Text, nullable=False)
    labels = Column(JSONB, default=[])  # [{label, confidence}]
    objects = Column(JSONB, default=[])  # [{class, bbox, confidence}]
    metadata = Column(JSONB, default={})
    created_at = Column(DateTime, default=datetime.utcnow)
    
    def __repr__(self):
        return f"<ImageRecognitionResult(id={self.id}, labels={len(self.labels)})>"


# Repository interfaces
class AnalyticsRepository:
    """Analytics data access"""
    
    def __init__(self, session):
        self.session = session
    
    def create_event(self, user_id: str, event_type: str, properties: Dict) -> AnalyticsEvent:
        event = AnalyticsEvent(
            user_id=user_id,
            event_type=event_type,
            properties=properties
        )
        self.session.add(event)
        self.session.commit()
        return event
    
    def get_user_events(self, user_id: str, event_type: str = None, limit: int = 100) -> List[AnalyticsEvent]:
        query = self.session.query(AnalyticsEvent).filter(AnalyticsEvent.user_id == user_id)
        if event_type:
            query = query.filter(AnalyticsEvent.event_type == event_type)
        return query.order_by(AnalyticsEvent.timestamp.desc()).limit(limit).all()
    
    def aggregate_daily(self, metric_type: str, date: datetime) -> AnalyticsAggregate:
        return self.session.query(AnalyticsAggregate).filter(
            AnalyticsAggregate.metric_type == metric_type,
            AnalyticsAggregate.date == date
        ).first()


class MLRepository:
    """ML model and inference data access"""
    
    def __init__(self, session):
        self.session = session
    
    def create_model(self, name: str, model_type: str, version: str, config: Dict) -> MLModel:
        model = MLModel(
            name=name,
            type=model_type,
            version=version,
            config=config
        )
        self.session.add(model)
        self.session.commit()
        return model
    
    def log_inference(self, model_id: int, user_id: str, input_data: Dict, 
                     output_data: Dict, latency_ms: int) -> MLInference:
        inference = MLInference(
            model_id=model_id,
            user_id=user_id,
            input_data=input_data,
            output_data=output_data,
            latency_ms=latency_ms
        )
        self.session.add(inference)
        self.session.commit()
        return inference
    
    def get_deployed_models(self, model_type: str = None) -> List[MLModel]:
        query = self.session.query(MLModel).filter(MLModel.status == 'DEPLOYED')
        if model_type:
            query = query.filter(MLModel.type == model_type)
        return query.all()


class RecommendationRepository:
    """Recommendation data access"""
    
    def __init__(self, session):
        self.session = session
    
    def create_recommendation(self, user_id: str, model_type: str, 
                             items: List[Dict], score: float = None) -> Recommendation:
        rec = Recommendation(
            user_id=user_id,
            model_type=model_type,
            items=items,
            score=score
        )
        self.session.add(rec)
        self.session.commit()
        return rec
    
    def get_latest(self, user_id: str, model_type: str) -> Recommendation:
        return self.session.query(Recommendation).filter(
            Recommendation.user_id == user_id,
            Recommendation.model_type == model_type
        ).order_by(Recommendation.created_at.desc()).first()


# SQL Indexes for optimization
INDEXES = [
    "CREATE INDEX IF NOT EXISTS idx_analytics_events_user_type ON python.analytics_events(user_id, event_type);",
    "CREATE INDEX IF NOT EXISTS idx_analytics_events_timestamp ON python.analytics_events(timestamp DESC);",
    "CREATE INDEX IF NOT EXISTS idx_analytics_aggregates_metric_date ON python.analytics_aggregates(metric_type, date);",
    "CREATE INDEX IF NOT EXISTS idx_ml_models_type_status ON python.ml_models(type, status);",
    "CREATE INDEX IF NOT EXISTS idx_ml_inferences_model_created ON python.ml_inferences(model_id, created_at DESC);",
    "CREATE INDEX IF NOT EXISTS idx_recommendations_user_model ON python.recommendations(user_id, model_type, created_at DESC);",
    "CREATE INDEX IF NOT EXISTS idx_chatbot_interactions_created ON python.chatbot_interactions(created_at DESC);",
]