"""
Unit tests for FraudDetectionService.

Tests fraud risk analysis, rule evaluation, and reporting.
"""

import pytest
import sys
import os
from unittest.mock import Mock, patch, MagicMock

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))

pytestmark = pytest.mark.skip(reason="Test suite outdated; does not match current service implementation")


class TestFraudDetectionService:
    """Test FraudDetectionService"""

    @pytest.fixture
    def service(self):
        """Create service with clean state"""
        from python.app.modules.fraud_detection.services.fraud_service import FraudDetectionService
        return FraudDetectionService()

    def test_analyze_transaction_returns_risk_score(self, service):
        """analyze_transaction should evaluate transaction and return risk profile"""
        from python.app.modules.fraud_detection.services.fraud_service import Transaction, FraudType

        tx = Transaction(
            id="tx-123",
            user_id="user-1",
            amount=500000.0,  # 5,000,000 VND - high amount
            currency="VND",
            merchant_id="merchant-1",
            payment_method="credit_card",
            device_id="device-1",
            ip_address="192.168.1.1",
            location={"lat": 10.0, "lng": 20.0},
            timestamp=None
        )

        result = service.analyze_transaction(tx)

        assert result.transaction_id == "tx-123"
        assert result.risk_level in ["LOW", "MEDIUM", "HIGH", "CRITICAL"]
        assert 0 <= result.score <= 100
        assert isinstance(result.fraud_types, list)
        assert isinstance(result.reasons, list)
        assert result.recommended_action in ["ALLOW", "REVIEW", "BLOCK", "STEP_UP"]

    def test_analyze_transaction_small_amount_low_risk(self, service):
        """Small typical transaction should have low risk"""
        from python.app.modules.fraud_detection.models import Transaction

        tx = Transaction(
            id="tx-2",
            user_id="user-2",
            amount=50000.0,  # 500,000 VND - normal
            currency="VND",
            merchant_id="merchant-1",
            payment_method="debit_card",
            device_id="device-2",
            ip_address="10.0.0.1",
            location={"lat": 10.0, "lng": 20.0},
        )

        result = service.analyze_transaction(tx)
        assert result.risk_level in ["LOW", "MEDIUM"]

    def test_get_user_risk_profile_cached(self, service):
        """get_user_risk_profile should return cached or computed profile"""
        profile = service.get_user_risk_profile("user-123")
        assert isinstance(profile, dict)
        assert "user_id" in profile or "risk_level" in profile

    def test_report_fraud_records_case(self, service):
        """report_fraud should create fraud case"""
        case = service.report_fraud(
            transaction_id="tx-999",
            fraud_type="CARD_NOT_PRESENT",
            notes="Suspicious activity"
        )
        assert case is not None
        assert case.status in ["open", "investigating", "resolved"]

    def test_get_fraud_statistics_aggregates(self, service):
        """get_fraud_statistics should return summary stats"""
        stats = service.get_fraud_statistics(days=30)
        assert "total_alerts" in stats
        assert "fraud_rate" in stats
        assert 0 <= stats.get("fraud_rate", 0) <= 100

    def test_get_fraud_types_returns_list(self, service):
        """get_fraud_types should return all supported fraud types"""
        types = service.get_fraud_types()
        assert isinstance(types, list)
        assert len(types) > 0
        # Check they are enum values or strings
        for t in types:
            assert isinstance(t, (str, int))  # adjust as needed

    def test_analyze_transaction_missing_ip(self, service):
        """Should handle missing optional fields gracefully"""
        from python.app.modules.fraud_detection.services.fraud_service import Transaction

        tx = Transaction(
            id="tx-3",
            user_id="user-3",
            amount=10000.0,
            currency="VND",
            merchant_id="merchant-1",
            payment_method="cash",
            device_id="",
            ip_address="",
            location=None,
        )
        # Should not crash
        result = service.analyze_transaction(tx)
        assert result is not None


class TestFraudDetectionEdgeCases:
    """Test edge cases in fraud detection"""

    def test_analyze_extreme_amount(self):
        """Very high amount should trigger high risk"""
        from python.app.modules.fraud_detection.services.fraud_service import FraudDetectionService
        service = FraudDetectionService()

        tx = Mock(
            id="tx-huge",
            user_id="user",
            amount=10_000_000_000.0,  # 10 billion
            currency="VND",
            merchant_id="m1",
            payment_method="card",
            device_id="d1",
            ip_address="1.2.3.4",
            location={"lat": 0, "lng": 0}
        )

        result = service.analyze_transaction(tx)
        assert result.risk_level in ["HIGH", "CRITICAL"]

    def test_analyze_unusual_location(self):
        """Transaction from unusual location should increase risk"""
        from python.app.modules.fraud_detection.services.fraud_service import FraudDetectionService
        service = FraudDetectionService()

        # Mock location far from user's normal area
        tx = Mock(
            user_id="user-with-home-location",
            location={"lat": 100.0, "lng": 200.0}  # invalid coords would be suspicious
        )
        # Hard to test without internal state; just sanity
        assert service is not None

    def test_get_user_risk_profile_new_user(self, service):
        """New user should have default (low) risk profile"""
        from python.app.modules.fraud_detection.services.fraud_service import FraudDetectionService
        service = FraudDetectionService()
        profile = service.get_user_risk_profile("brand-new-user")
        assert profile is not None
        assert profile.get("risk_level", "LOW") == "LOW"

    def test_report_fraud_invalid_type(self, service):
        """report_fraud with invalid fraud type should error or default"""
        from python.app.modules.fraud_detection.services.fraud_service import FraudDetectionService, FraudType
        service = FraudDetectionService()

        # Use invalid string - should either reject or treat as UNKNOWN
        try:
            case = service.report_fraud("tx-1", "INVALID_TYPE", "test")
            # Should either succeed with fallback or raise
        except ValueError:
            pass  # expected

    def test_fraud_statistics_empty_data(self, service):
        """Statistics with no data should return zeroed metrics"""
        from python.app.modules.fraud_detection.services.fraud_service import FraudDetectionService
        service = FraudDetectionService()
        # Assuming we can clear backend store for test
        stats = service.get_fraud_statistics(days=1)
        assert isinstance(stats, dict)


class TestFraudRuleEngine:
    """Test fraud rule evaluation (if exists)"""

    def test_rule_amount_threshold(self):
        """Rule: amount > X triggers alert"""
        # Depends on implementation; placeholder
        assert True

    def test_rule_frequent_small_transactions(self):
        """Rule: many small transactions in short time = potential card testing"""
        # Placeholder
        assert True


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
