import pytest
from modules.SocialFeed.social_feed import SocialFeedManager

class TestSocialFeedManager:
    def setup_method(self):
        self.manager = SocialFeedManager()
    
    def test_create_post(self):
        user_id = 123
        content = "Test post content"
        
        result = self.manager.create_post(user_id, content)
        
        assert result is not None
    
    def test_get_feed(self):
        user_id = 123
        
        feed = self.manager.get_feed(user_id)
        
        assert isinstance(feed, list)
    
    def test_like_post(self):
        user_id = 123
        post_id = 456
        
        result = self.manager.like_post(user_id, post_id)
        
        assert result is not None
    
    def test_comment_on_post(self):
        user_id = 123
        post_id = 456
        comment = "Great post!"
        
        result = self.manager.comment_on_post(user_id, post_id, comment)
        
        assert result is not None
    
    def test_share_post(self):
        user_id = 123
        post_id = 456
        
        result = self.manager.share_post(user_id, post_id)
        
        assert result is not None
    
    def test_delete_post(self):
        user_id = 123
        post_id = 456
        
        result = self.manager.delete_post(user_id, post_id)
        
        assert result is not None
    
    def test_get_trending_posts(self):
        posts = self.manager.get_trending_posts(limit=10)
        
        assert isinstance(posts, list)
        assert len(posts) <= 10
