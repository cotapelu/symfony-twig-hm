# Sample Application Service (Use Case)
class SampleApplicationService:
    def __init__(self, domain_service: 'SampleDomainService', repository: 'SampleRepositoryInterface'):
        self.domain_service = domain_service
        self.repository = repository

    def create_sample(self, name: str, value: int, description: Optional[str] = None) -> None:
        # Business logic validation
        if not name:
            raise ValueError("Name cannot be empty")
        
        if value < 0:
            raise ValueError("Value must be non-negative")

        # Create domain entity
        entity = SampleEntity(name, value, description)
        
        # Persist via repository
        self.repository.save(entity)

    def get_sample_by_id(self, sample_id: int) -> Optional['SampleEntity']:
        return self.repository.find_by_id(sample_id)