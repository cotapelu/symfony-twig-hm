"""Integration tests for circuit breaker pattern presence on SocialFeed repository."""
import pytest
from modules.SocialFeed.db_models import SocialFeedRepository
from observability.resilience import CircuitBreaker


def test_socialfeed_methods_have_circuit_breaker():
    """Verify that key SocialFeed repository methods are decorated with @circuit_breaker."""
    methods = [
        SocialFeedRepository.create_post,
        SocialFeedRepository.get_post,
        SocialFeedRepository.get_user_posts,
        SocialFeedRepository.toggle_post_like,
        SocialFeedRepository.get_post_likes,
        SocialFeedRepository.add_comment,
        SocialFeedRepository.get_post_comments,
        SocialFeedRepository.toggle_comment_like,
        SocialFeedRepository.follow_user,
    ]
    for method in methods:
        assert hasattr(method, 'circuit_breaker'), f"{method.__name__} missing circuit_breaker attribute"
        cb = method.circuit_breaker
        assert isinstance(cb, CircuitBreaker), f"{method.__name__}.circuit_breaker is not a CircuitBreaker"
        # Verify default configuration (cornerstone values)
        assert cb.failure_threshold == 5
        assert cb.recovery_timeout == 60.0
        assert cb.half_open_max_calls == 3
