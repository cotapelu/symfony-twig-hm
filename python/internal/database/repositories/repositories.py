"""
Repository implementations for Python schema models.
Provides comprehensive database operations for analytics, ML, chatbot, and image recognition.
"""

from typing import Optional, List
from uuid import UUID
from datetime import datetime
from sqlalchemy.orm import Session
from sqlalchemy import and_, or_, func

from models.optimized_schema import (
    AnalyticsEvent, AnalyticsAggregate,
    MLModel, MLInference,
    Recommendation,
    ChatbotConversation, ChatbotInteraction,
    ImageRecognitionResult
)


class AnalyticsEventRepository:
    """Repository for analytics events with advanced querying capabilities."""
    
    def __init__(self, session: Session):
        self.session = session
    
    def create(self, event: AnalyticsEvent) -> AnalyticsEvent:
        """Create a new analytics event."""
        self.session.add(event)
        self.session.commit()
        self.session.refresh(event)
        return event
    
    def get_by_id(self, event_id: UUID) -> Optional[AnalyticsEvent]:
        """Get event by ID."""
        return self.session.query(AnalyticsEvent).filter(
            AnalyticsEvent.id == event_id
        ).first()
    
    def get_by_user(self, user_id: UUID, limit: int = 100) -> List[AnalyticsEvent]:
        """Get events for a specific user."""
        return self.session.query(AnalyticsEvent).filter(
            AnalyticsEvent.user_id == user_id
        ).order_by(AnalyticsEvent.timestamp.desc()).limit(limit).all()
    
    def get_by_type(self, event_type: str, limit: int = 100) -> List[AnalyticsEvent]:
        """Get events by type."""
        return self.session.query(AnalyticsEvent).filter(
            AnalyticsEvent.event_type == event_type
        ).order_by(AnalyticsEvent.timestamp.desc()).limit(limit).all()
    
    def get_by_user_and_type(self, user_id: UUID, event_type: str, limit: int = 100) -> List[AnalyticsEvent]:
        """Get events for a user by type."""
        return self.session.query(AnalyticsEvent).filter(
            and_(
                AnalyticsEvent.user_id == user_id,
                AnalyticsEvent.event_type == event_type
            )
        ).order_by(AnalyticsEvent.timestamp.desc()).limit(limit).all()
    
    def get_by_date_range(self, start: datetime, end: datetime, limit: int = 1000) -> List[AnalyticsEvent]:
        """Get events within a date range."""
        return self.session.query(AnalyticsEvent).filter(
            and_(
                AnalyticsEvent.timestamp >= start,
                AnalyticsEvent.timestamp <= end
            )
        ).order_by(AnalyticsEvent.timestamp.desc()).limit(limit).all()
    
    def count_by_type(self, event_type: str) -> int:
        """Count events by type."""
        return self.session.query(func.count(AnalyticsEvent.id)).filter(
            AnalyticsEvent.event_type == event_type
        ).scalar()
    
    def count_by_user(self, user_id: UUID) -> int:
        """Count events for a user."""
        return self.session.query(func.count(AnalyticsEvent.id)).filter(
            AnalyticsEvent.user_id == user_id
        ).scalar()
    
    def delete_old_events(self, before_date: datetime) -> int:
        """Delete events older than a specific date."""
        count = self.session.query(AnalyticsEvent).filter(
            AnalyticsEvent.timestamp < before_date
        ).delete()
        self.session.commit()
        return count


class AnalyticsAggregateRepository:
    """Repository for pre-aggregated analytics data."""
    
    def __init__(self, session: Session):
        self.session = session
    
    def create(self, aggregate: AnalyticsAggregate) -> AnalyticsAggregate:
        """Create a new aggregate."""
        self.session.add(aggregate)
        self.session.commit()
        self.session.refresh(aggregate)
        return aggregate
    
    def get_by_id(self, aggregate_id: UUID) -> Optional[AnalyticsAggregate]:
        """Get aggregate by ID."""
        return self.session.query(AnalyticsAggregate).filter(
            AnalyticsAggregate.id == aggregate_id
        ).first()
    
    def get_by_metric_type(self, metric_type: str, limit: int = 100) -> List[AnalyticsAggregate]:
        """Get aggregates by metric type."""
        return self.session.query(AnalyticsAggregate).filter(
            AnalyticsAggregate.metric_type == metric_type
        ).order_by(AnalyticsAggregate.date.desc()).limit(limit).all()
    
    def get_by_date_range(self, metric_type: str, start: datetime, end: datetime) -> List[AnalyticsAggregate]:
        """Get aggregates for a metric type within a date range."""
        return self.session.query(AnalyticsAggregate).filter(
            and_(
                AnalyticsAggregate.metric_type == metric_type,
                AnalyticsAggregate.date >= start,
                AnalyticsAggregate.date <= end
            )
        ).order_by(AnalyticsAggregate.date.asc()).all()
    
    def get_latest_by_metric(self, metric_type: str) -> Optional[AnalyticsAggregate]:
        """Get the latest aggregate for a metric type."""
        return self.session.query(AnalyticsAggregate).filter(
            AnalyticsAggregate.metric_type == metric_type
        ).order_by(AnalyticsAggregate.date.desc()).first()
    
    def upsert(self, metric_type: str, dimensions: dict, value: int, date: datetime) -> AnalyticsAggregate:
        """Create or update an aggregate."""
        aggregate = self.session.query(AnalyticsAggregate).filter(
            and_(
                AnalyticsAggregate.metric_type == metric_type,
                AnalyticsAggregate.dimensions == dimensions,
                AnalyticsAggregate.date == date
            )
        ).first()
        
        if aggregate:
            aggregate.value = value
            aggregate.created_at = datetime.utcnow()
        else:
            aggregate = AnalyticsAggregate(
                metric_type=metric_type,
                dimensions=dimensions,
                value=value,
                date=date
            )
            self.session.add(aggregate)
        
        self.session.commit()
        self.session.refresh(aggregate)
        return aggregate


