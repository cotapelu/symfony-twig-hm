"""SearchML data access using shared database models."""
from typing import List, Optional, Dict, Any, AsyncGenerator
import json
from datetime import datetime
from sqlalchemy import select, delete, func
from sqlalchemy.ext.asyncio import AsyncSession
from app.shared.database import db
from app.shared.models import Event, SearchIndex


class SearchMLRepository:
    """Search service using shared Event and SearchIndex tables.

    Allows optional external session injection for testing or cross-module
    integration scenarios where the same session must be shared across
    multiple repositories.
    """

    def __init__(self, session: Optional[AsyncSession] = None):
        self._external_session = session

    async def init_db(self):
        """Database initialization handled centrally."""
        pass

    async def get_session(self) -> AsyncGenerator[AsyncSession, None]:
        """Get database session. Uses external session if provided, otherwise global."""
        if self._external_session is not None:
            yield self._external_session
        else:
            async with db.get_session() as session:
                yield session

    # Search Index Management
    async def index_entity(self, entity_id: str, entity_type: str, content: str, metadata: Dict = None) -> SearchIndex:
        """Index or update an entity for search."""
        async for session in self.get_session():
            # Check existing
            result = await session.execute(
                select(SearchIndex).where(
                    SearchIndex.entity_id == entity_id,
                    SearchIndex.entity_type == entity_type
                )
            )
            existing = result.scalar_one_or_none()
            if existing:
                existing.content = content
                existing.extra_metadata = json.dumps(metadata) if metadata else None
                existing.updated_at = datetime.utcnow()
            else:
                existing = SearchIndex(
                    entity_id=entity_id,
                    entity_type=entity_type,
                    content=content,
                    extra_metadata=json.dumps(metadata) if metadata else None,
                    score=0.0
                )
                session.add(existing)
            await session.commit()
            await session.refresh(existing)
            return existing

    async def search_index(self, query: str, entity_types: List[str] = None, limit: int = 20) -> List[SearchIndex]:
        """Search the index with optional entity type filter."""
        async for session in self.get_session():
            stmt = select(SearchIndex).where(
                SearchIndex.content.ilike(f"%{query}%")
            )
            if entity_types:
                stmt = stmt.where(SearchIndex.entity_type.in_(entity_types))
            result = await session.execute(
                stmt.order_by(SearchIndex.score.desc()).limit(limit)
            )
            return result.scalars().all()

    async def delete_index(self, entity_id: str) -> bool:
        """Remove an entity from the search index."""
        async for session in self.get_session():
            result = await session.execute(
                delete(SearchIndex).where(SearchIndex.entity_id == entity_id)
            )
            await session.commit()
            return result.rowcount > 0

    # Search History (store as Domain Events)
    async def record_search(self, user_id: str, query: str, entity_types: List[str], filters: Dict, result_count: int, clicked_results: List[str], search_time_ms: float) -> str:
        """Record a search event."""
        async for session in self.get_session():
            event = Event(
                event_type='search',
                aggregate_type='search',
                aggregate_id=None,
                payload=json.dumps({
                    'query': query,
                    'entity_types': entity_types,
                    'filters': filters,
                    'result_count': result_count,
                    'clicked_results': clicked_results,
                    'search_time_ms': search_time_ms
                }),
                metadata=None,
                user_id=user_id,
                occurred_at=datetime.utcnow()
            )
            session.add(event)
            await session.commit()
            await session.refresh(event)
            return str(event.id)

    async def get_search_history(self, user_id: str, limit: int = 50) -> List[Event]:
        """Get search history for a user (events with type 'search')."""
        async for session in self.get_session():
            result = await session.execute(
                select(Event)
                .where(Event.user_id == user_id)
                .where(Event.event_type == 'search')
                .order_by(Event.occurred_at.desc())
                .limit(limit)
            )
            return result.scalars().all()
