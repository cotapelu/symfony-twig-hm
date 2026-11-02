""" SearchService implementation. """
import logging
from typing import Any

logger = logging.getLogger(__name__)

class SearchService:
    """Business logic for Search operations."""

    def __init__(self, config: Any = None):
        self.config = config

    async def perform(self, param: str) -> str:
        """Perform a generic operation."""
        logger.info("%s called with %s", self.__class__.__name__, param)
        return "{}: {}".format(self.__class__.__name__, param)

    async def health(self) -> bool:
        """Health check."""
        return True
