"""Unit tests for app.modules.data_processing.service (DataProcessingService)."""
import pytest
from datetime import datetime

from app.modules.data_processing.service import DataProcessingService


@pytest.fixture
def service():
    return DataProcessingService()


# ==== process_batch ====

def test_process_batch_empty_returns_empty(service):
    assert service.process_batch([]) == []


def test_process_batch_adds_id_when_missing(service):
    records = [{"name": "Alice"}]
    result = service.process_batch(records)
    assert "id" in result[0]


def test_process_batch_preserves_existing_id(service):
    records = [{"id": "custom-id", "name": "Bob"}]
    result = service.process_batch(records)
    assert result[0]["id"] == "custom-id"


def test_process_batch_adds_created_at_when_missing(service):
    records = [{"name": "Alice"}]
    result = service.process_batch(records)
    assert "created_at" in result[0]


def test_process_batch_preserves_created_at(service):
    records = [{"name": "Alice", "created_at": "2024-01-01"}]
    result = service.process_batch(records)
    assert result[0]["created_at"] == "2024-01-01"


def test_process_batch_strips_string_whitespace(service):
    records = [{"name": "  Alice  "}]
    result = service.process_batch(records)
    assert result[0]["name"] == "Alice"


def test_process_batch_removes_none_values(service):
    records = [{"name": "Alice", "age": None}]
    result = service.process_batch(records)
    assert "age" not in result[0]


def test_process_batch_preserves_non_string_values(service):
    records = [{"age": 30, "active": True}]
    result = service.process_batch(records)
    assert result[0]["age"] == 30
    assert result[0]["active"] is True


def test_process_batch_processes_multiple_records(service):
    records = [
        {"name": "Alice"},
        {"name": "Bob"},
        {"name": "Carol"},
    ]
    result = service.process_batch(records)
    assert len(result) == 3


def test_process_batch_rejects_non_list(service):
    with pytest.raises(ValueError, match="must be a list"):
        service.process_batch("not-a-list")


def test_process_batch_rejects_oversized_batch(service):
    oversized = [{"name": f"r{i}"} for i in range(DataProcessingService.MAX_BATCH_SIZE + 1)]
    with pytest.raises(ValueError, match="exceeds maximum"):
        service.process_batch(oversized)


def test_process_batch_accepts_max_size(service):
    max_size = [{"name": f"r{i}"} for i in range(DataProcessingService.MAX_BATCH_SIZE)]
    result = service.process_batch(max_size)
    assert len(result) == DataProcessingService.MAX_BATCH_SIZE


def test_process_batch_rejects_non_dict_record(service):
    records = [{"name": "Alice"}, "not-a-dict"]
    with pytest.raises(ValueError, match="not a dict"):
        service.process_batch(records)


def test_process_batch_rejects_non_dict_with_index(service):
    records = [{"name": "Alice"}, "bad-record"]
    with pytest.raises(ValueError, match="index 1"):
        service.process_batch(records)


def test_process_batch_preserves_nested_dict(service):
    records = [{"name": "Alice", "address": {"city": "NY"}}]
    result = service.process_batch(records)
    assert result[0]["address"] == {"city": "NY"}


def test_process_batch_preserves_list_values(service):
    records = [{"tags": ["a", "b"]}]
    result = service.process_batch(records)
    assert result[0]["tags"] == ["a", "b"]


# ==== filter_records ====

def test_filter_records_empty_returns_empty(service):
    assert service.filter_records([], "name", "Alice") == []


def test_filter_records_exact_match(service):
    records = [
        {"name": "Alice", "age": 30},
        {"name": "Bob", "age": 25},
        {"name": "Alice", "age": 40},
    ]
    result = service.filter_records(records, "name", "Alice")
    assert len(result) == 2


def test_filter_records_no_match_returns_empty(service):
    records = [{"name": "Alice"}]
    assert service.filter_records(records, "name", "Bob") == []


def test_filter_records_field_missing_in_some_records(service):
    records = [{"name": "Alice"}, {"age": 30}, {"name": "Alice"}]
    result = service.filter_records(records, "name", "Alice")
    # Records without the field won't match
    assert len(result) == 2


def test_filter_records_rejects_empty_field(service):
    with pytest.raises(ValueError, match="non-empty"):
        service.filter_records([{}], "", "value")


def test_filter_records_rejects_non_string_field(service):
    with pytest.raises(ValueError, match="non-empty"):
        service.filter_records([{}], 123, "value")


def test_filter_records_rejects_none_field(service):
    with pytest.raises(ValueError, match="non-empty"):
        service.filter_records([{}], None, "value")


def test_filter_records_with_numeric_value(service):
    records = [{"age": 30}, {"age": 25}, {"age": 30}]
    result = service.filter_records(records, "age", 30)
    assert len(result) == 2


def test_filter_records_preserves_original_order(service):
    records = [{"id": 1}, {"id": 2}, {"id": 3}]
    result = service.filter_records(records, "id", 2)
    assert result[0]["id"] == 2


# ==== aggregate_sum ====

def test_aggregate_sum_empty_returns_empty(service):
    assert service.aggregate_sum([], "category", "amount") == {}


