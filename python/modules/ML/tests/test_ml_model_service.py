"""
Unit tests for MLModelService
Target: Increase Python coverage towards >85%
"""

import pytest
from datetime import datetime, timezone
from modules.ML.service.ml_model_service import (
    MLModelService,
    ModelStatus,
    ModelType,
    MLModel
)


@pytest.fixture
def temp_dir(tmp_path):
    return tmp_path


@pytest.fixture
def ml_service(temp_dir):
    storage = temp_dir / "ml_models.json"
    return MLModelService(storage_path=str(storage))


def test_create_model(ml_service):
    model = ml_service.create_model(
        name="Test Model",
        description="Test description",
        model_type=ModelType.CLASSIFICATION.value,
        features=["feature1", "feature2"]
    )
    assert model.name == "Test Model"
    assert model.description == "Test description"
    assert model.model_type == ModelType.CLASSIFICATION.value
    assert model.status == ModelStatus.DRAFT.value
    assert model.features == ["feature1", "feature2"]
    assert model.id in ml_service.models


def test_get_model(ml_service):
    created = ml_service.create_model(
        name="M",
        description="",
        model_type=ModelType.REGRESSION.value,
        features=[]
    )
    retrieved = ml_service.get_model(created.id)
    assert retrieved is not None
    assert retrieved.id == created.id
    assert retrieved.name == "M"


def test_list_models(ml_service):
    ml_service.create_model(name="M1", description="", model_type=ModelType.CLASSIFICATION.value, features=[])
    ml_service.create_model(name="M2", description="", model_type=ModelType.REGRESSION.value, features=[])
    models = ml_service.list_models()
    assert len(models) == 2
    names = {m.name for m in models}
    assert names == {"M1", "M2"}

def test_list_models_empty(ml_service):
    models = ml_service.list_models()
    assert models == []


def test_delete_model(ml_service):
    model = ml_service.create_model(
        name="DeleteTest",
        description="",
        model_type=ModelType.NLP.value,
        features=[]
    )
    result = ml_service.delete_model(model.id)
    assert result is True
    assert ml_service.get_model(model.id) is None
    assert ml_service.delete_model(model.id) is False


def test_train_model(ml_service):
    model = ml_service.create_model(
        name="TrainTest",
        description="",
        model_type=ModelType.CLASSIFICATION.value,
        features=["f1", "f2"]
    )
    assert model.status == ModelStatus.DRAFT.value
    trained = ml_service.train_model(
        model.id,
        training_data_size=1000,
        metrics={"accuracy": 0.92, "f1_score": 0.88}
    )
    assert trained is not None
    assert trained.status == ModelStatus.TRAINING.value
    assert trained.training_data_size == 1000
    assert trained.accuracy == 0.92
    assert trained.f1_score == 0.88


def test_deploy_model(ml_service):
    model = ml_service.create_model(
        name="DeployTest",
        description="",
        model_type=ModelType.NLP.value,
        features=[]
    )
    ml_service.train_model(model.id, training_data_size=100, metrics={"accuracy": 0.9})
    assert model.status == ModelStatus.TRAINING.value
    deployed = ml_service.deploy_model(model.id)
    assert deployed is not None
    assert deployed.status == ModelStatus.DEPLOYED.value
    assert deployed.deployed_at is not None


def test_predict_not_deployed_raises(ml_service):
    model = ml_service.create_model(
        name="P",
        description="",
        model_type=ModelType.CLASSIFICATION.value,
        features=[]
    )
    with pytest.raises(ValueError, match="DEPLOYED"):
        ml_service.predict(model.id, {"input": 1})


def test_predict_success(ml_service):
    model = ml_service.create_model(
        name="PredictTest",
        description="",
        model_type=ModelType.CLASSIFICATION.value,
        features=[]
    )
    ml_service.train_model(model.id, training_data_size=200, metrics={"accuracy": 0.95})
    ml_service.deploy_model(model.id)
    result = ml_service.predict(model.id, {"feature1": 0.5})
    assert "prediction" in result
    assert result["model_id"] == model.id
    assert result["confidence"] == 0.85


def test_get_inference_history(ml_service):
    model = ml_service.create_model(
        name="HistTest",
        description="",
        model_type=ModelType.CLASSIFICATION.value,
        features=[]
    )
    ml_service.train_model(model.id, training_data_size=100, metrics={"accuracy": 0.9})
    ml_service.deploy_model(model.id)
    ml_service.predict(model.id, {"x": 1})
    ml_service.predict(model.id, {"x": 2})
    history = ml_service.get_inference_history(model_id=model.id)
    assert len(history) == 2
    assert all(h["model_id"] == model.id for h in history)

def test_train_model_invalid_id(ml_service):
    result = ml_service.train_model("nonexistent", training_data_size=100, metrics={"accuracy": 0.9})
    assert result is None

def test_deploy_model_invalid_id(ml_service):
    result = ml_service.deploy_model("nonexistent")
    assert result is None

def test_get_model_statistics(ml_service):
    stats = ml_service.get_model_statistics()
    assert isinstance(stats, dict)
    assert 'total_models' in stats
    ml_service.create_model(name="S1", description="", model_type=ModelType.CLASSIFICATION.value, features=[])
    stats = ml_service.get_model_statistics()
    assert stats['total_models'] >= 1


def test_update_model(ml_service):
    model = ml_service.create_model(
        name="Old Name",
        description="Old description",
        model_type=ModelType.CLASSIFICATION.value,
        features=["feature1"]
    )
    updated = ml_service.update_model(
        model.id,
        name="New Name",
        description="New description",
        status=ModelStatus.DEPLOYED.value,
        metrics={
            "accuracy": 0.96,
            "f1_score": 0.95,
            "precision": 0.94,
            "recall": 0.93
        }
    )
    assert updated is not None
    assert updated.name == "New Name"
    assert updated.description == "New description"
    assert updated.status == ModelStatus.DEPLOYED.value
    assert updated.deployed_at is not None
    assert updated.accuracy == 0.96
    assert updated.f1_score == 0.95
    assert updated.precision == 0.94
    assert updated.recall == 0.93


def test_update_model_invalid_id(ml_service):
    result = ml_service.update_model("nonexistent", name="X")
    assert result is None


def test_predict_model_not_found(ml_service):
    with pytest.raises(ValueError, match="Model not found"):
        ml_service.predict("nonexistent", {"input": 1})


def test_deploy_model_invalid_status(ml_service):
    model = ml_service.create_model(
        name="DraftModel",
        description="",
        model_type=ModelType.CLASSIFICATION.value,
        features=[]
    )
    # Model is in DRAFT status; deploy should require TRAINING
    with pytest.raises(ValueError, match="must be in TRAINING"):
        ml_service.deploy_model(model.id)


def test_list_models_with_filters(ml_service):
    m1 = ml_service.create_model(name="M1", description="", model_type=ModelType.CLASSIFICATION.value, features=[])
    m2 = ml_service.create_model(name="M2", description="", model_type=ModelType.REGRESSION.value, features=[])
    # Initially both have status DRAFT
    models = ml_service.list_models(status=ModelStatus.DRAFT.value)
    assert len(models) == 2
    names = {m.name for m in models}
    assert names == {"M1", "M2"}
    models_by_type = ml_service.list_models(model_type=ModelType.CLASSIFICATION.value)
    assert len(models_by_type) == 1
    assert models_by_type[0].name == "M1"
