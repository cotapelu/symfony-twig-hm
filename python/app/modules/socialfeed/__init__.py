"""SocialFeed module package.

Re-export the router and engine for convenient imports.
"""
from .router import router, engine

__all__ = ['router', 'engine']
