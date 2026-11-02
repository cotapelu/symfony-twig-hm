from pydantic import BaseModel
from typing import List, Dict, Optional, Any, Literal
from datetime import datetime, timezone

class Metric(BaseModel):
    name: str
    value: float
    timestamp: datetime = datetime.now(timezone.utc)
    tags: Dict[str, str] = {}

class TimeSeriesData(BaseModel):
    metric_name: str
    data_points: List[Dict[str, Any]]
    granularity: Literal["hour", "day", "week", "month"]
    start_time: datetime
    end_time: datetime

class UserActivity(BaseModel):
    user_id: str
    event_type: str
    event_data: Dict[str, Any]
    timestamp: datetime = datetime.now(timezone.utc)
    session_id: Optional[str] = None
    device_type: Optional[str] = None

class DashboardWidget(BaseModel):
    id: str
    title: str
    widget_type: Literal["chart", "metric", "table", "gauge"]
    config: Dict[str, Any]
    data_source: str
    refresh_interval_seconds: int = 300

class Dashboard(BaseModel):
    id: str
    name: str
    description: str
    widgets: List[DashboardWidget]
    created_at: datetime = datetime.now(timezone.utc)
    updated_at: datetime = datetime.now(timezone.utc)

class Report(BaseModel):
    id: str
    name: str
    report_type: Literal["user_activity", "revenue", "engagement", "custom"]
    parameters: Dict[str, Any]
    generated_at: datetime = datetime.now(timezone.utc)
    data: Dict[str, Any]
    output_format: Literal["json", "csv", "pdf"] = "json"

class TimeRange(BaseModel):
    start: datetime
    end: datetime

class AnalyticsQuery(BaseModel):
    metric: str
    dimensions: List[str] = []
    filters: Dict[str, Any] = {}
    time_range: TimeRange
    aggregation: Literal["sum", "avg", "count", "min", "max"] = "sum"
    group_by: Optional[str] = None
