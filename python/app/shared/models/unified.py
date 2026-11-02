"""Unified database models for entire application.
Single schema 'app' with all essential tables (~25 tables).
Optimized for simplicity and performance.
"""
import uuid
from datetime import datetime
from decimal import Decimal
from enum import Enum
import sqlalchemy as sa
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import relationship

# Single base for all tables (schema 'app')
Base = declarative_base()


# ==================== ENUMS ====================
class ProductCategory(str, Enum):
    FOOD = "food"
    DRINK = "drink"
    CLOTHES = "clothes"
    ELECTRONICS = "electronics"
    OTHER = "other"


class BotType(str, Enum):
    SUPPORT = "support"
    SALES = "sales"
    MARKETING = "marketing"
    ASSISTANT = "assistant"


class ConversationType(str, Enum):
    AI = "ai"
    BOT = "bot"
    CHATBOT = "chatbot"


class ContentSource(str, Enum):
    AI_GENERATION = "ai_generation"
    BOT_TRAINING = "bot_training"
    USER = "user"


class EventSource(str, Enum):
    AUDIT = "audit"
    ANALYTICS = "analytics"
    SEARCH = "search"
    SYSTEM = "system"


# ==================== CORE TABLES ====================
class User(Base):
    """Core user identity."""
    __tablename__ = 'users'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    email = sa.Column(sa.String(255), unique=True, nullable=False, index=True)
    username = sa.Column(sa.String(100), unique=True, nullable=False, index=True)
    password_hash = sa.Column(sa.String(255), nullable=False)
    phone = sa.Column(sa.String(50))
    is_active = sa.Column(sa.Boolean, default=True, index=True)
    is_verified = sa.Column(sa.Boolean, default=False, index=True)
    last_login_at = sa.Column(sa.DateTime)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    # Indexes are automatically created by `index=True` above; no need for explicit duplicate entries.
    __table_args__ = ()

    # Relationships
    sessions = relationship("UserSession", back_populates="user", cascade="all, delete-orphan")
    devices = relationship("Device", back_populates="user", cascade="all, delete-orphan")
    oauth_accounts = relationship("OAuthAccount", back_populates="user", cascade="all, delete-orphan")
    roles = relationship("UserRole", foreign_keys="[UserRole.user_id]", back_populates="user", cascade="all, delete-orphan")
    notifications = relationship("Notification", back_populates="user", cascade="all, delete-orphan")
    events = relationship("Event", back_populates="user", cascade="all, delete-orphan")
    workflow_instances = relationship("WorkflowInstance", back_populates="user", cascade="all, delete-orphan")


class UserSession(Base):
    """Authentication sessions (JWT tokens)."""
    __tablename__ = 'user_sessions'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), sa.ForeignKey('users.id', ondelete='CASCADE'), nullable=False, index=True)
    session_token = sa.Column(sa.String(512), unique=True, nullable=False)
    expires_at = sa.Column(sa.DateTime, nullable=False)
    ip_address = sa.Column(sa.String(45))
    user_agent = sa.Column(sa.Text)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    user = relationship("User", back_populates="sessions")


class Device(Base):
    """Push notification device tokens."""
    __tablename__ = 'devices'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), sa.ForeignKey('users.id', ondelete='CASCADE'), nullable=False)
    device_token = sa.Column(sa.String(512), unique=True, nullable=False)
    platform = sa.Column(sa.String(50), nullable=False)
    is_active = sa.Column(sa.Boolean, default=True)
    last_used_at = sa.Column(sa.DateTime)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    user = relationship("User", back_populates="devices")
    __table_args__ = (sa.UniqueConstraint('user_id', 'device_token', name='uq_device_user_token'),)


class OAuthAccount(Base):
    """OAuth provider connections."""
    __tablename__ = 'oauth_accounts'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), sa.ForeignKey('users.id', ondelete='CASCADE'), nullable=False)
    provider = sa.Column(sa.String(50), nullable=False)
    provider_user_id = sa.Column(sa.String(255), nullable=False)
    access_token = sa.Column(sa.Text)
    refresh_token = sa.Column(sa.Text)
    expires_at = sa.Column(sa.DateTime)
    profile_data = sa.Column(sa.Text)
    user = relationship("User", back_populates="oauth_accounts")
    __table_args__ = (sa.UniqueConstraint('provider', 'provider_user_id', name='uq_oauth_account'),)


