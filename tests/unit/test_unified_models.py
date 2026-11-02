"""Smoke tests for unified models - ensure they are valid SQLAlchemy models."""
import pytest
import os

os.environ.setdefault("JWT_SECRET_KEY", "test-secret")

from app.shared.models import (
    Base,
    User, UserSession, Device, OAuthAccount, UserRole,
    Notification, NotificationChannel, NotificationTemplate,
    FeatureFlag, Workflow, WorkflowInstance,
    Product, ProductCategory, UserPreference, Cart, Order, OrderItem, Recommendation,
    SocialPost, SocialComment, SocialPostLike, SocialCommentLike, SocialFollow,
    Bot, Conversation, GeneratedContentLog, ModerationCache, MLModel, MLInference,
    SearchIndex, Event,
    EventSource, ConversationType, ContentSource, BotType,
)


class TestModelImports:
    """Test all models can be imported and are correct SQLAlchemy models."""

    def test_base_is_declarative(self):
        """Base should be declarative base"""
        from sqlalchemy.orm.decl_api import DeclarativeBase
        assert isinstance(Base, DeclarativeBase) or hasattr(Base, 'metadata')

    def test_user_model(self):
        """User model has correct table name"""
        assert User.__tablename__ == 'users'
        assert hasattr(User, 'id')
        assert hasattr(User, 'email')
        assert hasattr(User, 'sessions')

    def test_event_model(self):
        """Event model has event_source enum"""
        assert Event.__tablename__ == 'events'
        assert hasattr(Event, 'event_type')
        assert hasattr(Event, 'event_source')
        # Check enum is present
        from app.shared.models.unified import EventSource
        assert EventSource.AUDIT.value == 'audit'

    def test_conversation_model(self):
        """Conversation model has type discriminator"""
        assert Conversation.__tablename__ == 'conversations'
        assert hasattr(Conversation, 'type')
        assert hasattr(Conversation, 'bot_id')
        assert hasattr(Conversation, 'messages')

    def test_social_models(self):
        """Social models have correct table names"""
        assert SocialPost.__tablename__ == 'social_feed_posts'
        assert SocialComment.__tablename__ == 'social_feed_comments'
        assert SocialPostLike.__tablename__ == 'social_feed_post_likes'
        assert SocialCommentLike.__tablename__ == 'social_feed_comment_likes'
        assert SocialFollow.__tablename__ == 'social_feed_follows'

    def test_ecommerce_models(self):
        """Ecommerce models"""
        assert Product.__tablename__ == 'products'
        assert Order.__tablename__ == 'orders'
        assert Cart.__tablename__ == 'carts'
        assert UserPreference.__tablename__ == 'user_preferences'

    def test_ai_bot_models(self):
        """AI & Bot models"""
        assert Bot.__tablename__ == 'bots'
        assert GeneratedContentLog.__tablename__ == 'generated_content_log'
        assert ModerationCache.__tablename__ == 'moderation_cache'
        assert MLModel.__tablename__ == 'ml_models'
        assert MLInference.__tablename__ == 'ml_inferences'

    def test_search_models(self):
        """Search models"""
        assert SearchIndex.__tablename__ == 'search_index'

    def test_enums_defined(self):
        """All enums are defined correctly"""
        assert ProductCategory.FOOD.value == 'food'
        assert BotType.SUPPORT.value == 'support'
        assert ConversationType.AI.value == 'ai'
        assert ContentSource.AI_GENERATION.value == 'ai_generation'
        assert EventSource.ANALYTICS.value == 'analytics'

    def test_relationships_exist(self):
        """Check key relationships are defined (no errors)"""
        # These just check that relationship attributes exist
        assert hasattr(User, 'sessions')
        assert hasattr(User, 'devices')
        assert hasattr(User, 'oauth_accounts')
        assert hasattr(User, 'roles')
        assert hasattr(Product, 'carts')
        assert hasattr(Product, 'order_items')
        assert hasattr(SocialPost, 'comments')
        assert hasattr(Conversation, 'bot')
        assert hasattr(Bot, 'conversations')
