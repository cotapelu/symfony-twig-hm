"""Centralized async database connection manager."""
import os
from contextlib import asynccontextmanager
from typing import AsyncGenerator, Optional, Dict
import sqlalchemy as sa
from sqlalchemy.ext.asyncio import AsyncEngine, AsyncSession, create_async_engine
from sqlalchemy.orm import sessionmaker
from sqlalchemy.sql import text

from app.shared.config.settings import settings
from app.shared.utils.logger import setup_logger

# Import unified Base from models
from app.shared.models import Base

logger = setup_logger("database-manager", "INFO", "json")


class DatabaseManager:
    """Manages connection to PostgreSQL database.

    Single schema architecture: all tables in 'public' (or 'app' schema).
    Simplicity over complexity.
    """

    def __init__(self, schema: Optional[str] = None):
        self.schema = schema
        self.engine: Optional[AsyncEngine] = None
        self.session_factory: Optional[sessionmaker] = None
        self._initialized = False

    async def initialize(self) -> None:
        """Initialize async engine and session factory."""
        if self._initialized:
            return

        try:
            db_config = settings.database
            database_url = (
                f"postgresql+asyncpg://{db_config.user}:{db_config.password}"
                f"@{db_config.host}:{db_config.port}/{db_config.name}"
            )

            logger.info("Initializing unified database connection...")
            self.engine = create_async_engine(
                database_url,
                echo=settings.debug,
                pool_size=db_config.pool_size,
                max_overflow=db_config.max_overflow,
                pool_pre_ping=True,
                pool_recycle=db_config.pool_recycle,
            )

            # Create all tables (if not exist)
            async with self.engine.begin() as conn:
                await conn.run_sync(Base.metadata.create_all)
            logger.info("All database tables created/verified")

            self.session_factory = sessionmaker(
                self.engine,
                class_=AsyncSession,
                expire_on_commit=False
            )
            self._initialized = True
            logger.info("Database manager initialized successfully")

        except Exception as e:
            logger.error("Failed to initialize database", exc_info=e)
            raise

    @asynccontextmanager
    async def get_session(self) -> AsyncGenerator[AsyncSession, None]:
        """Get a database session."""
        if not self._initialized:
            raise RuntimeError("Database not initialized")

        session = self.session_factory()
        try:
            yield session
            await session.commit()
        except Exception as e:
            await session.rollback()
            logger.error("Database session error", exc_info=e)
            raise
        finally:
            await session.close()

    async def health_check(self) -> bool:
        """Check if database is reachable."""
        if not self._initialized:
            return False
        try:
            async with self.engine.connect() as conn:
                result = await conn.execute(sa.text("SELECT 1"))
                return result.scalar() == 1
        except Exception as e:
            logger.error("Database health check failed", exc_info=e)
            return False

    async def close(self) -> None:
        """Close engine and cleanup."""
        if self.engine:
            await self.engine.dispose()
            self.engine = None
            self.session_factory = None
            self._initialized = False
            logger.info("Database connections closed")


# Registry for multi-schema database managers
_db_managers: Dict[str, DatabaseManager] = {}

# Global singleton
db_manager = DatabaseManager()


def get_db_manager(schema: Optional[str] = None) -> DatabaseManager:
    """Get a database manager.
    
    Args:
        schema: Optional schema name. If provided, returns a singleton manager for that schema.
                If None (default), returns the global manager.
    """
    if schema is None:
        return db_manager
    if schema not in _db_managers:
        _db_managers[schema] = DatabaseManager(schema)
    return _db_managers[schema]


# Convenience
db = db_manager


async def initialize_all_databases() -> None:
    """Initialize all known database schemas."""
    # List of known schemas in the system
    KNOWN_SCHEMAS = ['common', 'php', 'go', 'python', 'kotlin']
    for schema in KNOWN_SCHEMAS:
        manager = get_db_manager(schema)
        await manager.initialize()


async def close_all_databases() -> None:
    """Close all registered database managers."""
    for manager in _db_managers.values():
        await manager.close()


async def database_health_check() -> Dict[str, dict]:
    """Health check returns dict of schema -> {status: bool}."""
    results: Dict[str, dict] = {}
    # Include all registered schema managers
    for schema, manager in _db_managers.items():
        results[schema] = {"status": await manager.health_check()}
    # Also include global manager under 'global' key for backward compatibility
    if 'global' not in results:
        results['global'] = {"status": await db_manager.health_check()}
    return results
