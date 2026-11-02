"""
Prometheus Metrics Exporter
Converts internal metrics to Prometheus text format
"""

from datetime import datetime
from typing import Dict, Any, List
from .middleware import metrics

def format_prometheus_metrics() -> str:
    """Render metrics in Prometheus text exposition format"""
    lines = []
    m = metrics.get_metrics()

    # Help texts
    lines.append("# HELP http_requests_total Total number of HTTP requests")
    lines.append("# TYPE http_requests_total counter")
    lines.append(f'http_requests_total{{}} {m["total_requests"]}')

    lines.append("# HELP http_request_errors_total Total number of HTTP errors")
    lines.append("# TYPE http_request_errors_total counter")
    lines.append(f'http_request_errors_total{{}} {m["error_count"]}')

    lines.append("# HELP http_request_duration_seconds Histogram of request durations")
    lines.append("# TYPE http_request_duration_seconds histogram")
    # Buckets (simplified)
    buckets = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]
    # We don't have bucketed data, so we'll just report p50/p99 as summaries
    lines.append("# HELP http_request_duration_seconds_sum Sum of request durations")
    lines.append("# TYPE http_request_duration_seconds_sum gauge")
    # Approximate sum from p50 and count (rough)
    if m["total_requests"] > 0:
        approx_sum = m["p50_response_time"] * m["total_requests"] * 0.5  # crude approximation
    else:
        approx_sum = 0.0
    lines.append(f'http_request_duration_seconds_sum {approx_sum}')

    lines.append("# HELP http_request_duration_seconds_count Count of requests")
    lines.append("# TYPE http_request_duration_seconds_count gauge")
    lines.append(f'http_request_duration_seconds_count {m["total_requests"]}')

    lines.append("# HELP http_requests_by_statuscode_total Requests by status code")
    lines.append("# TYPE http_requests_by_statuscode_total counter")
    for code, count in m.get("status_codes", {}).items():
        lines.append(f'http_requests_by_statuscode_total{{code="{code}"}} {count}')

    # Timestamp
    lines.append(f"# META timestamp {datetime.utcnow().isoformat()}Z")

    return "\n".join(lines)