class UserRole(Base):
    """User roles for RBAC."""
    __tablename__ = 'user_roles'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), sa.ForeignKey('users.id', ondelete='CASCADE'), nullable=False)
    role = sa.Column(sa.String(50), nullable=False)
    granted_by = sa.Column(sa.String(36), sa.ForeignKey('users.id'))
    granted_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    user = relationship("User", foreign_keys=[user_id], back_populates="roles")
    __table_args__ = (sa.UniqueConstraint('user_id', 'role', name='uq_user_role'),)


# ==================== NOTIFICATIONS ====================
class Notification(Base):
    """User notifications."""
    __tablename__ = 'notifications'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), sa.ForeignKey('users.id', ondelete='CASCADE'), nullable=False, index=True)
    channel = sa.Column(sa.String(50), nullable=False, index=True)
    type = sa.Column(sa.String(100), nullable=False, index=True)
    title = sa.Column(sa.String(500))
    body = sa.Column(sa.Text)
    data = sa.Column(sa.Text)
    is_read = sa.Column(sa.Boolean, default=False, index=True)
    scheduled_at = sa.Column(sa.DateTime)
    sent_at = sa.Column(sa.DateTime)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    user = relationship("User", back_populates="notifications")


class NotificationChannel(Base):
    """User notification channel preferences."""
    __tablename__ = 'notification_channels'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), sa.ForeignKey('users.id', ondelete='CASCADE'), nullable=False)
    channel = sa.Column(sa.String(50), nullable=False)
    is_enabled = sa.Column(sa.Boolean, default=True)
    settings = sa.Column(sa.Text)
    __table_args__ = (sa.UniqueConstraint('user_id', 'channel', name='uq_notification_channel'),)


class NotificationTemplate(Base):
    """Notification templates."""
    __tablename__ = 'notification_templates'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    type = sa.Column(sa.String(100), unique=True, nullable=False)
    channel = sa.Column(sa.String(50), nullable=False)
    subject_template = sa.Column(sa.String(500))
    body_template = sa.Column(sa.Text, nullable=False)
    is_active = sa.Column(sa.Boolean, default=True)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow)


# ==================== WORKFLOW & FEATURE FLAGS ====================
class FeatureFlag(Base):
    """Feature flags for gradual rollout."""
    __tablename__ = 'feature_flags'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    name = sa.Column(sa.String(100), unique=True, nullable=False, index=True)
    description = sa.Column(sa.Text)
    is_enabled = sa.Column(sa.Boolean, default=False, index=True)
    rollout_percentage = sa.Column(sa.Integer, default=0)
    conditions = sa.Column(sa.Text)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow)


class Workflow(Base):
    """Workflow definitions."""
    __tablename__ = 'workflows'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    name = sa.Column(sa.String(200), nullable=False)
    version = sa.Column(sa.Integer, nullable=False)
    definition = sa.Column(sa.Text, nullable=False)
    is_active = sa.Column(sa.Boolean, default=True, index=True)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    __table_args__ = (sa.UniqueConstraint('name', 'version', name='uq_workflow_name_version'),)


class WorkflowInstance(Base):
    """Workflow execution instances."""
    __tablename__ = 'workflow_instances'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    workflow_id = sa.Column(sa.String(36), sa.ForeignKey('workflows.id'), nullable=False, index=True)
    user_id = sa.Column(sa.String(36), sa.ForeignKey('users.id'))
    status = sa.Column(sa.String(50), nullable=False, default='running', index=True)
    current_step = sa.Column(sa.String(100))
    context = sa.Column(sa.Text)
    started_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    completed_at = sa.Column(sa.DateTime)
    user = relationship("User", back_populates="workflow_instances")


# ==================== ECOMMERCE ====================
class Product(Base):
    """Product catalog."""
    __tablename__ = 'products'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    name = sa.Column(sa.String(255), nullable=False, index=True)
    category = sa.Column(sa.Enum(ProductCategory), nullable=False, index=True)
    price = sa.Column(sa.Numeric(10, 2), nullable=False)
    currency = sa.Column(sa.String(3), default='USD')
    description = sa.Column(sa.Text, nullable=True)
    image_url = sa.Column(sa.String(500), nullable=True)
    rating = sa.Column(sa.Numeric(3, 2), default=0.0)
    review_count = sa.Column(sa.Integer, default=0)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    # Relationships
    carts = relationship("Cart", back_populates="product", cascade="all, delete-orphan")
    order_items = relationship("OrderItem", back_populates="product", cascade="all, delete-orphan")


