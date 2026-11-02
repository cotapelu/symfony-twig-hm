use axum::{
    extract::{Path, State, Json},
    http::StatusCode,
    routing::{post, get},
    Router,
};
use serde::{Deserialize, Serialize};
use std::sync::Arc;
use tracing::info;

use crate::banking::application::{BankingService, BankingError};
use crate::banking::domain::{AccountType, Account, AccountRepository};

#[derive(Deserialize)]
struct CreateAccountRequest {
    user_id: String,
    account_type: String,
    currency: String,
}

#[derive(Serialize)]
struct AccountResponse {
    id: String,
    user_id: String,
    account_number: String,
    account_type: String,
    currency: String,
    balance: f64,
    is_active: bool,
    created_at: String,
    updated_at: String,
}

impl From<Account> for AccountResponse {
    fn from(acc: Account) -> Self {
        Self {
            id: acc.id,
            user_id: acc.user_id,
            account_number: acc.account_number,
            account_type: match acc.account_type {
                AccountType::CHECKING => "CHECKING".to_string(),
                AccountType::SAVINGS => "SAVINGS".to_string(),
                AccountType::BUSINESS => "BUSINESS".to_string(),
            },
            currency: acc.currency,
            balance: acc.balance,
            is_active: acc.is_active,
            created_at: acc.created_at.to_rfc3339(),
            updated_at: acc.updated_at.to_rfc3339(),
        }
    }
}

#[derive(Deserialize)]
struct DepositRequest {
    amount: f64,
}

#[derive(Deserialize)]
struct WithdrawRequest {
    amount: f64,
}

pub fn router<R: AccountRepository + Send + Sync + 'static>(
    service: Arc<BankingService<R>>,
) -> Router {
    Router::new()
        .route("/accounts", post(create_account::<R>))
        .route("/accounts/:id", get(get_account::<R>))
        .route("/accounts/:id/deposit", post(deposit::<R>))
        .route("/accounts/:id/withdraw", post(withdraw::<R>))
        .with_state(service)
}

async fn create_account<R: AccountRepository + Send + Sync + 'static>(
    State(service): State<Arc<BankingService<R>>>,
    Json(req): Json<CreateAccountRequest>,
) -> Result<(StatusCode, Json<AccountResponse>), (StatusCode, String)> {
    let account_type = match req.account_type.to_uppercase().as_str() {
        "CHECKING" => AccountType::CHECKING,
        "SAVINGS" => AccountType::SAVINGS,
        "BUSINESS" => AccountType::BUSINESS,
        _ => {
            return Err((
                StatusCode::BAD_REQUEST,
                "Invalid account type".to_string(),
            ))
        }
    };

    let account = service
        .create_account(req.user_id, account_type, req.currency)
        .map_err(|e| (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()))?;

    info!("Account created: {}", account.id);
    Ok((StatusCode::CREATED, Json(AccountResponse::from(account))))
}

async fn get_account<R: AccountRepository + Send + Sync + 'static>(
    State(service): State<Arc<BankingService<R>>>,
    Path(account_id): Path<String>,
    axum::extract::Extension(user_id): axum::extract::Extension<String>,
) -> Result<(StatusCode, Json<AccountResponse>), (StatusCode, String)> {
    let account = service
        .get_account(&account_id, &user_id)
        .map_err(|e| match e {
            BankingError::NotFound => (StatusCode::NOT_FOUND, e.to_string()),
            BankingError::Unauthorized => (StatusCode::FORBIDDEN, e.to_string()),
            _ => (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()),
        })?;

    Ok((StatusCode::OK, Json(AccountResponse::from(account))))
}

async fn deposit<R: AccountRepository + Send + Sync + 'static>(
    State(service): State<Arc<BankingService<R>>>,
    Path(account_id): Path<String>,
    axum::extract::Extension(user_id): axum::extract::Extension<String>,
    Json(req): Json<DepositRequest>,
) -> Result<(StatusCode, Json<AccountResponse>), (StatusCode, String)> {
    let account = service
        .deposit(&account_id, req.amount, &user_id)
        .map_err(|e| match e {
            BankingError::NotFound => (StatusCode::NOT_FOUND, e.to_string()),
            BankingError::Unauthorized => (StatusCode::FORBIDDEN, e.to_string()),
            BankingError::InvalidOperation(msg) => (StatusCode::BAD_REQUEST, msg),
            _ => (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()),
        })?;

    info!("Deposit to {}: {}", account_id, req.amount);
    Ok((StatusCode::OK, Json(AccountResponse::from(account))))
}

async fn withdraw<R: AccountRepository + Send + Sync + 'static>(
    State(service): State<Arc<BankingService<R>>>,
    Path(account_id): Path<String>,
    axum::extract::Extension(user_id): axum::extract::Extension<String>,
    Json(req): Json<WithdrawRequest>,
) -> Result<(StatusCode, Json<AccountResponse>), (StatusCode, String)> {
    let account = service
        .withdraw(&account_id, req.amount, &user_id)
        .map_err(|e| match e {
            BankingError::NotFound => (StatusCode::NOT_FOUND, e.to_string()),
            BankingError::Unauthorized => (StatusCode::FORBIDDEN, e.to_string()),
            BankingError::InvalidOperation(msg) => (StatusCode::BAD_REQUEST, msg),
            _ => (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()),
        })?;

    info!("Withdraw from {}: {}", account_id, req.amount);
    Ok((StatusCode::OK, Json(AccountResponse::from(account))))
}
