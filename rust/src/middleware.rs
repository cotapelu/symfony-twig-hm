use axum::{
    body::Body,
    http::{Request, Response, HeaderMap, StatusCode},
    middleware::{self, Next},
    response::IntoResponse,
};
use std::{
    time::Instant,
    sync::Arc,
};
use tracing::{info_span, info, warn, error, Instrument, Span, Level};
use uuid::Uuid;
use prometheus::{IntCounterVec, register_int_counter_vec};

// Metric for requests: labels method, path, status
lazy_static::lazy_static! {
    pub static ref HTTP_REQUESTS_TOTAL: IntCounterVec =
        register_int_counter_vec!(
            "http_requests_total",
            "Total number of HTTP requests by method, path, status",
            &["method", "path", "status"]
        ).unwrap();
}

/// Middleware to add correlation ID to request/response and record metrics
pub async fn metrics_middleware(
    mut req: Request<Body>,
    next: Next,
) -> Response<Body> {
    // Correlation ID: get from header or generate
    let correlation_id = req
        .headers()
        .get("X-Correlation-ID")
        .and_then(|h| h.to_str().ok())
        .map(String::from)
        .unwrap_or_else(|| Uuid::new_v4().to_string());

    // Insert into request extensions for downstream logging
    req.extensions_mut().insert(correlation_id.clone());

    // Start timer
    let start = Instant::now();
    let method = req.method().to_string();
    let path = req.uri().path().to_string();

    // Execute request
    let mut response = next.run(req).await;

    let duration = start.elapsed();
    let status = response.status().as_u16().to_string();

    // Record metric
    HTTP_REQUESTS_TOTAL
        .with_label_values(&[&method, &path, &status])
        .inc();

    // Log request completion with correlation ID
    let level = if response.status().is_success() {
        tracing::Level::INFO
    } else if response.status().is_client_error() {
        tracing::Level::WARN
    } else {
        tracing::Level::ERROR
    };

    let span = info_span!(
        "request",
        method = &method,
        path = &path,
        status = &status,
        correlation_id = &correlation_id,
        duration_ms = duration.as_millis() as u64
    );

    // Add correlation ID header to response
    response.headers_mut().insert(
        "X-Correlation-ID",
        correlation_id.parse().unwrap(),
    );

    // Instrument response
    let _enter = span.enter();
    match level {
        tracing::Level::INFO => info!(target: "http", "request completed"),
        tracing::Level::WARN => tracing::warn!(target: "http", "request completed with client error"),
        tracing::Level::ERROR => tracing::error!(target: "http", "request completed with server error"),
        _ => {}
    }

    response
}