class UserPreference(Base):
    """User product preferences."""
    __tablename__ = 'user_preferences'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), nullable=False, unique=True, index=True)
    category_preferences = sa.Column(sa.Text, nullable=True)  # JSON
    price_min = sa.Column(sa.Numeric(10, 2), default=0)
    price_max = sa.Column(sa.Numeric(10, 2), default=100000)
    favorite_brands = sa.Column(sa.Text, nullable=True)  # JSON
    last_viewed_products = sa.Column(sa.Text, nullable=True)  # JSON
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)


class Cart(Base):
    """Shopping cart items."""
    __tablename__ = 'carts'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), nullable=False, index=True)
    product_id = sa.Column(sa.String(36), sa.ForeignKey('products.id'), nullable=False)
    quantity = sa.Column(sa.Integer, default=1)
    added_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    product = relationship("Product", back_populates="carts")
    __table_args__ = (sa.UniqueConstraint('user_id', 'product_id', name='uq_cart_user_product'),)


class Order(Base):
    """Orders."""
    __tablename__ = 'orders'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), nullable=False, index=True)
    total_amount = sa.Column(sa.Numeric(10, 2), nullable=False)
    currency = sa.Column(sa.String(3), default='USD')
    status = sa.Column(sa.String(20), default='pending', index=True)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    __table_args__ = (
        sa.Index('ix_orders_user_created', 'user_id', 'created_at'),
    )
    items = relationship("OrderItem", back_populates="order", cascade="all, delete-orphan")


class OrderItem(Base):
    """Order line items."""
    __tablename__ = 'order_items'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    order_id = sa.Column(sa.String(36), sa.ForeignKey('orders.id'), nullable=False, index=True)
    product_id = sa.Column(sa.String(36), sa.ForeignKey('products.id'), nullable=False)
    quantity = sa.Column(sa.Integer, default=1)
    unit_price = sa.Column(sa.Numeric(10, 2), nullable=False)
    order = relationship("Order", back_populates="items")
    product = relationship("Product", back_populates="order_items")


class Recommendation(Base):
    """User recommendations."""
    __tablename__ = 'recommendations'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), nullable=False, index=True)
    item_type = sa.Column(sa.String(50), nullable=False, index=True)
    item_id = sa.Column(sa.String(36), nullable=False)
    score = sa.Column(sa.Numeric(5, 4), nullable=False)
    reason = sa.Column(sa.String(200))
    model_version = sa.Column(sa.String(50))
    generated_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    expires_at = sa.Column(sa.DateTime, index=True)
    __table_args__ = (sa.UniqueConstraint('user_id', 'item_type', 'item_id', name='uq_recommendation_user_item'),)


# ==================== SOCIAL FEED ====================
class SocialPost(Base):
    """Social feed posts."""
    __tablename__ = 'social_feed_posts'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), nullable=False, index=True)
    content = sa.Column(sa.Text, nullable=False)
    media_urls = sa.Column(sa.Text, nullable=True)  # JSON array
    likes_count = sa.Column(sa.Integer, default=0)
    comments_count = sa.Column(sa.Integer, default=0)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    __table_args__ = (
        sa.Index('ix_social_post_user_created', 'user_id', 'created_at'),
        # Removed redundant single-column index on created_at (index=True already set)
    )
    # Relationships
    comments = relationship("SocialComment", back_populates="post", cascade="all, delete-orphan")
    post_likes = relationship("SocialPostLike", back_populates="post", cascade="all, delete-orphan")


class SocialComment(Base):
    """Comments on posts."""
    __tablename__ = 'social_feed_comments'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    post_id = sa.Column(sa.String(36), sa.ForeignKey('social_feed_posts.id', ondelete='CASCADE'), nullable=False, index=True)
    user_id = sa.Column(sa.String(36), nullable=False, index=True)
    content = sa.Column(sa.Text, nullable=False)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    post = relationship("SocialPost", back_populates="comments")
    comment_likes = relationship("SocialCommentLike", back_populates="comment", cascade="all, delete-orphan")


