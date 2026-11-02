use axum::{
    body::Body,
    http::{Request, Response, StatusCode},
    routing::get,
    Router,
};
use axum::response::IntoResponse;
use tower::Service;
use crate::middleware::{HTTP_REQUESTS_TOTAL, metrics_middleware};

fn init_logging() {
    let _ = tracing_subscriber::fmt::try_init();
}

#[tokio::test]
async fn test_metrics_middleware_records_metrics() {
    init_logging();

    async fn handler() -> &'static str {
        "OK"
    }

    let app = Router::new()
        .route("/test", get(handler))
        .layer(axum::middleware::from_fn(metrics_middleware));

    let req = Request::builder()
        .method("GET")
        .uri("/test")
        .body(Body::empty())
        .unwrap();

    let mut app = app;
    let response: Response<Body> = app.call(req).await.unwrap();

    assert_eq!(response.status(), StatusCode::OK);

    let metric = HTTP_REQUESTS_TOTAL.with_label_values(&["GET", "/test", "200"]);
    let count = metric.get();
    assert!(count > 0, "expected metric count > 0, got {}", count);
}

#[tokio::test]
async fn test_correlation_id_generated_if_missing() {
    init_logging();

    async fn handler() -> &'static str {
        "created"
    }

    let app = Router::new()
        .route("/api/endpoint", get(handler))
        .layer(axum::middleware::from_fn(metrics_middleware));

    let req = Request::builder()
        .method("GET")
        .uri("/api/endpoint")
        .body(Body::empty())
        .unwrap();

    let mut app = app;
    let response: Response<Body> = app.call(req).await.unwrap();

    assert_eq!(response.status(), StatusCode::OK);

    let headers = response.headers();
    let corr_id = headers.get("X-Correlation-ID").expect("missing X-Correlation-ID");
    let corr_str = corr_id.to_str().unwrap();
    assert!(!corr_str.is_empty(), "correlation ID should not be empty");
    assert!(corr_str.contains("-"), "correlation ID should look like UUID");
}

#[tokio::test]
async fn test_correlation_id_preserved_when_provided() {
    init_logging();

    let original_id = "test-correlation-123".to_string();

    async fn handler() -> &'static str {
        "ok"
    }

    let app = Router::new()
        .route("/health", get(handler))
        .layer(axum::middleware::from_fn(metrics_middleware));

    let req = Request::builder()
        .method("GET")
        .uri("/health")
        .header("X-Correlation-ID", original_id.clone())
        .body(Body::empty())
        .unwrap();

    let mut app = app;
    let response: Response<Body> = app.call(req).await.unwrap();

    let headers = response.headers();
    let returned_id = headers.get("X-Correlation-ID").unwrap().to_str().unwrap();
    assert_eq!(returned_id, original_id, "should preserve existing correlation ID");
}

#[tokio::test]
async fn test_metric_labels_for_different_path_and_status() {
    init_logging();

    let app = Router::new()
        .layer(axum::middleware::from_fn(metrics_middleware));

    let req = Request::builder()
        .method("DELETE")
        .uri("/nonexistent")
        .body(Body::empty())
        .unwrap();

    let mut app = app;
    let response: Response<Body> = app.call(req).await.unwrap();

    assert_eq!(response.status(), StatusCode::NOT_FOUND);

    let metric = HTTP_REQUESTS_TOTAL.with_label_values(&["DELETE", "/nonexistent", "404"]);
    let count = metric.get();
    assert!(count > 0, "expected metric count for 404 > 0, got {}", count);
}

#[tokio::test]
async fn test_metrics_middleware_for_server_error() {
    init_logging();

    async fn handler() -> impl IntoResponse {
        (StatusCode::INTERNAL_SERVER_ERROR, "Server error")
    }

    let app = Router::new()
        .route("/err", get(handler))
        .layer(axum::middleware::from_fn(metrics_middleware));

    let req = Request::builder()
        .method("GET")
        .uri("/err")
        .body(Body::empty())
        .unwrap();

    let mut app = app;
    let response: Response<Body> = app.call(req).await.unwrap();

    assert_eq!(response.status(), StatusCode::INTERNAL_SERVER_ERROR);

    let metric = HTTP_REQUESTS_TOTAL.with_label_values(&["GET", "/err", "500"]);
    let count = metric.get();
    assert!(count > 0, "expected metric count for 500 > 0, got {}", count);
}
