use axum::{http::StatusCode, Json, response::IntoResponse};
use serde_json::json;
use chrono::Utc;

/// Liveness probe
/// Returns 200 OK always if server is alive
pub async fn live() -> impl IntoResponse {
    (StatusCode::OK, Json(json!({
        "status": "alive",
        "timestamp": Utc::now().to_rfc3339()
    })))
}

/// Readiness probe
/// Checks dependencies (for now just returns ready)
pub async fn ready() -> impl IntoResponse {
    (StatusCode::OK, Json(json!({
        "status": "ready",
        "timestamp": Utc::now().to_rfc3339()
    })))
}

#[cfg(test)]
mod tests {
    use super::*;
    use axum::http::StatusCode;

    #[tokio::test]
    async fn test_live_returns_ok() {
        let resp = live().await;
        assert_eq!(resp.into_response().status(), StatusCode::OK);
    }

    #[tokio::test]
    async fn test_ready_returns_ok() {
        let resp = ready().await;
        assert_eq!(resp.into_response().status(), StatusCode::OK);
    }
}
