"""
Database migration script for Python services
Run this to initialize all PostgreSQL databases
"""
import asyncio
import sys
import os

sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'python/app'))

from modules.ai.models import AIRepository
from modules.ecommerce.db_models import EcommerceRepository
from modules.search_ml.db_models import SearchMLRepository
from modules.botautomation.db_models import BotAutomationRepository
from modules.analytics.db_models import AnalyticsRepository


async def migrate_ai():
    print("Migrating AI service database...")
    repo = AIRepository()
    await repo.init_db()
    print("✓ AI service database migrated")


async def migrate_ecommerce():
    print("Migrating Ecommerce service database...")
    repo = EcommerceRepository()
    await repo.init_db()
    print("✓ Ecommerce service database migrated")


async def migrate_search_ml():
    print("Migrating SearchML service database...")
    repo = SearchMLRepository()
    await repo.init_db()
    print("✓ SearchML service database migrated")


async def migrate_bot_automation():
    print("Migrating BotAutomation service database...")
    repo = BotAutomationRepository()
    await repo.init_db()
    print("✓ BotAutomation service database migrated")


async def migrate_analytics():
    print("Migrating Analytics service database...")
    repo = AnalyticsRepository()
    await repo.init_db()
    print("✓ Analytics service database migrated")


async def migrate_all():
    print("Starting Python services database migrations...")
    print("-" * 50)
    
    try:
        await asyncio.gather(
            migrate_ai(),
            migrate_ecommerce(),
            migrate_search_ml(),
            migrate_bot_automation(),
            migrate_analytics()
        )
        print("-" * 50)
        print("✓ All Python service databases migrated successfully!")
    except Exception as e:
        print(f"✗ Migration failed: {e}")
        raise


if __name__ == "__main__":
    asyncio.run(migrate_all())
