from typing import List, Dict, Optional
from models.schemas import UserProfile, Item, CollaborativeFilterModel, ContentBasedModel, HybridWeights

class RecommendationEngine:
    def __init__(self):
        self.collaborative_model = None
        self.content_model = None
        self.hybrid_weights = HybridWeights()
        self.item_index: Dict[str, Item] = {}
        self.user_profiles: Dict[str, UserProfile] = {}

    def add_item(self, item: Item):
        self.item_index[item.id] = item

    def update_user_profile(self, user_id: str, profile: UserProfile):
        self.user_profiles[user_id] = profile

    def get_popular_items(self, item_type: str, count: int, exclude: List[str] = []) -> List[Item]:
        items = [item for item in self.item_index.values() 
                if item.type == item_type and item.id not in exclude]
        return sorted(items, key=lambda x: x.popularity_score, reverse=True)[:count]

    def get_recency_items(self, item_type: str, count: int, exclude: List[str] = []) -> List[Item]:
        items = [item for item in self.item_index.values() 
                if item.type == item_type and item.id not in exclude]
        return sorted(items, key=lambda x: x.recency_score, reverse=True)[:count]

    def collaborative_filter(self, user_id: str, item_type: str, count: int) -> List[Item]:
        profile = self.user_profiles.get(user_id)
        if not profile:
            return self.get_popular_items(item_type, count)
        
        user_history = set(profile.history)
        recommendations = []
        
        for item in self.item_index.values():
            if item.type == item_type and item.id not in user_history:
                score = self._calculate_collab_score(profile, item)
                if score > 0:
                    recommendations.append((item, score))
        
        recommendations.sort(key=lambda x: x[1], reverse=True)
        return [item for item, _ in recommendations[:count]]

    def _calculate_collab_score(self, profile: UserProfile, item: Item) -> float:
        score = 0.0
        similar_users = profile.preferences
        
        for interest, weight in similar_users.items():
            if interest in item.categories:
                score += weight * item.popularity_score
        
        for interest in profile.interests:
            if interest in item.categories:
                score += 0.5
        
        return score

    def content_filter(self, user_id: str, item_type: str, count: int) -> List[Item]:
        profile = self.user_profiles.get(user_id)
        if not profile or not profile.preferences:
            return self.get_popular_items(item_type, count)
        
        recommendations = []
        for item in self.item_index.values():
            if item.type == item_type and item.id not in profile.history:
                score = self._calculate_content_score(profile, item)
                if score > 0:
                    recommendations.append((item, score))
        
        recommendations.sort(key=lambda x: x[1], reverse=True)
        return [item for item, _ in recommendations[:count]]

    def _calculate_content_score(self, profile: UserProfile, item: Item) -> float:
        score = 0.0
        for category in item.categories:
            if category in profile.preferences:
                score += profile.preferences[category]
        return score

    def hybrid_recommend(self, user_id: str, item_type: str, count: int, 
                         exclude: List[str] = [], context: Optional[Dict] = None) -> List[Item]:
        return self._hybrid_recommend_impl(user_id, item_type, count, exclude, context)

    def _hybrid_recommend_impl(self, user_id: str, item_type: str, count: int, 
                         exclude: List[str] = [], context: Optional[Dict] = None) -> List[Item]:
        collab_items = self.collaborative_filter(user_id, item_type, count * 2)
        content_items = self.content_filter(user_id, item_type, count * 2)
        popular_items = self.get_popular_items(item_type, count * 2, exclude)
        recency_items = self.get_recency_items(item_type, count * 2, exclude)
        
        item_scores: Dict[str, float] = {}
        item_reasons: Dict[str, str] = {}
        
        for item in collab_items:
            item_scores[item.id] = item_scores.get(item.id, 0) + self.hybrid_weights.collaborative_weight * item.popularity_score
            item_reasons[item.id] = "Because you liked similar items"
        
        for item in content_items:
            item_scores[item.id] = item_scores.get(item.id, 0) + self.hybrid_weights.content_weight * item.popularity_score
            if item.id in item_reasons:
                item_reasons[item.id] = "Popular and matches your interests"
            else:
                item_reasons[item.id] = "Matches your preferences"
        
        for item in popular_items:
            item_scores[item.id] = item_scores.get(item.id, 0) + self.hybrid_weights.popularity_weight * item.popularity_score
        
        for item in recency_items:
            item_scores[item.id] = item_scores.get(item.id, 0) + self.hybrid_weights.recency_weight * item.recency_score
        
        sorted_items = sorted(item_scores.items(), key=lambda x: x[1], reverse=True)
        result = []
        for item_id, score in sorted_items:
            if item_id not in exclude and len(result) < count:
                if item_id in self.item_index:
                    self.item_index[item_id].popularity_score = score
                    result.append(self.item_index[item_id])
        
        return result

    def similar_items(self, item_id: str, count: int = 5) -> List[Item]:
        if item_id not in self.item_index:
            return []
        
        target = self.item_index[item_id]
        similarities = []
        
        for other_id, item in self.item_index.items():
            if other_id != item_id:
                sim = self._calculate_similarity(target, item)
                similarities.append((item, sim))
        
        similarities.sort(key=lambda x: x[1], reverse=True)
        return [item for item, _ in similarities[:count]]

    def _calculate_similarity(self, item1: Item, item2: Item) -> float:
        common = set(item1.categories) & set(item2.categories)
        total = set(item1.categories) | set(item2.categories)
        if not total:
            return 0.0
        return len(common) / len(total)

    def trending_now(self, item_type: str, count: int = 10) -> List[Item]:
        items = [item for item in self.item_index.values() if item.type == item_type]
        return sorted(items, key=lambda x: (x.popularity_score, x.recency_score), reverse=True)[:count]
