"""
Database Models for Python Service
Optimized schema with 8 tables for Analytics & ML
"""

from datetime import datetime
from typing import Optional, List, Dict, Any
from sqlalchemy import Column, String, Integer, DateTime, Text, JSON, Float, Index
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import relationship

Base = declarative_base()


# Analytics & ML Tables

class AnalyticsEvent(Base):
    """Analytics event tracking"""
    __tablename__ = 'analytics_events'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(String(36), nullable=True, index=True)
    event_type = Column(String(100), nullable=False, index=True)
    properties = Column(JSON, nullable=True)
    timestamp = Column(DateTime, nullable=False, index=True)
    
    __table_args__ = (
        Index('idx_analytics_events_user_type', 'user_id', 'event_type'),
        Index('idx_analytics_events_timestamp', 'timestamp'),
    )
    
    def to_dict(self) -> Dict[str, Any]:
        return {
            'id': self.id,
            'user_id': self.user_id,
            'event_type': self.event_type,
            'properties': self.properties,
            'timestamp': self.timestamp.isoformat() if self.timestamp else None,
        }


class AnalyticsAggregate(Base):
    """Analytics aggregated data"""
    __tablename__ = 'analytics_aggregates'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    metric_type = Column(String(100), nullable=False, index=True)
    dimensions = Column(JSON, nullable=True)
    value = Column(Float, nullable=False)
    date = Column(DateTime, nullable=False, index=True)
    
    __table_args__ = (
        Index('idx_analytics_aggregates_metric_date', 'metric_type', 'date'),
    )
    
    def to_dict(self) -> Dict[str, Any]:
        return {
            'id': self.id,
            'metric_type': self.metric_type,
            'dimensions': self.dimensions,
            'value': self.value,
            'date': self.date.isoformat() if self.date else None,
        }


# AI/ML Tables

class MLModel(Base):
    """Machine learning model"""
    __tablename__ = 'ml_models'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(255), nullable=False)
    type = Column(String(100), nullable=False, index=True)
    version = Column(String(50), nullable=False)
    accuracy = Column(Float, nullable=True)
    status = Column(String(50), nullable=False, index=True)
    config = Column(JSON, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)
    
    __table_args__ = (
        Index('idx_ml_models_type_status', 'type', 'status'),
    )
    
    def to_dict(self) -> Dict[str, Any]:
        return {
            'id': self.id,
            'name': self.name,
            'type': self.type,
            'version': self.version,
            'accuracy': self.accuracy,
            'status': self.status,
            'config': self.config,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None,
        }


class MLInference(Base):
    """Machine learning inference record"""
    __tablename__ = 'ml_inferences'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    model_id = Column(Integer, nullable=False, index=True)
    user_id = Column(String(36), nullable=True, index=True)
    input_data = Column(JSON, nullable=True)
    output_data = Column(JSON, nullable=True)
    latency_ms = Column(Integer, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False, index=True)
    
    __table_args__ = (
        Index('idx_ml_inferences_model_user', 'model_id', 'user_id'),
        Index('idx_ml_inferences_created', 'created_at'),
    )
    
    def to_dict(self) -> Dict[str, Any]:
        return {
            'id': self.id,
            'model_id': self.model_id,
            'user_id': self.user_id,
            'input_data': self.input_data,
            'output_data': self.output_data,
            'latency_ms': self.latency_ms,
            'created_at': self.created_at.isoformat() if self.created_at else None,
        }


# Recommendations

class Recommendation(Base):
    """Recommendation for users"""
    __tablename__ = 'recommendations'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(String(36), nullable=False, index=True)
    model_type = Column(String(100), nullable=False, index=True)
    items = Column(JSON, nullable=True)
    score = Column(Float, nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False, index=True)
    
    __table_args__ = (
        Index('idx_recommendations_user_model', 'user_id', 'model_type'),
        Index('idx_recommendations_created', 'created_at'),
    )
    
    def to_dict(self) -> Dict[str, Any]:
        return {
            'id': self.id,
            'user_id': self.user_id,
            'model_type': self.model_type,
            'items': self.items,
            'score': self.score,
            'created_at': self.created_at.isoformat() if self.created_at else None,
        }


# Database operations

