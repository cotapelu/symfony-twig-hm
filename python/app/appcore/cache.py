"""
Cache utilities for testing and development
For testing, we use an in-memory cache
"""
from typing import Optional, Any
from datetime import timedelta
import json


class MockCache:
    """In-memory mock cache for testing"""
    
    def __init__(self):
        self._cache = {}
    
    def get(self, key: str) -> Optional[Any]:
        data = self._cache.get(key)
        if data and 'expires_at' in data:
            from datetime import datetime
            if datetime.now() > data['expires_at']:
                self.delete(key)
                return None
        value = data.get('value') if data else None
        if value and isinstance(value, str):
            try:
                return json.loads(value)
            except (json.JSONDecodeError, TypeError):
                return value
        return value
    
    def set(self, key: str, value: Any, ex: Optional[int] = None):
        from datetime import datetime, timedelta
        expires_at = None
        if ex:
            expires_at = datetime.now() + timedelta(seconds=ex)
        
        self._cache[key] = {
            'value': json.dumps(value) if isinstance(value, (dict, list)) else value,
            'expires_at': expires_at
        }
        return True
    
    def delete(self, key: str) -> bool:
        if key in self._cache:
            del self._cache[key]
            return True
        return False
    
    def clear(self):
        self._cache.clear()


# Singleton mock instance
_cache: Optional[MockCache] = None


def get_cache() -> MockCache:
    """
    Get cache client
    Returns mock cache for testing environment
    """
    global _cache
    if _cache is None:
        _cache = MockCache()
    return _cache
