from fastapi import APIRouter, HTTPException, Depends
from typing import Dict, List, Optional
from datetime import datetime, timedelta
from pydantic import BaseModel

from ..services.fraud_service import FraudDetectionService, Transaction, FraudType
from app.shared.middleware.auth import get_current_user_id

router = APIRouter(prefix="/api/fraud", tags=["fraud_detection"])

# Initialize service
fraud_service = FraudDetectionService()

class TransactionRequest(BaseModel):
    id: str
    user_id: str
    amount: float
    currency: str = "VND"
    merchant_id: str
    payment_method: str
    device_id: str
    ip_address: str
    location: Dict[str, float]
    metadata: Optional[Dict] = None

class FraudReportRequest(BaseModel):
    fraud_type: str
    notes: Optional[str] = None

class FraudScoreResponse(BaseModel):
    transaction_id: str
    risk_level: str
    score: float
    fraud_types: List[str]
    reasons: List[str]
    recommended_action: str
    timestamp: datetime

@router.post("/analyze", response_model=FraudScoreResponse)
async def analyze_transaction(
    transaction: TransactionRequest,
    current_user_id: str = Depends(get_current_user_id)
) -> FraudScoreResponse:
    """Analyze a transaction for fraud indicators"""
    # IDOR: ensure transaction belongs to authenticated user
    if transaction.user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot analyze transactions for other users")
    try:
        tx = Transaction(
            id=transaction.id,
            user_id=transaction.user_id,
            amount=transaction.amount,
            currency=transaction.currency,
            merchant_id=transaction.merchant_id,
            payment_method=transaction.payment_method,
            device_id=transaction.device_id,
            ip_address=transaction.ip_address,
            location=transaction.location,
            timestamp=datetime.now(),
            metadata=transaction.metadata
        )
        
        result = fraud_service.analyze_transaction(tx)
        
        return FraudScoreResponse(
            transaction_id=result.transaction_id,
            risk_level=result.risk_level.value,
            score=result.score,
            fraud_types=[ft.value for ft in result.fraud_types],
            reasons=result.reasons,
            recommended_action=result.recommended_action,
            timestamp=result.timestamp
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/user/{user_id}/risk-profile")
async def get_user_risk_profile(
    user_id: str,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict:
    """Get fraud risk profile for a user (IDOR protected)"""
    if user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other users' risk profiles")
    try:
        profile = fraud_service.get_user_risk_profile(user_id)
        return profile
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/report/{transaction_id}")
async def report_fraud(
    transaction_id: str,
    report: FraudReportRequest,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict:
    """Report a confirmed fraud case (IDOR protected)"""
    # Verify ownership: the transaction must belong to the current user
    tx = fraud_service.get_transaction(transaction_id)
    if not tx or tx.user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Not authorized to report this transaction")
    try:
        fraud_type = FraudType(report.fraud_type)
        result = fraud_service.report_fraud(
            transaction_id=transaction_id,
            fraud_type=fraud_type,
            notes=report.notes
        )
        return result
    except ValueError:
        raise HTTPException(status_code=400, detail=f"Invalid fraud type. Must be one of: {[ft.value for ft in FraudType]}")
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/statistics")
async def get_fraud_statistics(
    days: int = 30
):
    """Get fraud detection statistics"""
    try:
        end_date = datetime.now()
        start_date = end_date - timedelta(days=days)
        
        stats = fraud_service.get_fraud_statistics(start_date, end_date)
        return stats
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/fraud-types")
async def get_fraud_types():
    """Get list of supported fraud types"""
    return {
        "fraud_types": [
            {
                "type": ft.value,
                "description": {
                    FraudType.VELOCITY: "High transaction frequency",
                    FraudType.AMOUNT: "Unusual transaction amount",
                    FraudType.LOCATION: "Location mismatch",
                    FraudType.DEVICE: "Suspicious device",
                    FraudType.BEHAVIOR: "Behavioral anomaly",
                    FraudType.PATTERN: "Pattern mismatch"
                }.get(ft, "Unknown")
            }
            for ft in FraudType
        ]
    }

@router.post("/webhook/transaction")
async def webhook_transaction(transaction: TransactionRequest):
    """Webhook for real-time transaction fraud check"""
    try:
        tx = Transaction(
            id=transaction.id,
            user_id=transaction.user_id,
            amount=transaction.amount,
            currency=transaction.currency,
            merchant_id=transaction.merchant_id,
            payment_method=transaction.payment_method,
            device_id=transaction.device_id,
            ip_address=transaction.ip_address,
            location=transaction.location,
            timestamp=datetime.now(),
            metadata=transaction.metadata
        )
        
        result = fraud_service.analyze_transaction(tx)
        
        # Return quick decision for webhook
        return {
            "transaction_id": result.transaction_id,
            "decision": result.recommended_action,
            "risk_level": result.risk_level.value,
            "score": result.score,
            "requires_3ds": result.risk_level.value in ["high", "critical"],
            "timestamp": result.timestamp.isoformat()
        }
    except Exception as e:
        # On error, return safe default
        return {
            "transaction_id": transaction.id,
            "decision": "review",
            "risk_level": "medium",
            "score": 0.5,
            "requires_3ds": True,
            "timestamp": datetime.now().isoformat(),
            "error": str(e)
        }
