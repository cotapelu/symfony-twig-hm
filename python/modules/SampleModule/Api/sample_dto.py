# Sample Data Transfer Object for API layer
from dataclasses import dataclass
from typing import Optional

@dataclass
class SampleDto:
    name: str
    value: int
    description: Optional[str] = None