from fastapi import APIRouter, HTTPException, Query
from pydantic import BaseModel
from typing import List, Optional
from datetime import datetime

from observability.resilience import with_timeout, Fallback

router = APIRouter(prefix="/api/finance", tags=["finance"])

class Transaction(BaseModel):
    id: str
    type: str
    category: str
    amount: float
    date: str
    status: str

class BudgetItem(BaseModel):
    category: str
    allocated: float
    spent: float
    remaining: float

transactions = [
    {"id": "1", "type": "Thu", "category": "Thuế", "amount": 150000000, "date": "2026-02-10", "status": "Hoàn thành"},
    {"id": "2", "type": "Chi", "category": "Xây dựng cơ bản", "amount": 85000000, "date": "2026-02-08", "status": "Hoàn thành"},
    {"id": "3", "type": "Thu", "category": "Phí dịch vụ", "amount": 25000000, "date": "2026-02-05", "status": "Hoàn thành"},
    {"id": "4", "type": "Chi", "category": "Chi trả lương", "amount": 120000000, "date": "2026-02-01", "status": "Hoàn thành"},
    {"id": "5", "type": "Chi", "category": "Phát triển CNTT", "amount": 35000000, "date": "2026-01-28", "status": "Hoàn thành"},
]

budget_items = [
    {"category": "Xây dựng cơ bản", "allocated": 500000000, "spent": 325000000, "remaining": 175000000},
    {"category": "Chi phí hành chính", "allocated": 300000000, "spent": 245000000, "remaining": 55000000},
    {"category": "Giáo dục", "allocated": 200000000, "spent": 180000000, "remaining": 20000000},
    {"category": "Y tế", "allocated": 150000000, "spent": 125000000, "remaining": 25000000},
    {"category": "Văn hóa - Thể thao", "allocated": 100000000, "spent": 75000000, "remaining": 25000000},
    {"category": "An ninh - Quốc phòng", "allocated": 80000000, "spent": 65000000, "remaining": 15000000},
]

id_counter = 6

async def compute_summary():
    total_income = sum(t["amount"] for t in transactions if t["type"] == "Thu")
    total_expense = sum(t["amount"] for t in transactions if t["type"] == "Chi")
    return {
        "totalIncome": total_income,
        "totalExpense": total_expense,
        "balance": total_income - total_expense,
        "collected": 175000000,
        "pending": 50000000
    }

async def fallback_summary():
    return {
        "totalIncome": 0,
        "totalExpense": 0,
        "balance": 0,
        "collected": 0,
        "pending": 0
    }

async def compute_statistics():
    return {
        "totalIncome": sum(t["amount"] for t in transactions if t["type"] == "Thu"),
        "totalExpense": sum(t["amount"] for t in transactions if t["type"] == "Chi"),
        "budgetUtilization": 75.5,
        "collections": {
            "tax": 150000000,
            "fees": 25000000,
            "other": 10000000
        }
    }

async def fallback_statistics():
    return {
        "totalIncome": 0,
        "totalExpense": 0,
        "budgetUtilization": 0.0,
        "collections": {"tax": 0, "fees": 0, "other": 0}
    }

async def compute_transactions(page: int, limit: int, type_filter: Optional[str] = None):
    filtered = transactions
    if type_filter:
        filtered = [t for t in filtered if t["type"] == type_filter]
    total = len(filtered)
    start = (page - 1) * limit
    end = start + limit
    return {
        "items": filtered[start:end],
        "total": total,
        "page": page,
        "limit": limit,
        "totalPages": (total + limit - 1) // limit
    }

async def fallback_transactions(page: int, limit: int, type_filter: Optional[str] = None):
    return {
        "items": [],
        "total": 0,
        "page": page,
        "limit": limit,
        "totalPages": 0
    }

async def compute_budget():
    return {
        "items": budget_items,
        "total": len(budget_items)
    }

async def fallback_budget():
    return {
        "items": [],
        "total": 0
    }

@with_timeout(5)
@router.get("/summary")
async def get_summary():
    return await Fallback.with_fallback(compute_summary, fallback_summary)

@with_timeout(5)
@router.get("/statistics")
async def get_statistics():
    return await Fallback.with_fallback(compute_statistics, fallback_statistics)

@with_timeout(5)
@router.get("/transactions")
async def get_transactions(
    page: int = Query(1, ge=1),
    limit: int = Query(20, ge=1, le=100),
    type_filter: Optional[str] = Query(None, alias="type")
):
    return await Fallback.with_fallback(
        compute_transactions,
        fallback_transactions,
        page=page,
        limit=limit,
        type_filter=type_filter
    )

@router.get("/transactions/{transaction_id}")
async def get_transaction(transaction_id: str):
    for t in transactions:
        if t["id"] == transaction_id:
            return t
    raise HTTPException(status_code=404, detail="Transaction not found")

@router.post("/transactions")
async def create_transaction(transaction: Transaction):
    global id_counter
    transaction.id = str(id_counter)
    id_counter += 1
    transactions.append(transaction.dict())
    return transaction

@router.put("/transactions/{transaction_id}")
async def update_transaction(transaction_id: str, transaction: Transaction):
    for i, t in enumerate(transactions):
        if t["id"] == transaction_id:
            transaction.id = transaction_id
            transactions[i] = transaction.dict()
            return transaction
    raise HTTPException(status_code=404, detail="Transaction not found")

@router.delete("/transactions/{transaction_id}")
async def delete_transaction(transaction_id: str):
    for i, t in enumerate(transactions):
        if t["id"] == transaction_id:
            transactions.pop(i)
            return {"message": "Deleted successfully"}
    raise HTTPException(status_code=404, detail="Transaction not found")

@with_timeout(5)
@router.get("/budget")
async def get_budget():
    return await Fallback.with_fallback(
        compute_budget,
        fallback_budget
    )

@router.get("/budget/{category}")
async def get_budget_category(category: str):
    for item in budget_items:
        if item["category"] == category:
            return item
    raise HTTPException(status_code=404, detail="Budget category not found")

@router.put("/budget/{category}")
async def update_budget_category(category: str, item: BudgetItem):
    for i, b in enumerate(budget_items):
        if b["category"] == category:
            item.category = category
            budget_items[i] = item.dict()
            return item
    raise HTTPException(status_code=404, detail="Budget category not found")


