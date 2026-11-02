"""DataProcessingService - Generic data transformation and batch processing."""
from typing import List, Dict, Any, Optional
from datetime import datetime
import uuid
import logging

logger = logging.getLogger(__name__)

class DataProcessingService:
    """
    Service for data processing operations: batch processing, filtering, aggregation.
    Provides simple ETL-like capabilities with validation and transformation.
    """

    MAX_BATCH_SIZE = 1000

    def process_batch(self, *args, **kwargs):
        return self._process_batch_impl(*args, **kwargs)
    def _process_batch_impl(self, records: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Process a batch of records:
        - Validate input type and size
        - Clean each record (strip strings, remove None values)
        - Add id and created_at if missing
        - Return processed records

        Security: Limits batch size to prevent DoS.
        """
        if not isinstance(records, list):
            raise ValueError("Records must be a list")
        if len(records) > self.MAX_BATCH_SIZE:
            raise ValueError(f"Batch size exceeds maximum ({self.MAX_BATCH_SIZE})")

        processed = []
        for idx, record in enumerate(records):
            if not isinstance(record, dict):
                raise ValueError(f"Record at index {idx} is not a dict")
            cleaned = self._clean_record(record)
            validated = self._ensure_metadata(cleaned)
            processed.append(validated)

        logger.info("Processed batch", extra={"count": len(processed)})
        return processed

    def _clean_record(self, record: Dict[str, Any]) -> Dict[str, Any]:
        """Remove None values and strip string whitespace."""
        cleaned = {}
        for key, value in record.items():
            if value is None:
                continue
            if isinstance(value, str):
                cleaned[key] = value.strip()
            else:
                cleaned[key] = value
        return cleaned

    def _ensure_metadata(self, record: Dict[str, Any]) -> Dict[str, Any]:
        """Ensure record has an id and created_at."""
        if 'id' not in record:
            record['id'] = str(uuid.uuid4())
        if 'created_at' not in record:
            record['created_at'] = datetime.utcnow().isoformat()
        return record

    def filter_records(
        self,
        records: List[Dict[str, Any]],
        field: str,
        value: Any
    ) -> List[Dict[str, Any]]:
        """
        Filter records by exact match on a field.
        Requires non-empty field name.
        """
        if not records:
            return []
        if not field or not isinstance(field, str):
            raise ValueError("Field must be a non-empty string")

        filtered = [r for r in records if r.get(field) == value]
        logger.debug("Filtered records", extra={"field": field, "value": value, "count": len(filtered)})
        return filtered

    def aggregate_sum(self, *args, **kwargs):
        return self._aggregate_sum_impl(*args, **kwargs)
    def _aggregate_sum_impl(self, records: List[Dict[str, Any]], group_by: str, sum_field: str) -> Dict[str, float]:
        """
        Group records by a field and sum another numeric field.
        Returns mapping: group_value -> sum.
        """
        if not records:
            return {}
        if not group_by or not sum_field:
            raise ValueError("group_by and sum_field are required")

        groups: Dict[str, float] = {}
        for r in records:
            key = r.get(group_by)
            if key is None:
                continue
            try:
                amount = float(r.get(sum_field, 0))
            except (TypeError, ValueError):
                raise ValueError(f"Invalid numeric value in field '{sum_field}' for record: {r}")
            groups[key] = groups.get(key, 0) + amount

        logger.debug("Aggregated sum", extra={"group_by": group_by, "sum_field": sum_field, "groups": len(groups)})
        return groups

    def validate_schema(self, *args, **kwargs):
        return self._validate_schema_impl(*args, **kwargs)
    def _validate_schema_impl(self, records: List[Dict[str, Any]], required_fields: List[str]) -> Dict[str, List[str]]:
        """
        Validate that each record contains all required fields.
        Returns dict mapping record IDs to list of missing fields.
        """
        errors: Dict[str, List[str]] = {}
        for record in records:
            rec_id = record.get('id', 'unknown')
            missing = [f for f in required_fields if f not in record]
            if missing:
                errors[rec_id] = missing

        if errors:
            logger.warning("Schema validation errors", extra={"errors_count": len(errors)})
        else:
            logger.info("All records passed schema validation")

        return errors
