"""Social Feed Manager - re-exports from service engine"""
from modules.SocialFeed.service.engine import SocialFeedEngine

SocialFeedManager = SocialFeedEngine  # Alias for compatibility

__all__ = ['SocialFeedManager', 'SocialFeedEngine']
