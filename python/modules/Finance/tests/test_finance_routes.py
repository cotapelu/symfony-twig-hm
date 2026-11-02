"""
Integration tests for Finance routes (API endpoints)
Target: Increase Python coverage beyond 85%
"""

import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient
from modules.Finance.routes import router


@pytest.fixture
def client():
    """Create a FastAPI test client with Finance router."""
    app = FastAPI()
    app.include_router(router)
    return TestClient(app)


def test_get_summary(client):
    response = client.get("/api/finance/summary")
    assert response.status_code == 200
    data = response.json()
    assert "totalIncome" in data
    assert "totalExpense" in data
    assert "balance" in data


def test_get_transactions(client):
    response = client.get("/api/finance/transactions")
    assert response.status_code == 200
    data = response.json()
    assert "items" in data
    assert isinstance(data["items"], list)
    assert "total" in data
    assert "page" in data
    assert "limit" in data
    assert "totalPages" in data


def test_get_transactions_with_type_filter(client):
    response = client.get("/api/finance/transactions?type=Thu")
    assert response.status_code == 200
    data = response.json()
    for item in data["items"]:
        assert item["type"] == "Thu"


def test_get_transaction_not_found(client):
    response = client.get("/api/finance/transactions/999999")
    assert response.status_code == 404


def test_create_transaction(client):
    new_tx = {
        "id": "",
        "type": "Thu",
        "category": "Test",
        "amount": 123.0,
        "date": "2026-01-01",
        "status": "Hoàn thành"
    }
    response = client.post("/api/finance/transactions", json=new_tx)
    assert response.status_code == 200
    data = response.json()
    assert "id" in data
    # Verify it appears in list
    resp2 = client.get("/api/finance/transactions")
    assert resp2.status_code == 200
    ids = [t["id"] for t in resp2.json()["items"]]
    assert data["id"] in ids


def test_update_transaction(client):
    # First create a transaction
    new_tx = {
        "id": "",
        "type": "Chi",
        "category": "Init",
        "amount": 50.0,
        "date": "2026-01-02",
        "status": "Hoàn thành"
    }
    create_resp = client.post("/api/finance/transactions", json=new_tx)
    assert create_resp.status_code == 200
    tx_id = create_resp.json()["id"]
    # Update
    updated = {
        "id": tx_id,
        "type": "Chi",
        "category": "Updated",
        "amount": 75.0,
        "date": "2026-01-02",
        "status": "Hoàn thành"
    }
    update_resp = client.put(f"/api/finance/transactions/{tx_id}", json=updated)
    assert update_resp.status_code == 200
    data = update_resp.json()
    assert data["category"] == "Updated"
    assert data["amount"] == 75.0


def test_delete_transaction(client):
    # Create then delete
    new_tx = {
        "id": "",
        "type": "Thu",
        "category": "ToDelete",
        "amount": 10.0,
        "date": "2026-01-03",
        "status": "Hoàn thành"
    }
    create_resp = client.post("/api/finance/transactions", json=new_tx)
    assert create_resp.status_code == 200
    tx_id = create_resp.json()["id"]
    del_resp = client.delete(f"/api/finance/transactions/{tx_id}")
    assert del_resp.status_code == 200
    # Verify get returns 404
    get_resp = client.get(f"/api/finance/transactions/{tx_id}")
    assert get_resp.status_code == 404


def test_get_budget(client):
    response = client.get("/api/finance/budget")
    assert response.status_code == 200
    data = response.json()
    assert "items" in data
    assert isinstance(data["items"], list)
    assert "total" in data


def test_get_budget_category_found(client):
    response = client.get("/api/finance/budget/Xây dựng cơ bản")
    assert response.status_code == 200
    data = response.json()
    assert data["category"] == "Xây dựng cơ bản"


def test_get_budget_category_not_found(client):
    response = client.get("/api/finance/budget/Unknown")
    assert response.status_code == 404


def test_update_budget_category(client):
    # Update existing category
    item = {
        "category": "Xây dựng cơ bản",
        "allocated": 600000000,
        "spent": 300000000,
        "remaining": 300000000
    }
    response = client.put("/api/finance/budget/Xây dựng cơ bản", json=item)
    assert response.status_code == 200
    data = response.json()
    assert data["category"] == "Xây dựng cơ bản"
    assert data["allocated"] == 600000000


def test_get_statistics(client):
    response = client.get("/api/finance/statistics")
    assert response.status_code == 200
    data = response.json()
    assert "totalIncome" in data
    assert "totalExpense" in data
    assert "budgetUtilization" in data
    assert "collections" in data


def test_update_transaction_not_found(client):
    # Attempt to update a non-existent transaction
    upd = {
        "id": "nonexistent",
        "type": "Chi",
        "category": "None",
        "amount": 100.0,
        "date": "2026-01-01",
        "status": "Hoàn thành"
    }
    response = client.put("/api/finance/transactions/nonexistent", json=upd)
    assert response.status_code == 404


def test_delete_transaction_not_found(client):
    response = client.delete("/api/finance/transactions/nonexistent")
    assert response.status_code == 404


def test_update_budget_category_not_found(client):
    item = {
        "category": "Unknown",
        "allocated": 500000000,
        "spent": 100000000,
        "remaining": 400000000
    }
    response = client.put("/api/finance/budget/Unknown", json=item)
    assert response.status_code == 404


def test_get_transactions_type_none(client):
    # Request a type that matches no transactions
    response = client.get("/api/finance/transactions?type=Nonexistent")
    assert response.status_code == 200
    data = response.json()
    assert data["total"] == 0
    assert len(data["items"]) == 0


def test_get_statistics_fallback(client, monkeypatch):
    """Simulate error in compute_statistics and verify fallback response."""
    async def broken_compute():
        raise RuntimeError("simulated failure")
    monkeypatch.setattr("modules.Finance.routes.compute_statistics", broken_compute)
    response = client.get("/api/finance/statistics")
    assert response.status_code == 200
    data = response.json()
    assert data["totalIncome"] == 0
    assert data["totalExpense"] == 0
    assert data["budgetUtilization"] == 0.0
    assert data["collections"] == {"tax": 0, "fees": 0, "other": 0}


def test_get_transactions_fallback(client, monkeypatch):
    """Simulate error in compute_transactions and verify fallback response."""
    async def broken_compute(page, limit, type_filter=None):
        raise RuntimeError("simulated failure")
    monkeypatch.setattr("modules.Finance.routes.compute_transactions", broken_compute)
    response = client.get("/api/finance/transactions")
    assert response.status_code == 200
    data = response.json()
    assert data["items"] == []
    assert data["total"] == 0
    assert data["page"] == 1
    assert data["limit"] == 20
    assert data["totalPages"] == 0


def test_get_budget_fallback(client, monkeypatch):
    """Simulate error in compute_budget and verify fallback response."""
    async def broken_compute():
        raise RuntimeError("simulated failure")
    monkeypatch.setattr("modules.Finance.routes.compute_budget", broken_compute)
    response = client.get("/api/finance/budget")
    assert response.status_code == 200
    data = response.json()
    assert data["items"] == []
    assert data["total"] == 0
