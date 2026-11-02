"""Sample Controller for API layer with input validation and error handling"""
from typing import Optional, Dict, Any, Tuple
from dataclasses import dataclass

class SampleControllerError(Exception):
    """Base exception for SampleController"""
    pass

class ValidationError(SampleControllerError):
    """Raised when input validation fails"""
    pass

class NotFoundError(SampleControllerError):
    """Raised when resource not found"""
    pass

class SampleController:
    MAX_NAME_LENGTH = 255
    MAX_VALUE = 10**12
    MIN_VALUE = -10**12
    MAX_DESCRIPTION_LENGTH = 1000
    MAX_SAMPLE_ID = 10**12

    def __init__(self, application_service: 'SampleApplicationService'):
        self.application_service = application_service

    def _validate_id(self, sample_id: int) -> None:
        """Validate sample ID"""
        if not isinstance(sample_id, int):
            raise ValidationError("Sample ID must be an integer")
        if sample_id <= 0:
            raise ValidationError("Sample ID must be positive")
        if sample_id > self.MAX_SAMPLE_ID:
            raise ValidationError(f"Sample ID exceeds maximum ({self.MAX_SAMPLE_ID})")

    def _validate_name(self, name: str) -> None:
        """Validate sample name"""
        if not isinstance(name, str):
            raise ValidationError("Name must be a string")
        if not name or not name.strip():
            raise ValidationError("Name is required")
        if len(name) > self.MAX_NAME_LENGTH:
            raise ValidationError(f"Name too long (max {self.MAX_NAME_LENGTH} chars)")

    def _validate_value(self, value: float) -> None:
        """Validate sample value"""
        if not isinstance(value, (int, float)):
            raise ValidationError("Value must be a number")
        if value < self.MIN_VALUE or value > self.MAX_VALUE:
            raise ValidationError(f"Value must be between {self.MIN_VALUE} and {self.MAX_VALUE}")

    def _validate_description(self, description: Optional[str]) -> None:
        """Validate sample description"""
        if description is not None:
            if not isinstance(description, str):
                raise ValidationError("Description must be a string")
            if len(description) > self.MAX_DESCRIPTION_LENGTH:
                raise ValidationError(f"Description too long (max {self.MAX_DESCRIPTION_LENGTH} chars)")

    def get_sample(self, sample_id: int) -> Tuple[Dict[str, Any], int]:
        """Get sample by ID"""
        try:
            self._validate_id(sample_id)
        except ValidationError as e:
            return {"error": str(e)}, 400

        try:
            entity = self.application_service.get_sample_by_id(sample_id)
            if entity is None:
                return {"error": "Sample not found"}, 404
            return {
                "id": entity.id,
                "name": entity.name,
                "value": entity.value,
                "description": entity.description
            }, 200
        except SampleControllerError as e:
            return {"error": str(e)}, 400

    def create_sample(self, *args, **kwargs):
        return self._create_sample_impl(*args, **kwargs)
    def _create_sample_impl(self, dto: 'SampleDto') -> Tuple[Dict[str, Any], int]:
        """Create a new sample"""
        try:
            self._validate_name(dto.name)
            self._validate_value(dto.value)
            self._validate_description(dto.description)
        except ValidationError as e:
            return {"error": str(e)}, 400

        try:
            entity = self.application_service.create_sample(
                dto.name, 
                dto.value, 
                dto.description
            )
            return {
                "message": "Sample created successfully",
                "id": entity.id,
                "name": entity.name
            }, 201
        except SampleControllerError as e:
            return {"error": str(e)}, 400

    def update_sample(self, *args, **kwargs):
        return self._update_sample_impl(*args, **kwargs)
    def _update_sample_impl(self, sample_id: int, dto: 'SampleDto') -> Tuple[Dict[str, Any], int]:
        """Update an existing sample"""
        try:
            self._validate_id(sample_id)
            if dto.name is not None:
                self._validate_name(dto.name)
            if dto.value is not None:
                self._validate_value(dto.value)
            if dto.description is not None:
                self._validate_description(dto.description)
        except ValidationError as e:
            return {"error": str(e)}, 400

        try:
            entity = self.application_service.update_sample(
                sample_id,
                dto.name,
                dto.value,
                dto.description
            )
            if entity is None:
                return {"error": "Sample not found"}, 404
            return {
                "message": "Sample updated successfully",
                "id": entity.id,
                "name": entity.name
            }, 200
        except SampleControllerError as e:
            return {"error": str(e)}, 400

    def delete_sample(self, sample_id: int) -> Tuple[Dict[str, Any], int]:
        """Delete a sample"""
        try:
            self._validate_id(sample_id)
        except ValidationError as e:
            return {"error": str(e)}, 400

        try:
            success = self.application_service.delete_sample(sample_id)
            if not success:
                return {"error": "Sample not found"}, 404
            return {"message": "Sample deleted successfully"}, 200
        except SampleControllerError as e:
            return {"error": str(e)}, 400

    def list_samples(self, limit: int = 20, offset: int = 0) -> Tuple[Dict[str, Any], int]:
        """List samples with pagination"""
        if not isinstance(limit, int) or not isinstance(offset, int):
            return {"error": "Limit and offset must be integers"}, 400
        if limit < 0 or limit > 100:
            return {"error": "Limit must be between 0 and 100"}, 400
        if offset < 0:
            return {"error": "Offset must be non-negative"}, 400

        try:
            samples = self.application_service.list_samples(limit, offset)
            total = self.application_service.count_samples()
            return {
                "samples": samples,
                "total": total,
                "limit": limit,
                "offset": offset
            }, 200
        except SampleControllerError as e:
            return {"error": str(e)}, 400