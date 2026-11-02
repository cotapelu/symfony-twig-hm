"""Analytics service using unified Event model."""
from typing import Dict, Any, List
from datetime import datetime, timedelta
from statistics import mean, median
from collections import defaultdict
import sqlalchemy as sa
import json

from app.shared.database import db
from app.shared.models import Event, EventSource


class AnalyticsService:
    """Analytics service using unified Event model."""

    def __init__(self):
        self.db = db

    async def track_event(self, user_id: str, event_type: str, properties: dict = None, metadata: dict = None) -> Event:
        import json
        async with self.db.get_session() as session:
            event = Event(
                user_id=user_id,
                event_type=event_type,
                aggregate_type='analytics',
                aggregate_id=None,
                payload=json.dumps(properties) if properties else None,
                event_metadata=json.dumps(metadata) if metadata else None,
                event_source=EventSource.ANALYTICS,
                occurred_at=datetime.utcnow()
            )
            session.add(event)
            await session.commit()
            await session.refresh(event)
            return event

    async def get_user_activity(self, user_id: str, days: int = 30) -> Dict[str, Any]:
        async with self.db.get_session() as session:
            cutoff_date = datetime.utcnow() - timedelta(days=days)
            result = await session.execute(
                sa.select(Event)
                .where(Event.user_id == user_id)
                .where(Event.occurred_at >= cutoff_date)
            )
            user_events = result.scalars().all()

            event_types = defaultdict(int)
            active_dates = set()
            for event in user_events:
                event_types[event.event_type] += 1
                if event.occurred_at:
                    active_dates.add(event.occurred_at.date())

            return {
                "user_id": user_id,
                "total_events": len(user_events),
                "event_types": dict(event_types),
                "days_active": len(active_dates),
                "average_daily_events": len(user_events) / days if days > 0 else 0.0
            }

    async def get_engagement_metrics(self, date_range: int = 30) -> Dict[str, Any]:
        async with self.db.get_session() as session:
            cutoff_date = datetime.utcnow() - timedelta(days=date_range)

            result = await session.execute(
                sa.select(Event).where(Event.occurred_at >= cutoff_date)
            )
            recent_events = result.scalars().all()

            # Daily active users: count distinct users per day
            result = await session.execute(
                sa.select(sa.func.date(Event.occurred_at), sa.func.count(sa.func.distinct(Event.user_id)).label('count'))
                .where(Event.occurred_at >= cutoff_date)
                .group_by(sa.func.date(Event.occurred_at))
            )
            dau_list = [row.count for row in result.all()]

            # Retention: users from first day vs last day
            first_day = (datetime.utcnow() - timedelta(days=date_range)).date()
            last_day = datetime.utcnow().date()

            first_day_users = set()
            last_day_users = set()

            if dau_list:
                # Get users on first and last day directly
                result_first = await session.execute(
                    sa.select(sa.func.distinct(Event.user_id))
                    .where(sa.func.date(Event.occurred_at) == first_day)
                )
                first_day_users = set(u[0] for u in result_first.all())

                result_last = await session.execute(
                    sa.select(sa.func.distinct(Event.user_id))
                    .where(sa.func.date(Event.occurred_at) == last_day)
                )
                last_day_users = set(u[0] for u in result_last.all())

            retention_rate = len(first_day_users & last_day_users) / len(first_day_users) if first_day_users else 0.0

            return {
                "total_events": len(recent_events),
                "daily_active_users": {
                    "average": mean(dau_list) if dau_list else 0.0,
                    "median": median(dau_list) if dau_list else 0.0,
                    "max": max(dau_list) if dau_list else 0
                },
                "retention_rate": retention_rate,
                "date_range_days": date_range
            }

    async def analyze_funnel(self, funnel_name: str, funnel_steps: List[str], date_range: int = 30) -> Dict[str, Any]:
        async with self.db.get_session() as session:
            cutoff_date = datetime.utcnow() - timedelta(days=date_range)
            result = await session.execute(
                sa.select(Event)
                .where(Event.occurred_at >= cutoff_date)
                .where(Event.event_type.in_(funnel_steps))
            )
            recent_events = result.scalars().all()

            funnel_data: Dict[str, set] = {step: set() for step in funnel_steps}
            for event in recent_events:
                if event.event_type in funnel_data and event.user_id:
                    funnel_data[event.event_type].add(event.user_id)

            conversion_rates = {}
            for i in range(1, len(funnel_steps)):
                prev = funnel_steps[i-1]
                curr = funnel_steps[i]
                prev_count = len(funnel_data[prev])
                curr_count = len(funnel_data[curr])
                conversion_rates[f"{prev}_to_{curr}"] = curr_count / prev_count if prev_count > 0 else 0.0

            return {
                "funnel_name": funnel_name,
                "funnel_steps": {s: len(funnel_data[s]) for s in funnel_steps},
                "conversion_rates": conversion_rates,
                "date_range_days": date_range
            }

    async def get_cohort_analysis(self, cohort_period: str = "week", periods: int = 8) -> Dict[str, Any]:
        # Simplified: return empty for now (expensive query)
        return {"cohort_retention": {}, "periods": periods, "cohort_period": cohort_period}

    async def predict_churn(self, user_id: str) -> Dict[str, Any]:
        activity = await self.get_user_activity(user_id, days=30)
        low_activity = activity["average_daily_events"] < 1
        declining = activity["days_active"] < 10
        no_recent = activity["total_events"] == 0
        score = (0.3 if low_activity else 0) + (0.4 if declining else 0) + (0.3 if no_recent else 0)
        risk = "high" if score > 0.7 else "medium" if score > 0.4 else "low"
        return {"user_id": user_id, "churn_probability": score, "churn_risk": risk, "features": {}}

    async def get_top_features(self, event_type: str, limit: int = 10) -> List[Dict[str, Any]]:
        async with self.db.get_session() as session:
            result = await session.execute(
                sa.select(Event).where(Event.event_type == event_type)
            )
            events = result.scalars().all()

            feature_counts: Dict[str, int] = defaultdict(int)
            for event in events:
                try:
                    if event.payload:
                        props = json.loads(event.payload)
                        for key, value in props.items():
                            feature_counts[f"{key}:{value}"] += 1
                except:
                    pass

            sorted_features = sorted(feature_counts.items(), key=lambda x: x[1], reverse=True)
            return [{"feature": f, "count": c} for f, c in sorted_features[:limit]]
