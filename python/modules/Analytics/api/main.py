from fastapi import FastAPI, HTTPException, Query, Header, Depends
from typing import Dict, List, Optional
from datetime import datetime, timezone, timedelta
from ..models.schemas import (
    Metric, TimeSeriesData, UserActivity, Dashboard, DashboardWidget,
    Report, AnalyticsQuery, TimeRange
)
from service.analytics_service import AnalyticsService
import logging
import os

logger = logging.getLogger(__name__)

# Simple API key verification - in production, use a proper secrets manager
def verify_api_key(x_api_key: Optional[str] = Header(None)):
    expected_key = os.environ.get("ANALYTICS_API_KEY", "dev-key-123")  # Default for development
    if x_api_key is None or x_api_key != expected_key:
        raise HTTPException(
            status_code=403,
            detail="Invalid or missing API key"
        )
    return x_api_key

app = FastAPI(title="Analytics API", version="1.0.0")
service = AnalyticsService()

@app.post("/api/analytics/metrics", response_model=Metric)
async def record_metric(name: str, value: float, tags: Optional[Dict[str, str]] = None, api_key: str = Depends(verify_api_key)):
    # Input validation
    if not name or not name.strip():
        raise HTTPException(status_code=400, detail="Metric name cannot be empty")
    if len(name) > 200:
        raise HTTPException(status_code=400, detail="Metric name cannot exceed 200 characters")
    # Check for reasonable value range
    if not (-float('inf') < value < float('inf')):
        raise HTTPException(status_code=400, detail="Metric value must be a finite number")
    # Validate tags if provided
    if tags:
        if len(tags) > 20:
            raise HTTPException(status_code=400, detail="Cannot have more than 20 tags")
        for k, v in tags.items():
            if not k or not k.strip():
                raise HTTPException(status_code=400, detail="Tag keys cannot be empty")
            if len(k) > 50:
                raise HTTPException(status_code=400, detail="Tag key cannot exceed 50 characters")
            if len(v) > 200:
                raise HTTPException(status_code=400, detail="Tag value cannot exceed 200 characters")
    
    logger.info(f"Recording metric: {name} = {value}", extra={"tags": tags})
    return service.record_metric(name, value, tags or {})

@app.get("/api/analytics/metrics/{metric_name}")
async def get_metrics(
    metric_name: str,
    start_time: Optional[datetime] = None,
    end_time: Optional[datetime] = None
):
    metrics = service.get_metrics(metric_name, start_time, end_time)
    return {
        "metric_name": metric_name,
        "count": len(metrics),
        "metrics": [
            {
                "name": m.name,
                "value": m.value,
                "timestamp": m.timestamp.isoformat(),
                "tags": m.tags
            }
            for m in metrics
        ]
    }

@app.get("/api/analytics/metrics/{metric_name}/timeseries")
async def get_time_series(
    metric_name: str,
    granularity: str = "hour",
    start_time: Optional[datetime] = None,
    end_time: Optional[datetime] = None
):
    if not start_time:
        start_time = datetime.now(timezone.utc) - timedelta(days=7)
    if not end_time:
        end_time = datetime.now(timezone.utc)
    
    ts_data = service.get_time_series(metric_name, granularity, start_time, end_time)
    return {
        "metric_name": ts_data.metric_name,
        "granularity": ts_data.granularity,
        "start_time": ts_data.start_time.isoformat(),
        "end_time": ts_data.end_time.isoformat(),
        "data_points": ts_data.data_points
    }

@app.post("/api/analytics/activities")
async def record_activity(
    user_id: str,
    event_type: str,
    event_data: Dict,
    session_id: Optional[str] = None,
    device_type: Optional[str] = None
):
    return service.record_activity(user_id, event_type, event_data, session_id, device_type)

@app.get("/api/analytics/activities")
async def get_activities(
    user_id: Optional[str] = None,
    event_type: Optional[str] = None,
    start_time: Optional[datetime] = None,
    end_time: Optional[datetime] = None,
    limit: int = Query(default=100, le=1000)
):
    activities = service.get_activities(user_id, event_type, start_time, end_time)
    activities = activities[:limit]
    
    return {
        "count": len(activities),
        "activities": [
            {
                "user_id": a.user_id,
                "event_type": a.event_type,
                "event_data": a.event_data,
                "timestamp": a.timestamp.isoformat(),
                "session_id": a.session_id,
                "device_type": a.device_type
            }
            for a in activities
        ]
    }

@app.post("/api/analytics/dashboards")
async def create_dashboard(
    name: str,
    description: str,
    widgets: List[Dict]
):
    widget_objects = [DashboardWidget(**w) for w in widgets]
    dashboard = service.create_dashboard(name, description, widget_objects)
    return {
        "id": dashboard.id,
        "name": dashboard.name,
        "description": dashboard.description,
        "widgets": [
            {
                "id": w.id,
                "title": w.title,
                "widget_type": w.widget_type,
                "config": w.config,
                "data_source": w.data_source,
                "refresh_interval_seconds": w.refresh_interval_seconds
            }
            for w in dashboard.widgets
        ],
        "created_at": dashboard.created_at.isoformat()
    }

@app.get("/api/analytics/dashboards")
async def list_dashboards():
    dashboards = service.list_dashboards()
    return {
        "count": len(dashboards),
        "dashboards": [
            {
                "id": d.id,
                "name": d.name,
                "description": d.description,
                "widget_count": len(d.widgets),
                "created_at": d.created_at.isoformat()
            }
            for d in dashboards
        ]
    }

@app.get("/api/analytics/dashboards/{dashboard_id}")
async def get_dashboard(dashboard_id: str):
    dashboard = service.get_dashboard(dashboard_id)
    if not dashboard:
        raise HTTPException(status_code=404, detail="Dashboard not found")
    
    return {
        "id": dashboard.id,
        "name": dashboard.name,
        "description": dashboard.description,
        "widgets": [
            {
                "id": w.id,
                "title": w.title,
                "widget_type": w.widget_type,
                "config": w.config,
                "data_source": w.data_source,
                "refresh_interval_seconds": w.refresh_interval_seconds
            }
            for w in dashboard.widgets
        ],
        "created_at": dashboard.created_at.isoformat(),
        "updated_at": dashboard.updated_at.isoformat()
    }

@app.post("/api/analytics/reports")
async def generate_report(
    name: str,
    report_type: str,
    parameters: Optional[Dict] = None
):
    report = service.generate_report(name, report_type, parameters or {})
    return {
        "id": report.id,
        "name": report.name,
        "report_type": report.report_type,
        "generated_at": report.generated_at.isoformat(),
        "data": report.data,
        "output_format": report.output_format
    }

@app.post("/api/analytics/query")
async def execute_query(query: AnalyticsQuery):
    result = service.execute_query(query)
    return result

@app.get("/api/analytics/health")
async def health_check():
    return {"status": "healthy", "service": "analytics"}
