use std::collections::HashMap;
use std::sync::{Arc, RwLock};
use crate::banking::domain::{Account, AccountRepository, AccountType};
use crate::banking::application::{BankingService, BankingError};

/// Simple in-memory mock repository for tests
#[derive(Debug, Clone)]
struct MockRepository {
    accounts: Arc<RwLock<HashMap<String, Account>>>,
}

impl MockRepository {
    fn new() -> Self {
        Self {
            accounts: Arc::new(RwLock::new(HashMap::new())),
        }
    }
}

impl AccountRepository for MockRepository {
    fn save(&self, account: &Account) -> Result<(), String> {
        let mut accounts = self.accounts.write().unwrap();
        accounts.insert(account.id.clone(), account.clone());
        Ok(())
    }

    fn find_by_id(&self, id: &str) -> Result<Option<Account>, String> {
        let accounts = self.accounts.read().unwrap();
        Ok(accounts.get(id).cloned())
    }

    fn find_by_user_id(&self, user_id: &str) -> Result<Vec<Account>, String> {
        let accounts = self.accounts.read().unwrap();
        Ok(accounts
            .values()
            .filter(|acc| acc.user_id == user_id && acc.is_active)
            .cloned()
            .collect())
    }

    fn find_by_account_number(&self, number: &str) -> Result<Option<Account>, String> {
        let accounts = self.accounts.read().unwrap();
        Ok(accounts.values().find(|acc| acc.account_number == number).cloned())
    }

    fn delete(&self, id: &str) -> Result<bool, String> {
        let mut accounts = self.accounts.write().unwrap();
        Ok(accounts.remove(id).is_some())
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use chrono::Utc;

    fn create_test_account(user_id: &str, account_type: AccountType, currency: &str) -> Account {
        Account::new(user_id.to_string(), account_type, currency.to_string())
    }

    #[test]
    fn test_create_account_success() {
        let repo = MockRepository::new();
        let service = BankingService::<MockRepository>::new(Arc::new(repo.clone()));

        let result = service.create_account("user1".to_string(), AccountType::CHECKING, "USD".to_string());
        assert!(result.is_ok());
        let account = result.unwrap();

        assert_eq!(account.user_id, "user1");
        assert_eq!(account.account_type, AccountType::CHECKING);
        assert_eq!(account.currency, "USD");
        assert_eq!(account.balance, 0.0);
        assert!(account.is_active);
    }

    #[test]
    fn test_get_account_by_id() {
        let repo = MockRepository::new();
        let service = BankingService::<MockRepository>::new(Arc::new(repo.clone()));

        let account = create_test_account("user2", AccountType::SAVINGS, "XPF");
        // Insert directly via repository
        repo.save(&account).unwrap();

        let found = service.get_account(&account.id, "user2");
        assert!(found.is_ok());
        assert_eq!(found.unwrap().id, account.id);
    }

    #[test]
    fn test_get_account_unauthorized() {
        let repo = MockRepository::new();
        let service = BankingService::<MockRepository>::new(Arc::new(repo.clone()));

        let account = create_test_account("user3", AccountType::BUSINESS, "EUR");
        repo.save(&account).unwrap();

        let result = service.get_account(&account.id, "other_user");
        assert!(result.is_err());
        match result.unwrap_err() {
            BankingError::Unauthorized => {}
            _ => panic!("Expected Unauthorized error"),
        }
    }

    #[test]
    fn test_get_user_accounts() {
        let repo = MockRepository::new();
        let service = BankingService::<MockRepository>::new(Arc::new(repo.clone()));

        let acc1 = create_test_account("user4", AccountType::CHECKING, "USD");
        let acc2 = create_test_account("user4", AccountType::SAVINGS, "USD");
        let acc3 = create_test_account("user5", AccountType::CHECKING, "USD"); // different user
        repo.save(&acc1).unwrap();
        repo.save(&acc2).unwrap();
        repo.save(&acc3).unwrap();

        let accounts = service.get_user_accounts("user4");
        assert!(accounts.is_ok());
        let list = accounts.unwrap();
        assert_eq!(list.len(), 2);
        assert!(list.iter().all(|acc| acc.user_id == "user4"));
    }

    #[test]
    fn test_deposit_positive_amount() {
        let repo = MockRepository::new();
        let service = BankingService::<MockRepository>::new(Arc::new(repo.clone()));

        let mut account = create_test_account("user6", AccountType::CHECKING, "VND");
        repo.save(&account).unwrap();

        let result = service.deposit(&account.id, 1000.0, "user6");
        assert!(result.is_ok());

        let updated = service.get_account(&account.id, "user6").unwrap();
        assert_eq!(updated.balance, 1000.0);
    }

    #[test]
    fn test_deposit_negative_amount() {
        let repo = MockRepository::new();
        let service = BankingService::<MockRepository>::new(Arc::new(repo.clone()));

        let mut account = create_test_account("user7", AccountType::SAVINGS, "JPY");
        repo.save(&account).unwrap();

        let result = service.deposit(&account.id, -50.0, "user7");
        assert!(result.is_err());
        assert!(matches!(result.unwrap_err(), BankingError::InvalidOperation(_)));
    }

    #[test]
    fn test_withdraw_success() {
        let repo = MockRepository::new();
        let service = BankingService::<MockRepository>::new(Arc::new(repo.clone()));

        let mut account = create_test_account("user8", AccountType::CHECKING, "USD");
        account.balance = 200.0;
        repo.save(&account).unwrap();

        let result = service.withdraw(&account.id, 75.0, "user8");
        assert!(result.is_ok());

        let updated = service.get_account(&account.id, "user8").unwrap();
        assert_eq!(updated.balance, 125.0);
    }

    #[test]
    fn test_withdraw_insufficient_funds() {
        let repo = MockRepository::new();
        let service = BankingService::<MockRepository>::new(Arc::new(repo.clone()));

        let mut account = create_test_account("user9", AccountType::CHECKING, "USD");
        account.balance = 50.0;
        repo.save(&account).unwrap();

        let result = service.withdraw(&account.id, 100.0, "user9");
        assert!(result.is_err());
        assert!(matches!(result.unwrap_err(), BankingError::InsufficientFunds));
    }

    #[test]
    fn test_delete_account() {
        let repo = MockRepository::new();
        let service = BankingService::<MockRepository>::new(Arc::new(repo.clone()));

        let account = create_test_account("user10", AccountType::BUSINESS, "EUR");
        repo.save(&account).unwrap();

        let delete_result = service.delete_account(&account.id);
        assert!(delete_result.is_ok());
        assert!(delete_result.unwrap());

        let find_result = service.get_account(&account.id, "user10");
        assert!(find_result.is_err()); // Should be NotFound
    }
}
