use std::sync::Arc;
use crate::banking::{BankingService, InMemoryBankingRepository};

#[derive(Clone)]
pub struct AppState {
    pub banking_service: Arc<BankingService<InMemoryBankingRepository>>,
    // Add more services as they are implemented
}

impl AppState {
    pub fn new() -> Self {
        let repo = Arc::new(InMemoryBankingRepository::new());
        let banking_service = Arc::new(BankingService::new(repo));
        Self { banking_service }
    }
}
