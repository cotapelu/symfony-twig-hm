"""Custom assertions for API responses."""
from datetime import datetime
import json

def assert_json_response(response, expected_status: int = 200, expected_keys: list = None):
    """Assert that response is valid JSON with expected status and keys."""
    assert response.status_code == expected_status, f"Expected status {expected_status}, got {response.status_code}"
    try:
        data = response.json()
    except json.JSONDecodeError:
        raise AssertionError("Response is not valid JSON")

    if expected_keys:
        for key in expected_keys:
            assert key in data, f"Missing key '{key}' in response JSON"

def assert_iso8601_datetime(value: str):
    """Assert that a string is a valid ISO 8601 datetime."""
    try:
        datetime.fromisoformat(value.replace('Z', '+00:00'))
    except ValueError as e:
        raise AssertionError(f"Invalid ISO8601 datetime: {value}") from e

def assert_pagination_meta(data: dict):
    """Assert pagination metadata is present and valid."""
    assert 'page' in data
    assert 'page_size' in data
    assert 'total' in data
    assert isinstance(data['page'], int)
    assert isinstance(data['page_size'], int)
    assert isinstance(data['total'], int)