class SocialPostLike(Base):
    """Post likes."""
    __tablename__ = 'social_feed_post_likes'
    id = sa.Column(sa.Integer, primary_key=True, autoincrement=True)
    post_id = sa.Column(sa.String(36), sa.ForeignKey('social_feed_posts.id', ondelete='CASCADE'), nullable=False, index=True)
    user_id = sa.Column(sa.String(36), nullable=False, index=True)
    __table_args__ = (sa.UniqueConstraint('post_id', 'user_id', name='uq_social_post_like'),)
    post = relationship("SocialPost", back_populates="post_likes")


class SocialCommentLike(Base):
    """Comment likes."""
    __tablename__ = 'social_feed_comment_likes'
    id = sa.Column(sa.Integer, primary_key=True, autoincrement=True)
    comment_id = sa.Column(sa.String(36), sa.ForeignKey('social_feed_comments.id', ondelete='CASCADE'), nullable=False, index=True)
    user_id = sa.Column(sa.String(36), nullable=False, index=True)
    __table_args__ = (sa.UniqueConstraint('comment_id', 'user_id', name='uq_social_comment_like'),)
    comment = relationship("SocialComment", back_populates="comment_likes")


class SocialFollow(Base):
    """User follows."""
    __tablename__ = 'social_feed_follows'
    id = sa.Column(sa.Integer, primary_key=True, autoincrement=True)
    follower_id = sa.Column(sa.String(36), nullable=False, index=True)
    following_id = sa.Column(sa.String(36), nullable=False, index=True)
    __table_args__ = (sa.UniqueConstraint('follower_id', 'following_id', name='uq_social_follow'),)


# ==================== AI & BOTS ====================
class Bot(Base):
    """Chatbots."""
    __tablename__ = 'bots'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    name = sa.Column(sa.String(255), nullable=False)
    type = sa.Column(sa.Enum(BotType), nullable=False, index=True)
    enabled = sa.Column(sa.Boolean, default=True, index=True)
    capabilities = sa.Column(sa.Text, nullable=True)  # JSON
    config = sa.Column(sa.Text, nullable=True)  # JSON
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    conversations = relationship("Conversation", back_populates="bot", cascade="all, delete-orphan")



class Conversation(Base):
    """Unified conversations (AI, Bot, Chatbot)."""
    __tablename__ = 'conversations'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), nullable=False, index=True)
    bot_id = sa.Column(sa.String(36), sa.ForeignKey('bots.id', ondelete='SET NULL'), nullable=True, index=True)
    type = sa.Column(sa.Enum(ConversationType), nullable=False, index=True)
    title = sa.Column(sa.String(255), nullable=False)
    messages = sa.Column(sa.Text, nullable=True)  # JSON array
    summary_data = sa.Column(sa.Text, nullable=True)  # JSON: {summary_text, key_points, action_items, rating}
    status = sa.Column(sa.String(20), default='active', index=True)
    started_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    ended_at = sa.Column(sa.DateTime, nullable=True)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    __table_args__ = (
        sa.Index('ix_conv_user_started', 'user_id', 'started_at'),
        sa.Index('ix_conv_bot_type', 'bot_id', 'type'),
    )
    bot = relationship("Bot", back_populates="conversations")


class ConversationSummary(Base):
    """Conversation summary storage."""
    __tablename__ = 'conversation_summaries'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    conversation_id = sa.Column(sa.String(36), sa.ForeignKey('conversations.id', ondelete='CASCADE'), nullable=False, index=True)
    summary_text = sa.Column(sa.Text, nullable=False)
    key_points = sa.Column(sa.Text, nullable=True)  # JSON array
    action_items = sa.Column(sa.Text, nullable=True)  # JSON array
    participant_count = sa.Column(sa.Integer, default=0)
    rating = sa.Column(sa.Numeric(3, 2), nullable=True)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    __table_args__ = (sa.UniqueConstraint('conversation_id', name='uq_conversation_summary'),)



