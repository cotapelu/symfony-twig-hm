use crate::metrics::metrics_handler;
use axum::{
    http::StatusCode,
    response::IntoResponse,
};

#[tokio::test]
async fn test_metrics_handler_returns_200() {
    let response = metrics_handler().await.into_response();
    assert_eq!(response.status(), StatusCode::OK);
}

#[tokio::test]
async fn test_metrics_handler_increments_counter() {
    // Get initial value
    let initial = crate::metrics::METRICS_ENDPOINT_REQUESTS_TOTAL.get();

    // Call handler twice
    metrics_handler().await.into_response();
    metrics_handler().await.into_response();

    let after = crate::metrics::METRICS_ENDPOINT_REQUESTS_TOTAL.get();
    assert!(after > initial, "Counter should have increased");
    // Note: exact increment may vary if other tests run in parallel; just ensure it went up
}