class MLModelRepository:
    """Repository for ML models."""
    
    def __init__(self, session: Session):
        self.session = session
    
    def create(self, model: MLModel) -> MLModel:
        """Create a new ML model."""
        self.session.add(model)
        self.session.commit()
        self.session.refresh(model)
        return model
    
    def get_by_id(self, model_id: UUID) -> Optional[MLModel]:
        """Get model by ID."""
        return self.session.query(MLModel).filter(
            MLModel.id == model_id
        ).first()
    
    def get_by_type(self, model_type: str) -> List[MLModel]:
        """Get models by type."""
        return self.session.query(MLModel).filter(
            MLModel.type == model_type
        ).order_by(MLModel.created_at.desc()).all()
    
    def get_active(self) -> List[MLModel]:
        """Get all active models."""
        return self.session.query(MLModel).filter(
            MLModel.status == 'active'
        ).order_by(MLModel.created_at.desc()).all()
    
    def get_active_by_type(self, model_type: str) -> Optional[MLModel]:
        """Get the active model for a specific type."""
        return self.session.query(MLModel).filter(
            and_(
                MLModel.type == model_type,
                MLModel.status == 'active'
            )
        ).order_by(MLModel.created_at.desc()).first()
    
    def update_status(self, model_id: UUID, status: str) -> Optional[MLModel]:
        """Update model status."""
        model = self.get_by_id(model_id)
        if model:
            model.status = status
            model.updated_at = datetime.utcnow()
            self.session.commit()
            self.session.refresh(model)
        return model
    
    def update_accuracy(self, model_id: UUID, accuracy: float) -> Optional[MLModel]:
        """Update model accuracy."""
        model = self.get_by_id(model_id)
        if model:
            model.accuracy = accuracy
            model.updated_at = datetime.utcnow()
            self.session.commit()
            self.session.refresh(model)
        return model


class MLInferenceRepository:
    """Repository for ML inferences."""
    
    def __init__(self, session: Session):
        self.session = session
    
    def create(self, inference: MLInference) -> MLInference:
        """Create a new inference."""
        self.session.add(inference)
        self.session.commit()
        self.session.refresh(inference)
        return inference
    
    def get_by_id(self, inference_id: UUID) -> Optional[MLInference]:
        """Get inference by ID."""
        return self.session.query(MLInference).filter(
            MLInference.id == inference_id
        ).first()
    
    def get_by_model(self, model_id: UUID, limit: int = 100) -> List[MLInference]:
        """Get inferences for a model."""
        return self.session.query(MLInference).filter(
            MLInference.model_id == model_id
        ).order_by(MLInference.created_at.desc()).limit(limit).all()
    
    def get_by_user(self, user_id: UUID, limit: int = 100) -> List[MLInference]:
        """Get inferences for a user."""
        return self.session.query(MLInference).filter(
            MLInference.user_id == user_id
        ).order_by(MLInference.created_at.desc()).limit(limit).all()
    
    def get_by_model_and_user(self, model_id: UUID, user_id: UUID, limit: int = 100) -> List[MLInference]:
        """Get inferences for a model and user."""
        return self.session.query(MLInference).filter(
            and_(
                MLInference.model_id == model_id,
                MLInference.user_id == user_id
            )
        ).order_by(MLInference.created_at.desc()).limit(limit).all()
    
    def get_average_latency(self, model_id: UUID) -> Optional[float]:
        """Get average latency for a model."""
        result = self.session.query(func.avg(MLInference.latency_ms)).filter(
            MLInference.model_id == model_id
        ).scalar()
        return float(result) if result else None
    
    def count_by_model(self, model_id: UUID) -> int:
        """Count inferences for a model."""
        return self.session.query(func.count(MLInference.id)).filter(
            MLInference.model_id == model_id
        ).scalar()


