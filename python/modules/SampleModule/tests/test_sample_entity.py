import pytest
from modules.SampleModule.Domain.sample_entity import SampleEntity

def test_create_entity():
    entity = SampleEntity(name="Test", value=10)
    assert entity.name == "Test"
    assert entity.value == 10
    assert entity.description is None

def test_create_entity_with_description():
    entity = SampleEntity(name="Test", value=10, description="A test entity")
    assert entity.description == "A test entity"

def test_create_entity_with_id():
    entity = SampleEntity(name="Test", value=10, id=5)
    assert entity.id == 5

def test_create_entity_empty_name():
    with pytest.raises(ValueError):
        SampleEntity(name="", value=10)

def test_create_entity_negative_value():
    with pytest.raises(ValueError):
        SampleEntity(name="Test", value=-1)

def test_update_entity(entity):
    entity.update("Updated", 20)
    assert entity.name == "Updated"
    assert entity.value == 20

def test_update_entity_empty_name(entity):
    with pytest.raises(ValueError):
        entity.update("", 20)

def test_update_entity_negative_value(entity):
    with pytest.raises(ValueError):
        entity.update("Test", -1)

@pytest.fixture
def entity():
    return SampleEntity(name="Original", value=5)