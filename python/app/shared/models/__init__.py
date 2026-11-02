"""Unified database models - all tables in single schema."""
from .unified import Base
from .unified import (
    # Enums
    ProductCategory, BotType, ConversationType, ContentSource, EventSource,
    # Core
    User, UserSession, Device, OAuthAccount, UserRole,
    # Notifications
    Notification, NotificationChannel, NotificationTemplate,
    # Workflow
    FeatureFlag, Workflow, WorkflowInstance,
    # Ecommerce
    Product, UserPreference, Cart, Order, OrderItem, Recommendation,
    # Social
    SocialPost, SocialComment, SocialPostLike, SocialCommentLike, SocialFollow,
    # AI & Bots
    Bot, Conversation, ConversationSummary, GeneratedContentLog, ModerationCache, MLModel, MLInference,
    # Search & Events
    SearchIndex, Event,
)

__all__ = [
    'Base',
    # Enums
    'ProductCategory', 'BotType', 'ConversationType', 'ContentSource', 'EventSource',
    # Core
    'User', 'UserSession', 'Device', 'OAuthAccount', 'UserRole',
    # Notifications
    'Notification', 'NotificationChannel', 'NotificationTemplate',
    # Workflow
    'FeatureFlag', 'Workflow', 'WorkflowInstance',
    # Ecommerce
    'Product', 'UserPreference', 'Cart', 'Order', 'OrderItem', 'Recommendation',
    # Social
    'SocialPost', 'SocialComment', 'SocialPostLike', 'SocialCommentLike', 'SocialFollow',
    # AI & Bots
    'Bot', 'Conversation', 'ConversationSummary', 'GeneratedContentLog', 'ModerationCache', 'MLModel', 'MLInference',
    # Search & Events
    'SearchIndex', 'Event',
]
