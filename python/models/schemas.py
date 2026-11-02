"""Aggregated schemas from all modules - re-export from all module-specific schemas"""
import sys
import os

# Add modules directory to path for imports
_modules_path = os.path.join(os.path.dirname(__file__), '..', 'modules')
sys.path.insert(0, _modules_path)

# Import from each module's schemas
from Ads.models.schemas import (
    Advertisement, AdCampaign, AdImpression, AdClick, AdStats, TargetingCriteria
)
from Analytics.models.schemas import (
    Metric, TimeSeriesData, UserActivity, DashboardWidget, Dashboard, Report, TimeRange, AnalyticsQuery
)
from Chatbot.models.schemas import (
    ChatMessage, Conversation, Intent, Entity, ChatbotResponse, ConversationContext, KnowledgeBaseArticle
)
from DoctorBooking.models.schemas import (
    BookingStatus, PaymentStatus, Hospital, Department, Doctor, TimeSlot, Appointment, PatientInfo, BookingRequest
)
from Favorites.models.schemas import (
    Favorite, FavoriteCollection, FavoriteFolder
)
from History.models.schemas import (
    ActivityType, DeviceType, ActivityCreate, ActivityResponse, HistoryEntry, HistorySearchRequest, HistoryStats, SessionSummary
)
from ImageRecognition.models.schemas import (
    ImageAnalysis, Label, DetectedObject, FaceAnnotation, ColorInfo, ProductRecognition, OCRResult, ImageModeration
)
from Messaging.models.schemas import (
    Conversation, Message, GroupChat, MessageReadReceipt
)
from Recommendations.models.schemas import (
    UserProfile, Item, CollaborativeFilterModel, ContentBasedModel, HybridWeights, RecommendationRequest, RecommendationResponse, RecommendedItem
)
from Reviews.models.schemas import (
    Review, ReviewReply, ReviewHelpful, ReviewSummary
)
from Settings.models.schemas import (
    UserSettings, NotificationSettings, PrivacySettings
)
from SocialFeed.models.schemas import (
    SocialPost, SocialComment, UserFollow, SocialFeedItem
)
from Telemedicine.models.schemas import (
    AppointmentStatus, VideoSessionStatus, ConsultationType, Patient, Doctor as TelemedicineDoctor, 
    TimeSlot as TelemedicineTimeSlot, Appointment as TelemedicineAppointment, 
    VideoSession, MedicalRecord
)

__all__ = [
    # Ads
    'Advertisement', 'AdCampaign', 'AdImpression', 'AdClick', 'AdStats', 'TargetingCriteria',
    # Analytics
    'Metric', 'TimeSeriesData', 'UserActivity', 'DashboardWidget', 'Dashboard', 'Report', 'TimeRange', 'AnalyticsQuery',
    # Chatbot
    'ChatMessage', 'Conversation', 'Intent', 'Entity', 'ChatbotResponse', 'ConversationContext', 'KnowledgeBaseArticle',
    # DoctorBooking
    'BookingStatus', 'PaymentStatus', 'Hospital', 'Department', 'Doctor', 'TimeSlot', 'Appointment', 'PatientInfo', 'BookingRequest',
    # Favorites
    'Favorite', 'FavoriteCollection', 'FavoriteFolder',
    # History
    'ActivityType', 'DeviceType', 'ActivityCreate', 'ActivityResponse', 'HistoryEntry', 'HistorySearchRequest', 'HistoryStats', 'SessionSummary',
    # ImageRecognition
    'ImageAnalysis', 'Label', 'DetectedObject', 'FaceAnnotation', 'ColorInfo', 'ProductRecognition', 'OCRResult', 'ImageModeration',
    # Messaging
    'Conversation', 'Message', 'GroupChat', 'MessageReadReceipt',
    # Recommendations
    'UserProfile', 'Item', 'CollaborativeFilterModel', 'ContentBasedModel', 'HybridWeights', 'RecommendationRequest', 'RecommendationResponse', 'RecommendedItem',
    # Reviews
    'Review', 'ReviewReply', 'ReviewHelpful', 'ReviewSummary',
    # Settings
    'UserSettings', 'NotificationSettings', 'PrivacySettings',
    # SocialFeed
    'SocialPost', 'SocialComment', 'UserFollow', 'SocialFeedItem',
    # Telemedicine
    'AppointmentStatus', 'VideoSessionStatus', 'ConsultationType', 'Patient', 'Doctor', 'TimeSlot', 'Appointment', 'VideoSession', 'MedicalRecord',
]
