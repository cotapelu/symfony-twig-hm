use axum::{
    http::StatusCode,
    response::{IntoResponse, Response},
};
use prometheus::{register_int_counter, IntCounter, Encoder, TextEncoder};
use lazy_static::lazy_static;

// Global metrics
lazy_static! {
    pub static ref METRICS_ENDPOINT_REQUESTS_TOTAL: IntCounter = register_int_counter!(
        "metrics_endpoint_requests_total",
        "Total number of requests to the metrics endpoint"
    ).unwrap();
}

pub async fn metrics_handler() -> impl IntoResponse {
    // Increment for this request
    METRICS_ENDPOINT_REQUESTS_TOTAL.inc();

    // Gather all metrics
    let encoder = TextEncoder::new();
    let metric_families = prometheus::gather();
    let mut buffer = Vec::new();

    // Encode metrics to Prometheus text format
    if encoder.encode(&metric_families, &mut buffer).is_err() {
        return (StatusCode::INTERNAL_SERVER_ERROR, "Failed to encode metrics").into_response();
    }

    let metrics_output = String::from_utf8(buffer).unwrap_or_default();
    (StatusCode::OK, metrics_output).into_response()
}

#[cfg(test)]
mod tests {
    use super::*;

    #[tokio::test]
    async fn test_metrics_handler_increments_counter() {
        let before = METRICS_ENDPOINT_REQUESTS_TOTAL.get();
        // Call handler; it should increment the counter and return a response
        let _response = metrics_handler().await.into_response();
        let after = METRICS_ENDPOINT_REQUESTS_TOTAL.get();
        assert!(after > before, "counter should increment");
    }
}
