"""Unit tests for DataProcessingService."""
import pytest
from datetime import datetime

from app.modules.data_processing.service import DataProcessingService


class TestDataProcessingService:
    """Test suite for DataProcessingService."""

    def setup_method(self):
        """Set up test fixtures before each test method."""
        self.service = DataProcessingService()

    def test_process_batch_success(self):
        """Test processing a batch of valid records."""
        records = [
            {"name": "Alice", "age": 30},
            {"name": "Bob", "age": 25, "email": "bob@example.com"},
        ]
        result = self.service.process_batch(records)

        assert len(result) == 2
        # Each record should have id and created_at
        for rec in result:
            assert "id" in rec
            assert isinstance(rec["id"], str)
            assert "created_at" in rec
            # Name should be stripped (no change)
            assert rec["name"] in ("Alice", "Bob")
        # None values should be removed
        assert "email" not in result[0]
        assert result[1]["email"] == "bob@example.com"

    def test_process_batch_empty_list(self):
        """Test processing an empty batch."""
        result = self.service.process_batch([])
        assert result == []

    def test_process_batch_not_list(self):
        """Test that non-list input raises ValueError."""
        with pytest.raises(ValueError, match="Records must be a list"):
            self.service.process_batch({"not": "a list"})

    def test_process_batch_too_large(self):
        """Test that batch exceeding MAX_BATCH_SIZE raises ValueError."""
        large_batch = [{"x": i} for i in range(self.service.MAX_BATCH_SIZE + 1)]
        with pytest.raises(ValueError, match="Batch size exceeds maximum"):
            self.service.process_batch(large_batch)

    def test_process_batch_record_not_dict(self):
        """Test that a non-dict record raises ValueError."""
        records = [{"a": 1}, ["not", "dict"], {"b": 2}]
        with pytest.raises(ValueError, match="Record at index 1 is not a dict"):
            self.service.process_batch(records)

    def test_clean_record_strips_strings_and_removes_none(self):
        """Test that _clean_record trims strings and removes None values."""
        record = {
            "name": "  Charlie  ",
            "email": None,
            "age": 40,
            "notes": "",
        }
        cleaned = self.service._clean_record(record)
        assert cleaned["name"] == "Charlie"
        assert "email" not in cleaned
        assert cleaned["age"] == 40
        # Empty string is kept (not None)
        assert cleaned["notes"] == ""

    def test_ensure_metadata_adds_missing_fields(self):
        """Test that _ensure_metadata adds id and created_at if missing."""
        record = {"name": "Dana"}
        result = self.service._ensure_metadata(record)
        assert "id" in result
        assert isinstance(result["id"], str)
        assert "created_at" in result
        # Should parse as ISO datetime
        datetime.fromisoformat(result["created_at"])

    def test_ensure_metadata_preserves_existing(self):
        """Test that existing id and created_at are not overwritten."""
        record = {"id": "existing-id", "created_at": "2020-01-01T00:00:00", "name": "Eve"}
        result = self.service._ensure_metadata(record)
        assert result["id"] == "existing-id"
        assert result["created_at"] == "2020-01-01T00:00:00"

    def test_filter_records_basic(self):
        """Test filtering records by field value."""
        records = [
            {"city": "NYC", "population": 8},
            {"city": "LA", "population": 4},
            {"city": "NYC", "population": 8.5},
        ]
        filtered = self.service.filter_records(records, "city", "NYC")
        assert len(filtered) == 2
        assert all(r["city"] == "NYC" for r in filtered)

    def test_filter_records_empty_records(self):
        """Test filtering with empty records returns empty list."""
        result = self.service.filter_records([], "city", "NYC")
        assert result == []

    def test_filter_records_invalid_field(self):
        """Test that empty or non-string field raises ValueError."""
        with pytest.raises(ValueError, match="Field must be a non-empty string"):
            self.service.filter_records([{"a": 1}], "", "val")
        with pytest.raises(ValueError, match="Field must be a non-empty string"):
            self.service.filter_records([{"a": 1}], 123, "val")

    def test_aggregate_sum_basic(self):
        """Test grouping and summing numeric field."""
        records = [
            {"product": "A", "sales": 100},
            {"product": "B", "sales": 200},
            {"product": "A", "sales": 150},
        ]
        result = self.service.aggregate_sum(records, "product", "sales")
        assert result == {"A": 250.0, "B": 200.0}

    def test_aggregate_sum_empty_records(self):
        """Test aggregating empty records returns empty dict."""
        result = self.service.aggregate_sum([], "product", "sales")
        assert result == {}

    def test_aggregate_sum_requires_group_by_and_sum_field(self):
        """Test that missing group_by or sum_field raises ValueError."""
        records = [{"a": 1}]
        with pytest.raises(ValueError, match="group_by and sum_field are required"):
            self.service.aggregate_sum(records, "", "val")
        with pytest.raises(ValueError, match="group_by and sum_field are required"):
            self.service.aggregate_sum(records, "a", "")

    def test_aggregate_sum_handles_non_numeric(self):
        """Test that non-numeric sum_field raises ValueError."""
        records = [{"category": "X", "amount": "not-a-number"}]
        with pytest.raises(ValueError, match="Invalid numeric value"):
            self.service.aggregate_sum(records, "category", "amount")

    def test_validate_schema_success(self):
        """Test validation passes when all required fields present."""
        records = [
            {"id": "1", "name": "A", "value": 10},
            {"id": "2", "name": "B", "value": 20},
        ]
        errors = self.service.validate_schema(records, ["id", "name"])
        assert errors == {}

    def test_validate_schema_detects_missing(self):
        """Test validation detects missing fields."""
        records = [
            {"id": "1", "name": "A"},
            {"id": "2", "value": 20},  # missing name
            {"name": "C"},  # missing id
        ]
        errors = self.service.validate_schema(records, ["id", "name"])
        assert "2" in errors
        assert "name" in errors["2"]
        # For record without id, key is 'unknown'
        assert "unknown" in errors
        assert "id" in errors["unknown"]

    def test_validate_schema_empty_required_fields(self):
        """Test validation with empty required fields list always passes."""
        records = [{}]
        errors = self.service.validate_schema(records, [])
        assert errors == {}
