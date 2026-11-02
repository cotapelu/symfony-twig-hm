use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

/// Bank account entity
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Account {
    pub id: String,
    pub user_id: String,
    pub account_number: String,
    pub account_type: AccountType,
    pub currency: String,
    pub balance: f64,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

impl Account {
    pub fn new(user_id: String, account_type: AccountType, currency: String) -> Self {
        Self {
            id: Uuid::new_v4().to_string(),
            user_id,
            account_number: Self::generate_account_number(),
            account_type,
            currency,
            balance: 0.0,
            is_active: true,
            created_at: Utc::now(),
            updated_at: Utc::now(),
        }
    }

    fn generate_account_number() -> String {
        format!("PHX-{}", Uuid::new_v4().to_string()[0..8].to_uppercase())
    }

    pub fn deposit(&mut self, amount: f64) -> Result<(), String> {
        if amount <= 0.0 {
            return Err("Deposit amount must be positive".to_string());
        }
        self.balance += amount;
        self.updated_at = Utc::now();
        Ok(())
    }

    pub fn withdraw(&mut self, amount: f64) -> Result<(), String> {
        if amount <= 0.0 {
            return Err("Withdrawal amount must be positive".to_string());
        }
        if amount > self.balance {
            return Err("Insufficient funds".to_string());
        }
        self.balance -= amount;
        self.updated_at = Utc::now();
        Ok(())
    }
}

#[derive(Debug, Clone, Serialize, Deserialize, PartialEq)]
pub enum AccountType {
    CHECKING,
    SAVINGS,
    BUSINESS,
}

/// Repository trait (Domain layer)
pub trait AccountRepository: Send + Sync {
    fn save(&self, account: &Account) -> Result<(), String>;
    fn find_by_id(&self, id: &str) -> Result<Option<Account>, String>;
    fn find_by_user_id(&self, user_id: &str) -> Result<Vec<Account>, String>;
    fn find_by_account_number(&self, number: &str) -> Result<Option<Account>, String>;
    fn delete(&self, id: &str) -> Result<bool, String>;
}
