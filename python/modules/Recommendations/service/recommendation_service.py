"""
Recommendation Service - Product and content recommendation engine
"""
import json
import os
from datetime import datetime, timezone
from typing import List, Dict, Optional, Any
from dataclasses import dataclass, asdict
from enum import Enum

from app.validation import Validator, ValidationError


class RecommendationType(str, Enum):
    PRODUCT = "product"
    CONTENT = "content"
    USER = "user"
    SIMILAR = "similar"
    TRENDING = "trending"
    PERSONALIZED = "personalized"
    POPULARITY = "popularity"
    COLLABORATIVE = "collaborative"


class AlgorithmType(str, Enum):
    COLLABORATIVE = "collaborative"
    CONTENT_BASED = "content_based"
    HYBRID = "hybrid"
    POPULARITY = "popularity"
    RANDOM = "random"


@dataclass
class RecommendationItem:
    item_id: str
    item_type: str
    score: float
    reason: str
    metadata: Dict[str, Any] = None

    def __post_init__(self):
        if self.metadata is None:
            self.metadata = {}


@dataclass
class UserPreference:
    user_id: str
    preferred_categories: List[str] = None
    preferred_tags: List[str] = None
    interaction_history: List[Dict] = None
    last_updated: Optional[datetime] = None

    def __post_init__(self):
        if self.preferred_categories is None:
            self.preferred_categories = []
        if self.preferred_tags is None:
            self.preferred_tags = []
        if self.interaction_history is None:
            self.interaction_history = []
        if self.last_updated is None:
            self.last_updated = datetime.now(timezone.utc)


@dataclass
class RecommendationRequest:
    user_id: str
    recommendation_type: str
    limit: int = 10
    category: Optional[str] = None
    tags: Optional[List[str]] = None


