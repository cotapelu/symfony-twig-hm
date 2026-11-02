"""Integration tests for SearchML module with real database."""
import os
import pytest
from datetime import datetime, timezone

os.environ.setdefault('JWT_SECRET_KEY', 'test-secret-key-for-integration')
os.environ.setdefault('JWT_ALGORITHM', 'HS256')

import sys
from pathlib import Path
sys.path.append(str(Path(__file__).resolve().parents[2] / 'python'))

from app.shared.models import Base, User
from app.shared.database import db as global_db
from app.modules.search_ml.repository import SearchMLRepository
# Ensure telemedicine tables are included (they have FKs to users)
from app.modules.telemedicine.infrastructure.models import DoctorModel, AppointmentModel, ReviewModel  # noqa: F401
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import sessionmaker
from sqlalchemy import select


@pytest.fixture(scope="session")
def db_url():
    url = os.getenv("INTEGRATION_TEST_DATABASE_URL")
    if not url:
        pytest.skip("INTEGRATION_TEST_DATABASE_URL not set, skipping integration tests")
    if not url.startswith("postgresql+asyncpg://"):
        if url.startswith("postgresql://"):
            url = url.replace("postgresql://", "postgresql+asyncpg://", 1)
        else:
            pytest.fail(f"Unsupported database URL: {url}")
    return url


@pytest.fixture
async def db_engine(db_url):
    engine = create_async_engine(db_url, echo=False, pool_size=5, max_overflow=10)
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.drop_all)
        await conn.run_sync(Base.metadata.create_all)
    # Patch global_db to use this test engine
    global_db.engine = engine
    global_db.session_factory = sessionmaker(engine, class_=AsyncSession, expire_on_commit=False)
    global_db._initialized = True
    yield engine
    await engine.dispose()


@pytest.fixture
async def db_session(db_engine):
    async_session = sessionmaker(db_engine, class_=AsyncSession, expire_on_commit=False)
    async with async_session() as session:
        async with session.begin():
            yield session
        await session.rollback()


@pytest.fixture
def repo(db_engine):
    return SearchMLRepository()

@pytest.fixture
async def ensure_test_user(db_engine):
    """Ensure test user exists for Event foreign key."""
    async_session = sessionmaker(db_engine, class_=AsyncSession, expire_on_commit=False)
    async with async_session() as session:
        result = await session.execute(select(User).where(User.id == "test_user"))
        user = result.scalar_one_or_none()
        if not user:
            user = User(
                id="test_user",
                email="test@example.com",
                username="testuser",
                password_hash="dummy_hash",
                is_active=True,
                is_verified=True
            )
            session.add(user)
            await session.commit()
        return user





@pytest.fixture
def mock_auth(monkeypatch, user_id: str = "test_user"):
    async def mock_dep():
        return user_id
    from app.modules.search_ml.api import routes as search_ml_routes
    monkeypatch.setattr(search_ml_routes, 'get_current_user_id', mock_dep)
    return mock_dep


class TestSearchMLIntegration:
    """Integration tests for SearchML module using real database."""

    @pytest.mark.asyncio
    async def test_index_and_search_entity(self, repo, mock_auth):
        """Index a product and then search for it."""
        # Index a product
        product_id = "prod_test_1"
        content = "Fresh organic apples for sale"
        metadata = {"category": "food", "price": 50000}
        index_resp = await repo.index_entity(product_id, "product", content, metadata)
        assert index_resp.entity_id == product_id
        assert content in index_resp.content

        # Search for it
        search_results = await repo.search_index("apples", entity_types=["product"])
        assert len(search_results) >= 1
        found = next((s for s in search_results if s.entity_id == product_id), None)
        assert found is not None
        assert content in found.content

    @pytest.mark.asyncio
    async def test_search_records_history(self, repo, mock_auth, db_session, ensure_test_user):
        """Search should record a search event in Event table."""
        query = "organic bananas"
        results = await repo.search_index(query, entity_types=["product"])
        event_id = await repo.record_search(
            user_id="test_user",
            query=query,
            entity_types=["product"],
            filters={},
            result_count=len(results),
            clicked_results=[],
            search_time_ms=123.4
        )
        assert event_id is not None

        # Verify event persisted using the injected db_session
        from app.shared.models import Event
        event = await db_session.get(Event, event_id)
        assert event is not None
        assert event.event_type == "search"
        assert event.user_id == "test_user"

    @pytest.mark.asyncio
    async def test_delete_index(self, repo, mock_auth):
        """Delete entity from search index."""
        # First index something
        entity_id = "prod_to_delete"
        content = "Temporary product for deletion test"
        await repo.index_entity(entity_id, "product", content, {})

        # Delete it
        deleted = await repo.delete_index(entity_id)
        assert deleted is True

        # Verify not found in search
        results = await repo.search_index("temporary", entity_types=["product"])
        assert not any(s.entity_id == entity_id for s in results)