class RecommendationRepository:
    """Repository for user recommendations."""
    
    def __init__(self, session: Session):
        self.session = session
    
    def create(self, recommendation: Recommendation) -> Recommendation:
        """Create a new recommendation."""
        self.session.add(recommendation)
        self.session.commit()
        self.session.refresh(recommendation)
        return recommendation
    
    def get_by_id(self, recommendation_id: UUID) -> Optional[Recommendation]:
        """Get recommendation by ID."""
        return self.session.query(Recommendation).filter(
            Recommendation.id == recommendation_id
        ).first()
    
    def get_by_user(self, user_id: UUID, model_type: Optional[str] = None) -> List[Recommendation]:
        """Get recommendations for a user."""
        query = self.session.query(Recommendation).filter(
            Recommendation.user_id == user_id
        )
        
        if model_type:
            query = query.filter(Recommendation.model_type == model_type)
        
        return query.order_by(Recommendation.created_at.desc()).all()
    
    def get_latest(self, user_id: UUID, model_type: str) -> Optional[Recommendation]:
        """Get the latest recommendation for a user and model type."""
        return self.session.query(Recommendation).filter(
            and_(
                Recommendation.user_id == user_id,
                Recommendation.model_type == model_type
            )
        ).order_by(Recommendation.created_at.desc()).first()
    
    def upsert(self, user_id: UUID, model_type: str, items: list, score: Optional[float] = None) -> Recommendation:
        """Create or update a recommendation."""
        recommendation = self.get_latest(user_id, model_type)
        
        if recommendation:
            recommendation.items = items
            recommendation.score = score
            recommendation.created_at = datetime.utcnow()
        else:
            recommendation = Recommendation(
                user_id=user_id,
                model_type=model_type,
                items=items,
                score=score
            )
            self.session.add(recommendation)
        
        self.session.commit()
        self.session.refresh(recommendation)
        return recommendation


class ChatbotConversationRepository:
    """Repository for chatbot conversations."""
    
    def __init__(self, session: Session):
        self.session = session
    
    def create(self, conversation: ChatbotConversation) -> ChatbotConversation:
        """Create a new conversation."""
        self.session.add(conversation)
        self.session.commit()
        self.session.refresh(conversation)
        return conversation
    
    def get_by_id(self, conversation_id: UUID) -> Optional[ChatbotConversation]:
        """Get conversation by ID."""
        return self.session.query(ChatbotConversation).filter(
            ChatbotConversation.id == conversation_id
        ).first()
    
    def get_by_session_id(self, session_id: str) -> Optional[ChatbotConversation]:
        """Get conversation by session ID."""
        return self.session.query(ChatbotConversation).filter(
            ChatbotConversation.session_id == session_id
        ).first()
    
    def get_by_user(self, user_id: UUID, limit: int = 100) -> List[ChatbotConversation]:
        """Get conversations for a user."""
        return self.session.query(ChatbotConversation).filter(
            ChatbotConversation.user_id == user_id
        ).order_by(ChatbotConversation.created_at.desc()).limit(limit).all()
    
    def get_active(self, user_id: Optional[UUID] = None) -> List[ChatbotConversation]:
        """Get active conversations."""
        query = self.session.query(ChatbotConversation).filter(
            ChatbotConversation.status == 'active'
        )
        
        if user_id:
            query = query.filter(ChatbotConversation.user_id == user_id)
        
        return query.order_by(ChatbotConversation.created_at.desc()).all()
    
    def update_status(self, conversation_id: UUID, status: str) -> Optional[ChatbotConversation]:
        """Update conversation status."""
        conversation = self.get_by_id(conversation_id)
        if conversation:
            conversation.status = status
            conversation.updated_at = datetime.utcnow()
            self.session.commit()
            self.session.refresh(conversation)
        return conversation


