use crate::banking::domain::{Account, AccountRepository, AccountType};
use std::sync::Arc;
use thiserror::Error;

#[derive(Error, Debug)]
pub enum BankingError {
    #[error("Account not found")]
    NotFound,
    #[error("Access denied: not account owner")]
    Unauthorized,
    #[error("Invalid operation: {0}")]
    InvalidOperation(String),
    #[error("Repository error: {0}")]
    RepositoryError(String),
    #[error("Insufficient funds")]
    InsufficientFunds,
}

pub struct BankingService<R: AccountRepository> {
    repository: Arc<R>,
}

impl<R: AccountRepository + Send + Sync + 'static> BankingService<R> {
    pub fn new(repository: Arc<R>) -> Self {
        Self { repository }
    }

    pub fn create_account(
        &self,
        user_id: String,
        account_type: AccountType,
        currency: String,
    ) -> Result<Account, BankingError> {
        let account = Account::new(user_id.clone(), account_type, currency);
        self.repository
            .save(&account)
            .map_err(|e| BankingError::RepositoryError(e))?;
        Ok(account)
    }

    pub fn get_account(&self, id: &str, requesting_user_id: &str) -> Result<Account, BankingError> {
        let account = self
            .repository
            .find_by_id(id)
            .map_err(|e| BankingError::RepositoryError(e))?
            .ok_or(BankingError::NotFound)?;

        if account.user_id != requesting_user_id {
            return Err(BankingError::Unauthorized);
        }

        Ok(account)
    }

    pub fn get_user_accounts(&self, user_id: &str) -> Result<Vec<Account>, BankingError> {
        self.repository
            .find_by_user_id(user_id)
            .map_err(|e| BankingError::RepositoryError(e))
    }

    pub fn deposit(
        &self,
        account_id: &str,
        amount: f64,
        requesting_user_id: &str,
    ) -> Result<Account, BankingError> {
        let mut account = self.get_account(account_id, requesting_user_id)?;
        account
            .deposit(amount)
            .map_err(|e| BankingError::InvalidOperation(e))?;
        self.repository
            .save(&account)
            .map_err(|e| BankingError::RepositoryError(e))?;
        Ok(account)
    }

    pub fn withdraw(
        &self,
        account_id: &str,
        amount: f64,
        requesting_user_id: &str,
    ) -> Result<Account, BankingError> {
        let mut account = self.get_account(account_id, requesting_user_id)?;
        account
            .withdraw(amount)
            .map_err(|e| {
                if e == "Insufficient funds" {
                    BankingError::InsufficientFunds
                } else {
                    BankingError::InvalidOperation(e)
                }
            })?;
        self.repository
            .save(&account)
            .map_err(|e| BankingError::RepositoryError(e))?;
        Ok(account)
    }

    pub fn get_balance(&self, account_id: &str, requesting_user_id: &str) -> Result<f64, BankingError> {
        let account = self.get_account(account_id, requesting_user_id)?;
        Ok(account.balance)
    }

    pub fn delete_account(&self, id: &str) -> Result<bool, BankingError> {
        self.repository
            .delete(id)
            .map_err(|e| BankingError::RepositoryError(e))
    }
}