class GeneratedContentLog(Base):
    """AI generated content audit log."""
    __tablename__ = 'generated_content_log'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = sa.Column(sa.String(36), nullable=False, index=True)
    content_type = sa.Column(sa.String(50), nullable=False, index=True)
    prompt = sa.Column(sa.Text, nullable=False)
    generated_text = sa.Column(sa.Text, nullable=False)
    source = sa.Column(sa.Enum(ContentSource), nullable=False, index=True)
    extra_metadata = sa.Column(sa.Text, nullable=True)  # JSON
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)


class ModerationCache(Base):
    """Content moderation cache."""
    __tablename__ = 'moderation_cache'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    content_hash = sa.Column(sa.String(64), unique=True, nullable=False, index=True)
    is_violation = sa.Column(sa.Boolean, default=False, index=True)
    severity = sa.Column(sa.Float, default=0.0)
    violations = sa.Column(sa.Text, nullable=True)  # JSON
    action = sa.Column(sa.String(20), default='allow')
    content_type = sa.Column(sa.String(50), default='text')
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    expires_at = sa.Column(sa.DateTime, nullable=True, index=True)


class MLModel(Base):
    """ML model metadata."""
    __tablename__ = 'ml_models'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    name = sa.Column(sa.String(200), nullable=False)
    version = sa.Column(sa.String(50), nullable=False)
    model_type = sa.Column(sa.String(100), nullable=False)
    framework = sa.Column(sa.String(50))
    model_path = sa.Column(sa.Text, nullable=False)
    hyperparameters = sa.Column(sa.Text, nullable=True)  # JSON
    performance_metrics = sa.Column(sa.Text, nullable=True)  # JSON
    is_active = sa.Column(sa.Boolean, default=True, index=True)
    trained_at = sa.Column(sa.DateTime)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    __table_args__ = (sa.UniqueConstraint('name', 'version', name='uq_ml_model_name_version'),)


class MLInference(Base):
    """ML inference logs."""
    __tablename__ = 'ml_inferences'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    model_id = sa.Column(sa.String(36), sa.ForeignKey('ml_models.id', ondelete='SET NULL'), nullable=True, index=True)
    user_id = sa.Column(sa.String(36), sa.ForeignKey('users.id'), nullable=True)
    input_data = sa.Column(sa.Text, nullable=True)
    prediction = sa.Column(sa.Text, nullable=True)
    confidence = sa.Column(sa.Float, nullable=True)
    processed_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    latency_ms = sa.Column(sa.Integer, nullable=True)


# ==================== SEARCH & EVENTS ====================
class SearchIndex(Base):
    """Full-text search index."""
    __tablename__ = 'search_index'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    entity_id = sa.Column(sa.String(36), nullable=False, index=True)
    entity_type = sa.Column(sa.String(50), nullable=False, index=True)
    content = sa.Column(sa.Text, nullable=False)
    extra_metadata = sa.Column(sa.Text, nullable=True)  # JSON
    score = sa.Column(sa.Float, default=0.0, index=True)
    updated_at = sa.Column(sa.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, index=True)
    __table_args__ = (sa.Index('ix_search_entity', 'entity_id', 'entity_type'),)


class Event(Base):
    """Unified events (event sourcing + analytics + search tracking)."""
    __tablename__ = 'events'
    id = sa.Column(sa.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    event_type = sa.Column(sa.String(100), nullable=False, index=True)
    aggregate_type = sa.Column(sa.String(100), nullable=False, index=True)
    aggregate_id = sa.Column(sa.String(36), nullable=True)
    payload = sa.Column(sa.Text, nullable=False)  # JSON
    event_metadata = sa.Column('metadata', sa.Text, nullable=True)  # JSON
    user_id = sa.Column(sa.String(36), sa.ForeignKey('users.id'), nullable=True, index=True)
    event_source = sa.Column(sa.Enum(EventSource), nullable=False, default=EventSource.AUDIT, index=True)
    occurred_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    created_at = sa.Column(sa.DateTime, default=datetime.utcnow, index=True)
    date = sa.Column(sa.Date, default=datetime.utcnow, index=True)
    user = relationship("User", back_populates="events")
    __table_args__ = (
        sa.Index('ix_events_user_occurred', 'user_id', 'occurred_at'),
        sa.Index('ix_events_type_source', 'event_type', 'event_source'),
        # Removed redundant single-column index on date (index=True already set)
    )
