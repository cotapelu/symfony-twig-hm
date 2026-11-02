"""Unit tests for BotAutomationService."""
import pytest
from app.modules.botautomation.bot_service import BotAutomationService

class TestBotAutomationService:
    """Test suite for BotAutomationService."""

    @pytest.fixture
    def service(self):
        return BotAutomationService()

    def test_list_bots_returns_enabled_by_default(self, service):
        bots = service.list_bots()
        assert len(bots) == 2
        assert all(bot["enabled"] for bot in bots)

    def test_list_bots_limit_offset(self, service):
        bots = service.list_bots(limit=1, offset=0)
        assert len(bots) == 1
        bot_id = bots[0]["id"]
        # Next bot with offset=1
        bots2 = service.list_bots(limit=1, offset=1)
        assert len(bots2) == 1
        assert bots2[0]["id"] != bot_id

    def test_get_bot_existing(self, service):
        bot = service.get_bot("bot_001")
        assert bot is not None
        assert bot["name"] == "Customer Support Bot"
        assert bot["type"] == "support"

    def test_get_bot_nonexistent(self, service):
        bot = service.get_bot("unknown_bot")
        assert bot is None

    def test_process_message_bot_not_found(self, service):
        result = service.process_message("bot_999", "hello")
        assert result["success"] is False
        assert result["response"] == "Bot not found"

    def test_process_message_bot_disabled(self, service):
        # Disable a bot
        service._bots["bot_001"]["enabled"] = False
        result = service.process_message("bot_001", "test")
        assert result["success"] is False
        assert result["response"] == "Bot is disabled"

    def test_process_message_support_bot(self, service):
        result = service.process_message("bot_001", "I need help with my account")
        assert result["success"] is True
        assert result["bot_id"] == "bot_001"
        assert "help" in result["response"].lower()
        assert result["message_id"].startswith("msg_")

    def test_process_message_sales_bot(self, service):
        result = service.process_message("bot_002", "Can you recommend a product?")
        assert result["success"] is True
        assert "recommend" in result["response"].lower()

    def test_process_message_generic(self, service):
        # Add a bot with unknown type to test fallback
        service._bots["bot_003"] = {"id": "bot_003", "name": "Generic", "type": "generic", "enabled": True}
        result = service.process_message("bot_003", "Hello")
        assert result["success"] is True
        assert result["response"] == "I received your message. How can I assist you today?"

    def test_train_bot_existing(self, service):
        success = service.train_bot("bot_001", {"data": "sample"})
        assert success is True

    def test_train_bot_nonexistent(self, service):
        success = service.train_bot("bot_999", {})
        assert success is False

    def test_record_feedback_no_error(self, service):
        # Should not raise any exception
        service.record_feedback("bot_001", "msg_0", 5, "Great!")

    def test_get_analytics(self, service):
        analytics = service.get_analytics("bot_001")
        assert analytics["bot_id"] == "bot_001"
        assert "total_conversations" in analytics
        assert "average_rating" in analytics
        assert "resolution_rate" in analytics

if __name__ == "__main__":
    pytest.main([__file__, "-v"])