class DatabaseManager:
    """Database manager for Python service"""
    
    def __init__(self, session):
        self.session = session
    
    # Analytics operations
    
    def create_analytics_event(self, user_id: Optional[str], event_type: str, 
                                properties: Optional[Dict[str, Any]]) -> AnalyticsEvent:
        """Create a new analytics event"""
        event = AnalyticsEvent(
            user_id=user_id,
            event_type=event_type,
            properties=properties,
            timestamp=datetime.utcnow()
        )
        self.session.add(event)
        self.session.commit()
        return event
    
    def get_analytics_events_by_user(self, user_id: str, limit: int = 100) -> List[AnalyticsEvent]:
        """Get analytics events for a user"""
        return self.session.query(AnalyticsEvent)\
            .filter(AnalyticsEvent.user_id == user_id)\
            .order_by(AnalyticsEvent.timestamp.desc())\
            .limit(limit)\
            .all()
    
    def get_analytics_events_by_type(self, event_type: str, limit: int = 100) -> List[AnalyticsEvent]:
        """Get analytics events by type"""
        return self.session.query(AnalyticsEvent)\
            .filter(AnalyticsEvent.event_type == event_type)\
            .order_by(AnalyticsEvent.timestamp.desc())\
            .limit(limit)\
            .all()
    
    def create_analytics_aggregate(self, metric_type: str, dimensions: Optional[Dict[str, Any]], 
                                    value: float, date: datetime) -> AnalyticsAggregate:
        """Create or update an analytics aggregate"""
        aggregate = AnalyticsAggregate(
            metric_type=metric_type,
            dimensions=dimensions,
            value=value,
            date=date
        )
        self.session.add(aggregate)
        self.session.commit()
        return aggregate
    
    def get_analytics_aggregates_by_metric(self, metric_type: str, limit: int = 100) -> List[AnalyticsAggregate]:
        """Get analytics aggregates by metric type"""
        return self.session.query(AnalyticsAggregate)\
            .filter(AnalyticsAggregate.metric_type == metric_type)\
            .order_by(AnalyticsAggregate.date.desc())\
            .limit(limit)\
            .all()
    
    # ML operations
    
    def create_ml_model(self, name: str, model_type: str, version: str, 
                        config: Optional[Dict[str, Any]]) -> MLModel:
        """Create a new ML model"""
        model = MLModel(
            name=name,
            type=model_type,
            version=version,
            status='training',
            config=config
        )
        self.session.add(model)
        self.session.commit()
        return model
    
    def get_ml_models_by_type(self, model_type: str) -> List[MLModel]:
        """Get ML models by type"""
        return self.session.query(MLModel)\
            .filter(MLModel.type == model_type)\
            .order_by(MLModel.created_at.desc())\
            .all()
    
    def get_active_ml_models(self) -> List[MLModel]:
        """Get all active ML models"""
        return self.session.query(MLModel)\
            .filter(MLModel.status == 'active')\
            .order_by(MLModel.accuracy.desc())\
            .all()
    
    def update_ml_model_status(self, model_id: int, status: str, accuracy: Optional[float] = None) -> Optional[MLModel]:
        """Update ML model status"""
        model = self.session.query(MLModel).filter(MLModel.id == model_id).first()
        if model:
            model.status = status
            if accuracy is not None:
                model.accuracy = accuracy
            model.updated_at = datetime.utcnow()
            self.session.commit()
        return model
    
    def create_ml_inference(self, model_id: int, user_id: Optional[str], 
                           input_data: Dict[str, Any], output_data: Dict[str, Any], 
                           latency_ms: int) -> MLInference:
        """Create a new ML inference record"""
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
    
    def get_ml_inferences_by_model(self, model_id: int, limit: int = 100) -> List[MLInference]:
        """Get ML inferences for a model"""
        return self.session.query(MLInference)\
            .filter(MLInference.model_id == model_id)\
            .order_by(MLInference.created_at.desc())\
            .limit(limit)\
            .all()
    
    def get_ml_inferences_by_user(self, user_id: str, limit: int = 100) -> List[MLInference]:
        """Get ML inferences for a user"""
        return self.session.query(MLInference)\
            .filter(MLInference.user_id == user_id)\
            .order_by(MLInference.created_at.desc())\
            .limit(limit)\
            .all()
    
    def get_average_inference_latency(self, model_id: int) -> Optional[float]:
        """Get average inference latency for a model"""
        result = self.session.query(MLInference.latency_ms)\
            .filter(MLInference.model_id == model_id)\
            .all()
        
        if not result:
            return None
        
        total = sum(inf.latency_ms for inf in result)
        return total / len(result)
    
    # Recommendations operations
    
    def create_recommendation(self, user_id: str, model_type: str, 
                               items: List[Dict[str, Any]], score: float) -> Recommendation:
        """Create a new recommendation"""
        recommendation = Recommendation(
            user_id=user_id,
            model_type=model_type,
            items=items,
            score=score
        )
        self.session.add(recommendation)
        self.session.commit()
        return recommendation
    
    def get_recommendations_by_user(self, user_id: str, limit: int = 10) -> List[Recommendation]:
        """Get recommendations for a user"""
        return self.session.query(Recommendation)\
            .filter(Recommendation.user_id == user_id)\
            .order_by(Recommendation.score.desc())\
            .limit(limit)\
            .all()
    
    def get_recommendations_by_model(self, model_type: str, limit: int = 100) -> List[Recommendation]:
        """Get recommendations by model type"""
        return self.session.query(Recommendation)\
            .filter(Recommendation.model_type == model_type)\
            .order_by(Recommendation.score.desc())\
            .limit(limit)\
            .all()
    
    def get_latest_recommendation(self, user_id: str, model_type: str) -> Optional[Recommendation]:
        """Get the latest recommendation for a user and model type"""
        return self.session.query(Recommendation)\
            .filter(Recommendation.user_id == user_id)\
            .filter(Recommendation.model_type == model_type)\
            .order_by(Recommendation.created_at.desc())\
            .first()
