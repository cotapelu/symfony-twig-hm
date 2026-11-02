from typing import List, Dict, Optional, Any
from datetime import datetime, timezone, timedelta
from collections import defaultdict
import json
import os

from models.schemas import (
    Metric, TimeSeriesData, UserActivity, Dashboard, DashboardWidget,
    Report, AnalyticsQuery, TimeRange
)

class AnalyticsService:
    def __init__(self, ets_file: str = "/dev/shm/data/analytics.json"):
        self.ets_file = ets_file
        self.metrics: Dict[str, List[Metric]] = defaultdict(list)
        self.activities: List[UserActivity] = []
        self.dashboards: Dict[str, Dashboard] = {}
        self.reports: Dict[str, Report] = {}
        self.next_id = 1
        
        self.ensure_data_directory()
        self.restore_from_ets()
    
    def ensure_data_directory(self):
        dir_path = os.path.dirname(self.ets_file)
        if dir_path and dir_path != "." and not os.path.exists(dir_path):
            os.makedirs(dir_path, exist_ok=True)
    
    def restore_from_ets(self):
        return self._restore_from_ets_impl()

    def _restore_from_ets_impl(self):
        if not os.path.exists(self.ets_file):
            return
        
        try:
            with open(self.ets_file, 'r') as f:
                data = json.load(f)
            
            if 'metrics' in data:
                for name, metrics_data in data['metrics'].items():
                    self.metrics[name] = [
                        Metric(**m) for m in metrics_data
                    ]
            
            if 'activities' in data:
                self.activities = [
                    UserActivity(**a) for a in data['activities']
                ]
            
            if 'dashboards' in data:
                for k, v in data['dashboards'].items():
                    widgets = [DashboardWidget(**w) for w in v.get('widgets', [])]
                    self.dashboards[k] = Dashboard(
                        id=v['id'],
                        name=v['name'],
                        description=v.get('description', ''),
                        widgets=widgets,
                        created_at=datetime.fromisoformat(v['created_at']),
                        updated_at=datetime.fromisoformat(v['updated_at'])
                    )
            
            if 'reports' in data:
                for k, v in data['reports'].items():
                    self.reports[k] = Report(**v)
            
            if 'next_id' in data:
                self.next_id = data['next_id']
                
        except Exception as e:
            print(f"Error restoring analytics data: {e}")
    
    def persist_to_ets(self):
        return self._persist_to_ets_impl()

    def _persist_to_ets_impl(self):
        data = {
            'metrics': {
                name: [
                    {
                        'name': m.name,
                        'value': m.value,
                        'timestamp': m.timestamp.isoformat(),
                        'tags': m.tags
                    } for m in metrics
                ]
                for name, metrics in self.metrics.items()
            },
            'activities': [
                {
                    'user_id': a.user_id,
                    'event_type': a.event_type,
                    'event_data': a.event_data,
                    'timestamp': a.timestamp.isoformat(),
                    'session_id': a.session_id,
                    'device_type': a.device_type
                }
                for a in self.activities
            ],
            'dashboards': {
                k: {
                    'id': v.id,
                    'name': v.name,
                    'description': v.description,
                    'widgets': [
                        {
                            'id': w.id,
                            'title': w.title,
                            'widget_type': w.widget_type,
                            'config': w.config,
                            'data_source': w.data_source,
                            'refresh_interval_seconds': w.refresh_interval_seconds
                        }
                        for w in v.widgets
                    ],
                    'created_at': v.created_at.isoformat(),
                    'updated_at': v.updated_at.isoformat()
                }
                for k, v in self.dashboards.items()
            },
            'reports': {
                k: {
                    'id': v.id,
                    'name': v.name,
                    'report_type': v.report_type,
                    'parameters': v.parameters,
                    'generated_at': v.generated_at.isoformat(),
                    'data': v.data,
                    'output_format': v.output_format
                }
                for k, v in self.reports.items()
            },
            'next_id': self.next_id
        }
        
        with open(self.ets_file, 'w') as f:
            json.dump(data, f, indent=2)
    
    def record_metric(self, name: str, value: float, tags: Optional[Dict[str, str]] = None) -> Metric:
        metric = Metric(name=name, value=value, tags=tags or {})
        self.metrics[name].append(metric)
        return metric
    
    def record_activity(self, user_id: str, event_type: str, event_data: Dict[str, Any],
                       session_id: Optional[str] = None, device_type: Optional[str] = None) -> UserActivity:
        activity = UserActivity(
            user_id=user_id,
            event_type=event_type,
            event_data=event_data,
            session_id=session_id,
            device_type=device_type
        )
        self.activities.append(activity)
        return activity
    
    def get_metrics(self, name: str, start_time: Optional[datetime] = None, 
                   end_time: Optional[datetime] = None) -> List[Metric]:
        metrics = self.metrics.get(name, [])
        
        if start_time:
            metrics = [m for m in metrics if m.timestamp >= start_time]
        if end_time:
            metrics = [m for m in metrics if m.timestamp <= end_time]
        
        return sorted(metrics, key=lambda m: m.timestamp)
    
    def get_time_series(self, metric_name: str, granularity: str,
                       start_time: datetime, end_time: datetime) -> TimeSeriesData:
        metrics = self.get_metrics(metric_name, start_time, end_time)
        
        data_points = []
        for m in metrics:
            data_points.append({
                'timestamp': m.timestamp.isoformat(),
                'value': m.value,
                'tags': m.tags
            })
        
        return TimeSeriesData(
            metric_name=metric_name,
            data_points=data_points,
            granularity=granularity,
            start_time=start_time,
            end_time=end_time
        )
    
    def get_activities(self, user_id: Optional[str] = None, event_type: Optional[str] = None,
                      start_time: Optional[datetime] = None, end_time: Optional[datetime] = None) -> List[UserActivity]:
        activities = self.activities
        
        if user_id:
            activities = [a for a in activities if a.user_id == user_id]
        if event_type:
            activities = [a for a in activities if a.event_type == event_type]
        if start_time:
            activities = [a for a in activities if a.timestamp >= start_time]
        if end_time:
            activities = [a for a in activities if a.timestamp <= end_time]
        
        return sorted(activities, key=lambda a: a.timestamp, reverse=True)
    
    def create_dashboard(self, name: str, description: str, 
                        widgets: List[DashboardWidget]) -> Dashboard:
        dashboard_id = f"dash_{self.next_id}"
        self.next_id += 1
        
        dashboard = Dashboard(
            id=dashboard_id,
            name=name,
            description=description,
            widgets=widgets
        )
        
        self.dashboards[dashboard_id] = dashboard
        return dashboard
    
    def get_dashboard(self, dashboard_id: str) -> Optional[Dashboard]:
        return self.dashboards.get(dashboard_id)
    
    def list_dashboards(self) -> List[Dashboard]:
        return list(self.dashboards.values())
    
    def generate_report(self, name: str, report_type: str, 
                       parameters: Dict[str, Any]) -> Report:
        report_id = f"report_{self.next_id}"
        self.next_id += 1
        
        data = self._generate_report_data(report_type, parameters)
        
        report = Report(
            id=report_id,
            name=name,
            report_type=report_type,
            parameters=parameters,
            data=data
        )
        
        self.reports[report_id] = report
        return report
    
    def _generate_report_data(self, report_type: str, parameters: Dict[str, Any]) -> Dict[str, Any]:
        if report_type == "user_activity":
            return self._generate_user_activity_report(parameters)
        elif report_type == "engagement":
            return self._generate_engagement_report(parameters)
        elif report_type == "revenue":
            return self._generate_revenue_report(parameters)
        else:
            return {"message": "Custom report not implemented"}
    
    def _generate_user_activity_report(self, parameters: Dict[str, Any]) -> Dict[str, Any]:
        start_time = parameters.get('start_time')
        end_time = parameters.get('end_time')
        
        if isinstance(start_time, str):
            start_time = datetime.fromisoformat(start_time)
        if isinstance(end_time, str):
            end_time = datetime.fromisoformat(end_time)
        
        activities = self.get_activities(start_time=start_time, end_time=end_time)
        
        event_counts = defaultdict(int)
        user_counts = defaultdict(int)
        
        for activity in activities:
            event_counts[activity.event_type] += 1
            user_counts[activity.user_id] += 1
        
        return {
            'total_activities': len(activities),
            'unique_users': len(user_counts),
            'event_breakdown': dict(event_counts),
            'top_users': sorted(
                [{'user_id': k, 'count': v} for k, v in user_counts.items()],
                key=lambda x: x['count'],
                reverse=True
            )[:10]
        }
    
    def _generate_engagement_report(self, parameters: Dict[str, Any]) -> Dict[str, Any]:
        return {
            'daily_active_users': len(set(
                a.user_id for a in self.activities
                if a.timestamp > datetime.now(timezone.utc) - timedelta(days=1)
            )),
            'weekly_active_users': len(set(
                a.user_id for a in self.activities
                if a.timestamp > datetime.now(timezone.utc) - timedelta(days=7)
            )),
            'monthly_active_users': len(set(
                a.user_id for a in self.activities
                if a.timestamp > datetime.now(timezone.utc) - timedelta(days=30)
            ))
        }
    
    def _generate_revenue_report(self, parameters: Dict[str, Any]) -> Dict[str, Any]:
        return {
            'message': 'Revenue report requires integration with payment modules'
        }
    
    def execute_query(self, query: AnalyticsQuery) -> Dict[str, Any]:
        return self._execute_query_impl(query)

    def _execute_query_impl(self, query: AnalyticsQuery) -> Dict[str, Any]:
        metrics = self.get_metrics(
            query.metric,
            query.time_range.start,
            query.time_range.end
        )
        
        if query.filters:
            for key, value in query.filters.items():
                metrics = [m for m in metrics if m.tags.get(key) == value]
        
        values = [m.value for m in metrics]
        
        if query.aggregation == "sum":
            result = sum(values) if values else 0
        elif query.aggregation == "avg":
            result = sum(values) / len(values) if values else 0
        elif query.aggregation == "count":
            result = len(values)
        elif query.aggregation == "min":
            result = min(values) if values else 0
        elif query.aggregation == "max":
            result = max(values) if values else 0
        else:
            result = 0
        
        return {
            'metric': query.metric,
            'aggregation': query.aggregation,
            'result': result,
            'count': len(values),
            'dimensions': query.dimensions
        }
    
    def terminate(self):
        self.persist_to_ets()
