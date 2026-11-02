"""Coverage for social_feed.py re-export module"""

def test_social_feed_imports():
    from modules.SocialFeed.social_feed import SocialFeedManager, SocialFeedEngine
    assert SocialFeedManager is SocialFeedEngine
