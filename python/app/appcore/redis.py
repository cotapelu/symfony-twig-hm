"""
Redis cache utilities for testing and development
For testing, we use an in-memory cache instead of actual Redis
"""
from typing import Optional, Any
from datetime import timedelta
import json


class MockRedis:
    """In-memory mock Redis for testing"""
    
    def __init__(self):
        self._cache = {}
    
    def get(self, key: str) -> Optional[Any]:
        data = self._cache.get(key)
        if data and 'expires_at' in data:
            from datetime import datetime
            if datetime.now() > data['expires_at']:
                self.delete(key)
                return None
        return data.get('value') if data else None
    
    def set(self, key: str, value: Any, ex: Optional[int] = None):
        from datetime import datetime, timedelta
        expires_at = None
        if ex:
            expires_at = datetime.now() + timedelta(seconds=ex)
        
        self._cache[key] = {
            'value': value if not isinstance(value, (dict, list)) else json.dumps(value),
            'expires_at': expires_at
        }
        return True
    
    def delete(self, key: str) -> bool:
        if key in self._cache:
            del self._cache[key]
            return True
        return False
    
    def exists(self, key: str) -> int:
        return 1 if key in self._cache else 0


# Singleton mock instance
_redis: Optional[MockRedis] = None


def get_redis_cache() -> MockRedis:
    """
    Get Redis cache client
    Returns mock Redis for testing environment
    """
    global _redis
    if _redis is None:
        _redis = MockRedis()
    return _redis
