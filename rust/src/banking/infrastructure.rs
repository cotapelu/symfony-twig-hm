use std::collections::HashMap;
use std::sync::{Arc, RwLock};
use crate::banking::domain::{Account, AccountRepository};

/// In-memory implementation of AccountRepository for development/testing
#[derive(Clone, Debug)]
pub struct InMemoryBankingRepository {
    accounts: Arc<RwLock<HashMap<String, Account>>>,
}

impl InMemoryBankingRepository {
    pub fn new() -> Self {
        Self {
            accounts: Arc::new(RwLock::new(HashMap::new())),
        }
    }
}

impl AccountRepository for InMemoryBankingRepository {
    fn save(&self, account: &Account) -> Result<(), String> {
        let mut accounts = self
            .accounts
            .write()
            .map_err(|e| format!("Lock error: {}", e))?;
        accounts.insert(account.id.clone(), account.clone());
        Ok(())
    }

    fn find_by_id(&self, id: &str) -> Result<Option<Account>, String> {
        let accounts = self
            .accounts
            .read()
            .map_err(|e| format!("Lock error: {}", e))?;
        Ok(accounts.get(id).cloned())
    }

    fn find_by_user_id(&self, user_id: &str) -> Result<Vec<Account>, String> {
        let accounts = self
            .accounts
            .read()
            .map_err(|e| format!("Lock error: {}", e))?;
        let mut result = Vec::new();
        for acc in accounts.values() {
            if acc.user_id == user_id && acc.is_active {
                result.push(acc.clone());
            }
        }
        Ok(result)
    }

    fn find_by_account_number(&self, number: &str) -> Result<Option<Account>, String> {
        let accounts = self
            .accounts
            .read()
            .map_err(|e| format!("Lock error: {}", e))?;
        Ok(accounts
            .values()
            .find(|acc| acc.account_number == number)
            .cloned())
    }

    fn delete(&self, id: &str) -> Result<bool, String> {
        let mut accounts = self
            .accounts
            .write()
            .map_err(|e| format!("Lock error: {}", e))?;
        Ok(accounts.remove(id).is_some())
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_in_memory_repository_crud() {
        let repo = InMemoryBankingRepository::new();
        let account = Account::new(
            "user123".to_string(),
            crate::banking::domain::AccountType::CHECKING,
            "USD".to_string(),
        );

        // Save
        repo.save(&account).unwrap();

        // Find by ID
        let found = repo.find_by_id(&account.id).unwrap().unwrap();
        assert_eq!(found.id, account.id);

        // Find by user
        let user_accounts = repo.find_by_user_id("user123").unwrap();
        assert_eq!(user_accounts.len(), 1);

        // Find by account number
        let by_number = repo.find_by_account_number(&account.account_number).unwrap().unwrap();
        assert_eq!(by_number.id, account.id);

        // Delete
        let deleted = repo.delete(&account.id).unwrap();
        assert!(deleted);
        let not_found = repo.find_by_id(&account.id).unwrap();
        assert!(not_found.is_none());
    }
}
