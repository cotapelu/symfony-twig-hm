use crate::utils::{generate_id, ApiResponse};
use chrono::{DateTime, Utc};
use serde_json::json;

#[test]
fn test_generate_id_returns_valid_uuid() {
    let id = generate_id();
    assert!(!id.is_empty());
    // UUID format: 8-4-4-4-12 hex digits
    let parts: Vec<&str> = id.split('-').collect();
    assert_eq!(parts.len(), 5);
    assert_eq!(parts[0].len(), 8);
    assert_eq!(parts[1].len(), 4);
    assert_eq!(parts[2].len(), 4);
    assert_eq!(parts[3].len(), 4);
    assert_eq!(parts[4].len(), 12);
}

#[test]
fn test_api_response_success() {
    let response = ApiResponse::success("data");
    assert!(response.success);
    assert_eq!(response.data.unwrap(), "data");
    assert!(response.error.is_none());
    // timestamp should be recent
    let now = Utc::now();
    let elapsed = now.signed_duration_since(response.timestamp);
    assert!(elapsed.num_seconds() >= 0); // response time not after now
}

#[test]
fn test_api_response_error() {
    let response = ApiResponse::<()>::error("Something went wrong");
    assert!(!response.success);
    assert!(response.data.is_none());
    assert_eq!(response.error.unwrap(), "Something went wrong");
}

#[test]
fn test_api_response_timestamp_is_set() {
    let before = Utc::now();
    let response = ApiResponse::success(42);
    let after = Utc::now();
    assert!(response.timestamp >= before);
    assert!(response.timestamp <= after);
}