class RecommendationService:
    """
    Service for generating recommendations - products, content, users
    """

    def __init__(self, storage_path: str = "/dev/shm/data/recommendations.json"):
        self.storage_path = storage_path
        self.recommendations: Dict[str, List[RecommendationItem]] = {}
        self.user_preferences: Dict[str, UserPreference] = {}
        self.item_scores: Dict[str, Dict[str, float]] = {}  # item_id -> {feature: score}
        self.interaction_counts: Dict[str, int] = {}  # item_id -> count

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
                if 'user_preferences' in data:
                    for user_id, prefs in data['user_preferences'].items():
                        prefs['last_updated'] = datetime.fromisoformat(prefs['last_updated']) if prefs.get('last_updated') else datetime.now(timezone.utc)
                        self.user_preferences[user_id] = UserPreference(**prefs)
                if 'item_scores' in data:
                    self.item_scores = data['item_scores']
                if 'interaction_counts' in data:
                    self.interaction_counts = data['interaction_counts']
                if 'recommendations' in data:
                    self.recommendations = {
                        k: [RecommendationItem(**item) for item in v]
                        for k, v in data['recommendations'].items()
                    }
        except Exception as e:
            print(f"Failed to load recommendations: {e}")

    def save_to_storage(self):
        return self._save_to_storage_impl()

    def _save_to_storage_impl(self):
        try:
            data = {
                'user_preferences': {
                    k: {
                        **asdict(v),
                        'last_updated': v.last_updated.isoformat() if v.last_updated else None
                    } for k, v in self.user_preferences.items()
                },
                'item_scores': self.item_scores,
                'interaction_counts': self.interaction_counts,
                'recommendations': {
                    k: [
                        {
                            'item_id': r.item_id,
                            'item_type': r.item_type,
                            'score': r.score,
                            'reason': r.reason,
                            'metadata': r.metadata
                        } for r in v
                    ] for k, v in self.recommendations.items()
                }
            }
            with open(self.storage_path, 'w') as f:
                json.dump(data, f, indent=2)
        except Exception as e:
            print(f"Failed to save recommendations: {e}")

    def update_user_preference(
        self,
        user_id: str,
        category: Optional[str] = None,
        tags: Optional[List[str]] = None,
        item_id: Optional[str] = None
    ):
        return self._update_user_preference_impl(user_id, category, tags, item_id)

    def _update_user_preference_impl(
        self,
        user_id: str,
        category: Optional[str] = None,
        tags: Optional[List[str]] = None,
        item_id: Optional[str] = None
    ):
        """Update user preference based on interaction"""
        if user_id not in self.user_preferences:
            self.user_preferences[user_id] = UserPreference(user_id=user_id)

        prefs = self.user_preferences[user_id]

        if category and category not in prefs.preferred_categories:
            prefs.preferred_categories.append(category)

        if tags:
            for tag in tags:
                if tag not in prefs.preferred_tags:
                    prefs.preferred_tags.append(tag)

        if item_id:
            prefs.interaction_history.append({
                'item_id': item_id,
                'timestamp': datetime.now(timezone.utc).isoformat()
            })
            # Keep last 100 interactions
            prefs.interaction_history = prefs.interaction_history[-100:]

        prefs.last_updated = datetime.now(timezone.utc)
        self.save_to_storage()

    def record_interaction(self, user_id: str, item_id: str, item_type: str, score: float = 1.0):
        """Record user interaction with an item"""
        # Update interaction count
        self.interaction_counts[item_id] = self.interaction_counts.get(item_id, 0) + 1

        # Update user preferences
        self.update_user_preference(user_id, item_id=item_id)

    def get_recommendations(
        self,
        user_id: str,
        recommendation_type: str = RecommendationType.PERSONALIZED.value,
        limit: int = 10,
        category: Optional[str] = None
    ) -> List[RecommendationItem]:
        return self._get_recommendations_impl(user_id, recommendation_type, limit, category)

    def _get_recommendations_impl(
        self,
        user_id: str,
        recommendation_type: str = RecommendationType.PERSONALIZED.value,
        limit: int = 10,
        category: Optional[str] = None
    ) -> List[RecommendationItem]:
        """Get recommendations for a user"""
        # Validate recommendation type
        valid_types = [t.value for t in RecommendationType]
        if recommendation_type not in valid_types:
            raise ValueError(f"Invalid recommendation type: {recommendation_type}")

        recommendations = []

        if recommendation_type == RecommendationType.PERSONALIZED.value:
            recommendations = self._get_personalized_recommendations(user_id, limit, category)
        elif recommendation_type == RecommendationType.TRENDING.value:
            recommendations = self._get_trending_recommendations(limit, category)
        elif recommendation_type == RecommendationType.SIMILAR.value:
            recommendations = self._get_similar_recommendations(user_id, limit)
        elif recommendation_type == RecommendationType.POPULARITY.value:
            recommendations = self._get_popularity_recommendations(limit, category)
        elif recommendation_type == RecommendationType.COLLABORATIVE.value:
            recommendations = self._get_collaborative_recommendations(user_id, limit)

        # Cache recommendations
        cache_key = f"{user_id}:{recommendation_type}"
        self.recommendations[cache_key] = recommendations
        self.save_to_storage()

        return recommendations

    def _get_personalized_recommendations(
        self,
        user_id: str,
        limit: int,
        category: Optional[str]
    ) -> List[RecommendationItem]:
        """Get personalized recommendations based on user preferences"""
        prefs = self.user_preferences.get(user_id)
        if not prefs:
            return self._get_trending_recommendations(limit, category)

        recommendations = []

        # Get top items based on user preferences
        for item_id, scores in self.item_scores.items():
            if category and scores.get('category') != category:
                continue

            score = 0.0

            # Boost items matching user preferences
            if prefs.preferred_categories:
                item_category = scores.get('category', '')
                if item_category in prefs.preferred_categories:
                    score += 30

            if prefs.preferred_tags:
                item_tags = scores.get('tags', [])
                matching_tags = set(item_tags) & set(prefs.preferred_tags)
                score += len(matching_tags) * 10

            # Add interaction count
            score += self.interaction_counts.get(item_id, 0) * 2

            if score > 0:
                recommendations.append(RecommendationItem(
                    item_id=item_id,
                    item_type=scores.get('type', 'product'),
                    score=score,
                    reason=f"Matches your preferences: {', '.join(prefs.preferred_categories[:2])}"
                ))

        # Sort by score and return top N
        recommendations.sort(key=lambda r: r.score, reverse=True)
        return recommendations[:limit]

    def _get_trending_recommendations(
        self,
        limit: int,
        category: Optional[str]
    ) -> List[RecommendationItem]:
        """Get trending items based on interaction count"""
        items = []

        for item_id, count in self.interaction_counts.items():
            scores = self.item_scores.get(item_id, {})
            if category and scores.get('category') != category:
                continue

            items.append(RecommendationItem(
                item_id=item_id,
                item_type=scores.get('type', 'product'),
                score=float(count),
                reason="Trending based on recent interactions"
            ))

        items.sort(key=lambda r: r.score, reverse=True)
        return items[:limit]

    def _get_similar_recommendations(
        self,
        user_id: str,
        limit: int
    ) -> List[RecommendationItem]:
        """Get items similar to user's recent interactions"""
        prefs = self.user_preferences.get(user_id)
        if not prefs or not prefs.interaction_history:
            return self._get_trending_recommendations(limit, None)

        # Get recently interacted items
        recent_items = [h['item_id'] for h in prefs.interaction_history[-5:]]

        recommendations = []
        seen = set(recent_items)

        for item_id in recent_items:
            item_scores = self.item_scores.get(item_id, {})
            similar_tags = set(item_scores.get('tags', []))

            # Find similar items
            for candidate_id, scores in self.item_scores.items():
                if candidate_id in seen:
                    continue

                candidate_tags = set(scores.get('tags', []))
                similarity = len(similar_tags & candidate_tags)

                if similarity > 0:
                    recommendations.append(RecommendationItem(
                        item_id=candidate_id,
                        item_type=scores.get('type', 'product'),
                        score=float(similarity) * 20,
                        reason=f"Similar to items you've interacted with"
                    ))
                    seen.add(candidate_id)

        recommendations.sort(key=lambda r: r.score, reverse=True)
        return recommendations[:limit]

    def _get_popularity_recommendations(
        self,
        limit: int,
        category: Optional[str]
    ) -> List[RecommendationItem]:
        """Get popular items"""
        return self._get_trending_recommendations(limit, category)

    def _get_collaborative_recommendations(
        self,
        user_id: str,
        limit: int
    ) -> List[RecommendationItem]:
        """Get recommendations based on collaborative filtering"""
        # Simplified collaborative filtering
        prefs = self.user_preferences.get(user_id)
        if not prefs:
            return self._get_trending_recommendations(limit, None)

        # Find similar users based on categories
        user_categories = set(prefs.preferred_categories)

        similar_scores = {}
        for other_user_id, other_prefs in self.user_preferences.items():
            if other_user_id == user_id:
                continue

            other_categories = set(other_prefs.preferred_categories)
            similarity = len(user_categories & other_categories)

            if similarity > 0:
                # Get items this user interacted with
                for interaction in other_prefs.interaction_history:
                    item_id = interaction['item_id']
                    similar_scores[item_id] = similar_scores.get(item_id, 0) + similarity

        recommendations = []
        for item_id, score in similar_scores.items():
            scores = self.item_scores.get(item_id, {})
            recommendations.append(RecommendationItem(
                item_id=item_id,
                item_type=scores.get('type', 'product'),
                score=score * 10,
                reason="Users with similar preferences liked this"
            ))

        recommendations.sort(key=lambda r: r.score, reverse=True)
        return recommendations[:limit]

    def add_item(self, item_id: str, item_type: str, category: str, tags: List[str]):
        """Add an item to the recommendation system"""
        # Validate inputs using validation module (TODO.md requirements)
        try:
            item_id = Validator.validate_string_length(item_id, 255, "Item ID")
            item_type = Validator.validate_type(item_type, ["product", "content", "user"])
            category = Validator.validate_category(category, ["electronics", "clothing", "food", "books", "sports", "other"])
        except ValidationError as e:
            raise ValueError(str(e))

        self.item_scores[item_id] = {
            'type': item_type,
            'category': category,
            'tags': tags
        }
        self.save_to_storage()

    def remove_item(self, item_id: str) -> bool:
        """Remove an item from recommendations"""
        if item_id in self.item_scores:
            del self.item_scores[item_id]
        if item_id in self.interaction_counts:
            del self.interaction_counts[item_id]
        self.save_to_storage()
        return True

    def get_user_preferences(self, user_id: str) -> Optional[UserPreference]:
        """Get user preferences"""
        return self.user_preferences.get(user_id)

    def get_recommendation_statistics(self) -> Dict[str, Any]:
        """Get statistics about recommendations"""
        total_items = len(self.item_scores)
        total_users = len(self.user_preferences)
        total_interactions = sum(self.interaction_counts.values())

        return {
            "total_items": total_items,
            "total_users": total_users,
            "total_interactions": total_interactions,
            "average_interactions_per_user": total_interactions / max(total_users, 1),
            "top_items": sorted(
                self.interaction_counts.items(),
                key=lambda x: x[1],
                reverse=True
            )[:10]
        }