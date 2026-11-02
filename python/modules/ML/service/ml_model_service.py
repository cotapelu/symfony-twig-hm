"""
ML Model Service - Machine Learning model management service
"""
import json
import os
from datetime import datetime, timezone
from typing import List, Dict, Optional, Any
from dataclasses import dataclass, asdict
from enum import Enum

from app.validation import Validator, ValidationError


class ModelStatus(str, Enum):
    DRAFT = "draft"
    TRAINING = "training"
    DEPLOYED = "deployed"
    FAILED = "failed"
    ARCHIVED = "archived"


class ModelType(str, Enum):
    CLASSIFICATION = "classification"
    REGRESSION = "regression"
    CLUSTERING = "clustering"
    RECOMMENDATION = "recommendation"
    NLP = "nlp"
    COMPUTER_VISION = "computer_vision"


@dataclass
class MLModel:
    id: str
    name: str
    description: str
    model_type: str
    status: str
    version: str
    accuracy: Optional[float] = None
    f1_score: Optional[float] = None
    precision: Optional[float] = None
    recall: Optional[float] = None
    training_data_size: int = 0
    features: List[str] = None
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    deployed_at: Optional[datetime] = None
    metadata: Dict[str, Any] = None

    def __post_init__(self):
        if self.features is None:
            self.features = []
        if self.metadata is None:
            self.metadata = {}
        if self.created_at is None:
            self.created_at = datetime.now(timezone.utc)
        if self.updated_at is None:
            self.updated_at = datetime.now(timezone.utc)


@dataclass
class ModelTrainingRequest:
    name: str
    description: str
    model_type: str
    training_data_path: str
    features: List[str]
    target_column: str
    hyperparameters: Dict[str, Any] = None


@dataclass
class ModelInferenceRequest:
    model_id: str
    input_data: Dict[str, Any]
    confidence_threshold: float = 0.5


