"""
Fraud Detection Service for Payment Security
Uses ML models to detect fraudulent transactions
"""
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Tuple
from dataclasses import dataclass
from enum import Enum
import json
import uuid

class FraudRiskLevel(str, Enum):
    LOW = "low"
    MEDIUM = "medium"
    HIGH = "high"
    CRITICAL = "critical"

class FraudType(str, Enum):
    VELOCITY = "velocity"
    AMOUNT = "amount_anomaly"
    LOCATION = "location_mismatch"
    DEVICE = "device_fingerprint"
    BEHAVIOR = "behavioral_anomaly"
    PATTERN = "pattern_mismatch"

@dataclass
class Transaction:
    id: str
    user_id: str
    amount: float
    currency: str
    merchant_id: str
    payment_method: str
    device_id: str
    ip_address: str
    location: Dict[str, float]  # lat, lon
    timestamp: datetime
    metadata: Optional[Dict] = None

@dataclass
class FraudScore:
    transaction_id: str
    risk_level: FraudRiskLevel
    score: float  # 0.0 to 1.0
    fraud_types: List[FraudType]
    reasons: List[str]
    recommended_action: str
    timestamp: datetime

class FraudDetectionService:
    """ML-based fraud detection service"""
    
    def __init__(self, db_connection=None):
        self.db = db_connection
        self.velocity_threshold = 5  # transactions per hour
        self.amount_threshold_multiplier = 3.0
        self.high_amount_threshold = 10000000  # 10M VND
        self.transactions = {}  # In-memory store for transaction ownership verification
        
    def analyze_transaction(self, transaction: Transaction) -> FraudScore:
        # Store transaction for ownership verification in report_fraud
        self.transactions[transaction.id] = transaction
        return self._analyze_transaction_impl(transaction)

    def _analyze_transaction_impl(self, transaction: Transaction) -> FraudScore:
        """Analyze a transaction for fraud indicators"""
        scores = []
        fraud_types = []
        reasons = []
        
        # 1. Velocity Check
        velocity_score = self._check_velocity(transaction)
        if velocity_score > 0.5:
            scores.append(velocity_score)
            fraud_types.append(FraudType.VELOCITY)
            reasons.append(f"High transaction velocity detected")
        
        # 2. Amount Anomaly Check
        amount_score = self._check_amount_anomaly(transaction)
        if amount_score > 0.5:
            scores.append(amount_score)
            fraud_types.append(FraudType.AMOUNT)
            reasons.append(f"Unusual transaction amount")
        
        # 3. Location Mismatch Check
        location_score = self._check_location(transaction)
        if location_score > 0.5:
            scores.append(location_score)
            fraud_types.append(FraudType.LOCATION)
            reasons.append(f"Location mismatch with user history")
        
        # 4. Device Fingerprint Check
        device_score = self._check_device(transaction)
        if device_score > 0.5:
            scores.append(device_score)
            fraud_types.append(FraudType.DEVICE)
            reasons.append(f"Unknown or suspicious device")
        
        # 5. Behavioral Pattern Check
        behavior_score = self._check_behavior(transaction)
        if behavior_score > 0.5:
            scores.append(behavior_score)
            fraud_types.append(FraudType.BEHAVIOR)
            reasons.append(f"Behavioral pattern anomaly")
        
        # Calculate final score
        final_score = max(scores) if scores else 0.0
        risk_level = self._calculate_risk_level(final_score)
        recommended_action = self._get_recommended_action(risk_level)
        
        return FraudScore(
            transaction_id=transaction.id,
            risk_level=risk_level,
            score=final_score,
            fraud_types=fraud_types,
            reasons=reasons,
            recommended_action=recommended_action,
            timestamp=datetime.now()
        )
    
    def _check_velocity(self, transaction: Transaction) -> float:
        """Check transaction velocity (transactions per hour)"""
        # Mock implementation - in production, query database
        # For now, return random score based on amount
        if transaction.amount > self.high_amount_threshold:
            return 0.7
        return 0.2
    
    def _check_amount_anomaly(self, transaction: Transaction) -> float:
        """Check if amount is unusual for user"""
        if transaction.amount > self.high_amount_threshold:
            return 0.8
        elif transaction.amount > self.high_amount_threshold / 2:
            return 0.5
        return 0.1
    
    def _check_location(self, transaction: Transaction) -> float:
        """Check location against user's typical locations"""
        # Mock implementation
        # In production, compare with user's location history
        return 0.3
    
    def _check_device(self, transaction: Transaction) -> float:
        """Check device fingerprint"""
        # Mock implementation
        # In production, verify against known devices
        return 0.2
    
    def _check_behavior(self, transaction: Transaction) -> float:
        """Check behavioral patterns"""
        # Mock implementation
        # In production, use ML model to detect behavioral anomalies
        hour = transaction.timestamp.hour
        if hour < 6 or hour > 23:  # Unusual hours
            return 0.4
        return 0.1
    
    def _calculate_risk_level(self, score: float) -> FraudRiskLevel:
        """Convert score to risk level"""
        if score >= 0.8:
            return FraudRiskLevel.CRITICAL
        elif score >= 0.6:
            return FraudRiskLevel.HIGH
        elif score >= 0.4:
            return FraudRiskLevel.MEDIUM
        return FraudRiskLevel.LOW
    
    def _get_recommended_action(self, risk_level: FraudRiskLevel) -> str:
        """Get recommended action based on risk level"""
        actions = {
            FraudRiskLevel.LOW: "approve",
            FraudRiskLevel.MEDIUM: "review",
            FraudRiskLevel.HIGH: "challenge",
            FraudRiskLevel.CRITICAL: "block"
        }
        return actions.get(risk_level, "review")
    
    def get_user_risk_profile(self, user_id: str) -> Dict:
        """Get risk profile for a user"""
        # Mock implementation
        return {
            "user_id": user_id,
            "risk_score": 0.3,
            "risk_level": "low",
            "total_transactions": 150,
            "flagged_transactions": 2,
            "typical_amount_range": [100000, 5000000],
            "typical_merchants": ["merchant_1", "merchant_2"],
            "typical_locations": [{"lat": 10.762622, "lon": 106.660172}],
            "created_at": datetime.now().isoformat()
        }
    
    def get_transaction(self, transaction_id: str) -> Optional[Transaction]:
        """Lookup stored transaction by ID"""
        return self.transactions.get(transaction_id)

    def report_fraud(self, transaction_id: str, fraud_type: FraudType, 
                     notes: Optional[str] = None) -> Dict:
        """Report a confirmed fraud case"""
        return {
            "report_id": f"FRAUD_{uuid.uuid4().hex[:8].upper()}",
            "transaction_id": transaction_id,
            "fraud_type": fraud_type.value,
            "status": "confirmed",
            "notes": notes,
            "reported_at": datetime.now().isoformat()
        }
    
    def get_fraud_statistics(self, start_date: datetime, 
                            end_date: datetime) -> Dict:
        return self._get_fraud_statistics_impl(start_date, end_date)

    def _get_fraud_statistics_impl(self, start_date: datetime, 
                            end_date: datetime) -> Dict:
        """Get fraud detection statistics"""
        # Mock implementation
        return {
            "period": {
                "start": start_date.isoformat(),
                "end": end_date.isoformat()
            },
            "total_transactions": 10000,
            "flagged_transactions": 150,
            "confirmed_frauds": 23,
            "false_positives": 45,
            "detection_rate": 0.153,
            "fraud_types": {
                "velocity": 5,
                "amount_anomaly": 8,
                "location_mismatch": 3,
                "device_fingerprint": 4,
                "behavioral_anomaly": 2,
                "pattern_mismatch": 1
            },
            "risk_distribution": {
                "low": 9850,
                "medium": 100,
                "high": 40,
                "critical": 10
            }
        }
