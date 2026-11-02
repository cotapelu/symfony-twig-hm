# Sample Controller for API layer
from typing import Optional
from dataclasses import dataclass

# In a real implementation, this would be in a web framework like FastAPI or Flask
class SampleController:
    def __init__(self, application_service: 'SampleApplicationService'):
        self.application_service = application_service

    def get_sample(self, sample_id: int) -> dict:
        # Delegate to application layer
        entity = self.application_service.get_sample_by_id(sample_id)
        if entity is None:
            return {"error": "Sample not found"}, 404
        return {
            "id": entity.id,
            "name": entity.name,
            "value": entity.value,
            "description": entity.description
        }, 200

    def create_sample(self, dto: 'SampleDto') -> dict:
        # Delegate to application layer
        self.application_service.create_sample(dto.name, dto.value, dto.description)
        return {"message": "Sample created successfully"}, 201