class MLModelService:
    """
    Service for managing ML models - training, deployment, and inference
    """

    def __init__(self, storage_path: str = "/dev/shm/data/ml_models.json"):
        self.storage_path = storage_path
        self.models: Dict[str, MLModel] = {}
        self.inference_history: List[Dict] = []
        self.next_id = 1

        self.ensure_storage_directory()
        self.load_from_storage()

    def ensure_storage_directory(self):
        dir_path = os.path.dirname(self.storage_path)
        if dir_path and dir_path != "." and not os.path.exists(dir_path):
            os.makedirs(dir_path, exist_ok=True)

    def load_from_storage(self):
        return self._load_from_storage_impl()

    def _load_from_storage_impl(self):
        if not os.path.exists(self.storage_path):
            return
        try:
            with open(self.storage_path, 'r') as f:
                data = json.load(f)
                if 'models' in data:
                    for model_data in data['models'].values():
                        model_data['created_at'] = datetime.fromisoformat(
                            model_data['created_at']
                        ) if model_data.get('created_at') else datetime.now(timezone.utc)
                        model_data['updated_at'] = datetime.fromisoformat(
                            model_data['updated_at']
                        ) if model_data.get('updated_at') else datetime.now(timezone.utc)
                        model_data['deployed_at'] = datetime.fromisoformat(
                            model_data['deployed_at']
                        ) if model_data.get('deployed_at') else None
                        self.models[model_data['id']] = MLModel(**model_data)
                if 'inference_history' in data:
                    self.inference_history = data['inference_history']
                self.next_id = data.get('next_id', 1)
        except Exception as e:
            print(f"Failed to load ML models: {e}")

    def save_to_storage(self):
        try:
            data = {
                'models': {
                    k: {
                        **asdict(v),
                        'created_at': v.created_at.isoformat() if v.created_at else None,
                        'updated_at': v.updated_at.isoformat() if v.updated_at else None,
                        'deployed_at': v.deployed_at.isoformat() if v.deployed_at else None,
                    } for k, v in self.models.items()
                },
                'inference_history': self.inference_history[-1000:],  # Keep last 1000
                'next_id': self.next_id
            }
            with open(self.storage_path, 'w') as f:
                json.dump(data, f, indent=2)
        except Exception as e:
            print(f"Failed to save ML models: {e}")

    def create_model(
        self,
        name: str,
        description: str,
        model_type: str,
        features: List[str]
    ) -> MLModel:
        return self._create_model_impl(name, description, model_type, features)

    def _create_model_impl(
        self,
        name: str,
        description: str,
        model_type: str,
        features: List[str]
    ) -> MLModel:
        """Create a new ML model"""
        # Validate inputs using validation module (TODO.md requirements)
        try:
            name = Validator.validate_name(name, "Model name")
            description = Validator.validate_description(description, "Model description")
        except ValidationError as e:
            raise ValueError(str(e))

        # Validate model type
        valid_types = [t.value for t in ModelType]
        if model_type not in valid_types:
            raise ValueError(f"Invalid model type: {model_type}. Must be one of: {valid_types}")

        model = MLModel(
            id=f"model_{self.next_id}",
            name=name,
            description=description,
            model_type=model_type,
            status=ModelStatus.DRAFT.value,
            version="1.0.0",
            features=features,
            created_at=datetime.now(timezone.utc),
            updated_at=datetime.now(timezone.utc)
        )

        self.models[model.id] = model
        self.next_id += 1
        self.save_to_storage()

        return model

    def get_model(self, model_id: str) -> Optional[MLModel]:
        """Get model by ID"""
        return self.models.get(model_id)

    def list_models(
        self,
        status: Optional[str] = None,
        model_type: Optional[str] = None,
        limit: int = 20,
        offset: int = 0
    ) -> List[MLModel]:
        """List models with optional filtering"""
        models = list(self.models.values())

        if status:
            models = [m for m in models if m.status == status]
        if model_type:
            models = [m for m in models if m.model_type == model_type]

        # Sort by updated_at descending
        models.sort(key=lambda m: m.updated_at, reverse=True)

        return models[offset:offset + limit]

    def update_model(
        self,
        model_id: str,
        name: Optional[str] = None,
        description: Optional[str] = None,
        status: Optional[str] = None,
        metrics: Optional[Dict[str, float]] = None
    ) -> Optional[MLModel]:
        return self._update_model_impl(model_id, name, description, status, metrics)

    def _update_model_impl(
        self,
        model_id: str,
        name: Optional[str] = None,
        description: Optional[str] = None,
        status: Optional[str] = None,
        metrics: Optional[Dict[str, float]] = None
    ) -> Optional[MLModel]:
        """Update model metadata"""
        model = self.models.get(model_id)
        if not model:
            return None

        if name:
            model.name = name
        if description:
            model.description = description
        if status:
            model.status = status
            if status == ModelStatus.DEPLOYED.value:
                model.deployed_at = datetime.now(timezone.utc)
        if metrics:
            model.accuracy = metrics.get('accuracy')
            model.f1_score = metrics.get('f1_score')
            model.precision = metrics.get('precision')
            model.recall = metrics.get('recall')

        model.updated_at = datetime.now(timezone.utc)
        self.save_to_storage()

        return model

    def delete_model(self, model_id: str) -> bool:
        """Delete a model"""
        if model_id in self.models:
            del self.models[model_id]
            self.save_to_storage()
            return True
        return False

    def train_model(
        self,
        model_id: str,
        training_data_size: int,
        metrics: Dict[str, float]
    ) -> Optional[MLModel]:
        return self._train_model_impl(model_id, training_data_size, metrics)

    def _train_model_impl(
        self,
        model_id: str,
        training_data_size: int,
        metrics: Dict[str, float]
    ) -> Optional[MLModel]:
        """Mark model as trained with metrics"""
        model = self.models.get(model_id)
        if not model:
            return None

        model.status = ModelStatus.TRAINING.value
        model.training_data_size = training_data_size
        model.accuracy = metrics.get('accuracy')
        model.f1_score = metrics.get('f1_score')
        model.precision = metrics.get('precision')
        model.recall = metrics.get('recall')
        model.updated_at = datetime.now(timezone.utc)

        self.save_to_storage()
        return model

    def deploy_model(self, model_id: str) -> Optional[MLModel]:
        """Deploy a model for inference"""
        model = self.models.get(model_id)
        if not model:
            return None

        if model.status != ModelStatus.TRAINING.value:
            raise ValueError(f"Model must be in TRAINING status to deploy. Current: {model.status}")

        model.status = ModelStatus.DEPLOYED.value
        model.deployed_at = datetime.now(timezone.utc)
        model.updated_at = datetime.now(timezone.utc)

        self.save_to_storage()
        return model

    def predict(self, model_id: str, input_data: Dict[str, Any]) -> Dict[str, Any]:
        return self._predict_impl(model_id, input_data)

    def _predict_impl(self, model_id: str, input_data: Dict[str, Any]) -> Dict[str, Any]:
        """Run inference on a deployed model"""
        model = self.models.get(model_id)
        if not model:
            raise ValueError(f"Model not found: {model_id}")

        if model.status != ModelStatus.DEPLOYED.value:
            raise ValueError(f"Model must be DEPLOYED to run inference. Current: {model.status}")

        # Simple mock prediction - in real implementation, this would use the actual model
        prediction = {
            "prediction": "class_a",  # Mock prediction
            "confidence": 0.85,
            "model_id": model_id,
            "timestamp": datetime.now(timezone.utc).isoformat()
        }

        # Record inference
        self.inference_history.append({
            "model_id": model_id,
            "input": input_data,
            "output": prediction,
            "timestamp": datetime.now(timezone.utc).isoformat()
        })
        self.save_to_storage()

        return prediction

    def get_inference_history(
        self,
        model_id: Optional[str] = None,
        limit: int = 20
    ) -> List[Dict]:
        """Get inference history"""
        history = self.inference_history

        if model_id:
            history = [h for h in history if h.get('model_id') == model_id]

        return history[-limit:]

    def get_model_statistics(self) -> Dict[str, Any]:
        """Get statistics about models"""
        models = list(self.models.values())

        by_status = {}
        by_type = {}

        for model in models:
            by_status[model.status] = by_status.get(model.status, 0) + 1
            by_type[model.model_type] = by_type.get(model.model_type, 0) + 1

        return {
            "total_models": len(models),
            "by_status": by_status,
            "by_type": by_type,
            "deployed_count": len([m for m in models if m.status == ModelStatus.DEPLOYED.value]),
            "average_accuracy": sum(m.accuracy for m in models if m.accuracy) / max(len([m for m in models if m.accuracy]), 1)
        }