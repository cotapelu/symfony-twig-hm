use axum::{
    body::Body,
    http::{Request, StatusCode},
    response::Response,
    routing::post,
    Router,
};
use axum::extract::State;
use hyper::Body as HyperBody;
use serde_json::json;
use std::sync::Arc;
use tower::ServiceExt; // for oneshot
use crate::banking::application::{BankingService, BankingError};
use crate::banking::domain::{Account, AccountRepository, AccountType};
use crate::banking::infrastructure::InMemoryBankingRepository;

// Helper to build app with service
fn setup_app() -> Router {
    let repo = Arc::new(InMemoryBankingRepository::new());
    let service = Arc::new(BankingService::new(repo));
    crate::banking::api::router(service)
}

#[tokio::test]
async fn test_create_account_success() {
    let app = setup_app();

    let response = app
        .oneshot(
            Request::builder()
                .method("POST")
                .uri("/accounts")
                .header("Content-Type", "application/json")
                .body(Body::from(
                    json!({
                        "user_id": "user1",
                        "account_type": "CHECKING",
                        "currency": "USD"
                    })
                    .to_string(),
                ))
                .unwrap(),
        )
        .await
        .unwrap();

    assert_eq!(response.status(), StatusCode::CREATED);
}

#[tokio::test]
async fn test_create_account_invalid_type() {
    let app = setup_app();

    let response = app
        .oneshot(
            Request::builder()
                .method("POST")
                .uri("/accounts")
                .header("Content-Type", "application/json")
                .body(Body::from(
                    json!({
                        "user_id": "user1",
                        "account_type": "INVALID",
                        "currency": "USD"
                    })
                    .to_string(),
                ))
                .unwrap(),
        )
        .await
        .unwrap();

    assert_eq!(response.status(), StatusCode::BAD_REQUEST);
}

#[tokio::test]
async fn test_get_account_not_found() {
    let app = setup_app();

    let response = app
        .oneshot(
            Request::builder()
                .method("GET")
                .uri("/accounts/nonexistent")
                .header("Content-Type", "application/json")
                .body(Body::empty())
                .unwrap(),
        )
        .await
        .unwrap();

    assert_eq!(response.status(), StatusCode::NOT_FOUND);
}

#[tokio::test]
async fn test_deposit_negative_amount() {
    let app = setup_app();

    // First create an account
    let app_clone = app.clone();
    let create_response = app_clone
        .oneshot(
            Request::builder()
                .method("POST")
                .uri("/accounts")
                .header("Content-Type", "application/json")
                .body(Body::from(
                    json!({
                        "user_id": "user2",
                        "account_type": "SAVINGS",
                        "currency": "VND"
                    })
                    .to_string(),
                ))
                .unwrap(),
        )
        .await
        .unwrap();
    assert_eq!(create_response.status(), StatusCode::CREATED);
    let body_bytes = hyper::body::to_bytes(create_response.into_body(), 1024).await.unwrap();
    let body: serde_json::Value = serde_json::from_slice(&body_bytes).unwrap();
    let account_id = body["id"].as_str().unwrap().to_string();

    // Attempt to deposit negative amount (should fail)
    let response = app
        .oneshot(
            Request::builder()
                .method("POST")
                .uri(format!("/accounts/{}/deposit", account_id))
                .header("Content-Type", "application/json")
                .body(Body::from(
                    json!({ "amount": -100.0 }).to_string(),
                ))
                .unwrap(),
        )
        .await
        .unwrap();

    assert_eq!(response.status(), StatusCode::BAD_REQUEST);
}