class ChatbotInteractionRepository:
    """Repository for chatbot interactions."""
    
    def __init__(self, session: Session):
        self.session = session
    
    def create(self, interaction: ChatbotInteraction) -> ChatbotInteraction:
        """Create a new interaction."""
        self.session.add(interaction)
        self.session.commit()
        self.session.refresh(interaction)
        return interaction
    
    def get_by_id(self, interaction_id: UUID) -> Optional[ChatbotInteraction]:
        """Get interaction by ID."""
        return self.session.query(ChatbotInteraction).filter(
            ChatbotInteraction.id == interaction_id
        ).first()
    
    def get_by_conversation(self, conversation_id: UUID, limit: int = 100) -> List[ChatbotInteraction]:
        """Get interactions for a conversation."""
        return self.session.query(ChatbotInteraction).filter(
            ChatbotInteraction.conversation_id == conversation_id
        ).order_by(ChatbotInteraction.created_at.asc()).limit(limit).all()
    
    def get_by_user(self, user_id: UUID, limit: int = 100) -> List[ChatbotInteraction]:
        """Get interactions for a user."""
        return self.session.query(ChatbotInteraction).filter(
            ChatbotInteraction.user_id == user_id
        ).order_by(ChatbotInteraction.created_at.desc()).limit(limit).all()
    
    def get_by_role(self, conversation_id: UUID, role: str, limit: int = 100) -> List[ChatbotInteraction]:
        """Get interactions by role within a conversation."""
        return self.session.query(ChatbotInteraction).filter(
            and_(
                ChatbotInteraction.conversation_id == conversation_id,
                ChatbotInteraction.role == role
            )
        ).order_by(ChatbotInteraction.created_at.asc()).limit(limit).all()
    
    def count_by_conversation(self, conversation_id: UUID) -> int:
        """Count interactions in a conversation."""
        return self.session.query(func.count(ChatbotInteraction.id)).filter(
            ChatbotInteraction.conversation_id == conversation_id
        ).scalar()


class ImageRecognitionResultRepository:
    """Repository for image recognition results."""
    
    def __init__(self, session: Session):
        self.session = session
    
    def create(self, result: ImageRecognitionResult) -> ImageRecognitionResult:
        """Create a new recognition result."""
        self.session.add(result)
        self.session.commit()
        self.session.refresh(result)
        return result
    
    def get_by_id(self, result_id: UUID) -> Optional[ImageRecognitionResult]:
        """Get result by ID."""
        return self.session.query(ImageRecognitionResult).filter(
            ImageRecognitionResult.id == result_id
        ).first()
    
    def get_by_user(self, user_id: UUID, limit: int = 100) -> List[ImageRecognitionResult]:
        """Get results for a user."""
        return self.session.query(ImageRecognitionResult).filter(
            ImageRecognitionResult.user_id == user_id
        ).order_by(ImageRecognitionResult.created_at.desc()).limit(limit).all()
    
    def get_by_model_type(self, model_type: str, limit: int = 100) -> List[ImageRecognitionResult]:
        """Get results by model type."""
        return self.session.query(ImageRecognitionResult).filter(
            ImageRecognitionResult.model_type == model_type
        ).order_by(ImageRecognitionResult.created_at.desc()).limit(limit).all()
    
    def get_by_image_url(self, image_url: str) -> List[ImageRecognitionResult]:
        """Get results by image URL."""
        return self.session.query(ImageRecognitionResult).filter(
            ImageRecognitionResult.image_url == image_url
        ).order_by(ImageRecognitionResult.created_at.desc()).all()
    
    def get_average_confidence(self, model_type: str) -> Optional[float]:
        """Get average confidence for a model type."""
        result = self.session.query(func.avg(ImageRecognitionResult.confidence)).filter(
            ImageRecognitionResult.model_type == model_type
        ).scalar()
        return float(result) if result else None
    
    def get_average_processing_time(self, model_type: str) -> Optional[float]:
        """Get average processing time for a model type."""
        result = self.session.query(func.avg(ImageRecognitionResult.processing_time_ms)).filter(
            ImageRecognitionResult.model_type == model_type
        ).scalar()
        return float(result) if result else None
    
    def count_by_user(self, user_id: UUID) -> int:
        """Count results for a user."""
        return self.session.query(func.count(ImageRecognitionResult.id)).filter(
            ImageRecognitionResult.user_id == user_id
        ).scalar()


class RepositoryFactory:
    """Factory for creating repository instances."""
    
    def __init__(self, session: Session):
        self.session = session
    
    def analytics_event(self) -> AnalyticsEventRepository:
        """Create analytics event repository."""
        return AnalyticsEventRepository(self.session)
    
    def analytics_aggregate(self) -> AnalyticsAggregateRepository:
        """Create analytics aggregate repository."""
        return AnalyticsAggregateRepository(self.session)
    
    def ml_model(self) -> MLModelRepository:
        """Create ML model repository."""
        return MLModelRepository(self.session)
    
    def ml_inference(self) -> MLInferenceRepository:
        """Create ML inference repository."""
        return MLInferenceRepository(self.session)
    
    def recommendation(self) -> RecommendationRepository:
        """Create recommendation repository."""
        return RecommendationRepository(self.session)
    
    def chatbot_conversation(self) -> ChatbotConversationRepository:
        """Create chatbot conversation repository."""
        return ChatbotConversationRepository(self.session)
    
    def chatbot_interaction(self) -> ChatbotInteractionRepository:
        """Create chatbot interaction repository."""
        return ChatbotInteractionRepository(self.session)
    
    def image_recognition_result(self) -> ImageRecognitionResultRepository:
        """Create image recognition result repository."""
        return ImageRecognitionResultRepository(self.session)
