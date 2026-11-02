"""Integration test for fallback pattern in Finance summary."""
import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient
from modules.Finance.routes import router, compute_summary
from unittest.mock import patch


@pytest.fixture
def client():
    app = FastAPI()
    app.include_router(router)
    return TestClient(app)


def test_get_summary_fallback_on_error(client, monkeypatch):
    """Simulate an error in compute_summary and verify fallback response."""
    # Monkeypatch compute_summary to raise an exception
    async def broken_summary():
        raise RuntimeError("Simulated failure")

    monkeypatch.setattr("modules.Finance.routes.compute_summary", broken_summary)

    response = client.get("/api/finance/summary")
    assert response.status_code == 200
    data = response.json()
    # Verify fallback values are zeros
    assert data["totalIncome"] == 0
    assert data["totalExpense"] == 0
    assert data["balance"] == 0
    assert data["collected"] == 0
    assert data["pending"] == 0


def test_get_summary_success(client):
    """Normal operation should return computed values."""
    response = client.get("/api/finance/summary")
    assert response.status_code == 200
    data = response.json()
    # Values should be from the in-memory transactions list
    assert "totalIncome" in data
    assert "totalExpense" in data
    # Check that they are integers (or numbers)
    assert isinstance(data["totalIncome"], int)
    assert isinstance(data["totalExpense"], int)
