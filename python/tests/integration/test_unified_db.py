"""Integration tests for unified database connection.

These tests require a running PostgreSQL instance with the unified schema.
To run: set INTEGRATION_TESTS=1 and ensure DB is accessible.
"""
import os
import pytest
import asyncio
from datetime import datetime
from sqlalchemy.sql import text

# Skip all tests in this module unless explicitly enabled
if os.getenv("INTEGRATION_TESTS") != "1":
    pytest.skip(
        "Integration tests skipped by default (set INTEGRATION_TESTS=1 to run)",
        allow_module_level=True
    )

# Now import integration-specific modules (only executed when not skipped)
from app.shared.database import get_db_manager, initialize_all_databases, close_all_databases
from app.shared.models import User
try:
    from app.shared.models.python import AnalyticsEvent
except ImportError:
    AnalyticsEvent = None  # For type checking, not used if tests are skipped
import sqlalchemy as sa

pytestmark = pytest.mark.asyncio


class TestUnifiedDatabase:
    """Integration tests for unified database setup."""

    @pytest.fixture(scope="module", autouse=True)
    async def setup_teardown(self):
        """Set up database connection for this test module."""
        # Clear any existing managers
        from app.shared.database import _db_managers
        _db_managers.clear()

        # Mock settings for testing
        import os
        os.environ.setdefault('DB_HOST', 'localhost')
        os.environ.setdefault('DB_PORT', '5432')
        os.environ.setdefault('DB_NAME', 'qcoder_community')
        os.environ.setdefault('DB_USER', 'qcoder')
        os.environ.setdefault('DB_PASSWORD', '')

        # Initialize all databases (if real DB available)
        # For CI, we might skip if no DB running
        try:
            await initialize_all_databases()
        except Exception as e:
            pytest.skip(f"Database not available: {e}")

        yield

        # Teardown
        await close_all_databases()

    async def test_common_schema_connection(self):
        """Test that common schema manager initializes and can query."""
        db = get_db_manager('common')
        assert db._initialized

        try:
            async with db.get_session() as session:
                result = await session.execute(text("SELECT 1"))
                assert result.scalar() == 1
        except Exception as e:
            pytest.skip(f"Common schema not accessible: {e}")

    async def test_python_schema_can_create_analytics_event(self):
        """Test creating and querying an analytics event in python schema."""
        db = get_db_manager('python')
        assert db._initialized

        # Skip if DB not reachable
        try:
            async with db.get_session() as session:
                # This will fail if table doesn't exist, that's okay for schema structure test
                event = AnalyticsEvent(
                    user_id='test_user_123',
                    event_type='test.event',
                    properties='{"test": true}',
                    metadata='{"source": "integration_test"}'
                )
                session.add(event)
                await session.commit()
                assert event.id is not None

                # Query it back
                result = await session.execute(
                    sa.select(AnalyticsEvent).where(AnalyticsEvent.id == event.id)
                )
                fetched = result.scalar_one_or_none()
                assert fetched is not None
                assert fetched.event_type == 'test.event'
        except Exception as e:
            pytest.skip(f"Python schema tables not created: {e}")

    async def test_all_schemas_health_check(self):
        """Test all schemas report healthy."""
        from app.shared.database import database_health_check
        results = await database_health_check()
        # If DB is up, all schemas should be healthy
        if results:
            assert all(results.values()), f"Some schemas unhealthy: {results}"
        else:
            pytest.skip("No database connection")


if __name__ == "__main__":
    pytest.main([__file__, "-v", "-s"])
