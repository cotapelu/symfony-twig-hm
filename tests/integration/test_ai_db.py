"""Integration tests for AI module with real database."""
import os
import pytest
import hashlib
from datetime import datetime

os.environ.setdefault('JWT_SECRET_KEY', 'test-secret-key-for-integration')
os.environ.setdefault('JWT_ALGORITHM', 'HS256')
# Force AI to use fallback (no OpenAI) for deterministic tests
os.environ['OPENAI_API_KEY'] = ''

import sys
from pathlib import Path
sys.path.append(str(Path(__file__).resolve().parents[2] / 'python'))

from app.shared.models import Base
from app.shared.database import db as global_db
from app.modules.ai.services.ai_service import AIService

# Ensure telemedicine tables are included (they have FKs to users)
from app.modules.telemedicine.infrastructure.models import DoctorModel, AppointmentModel, ReviewModel  # noqa: F401
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import sessionmaker


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
    # Ensure clean state: drop all tables first (if exist)
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
def ai_service(db_engine):
    """AI Service instance (uses patched global_db)."""
    return AIService()


class TestAIIntegration:
    """Integration tests for AI service using real database."""

    @pytest.mark.asyncio
    async def test_analyze_sentiment_saves_inference(self, ai_service, db_session):
        """Sentiment analysis should save MLInference record."""
        text = "I love this product, it's awesome!"
        result = await ai_service.analyze_sentiment(text)

        assert 'sentiment' in result
        assert result['sentiment'] in ['positive', 'negative', 'neutral']

        # Verify MLInference record created by checking input_data contains the content hash
        from app.shared.models import MLInference
        from sqlalchemy import select
        content_hash = hashlib.sha256(text.encode()).hexdigest()
        stmt = select(MLInference).where(
            MLInference.input_data.like(f'%{content_hash}%')
        )
        result = await db_session.execute(stmt)
        record = result.scalar_one_or_none()
        assert record is not None
        assert record.prediction is not None

    @pytest.mark.asyncio
    async def test_moderate_content_saves_cache(self, ai_service, db_session):
        """Content moderation should save ModerationCache entry."""
        content = "This is spam and abuse"
        result = await ai_service.moderate_content(content, content_type="text")

        assert 'is_violation' in result
        assert 'severity' in result
        assert 'violations' in result
        assert 'action' in result

        # Verify cache entry in MLInference
        from app.shared.models import MLInference
        from sqlalchemy import select
        import json
        content_hash = hashlib.sha256(content.encode()).hexdigest()
        stmt = select(MLInference).where(
            MLInference.input_data.like(f'%{content_hash}%')
        )
        result = await db_session.execute(stmt)
        record = result.scalar_one_or_none()
        assert record is not None
        # Check prediction contains expected keys
        prediction = json.loads(record.prediction)
        assert prediction.get('action') in ['allow', 'flag', 'block']
        assert 'is_violation' in prediction
        assert 'severity' in prediction
        assert 'violations' in prediction

    @pytest.mark.asyncio
    async def test_translate_text_deterministic(self, ai_service):
        """Translate fallback should return known translations."""
        # Without OpenAI, uses hardcoded dictionary
        result = ai_service.translate_text("Hello", "en", "es")
        assert result['translated_text'] == "Hola"

        result = ai_service.translate_text("Hello", "en", "fr")
        assert result['translated_text'] == "Bonjour"

        result = ai_service.translate_text("Hello", "en", "vi")
        assert result['translated_text'] == "Xin chào"

        # Same language returns original
        result = ai_service.translate_text("Hello", "en", "en")
        assert result['translated_text'] == "Hello"

    @pytest.mark.asyncio
    async def test_generate_smart_replies_fallback(self, ai_service):
        """Smart replies fallback should return appropriate responses."""
        # Question
        replies = await ai_service.generate_smart_replies([], "How are you?")
        assert any("question" in r.lower() or "think" in r.lower() for r in replies)

        # Thanks
        replies = await ai_service.generate_smart_replies([], "Thanks!")
        assert any("welcome" in r.lower() for r in replies)

        # Generic
        replies = await ai_service.generate_smart_replies([], "I had a good day")
        assert any("interesting" in r.lower() or "understand" in r.lower() for r in replies)

    @pytest.mark.asyncio
    async def test_conversation_history_stored(self, ai_service, db_session):
        """When retrieving recent history, it should come from Conversation table."""
        user_id = "user_history_test"
        # Create a conversation with messages directly in DB
        from app.shared.models import Conversation
        import json
        conv = Conversation(
            id="conv_hist_1",
            user_id=user_id,
            type="chatbot",
            title="Test Conversation",
            messages=json.dumps([
                {"role": "user", "content": "Hello", "timestamp": datetime.now().isoformat()},
                {"role": "assistant", "content": "Hi there!", "timestamp": datetime.now().isoformat()},
                {"role": "user", "content": "How are you?", "timestamp": datetime.now().isoformat()},
            ]),
            started_at=datetime.now()
        )
        db_session.add(conv)
        await db_session.commit()

        # Call generate_smart_replies with user_id to trigger history loading
        replies = await ai_service.generate_smart_replies([], "What's up?", user_id=user_id)
        # Should return replies (even if using fallback)
        assert isinstance(replies, list)
        assert len(replies) > 0