def test_aggregate_sum_single_group(service):
    records = [{"category": "A", "amount": 10}]
    result = service.aggregate_sum(records, "category", "amount")
    assert result == {"A": 10}


def test_aggregate_sum_multiple_records_same_group(service):
    records = [
        {"category": "A", "amount": 10},
        {"category": "A", "amount": 20},
        {"category": "A", "amount": 5},
    ]
    result = service.aggregate_sum(records, "category", "amount")
    assert result == {"A": 35}


def test_aggregate_sum_multiple_groups(service):
    records = [
        {"category": "A", "amount": 10},
        {"category": "B", "amount": 20},
        {"category": "A", "amount": 5},
    ]
    result = service.aggregate_sum(records, "category", "amount")
    assert result == {"A": 15, "B": 20}


def test_aggregate_sum_skips_records_with_missing_group(service):
    records = [
        {"category": "A", "amount": 10},
        {"amount": 20},  # missing category
    ]
    result = service.aggregate_sum(records, "category", "amount")
    assert result == {"A": 10}


def test_aggregate_sum_default_zero_for_missing_amount(service):
    records = [{"category": "A"}]  # no amount
    result = service.aggregate_sum(records, "category", "amount")
    assert result == {"A": 0}


def test_aggregate_sum_rejects_non_numeric_amount(service):
    records = [{"category": "A", "amount": "not-a-number"}]
    with pytest.raises(ValueError, match="Invalid numeric"):
        service.aggregate_sum(records, "category", "amount")


def test_aggregate_sum_converts_string_numbers(service):
    records = [{"category": "A", "amount": "10.5"}]
    result = service.aggregate_sum(records, "category", "amount")
    assert result == {"A": 10.5}


def test_aggregate_sum_rejects_missing_group_by(service):
    with pytest.raises(ValueError, match="required"):
        service.aggregate_sum([{"amount": 10}], "", "amount")


def test_aggregate_sum_rejects_missing_sum_field(service):
    with pytest.raises(ValueError, match="required"):
        service.aggregate_sum([{"category": "A"}], "category", "")


def test_aggregate_sum_with_integer_amounts(service):
    records = [{"category": "A", "amount": 10}, {"category": "A", "amount": 20}]
    result = service.aggregate_sum(records, "category", "amount")
    assert result == {"A": 30.0}


# ==== validate_schema ====

def test_validate_schema_empty_returns_empty(service):
    assert service.validate_schema([], ["name"]) == {}


def test_validate_schema_all_pass(service):
    records = [
        {"id": "1", "name": "Alice"},
        {"id": "2", "name": "Bob"},
    ]
    errors = service.validate_schema(records, ["name"])
    assert errors == {}


def test_validate_schema_missing_fields(service):
    records = [
        {"id": "1", "name": "Alice"},
        {"id": "2"},  # missing name
        {"id": "3", "name": "Carol"},
    ]
    errors = service.validate_schema(records, ["name"])
    assert errors == {"2": ["name"]}


def test_validate_schema_multiple_missing_fields(service):
    records = [
        {"id": "1", "name": "Alice"},  # missing age
        {"id": "2"},  # missing name AND age
    ]
    errors = service.validate_schema(records, ["name", "age"])
    assert errors == {"1": ["age"], "2": ["name", "age"]}


def test_validate_schema_missing_id_record_uses_unknown(service):
    records = [{}]  # no id, no required fields
    errors = service.validate_schema(records, ["name"])
    assert errors == {"unknown": ["name"]}


def test_validate_schema_empty_required_fields_passes_all(service):
    records = [{"id": "1"}, {"id": "2"}]
    errors = service.validate_schema(records, [])
    assert errors == {}


def test_validate_schema_partial_missing(service):
    records = [
        {"id": "1", "a": 1, "b": 2, "c": 3},
        {"id": "2", "a": 1},  # missing b, c
        {"id": "3", "b": 2},  # missing a, c
    ]
    errors = service.validate_schema(records, ["a", "b", "c"])
    assert "1" not in errors
    assert "2" in errors
    assert "3" in errors
    assert set(errors["2"]) == {"b", "c"}
    assert set(errors["3"]) == {"a", "c"}


# ==== Integration scenarios ====

def test_full_pipeline_process_filter_aggregate(service):
    """End-to-end: process → filter → aggregate."""
    raw_records = [
        {"category": "A", "amount": "10"},  # string amount
        {"category": "B", "amount": 20},
        {"category": "A", "amount": 5},
        {"category": "B", "amount": 15},
    ]

    # 1. Process batch
    processed = service.process_batch(raw_records)
    assert len(processed) == 4

    # 2. Filter by category
    a_records = service.filter_records(processed, "category", "A")
    assert len(a_records) == 2

    # 3. Aggregate
    total_a = service.aggregate_sum(a_records, "category", "amount")
    assert total_a == {"A": 15.0}


def test_validation_after_processing(service):
    """Validate that processing maintains required fields."""
    records = [{"name": "Alice"}, {"name": "Bob"}]
    processed = service.process_batch(records)

    # All processed records should have id + created_at + name
    errors = service.validate_schema(processed, ["id", "name", "created_at"])
    assert errors == {}
