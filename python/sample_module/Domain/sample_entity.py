# Sample Entity representing a domain object
class SampleEntity:
    def __init__(self, name: str, value: int, description: Optional[str] = None, id: Optional[int] = None):
        # Business rules validation
        if not name:
            raise ValueError("Name cannot be empty")
        
        if value < 0:
            raise ValueError("Value must be non-negative")

        self.id = id or 0  # 0 indicates new entity
        self.name = name
        self.value = value
        self.description = description

    def update(self, name: str, value: int, description: Optional[str] = None) -> None:
        # Business rules for update
        if not name:
            raise ValueError("Name cannot be empty")
        
        if value < 0:
            raise ValueError("Value must be non-negative")

        self.name = name
        self.value = value
        self.description = description