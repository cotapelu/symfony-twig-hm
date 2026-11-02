# Sample Repository implementing data access
class SampleRepository:
    def __init__(self):
        # In-memory storage for demonstration
        self._storage = {}
        self._next_id = 1

    def save(self, entity: 'SampleEntity') -> None:
        if entity.id == 0:
            # New entity
            entity.id = self._next_id
            self._next_id += 1
        self._storage[entity.id] = entity

    def find_by_id(self, entity_id: int) -> Optional['SampleEntity']:
        return self._storage.get(entity_id)

    def find_all() -> list['SampleEntity']:
        return list(self._storage.values())

    def delete_by_id(self, entity_id: int) -> None:
        if entity_id in self._storage:
            del self._storage[entity_id]