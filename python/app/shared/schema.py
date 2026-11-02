"""Unified schema registration and creation."""
from typing import List
from sqlalchemy.ext.asyncio import AsyncEngine
from sqlalchemy.sql import text

# Import all Base metadata from shared models
from app.shared.models import CommonBase
from app.shared.models.python import PythonBase

# Map schema name to Base
SCHEMA_BASES = {
    'common': CommonBase,
    'python': PythonBase,
    # php, go, kotlin bases will be added when those models are created
}

def register_schema_base(schema: str, base):
    """Register a declarative base for a schema."""
    SCHEMA_BASES[schema] = base

async def create_all_schemas(engine: AsyncEngine):
    """Create all tables for all registered schemas."""
    for schema, base in SCHEMA_BASES.items():
        async with engine.connect() as conn:
            # Ensure schema exists
            await conn.execute(text(f"CREATE SCHEMA IF NOT EXISTS {schema}"))
            await conn.commit()
        # Create tables for this base
        async with engine.begin() as conn:
            await conn.run_sync(base.metadata.create_all)
        print(f"Created tables for schema '{schema}'")

async def drop_all_schemas(engine: AsyncEngine):
    """Drop all tables for all registered schemas (DANGEROUS)."""
    for schema, base in SCHEMA_BASES.items():
        async with engine.begin() as conn:
            await conn.run_sync(base.metadata.drop_all)
        print(f"Dropped tables for schema '{schema}'")
