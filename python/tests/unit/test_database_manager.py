"""Unit tests for DatabaseManager."""
import pytest
import asyncio
from unittest.mock import patch, MagicMock, AsyncMock
from app.shared.database import DatabaseManager, get_db_manager, initialize_all_databases, close_all_databases, database_health_check, _db_managers


class MockAsyncContextManager:
    """Helper mock for async context managers."""
    def __init__(self, return_value=None):
        self.return_value = return_value or MagicMock()
    async def __aenter__(self):
        return self.return_value
    async def __aexit__(self, exc_type, exc, tb):
        return None


class TestDatabaseManager:
    """Test suite for DatabaseManager."""

    @pytest.fixture(autouse=True)
    def clear_registry(self):
        """Clear global registry before each test."""
        _db_managers.clear()
        yield
        _db_managers.clear()

    def test_get_db_manager_singleton(self):
        """Test that get_db_manager returns singleton per schema."""
        manager1 = get_db_manager('schema1')
        manager2 = get_db_manager('schema1')
        manager3 = get_db_manager('schema2')
        assert manager1 is manager2
        assert manager1 is not manager3
        assert manager2 is not manager3

    @pytest.mark.asyncio
    async def test_initialize(self):
        """Test database manager initialization."""
        # Create manager with test schema
        manager = DatabaseManager('test_schema')
        assert not manager._initialized

        # Mock settings
        with patch('app.shared.database.settings') as mock_settings:
            mock_settings.database = MagicMock(
                host='localhost',
                port=5432,
                name='test_db',
                user='test_user',
                password='test_pass',
                pool_size=5,
                max_overflow=5,
                pool_recycle=3600
            )
            # Mock create_async_engine
            with patch('app.shared.database.create_async_engine') as mock_engine_func:
                mock_engine = MagicMock()
                mock_conn = MagicMock()
                # Mock connection behavior: execute and commit
                async def mock_execute(*args, **kwargs):
                    return MagicMock(scalar=lambda: 1)
                mock_conn.execute = AsyncMock(side_effect=mock_execute)
                mock_conn.commit = AsyncMock()
                # Setup context manager for connect()
                mock_engine.connect.return_value = MockAsyncContextManager(mock_conn)
                mock_engine_func.return_value = mock_engine

                await manager.initialize()

                assert manager._initialized
                assert manager.engine is not None
                assert manager.session_factory is not None

    @pytest.mark.asyncio
    async def test_get_session_context(self):
        """Test session context manager."""
        manager = DatabaseManager('test')
        manager._initialized = True
        # Mock session factory to return a mock session
        mock_session = AsyncMock()
        mock_session.__aenter__ = AsyncMock(return_value=mock_session)
        mock_session.__aexit__ = AsyncMock()
        mock_session.commit = AsyncMock()
        mock_session.rollback = AsyncMock()
        mock_session.close = AsyncMock()
        manager.session_factory = lambda: mock_session

        async with manager.get_session() as session:
            assert session is mock_session
        # Ensure commit was called
        mock_session.commit.assert_awaited_once()

    @pytest.mark.asyncio
    async def test_health_check_when_not_initialized(self):
        """Test health check returns False when not initialized."""
        manager = DatabaseManager('test')
        assert not await manager.health_check()

    @pytest.mark.asyncio
    async def test_close(self):
        """Test closing database manager."""
        manager = DatabaseManager('test')
        manager._initialized = True
        mock_engine = AsyncMock()
        manager.engine = mock_engine

        await manager.close()

        assert manager.engine is None
        assert manager.session_factory is None
        assert not manager._initialized
        mock_engine.dispose.assert_awaited_once()

    @pytest.mark.asyncio
    async def test_initialize_all_databases(self):
        """Test initializing all database schemas."""
        # Clear registry
        _db_managers.clear()
        # Create mock managers for each schema
        mock_managers = {}
        for schema in ['common', 'php', 'go', 'python', 'kotlin']:
            mm = AsyncMock()
            mm.schema = schema
            mock_managers[schema] = mm
        # Patch get_db_manager to return these mocks
        with patch('app.shared.database.get_db_manager', side_effect=lambda s: mock_managers[s]):
            await initialize_all_databases()
            # Each mock should have initialize called exactly once
            for mm in mock_managers.values():
                mm.initialize.assert_awaited_once()

    def test_database_settings_defaults(self):
        """Test DatabaseSettings default values."""
        from app.shared.config.settings import DatabaseSettings
        settings = DatabaseSettings()
        assert settings.host == "localhost"
        assert settings.port == 5432
        assert settings.name == "qcoder_community"
        assert settings.user == "qcoder"
        assert settings.pool_size == 20
        assert settings.max_overflow == 10

    @pytest.mark.asyncio
    async def test_database_health_check(self):
        """Test database health check returns proper dict."""
        # Clear and add a mock manager for 'common'
        _db_managers.clear()
        mock_common = AsyncMock()
        mock_common.health_check = AsyncMock(return_value=True)
        _db_managers['common'] = mock_common
        # Also add global db if needed; but database_health_check only iterates _db_managers
        # So we'll patch db.health_check not needed
        results = await database_health_check()
        assert 'common' in results
        # Expect dict with 'status' key per schema
        assert isinstance(results['common'], dict)
        assert results['common'].get('status') is True


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
