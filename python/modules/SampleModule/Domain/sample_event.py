# Sample Domain Event
from datetime import datetime

class SampleEvent:
    def __init__(self, entity_id: int, name: str, value: int):
        self.entity_id = entity_id
        self.name = name
        self.value = value
        self.occurred_on = datetime.now()

    # Getters (optional, but good practice)
    def get_entity_id(self) -> int:
        return self.entity_id

    def get_name(self) -> str:
        return self.name

    def get_value(self) -> int:
        return self.value

    def get_occurred_on(self) -> datetime:
        return self.occurred_on