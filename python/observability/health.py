"""
Health Check Endpoints
Per GOAL.md Section 4.3: Health checks (ready, live, component-specific)
"""

import asyncio
import time
from typing import Dict, Any, List
from dataclasses import dataclass

@dataclass
class HealthStatus:
    """Health check result"""
    status: str  # "healthy", "degraded", "unhealthy"
    details: Dict[str, Any]
    timestamp: float

class HealthChecker:
    """Base health checker with dependency tracking"""

    def __init__(self):
        self.checks: List[tuple] = []  # (name, check_func, timeout)
        self.last_results: Dict[str, HealthStatus] = {}

    def add_check(self, name: str, check_func, timeout: float = 5.0):
        """Register a health check"""
        self.checks.append((name, check_func, timeout))

    async def run_checks(self) -> Dict[str, HealthStatus]:
        """Run all health checks concurrently"""
        tasks = []
        for name, check_func, timeout in self.checks:
            tasks.append(self._run_single_check(name, check_func, timeout))

        results = await asyncio.gather(*tasks, return_exceptions=True)
        for result in results:
            if isinstance(result, Exception):
                self.last_results["unknown"] = HealthStatus(
                    status="unhealthy",
                    details={"error": str(result)},
                    timestamp=time.time()
                )
            elif result:
                name, status = result
                self.last_results[name] = status

        return self.last_results

    async def _run_single_check(self, name: str, check_func, timeout: float):
        """Run single health check with timeout"""
        try:
            result = await asyncio.wait_for(
                asyncio.coroutine(check_func)(),
                timeout=timeout
            )
            return name, result
        except asyncio.TimeoutError:
            return name, HealthStatus(
                status="unhealthy",
                details={"error": f"Timeout after {timeout}s"},
                timestamp=time.time()
            )
        except Exception as e:
            return name, HealthStatus(
                status="unhealthy",
                details={"error": str(e)},
                timestamp=time.time()
            )

    def get_overall_status(self) -> str:
        """Determine overall health from last results"""
        if not self.last_results:
            return "unknown"
        statuses = [s.status for s in self.last_results.values()]
        if any(s == "unhealthy" for s in statuses):
            return "unhealthy"
        if any(s == "degraded" for s in statuses):
            return "degraded"
        return "healthy"

# Global health checker instance
health_checker = HealthChecker()

def create_health_endpoints(app):
    """Add health endpoints to FastAPI app"""

    @app.get("/health/live")
    async def liveness() -> Dict[str, Any]:
        """Kubernetes liveness probe - process is alive"""
        return {
            "status": "alive",
            "timestamp": time.time(),
            "correlation_id": get_correlation_id()
        }

    @app.get("/health/ready")
    async def readiness() -> Dict[str, Any]:
        """Kubernetes readiness probe - dependencies are ready"""
        results = await health_checker.run_checks()
        overall = health_checker.get_overall_status()

        status_code = 200 if overall in ("healthy", "degraded") else 503

        return JSONResponse(
            status_code=status_code,
            content={
                "status": overall,
                "timestamp": time.time(),
                "correlation_id": get_correlation_id(),
                "checks": {name: {"status": s.status, "details": s.details} for name, s in results.items()}
            }
        )

    @app.get("/health")
    async def health() -> Dict[str, Any]:
        """Alias for readiness"""
        return await readiness()

    # Component-specific health checks
    @app.get("/health/db")
    async def db_health() -> Dict[str, Any]:
        """Database health check - to be implemented per module"""
        return {
            "status": "not_implemented",
            "timestamp": time.time(),
            "correlation_id": get_correlation_id()
        }

    @app.get("/health/cache")
    async def cache_health() -> Dict[str, Any]:
        """Cache health check - to be implemented per module"""
        return {
            "status": "not_implemented",
            "timestamp": time.time(),
            "correlation_id": get_correlation_id()
        }

    @app.get("/metrics")
    async def metrics_endpoint() -> Dict[str, Any]:
        """Prometheus-style metrics endpoint"""
        from .middleware import metrics
        return {
            "timestamp": time.time(),
            "metrics": metrics.get_metrics()
        }
