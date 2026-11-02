import builtins
import pytest
from datetime import datetime, timezone
from modules.ML.service.ml_model_service import (
    MLModelService,
    ModelStatus,
    ModelType,
    MLModel
)
from app.validation import Validator, ValidationError


@pytest.fixture
def ml_service_extra(tmp_path):
    storage = tmp_path / "ml_models.json"
    return MLModelService(storage_path=str(storage))


def test_list_models_with_offset(ml_service_extra):
    # Create additional models directly on ml_service_extra
    ml_service_extra.create_model(name="M_A", description="", model_type=ModelType.CLASSIFICATION.value, features=[])
    ml_service_extra.create_model(name="M_B", description="", model_type=ModelType.CLASSIFICATION.value, features=[])
    ml_service_extra.create_model(name="M_C", description="", model_type=ModelType.CLASSIFICATION.value, features=[])
    # List with offset=1 should return 2 models (excluding the most recent)
    models = ml_service_extra.list_models(offset=1, limit=10)
    assert len(models) == 2
    names = {m.name for m in models}
    assert names == {"M_B", "M_A"}  # M_C is newest, excluded


def test_get_inference_history_limit(ml_service_extra):
    model = ml_service_extra.create_model(name="HistLimit", description="", model_type=ModelType.CLASSIFICATION.value, features=[])
    ml_service_extra.train_model(model.id, training_data_size=100, metrics={"accuracy": 0.9})
    ml_service_extra.deploy_model(model.id)
    ml_service_extra.predict(model.id, {"x": 1})
    ml_service_extra.predict(model.id, {"x": 2})
    ml_service_extra.predict(model.id, {"x": 3})
    history = ml_service_extra.get_inference_history(model_id=model.id, limit=2)
    assert len(history) == 2
    assert all(h["model_id"] == model.id for h in history)


def test_get_inference_history_all(ml_service_extra):
    model = ml_service_extra.create_model(name="HistAll", description="", model_type=ModelType.CLASSIFICATION.value, features=[])
    ml_service_extra.train_model(model.id, training_data_size=100, metrics={"accuracy": 0.9})
    ml_service_extra.deploy_model(model.id)
    ml_service_extra.predict(model.id, {"x": 1})
    ml_service_extra.predict(model.id, {"x": 2})
    history = ml_service_extra.get_inference_history(limit=10)
    assert len(history) >= 2
    assert any(h["model_id"] == model.id for h in history)


def test_ensure_storage_directory_creates(tmp_path):
    storage_path = tmp_path / "new_dir" / "data.json"
    assert not storage_path.parent.exists()
    service = MLModelService(storage_path=str(storage_path))
    assert storage_path.parent.exists()


def test_load_from_storage_corrupt(tmp_path):
    storage_path = str(tmp_path / "corrupt.json")
    # Write invalid JSON
    with open(storage_path, "w") as f:
        f.write("{ invalid json")
    # Instantiating should not raise; it should catch exception and initialize empty
    service = MLModelService(storage_path=storage_path)
    assert service.models == {}
    assert service.inference_history == []
    assert service.next_id == 1


def test_save_to_storage_exception(ml_service_extra, monkeypatch):
    def mock_open(*args, **kwargs):
        raise IOError("disk full")
    monkeypatch.setattr(builtins, "open", mock_open)
    # Should not raise
    ml_service_extra.save_to_storage()


def test_create_model_validation_failure(ml_service_extra, monkeypatch):
    def fake_validate_name(value, field_name):
        raise ValidationError("Invalid name")
    monkeypatch.setattr(Validator, "validate_name", fake_validate_name)
    with pytest.raises(ValueError, match="Invalid name"):
        ml_service_extra.create_model("Bad Name", "desc", ModelType.CLASSIFICATION.value, ["f1"])