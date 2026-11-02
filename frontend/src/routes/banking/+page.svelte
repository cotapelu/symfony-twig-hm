<script lang="ts">
  import { onMount } from 'svelte';
  import { fade, slide } from 'svelte/transition';

  // Types
  interface BankAccount {
    id: string;
    name: string;
    type: 'checking' | 'savings' | 'credit';
    balance: number;
    accountNumber: string;
    currency: string;
  }

  interface Transaction {
    id: string;
    type: 'deposit' | 'withdrawal' | 'transfer' | 'payment' | 'bill';
    amount: number;
    date: string;
    description: string;
    category: string;
    status: 'completed' | 'pending' | 'failed';
    fromAccount?: string;
    toAccount?: string;
  }

  interface Bill {
    id: string;
    name: string;
    amount: number;
    dueDate: string;
    category: string;
    autoPay: boolean;
    logo: string;
  }

  // State
  let loading = true;
  let activeTab = 'overview';
  let accounts: BankAccount[] = [];
  let transactions: Transaction[] = [];
  let bills: Bill[] = [];
  let selectedAccount: BankAccount | null = null;
  
  // Filter state
  let filterType = 'all';
  let filterDateRange = 'all';
  let searchQuery = '';
  
  // Transfer form state
  let showTransferModal = false;
  let transferForm = {
    fromAccount: '',
    toAccount: '',
    amount: '',
    description: ''
  };
  let transferError = '';
  let transferSuccess = false;
  
  // Bill payment state
  let showBillModal = false;
  let selectedBill: Bill | null = null;
  let paymentAmount = '';
  
  // Account detail modal
  let showAccountDetail = false;

  // localStorage keys
  const STORAGE_KEYS = {
    accounts: 'banking_accounts',
    transactions: 'banking_transactions',
    bills: 'banking_bills'
  };

  onMount(() => {
    loadData();
  });

  function loadData() {
    loading = true;
    
    // Load from localStorage or initialize with defaults
    const storedAccounts = localStorage.getItem(STORAGE_KEYS.accounts);
    const storedTransactions = localStorage.getItem(STORAGE_KEYS.transactions);
    const storedBills = localStorage.getItem(STORAGE_KEYS.bills);

    if (storedAccounts) {
      accounts = JSON.parse(storedAccounts);
    } else {
      // Initialize with sample accounts
      accounts = [
        {
          id: 'acc_001',
          name: 'Primary Checking',
          type: 'checking',
          balance: 15450000,
          accountNumber: '****4521',
          currency: 'VND'
        },
        {
          id: 'acc_002',
          name: 'Savings Account',
          type: 'savings',
          balance: 52000000,
          accountNumber: '****7832',
          currency: 'VND'
        },
        {
          id: 'acc_003',
          name: 'Credit Card',
          type: 'credit',
          balance: -2500000,
          accountNumber: '****9012',
          currency: 'VND'
        }
      ];
      saveData();
    }

    if (storedTransactions) {
      transactions = JSON.parse(storedTransactions);
    } else {
      // Initialize with sample transactions
      transactions = [
        { id: 'tx_001', type: 'deposit', amount: 5000000, date: '2026-02-12', description: 'Salary Deposit', category: 'Income', status: 'completed' },
        { id: 'tx_002', type: 'transfer', amount: -2000000, date: '2026-02-11', description: 'Transfer to Mom', category: 'Transfer', status: 'completed', toAccount: '****7890' },
        { id: 'tx_003', type: 'bill', amount: -450000, date: '2026-02-10', description: 'Electricity Bill', category: 'Utilities', status: 'completed' },
        { id: 'tx_004', type: 'payment', amount: -850000, date: '2026-02-09', description: 'Grocery Store', category: 'Shopping', status: 'completed' },
        { id: 'tx_005', type: 'withdrawal', amount: -1000000, date: '2026-02-08', description: 'ATM Withdrawal', category: 'Cash', status: 'completed' },
        { id: 'tx_006', type: 'bill', amount: -320000, date: '2026-02-07', description: 'Internet Bill', category: 'Utilities', status: 'completed' },
        { id: 'tx_007', type: 'transfer', amount: -500000, date: '2026-02-06', description: 'Transfer to Friend', category: 'Transfer', status: 'completed', toAccount: '****3456' },
        { id: 'tx_008', type: 'deposit', amount: 1000000, date: '2026-02-05', description: 'Freelance Payment', category: 'Income', status: 'completed' },
        { id: 'tx_009', type: 'bill', amount: -650000, date: '2026-02-04', description: 'Water Bill', category: 'Utilities', status: 'completed' },
        { id: 'tx_010', type: 'payment', amount: -1500000, date: '2026-02-03', description: 'Gas Station', category: 'Transport', status: 'completed' },
        { id: 'tx_011', type: 'deposit', amount: 3000000, date: '2026-02-02', description: 'Refund', category: 'Income', status: 'completed' },
        { id: 'tx_012', type: 'bill', amount: -280000, date: '2026-02-01', description: 'Phone Bill', category: 'Utilities', status: 'completed' }
      ];
      saveData();
    }

    if (storedBills) {
      bills = JSON.parse(storedBills);
    } else {
      // Initialize with sample bills
      bills = [
        { id: 'bill_001', name: 'Electricity', amount: 450000, dueDate: '2026-02-20', category: 'Utilities', autoPay: true, logo: '⚡' },
        { id: 'bill_002', name: 'Internet', amount: 320000, dueDate: '2026-02-25', category: 'Utilities', autoPay: true, logo: '📶' },
        { id: 'bill_003', name: 'Water', amount: 280000, dueDate: '2026-02-18', category: 'Utilities', autoPay: false, logo: '💧' },
        { id: 'bill_004', name: 'Phone', amount: 350000, dueDate: '2026-02-28', category: 'Telecom', autoPay: true, logo: '📱' },
        { id: 'bill_005', name: 'Insurance', amount: 1200000, dueDate: '2026-03-01', category: 'Insurance', autoPay: false, logo: '🛡️' }
      ];
      saveData();
    }

    selectedAccount = accounts[0];
    loading = false;
  }

  function saveData() {
    localStorage.setItem(STORAGE_KEYS.accounts, JSON.stringify(accounts));
    localStorage.setItem(STORAGE_KEYS.transactions, JSON.stringify(transactions));
    localStorage.setItem(STORAGE_KEYS.bills, JSON.stringify(bills));
  }

  // Computed values
  $: totalBalance = accounts.reduce((sum, acc) => sum + acc.balance, 0);
  $: totalBillsDue = bills.reduce((sum, bill) => sum + bill.amount, 0);
  $: upcomingBills = bills.filter(b => new Date(b.dueDate) <= new Date(Date.now() + 7 * 24 * 60 * 60 * 1000));

  $: filteredTransactions = transactions.filter(tx => {
    // Type filter
    if (filterType !== 'all' && tx.type !== filterType) return false;
    
    // Date filter
    if (filterDateRange !== 'all') {
      const txDate = new Date(tx.date);
      const now = new Date();
      if (filterDateRange === 'week') {
        const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
        if (txDate < weekAgo) return false;
      } else if (filterDateRange === 'month') {
        const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
        if (txDate < monthAgo) return false;
      } else if (filterDateRange === '3months') {
        const threeMonthsAgo = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000);
        if (txDate < threeMonthsAgo) return false;
      }
    }
    
    // Search filter
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      return tx.description.toLowerCase().includes(query) || 
             tx.category.toLowerCase().includes(query);
    }
    
    return true;
  });

  // Helper functions
  function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
      minimumFractionDigits: 0
    }).format(Math.abs(amount));
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function getAccountTypeIcon(type: string): string {
    switch (type) {
      case 'checking': return '💳';
      case 'savings': return '🐷';
      case 'credit': return '💰';
      default: return '🏦';
    }
  }

  function getTransactionIcon(type: string): string {
    switch (type) {
      case 'deposit': return '📥';
      case 'withdrawal': return '📤';
      case 'transfer': return '🔄';
      case 'payment': return '💳';
      case 'bill': return '📄';
      default: return '💵';
    }
  }

  function getDaysUntilDue(dateStr: string): number {
    const due = new Date(dateStr);
    const now = new Date();
    return Math.ceil((due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
  }

  function getDueDateClass(dateStr: string): string {
    const days = getDaysUntilDue(dateStr);
    if (days <= 3) return 'due-soon';
    if (days <= 7) return 'due-week';
    return 'due-later';
  }

  // Transfer handlers
  function openTransferModal() {
    transferForm = {
      fromAccount: selectedAccount?.id || accounts[0]?.id || '',
      toAccount: '',
      amount: '',
      description: ''
    };
    transferError = '';
    transferSuccess = false;
    showTransferModal = true;
  }

  function closeTransferModal() {
    showTransferModal = false;
  }

  function submitTransfer() {
    transferError = '';
    
    const amount = parseFloat(transferForm.amount);
    const fromAcc = accounts.find(a => a.id === transferForm.fromAccount);
    
    if (!fromAcc) {
      transferError = 'Please select a source account';
      return;
    }
    
    if (!transferForm.toAccount || transferForm.toAccount.length < 8) {
      transferError = 'Please enter a valid recipient account number';
      return;
    }
    
    if (isNaN(amount) || amount <= 0) {
      transferError = 'Please enter a valid amount';
      return;
    }
    
    if (amount > fromAcc.balance) {
      transferError = 'Insufficient funds';
      return;
    }
    
    // Process transfer
    fromAcc.balance -= amount;
    
    // Add transaction
    const newTx: Transaction = {
      id: `tx_${Date.now()}`,
      type: 'transfer',
      amount: -amount,
      date: new Date().toISOString().split('T')[0],
      description: transferForm.description || 'Transfer',
      category: 'Transfer',
      status: 'completed',
      toAccount: transferForm.toAccount
    };
    
    transactions = [newTx, ...transactions];
    saveData();
    
    transferSuccess = true;
    
    setTimeout(() => {
      closeTransferModal();
    }, 1500);
  }

  // Bill payment handlers
  function openBillModal(bill: Bill) {
    selectedBill = bill;
    paymentAmount = bill.amount.toString();
    showBillModal = true;
  }

  function closeBillModal() {
    showBillModal = false;
    selectedBill = null;
  }

  function payBill() {
    if (!selectedBill) return;
    
    const amount = parseFloat(paymentAmount);
    if (isNaN(amount) || amount <= 0) return;
    
    const fromAcc = selectedAccount || accounts[0];
    if (!fromAcc) return;
    
    // Deduct from account
    fromAcc.balance -= amount;
    
    // Add transaction
    const newTx: Transaction = {
      id: `tx_${Date.now()}`,
      type: 'bill',
      amount: -amount,
      date: new Date().toISOString().split('T')[0],
      description: `${selectedBill.name} Payment`,
      category: 'Utilities',
      status: 'completed'
    };
    
    transactions = [newTx, ...transactions];
    
    // Remove paid bill or update
    bills = bills.filter(b => b.id !== selectedBill!.id);
    
    saveData();
    closeBillModal();
  }

  // Account detail handlers
  function viewAccountDetails(account: BankAccount) {
    selectedAccount = account;
    showAccountDetail = true;
  }

  function closeAccountDetail() {
    showAccountDetail = false;
  }
</script>

<svelte:head>
  <title>Banking - Hotel Booking App</title>
</svelte:head>

<div class="banking-page">
  <!-- Header -->
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <h1>🏦 My Banking</h1>
        <p>Manage your accounts and transactions</p>
      </div>
      <div class="header-actions">
        <button class="btn-primary" on:click={openTransferModal}>
          💸 Transfer
        </button>
        <button class="btn-secondary" on:click={() => activeTab = 'bills'}>
          📄 Pay Bills
        </button>
      </div>
    </div>
  </header>

  <!-- Tabs -->
  <nav class="tabs">
    <button 
      class="tab" 
      class:active={activeTab === 'overview'} 
      on:click={() => activeTab = 'overview'}
    >
      Overview
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'transactions'} 
      on:click={() => activeTab = 'transactions'}
    >
      Transactions
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'accounts'} 
      on:click={() => activeTab = 'accounts'}
    >
      Accounts
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'bills'} 
      on:click={() => activeTab = 'bills'}
    >
      Bills
    </button>
  </nav>

  <main class="main-content">
    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Loading your accounts...</p>
      </div>
    {:else}
      <!-- Overview Tab -->
      {#if activeTab === 'overview'}
        <div class="overview" in:fade>
          <!-- Total Balance Card -->
          <div class="total-balance-card">
            <div class="balance-header">
              <span class="balance-label">Total Balance</span>
              <span class="balance-icon">💰</span>
            </div>
            <div class="balance-amount">{formatCurrency(totalBalance)}</div>
            <div class="balance-breakdown">
              <span>Across {accounts.length} accounts</span>
            </div>
          </div>

          <!-- Quick Stats -->
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon">📊</div>
              <div class="stat-value">{accounts.length}</div>
              <div class="stat-label">Active Accounts</div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">📄</div>
              <div class="stat-value">{bills.length}</div>
              <div class="stat-label">Pending Bills</div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">⚠️</div>
              <div class="stat-value">{upcomingBills.length}</div>
              <div class="stat-label">Due This Week</div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">💵</div>
              <div class="stat-value">{formatCurrency(totalBillsDue)}</div>
              <div class="stat-label">Total Due</div>
            </div>
          </div>

          <!-- Accounts Preview -->
          <section class="section">
            <div class="section-header">
              <h3>My Accounts</h3>
              <button class="view-all" on:click={() => activeTab = 'accounts'}>View All</button>
            </div>
            <div class="accounts-grid">
              {#each accounts.slice(0, 3) as account}
                <div class="account-card" on:click={() => viewAccountDetails(account)}>
                  <div class="account-icon">{getAccountTypeIcon(account.type)}</div>
                  <div class="account-info">
                    <div class="account-name">{account.name}</div>
                    <div class="account-number">{account.accountNumber}</div>
                  </div>
                  <div class="account-balance" class:negative={account.balance < 0}>
                    {formatCurrency(account.balance)}
                  </div>
                </div>
              {/each}
            </div>
          </section>

          <!-- Recent Transactions -->
          <section class="section">
            <div class="section-header">
              <h3>Recent Transactions</h3>
              <button class="view-all" on:click={() => activeTab = 'transactions'}>View All</button>
            </div>
            <div class="transactions-list">
              {#each transactions.slice(0, 5) as tx}
                <div class="transaction-item">
                  <div class="tx-icon">{getTransactionIcon(tx.type)}</div>
                  <div class="tx-info">
                    <div class="tx-description">{tx.description}</div>
                    <div class="tx-category">{tx.category} • {formatDate(tx.date)}</div>
                  </div>
                  <div class="tx-amount" class:positive={tx.amount > 0} class:negative={tx.amount < 0}>
                    {tx.amount > 0 ? '+' : ''}{formatCurrency(tx.amount)}
                  </div>
                </div>
              {/each}
            </div>
          </section>

          <!-- Upcoming Bills -->
          {#if upcomingBills.length > 0}
            <section class="section">
              <div class="section-header">
                <h3>Upcoming Bills</h3>
                <button class="view-all" on:click={() => activeTab = 'bills'}>View All</button>
              </div>
              <div class="bills-list">
                {#each upcomingBills.slice(0, 3) as bill}
                  <div class="bill-item" on:click={() => openBillModal(bill)}>
                    <div class="bill-logo">{bill.logo}</div>
                    <div class="bill-info">
                      <div class="bill-name">{bill.name}</div>
                      <div class="bill-due {getDueDateClass(bill.dueDate)}">
                        Due in {getDaysUntilDue(bill.dueDate)} days
                      </div>
                    </div>
                    <div class="bill-amount">{formatCurrency(bill.amount)}</div>
                  </div>
                {/each}
              </div>
            </section>
          {/if}
        </div>
      {/if}

      <!-- Transactions Tab -->
      {#if activeTab === 'transactions'}
        <div class="transactions-page" in:fade>
          <div class="filters-bar">
            <div class="search-box">
              <input 
                type="text" 
                placeholder="Search transactions..." 
                bind:value={searchQuery}
              />
            </div>
            <div class="filter-group">
              <select bind:value={filterType}>
                <option value="all">All Types</option>
                <option value="deposit">Deposits</option>
                <option value="withdrawal">Withdrawals</option>
                <option value="transfer">Transfers</option>
                <option value="payment">Payments</option>
                <option value="bill">Bills</option>
              </select>
              <select bind:value={filterDateRange}>
                <option value="all">All Time</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="3months">Last 3 Months</option>
              </select>
            </div>
          </div>

          <div class="transactions-full-list">
            {#if filteredTransactions.length === 0}
              <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3>No transactions found</h3>
                <p>Try adjusting your filters</p>
              </div>
            {:else}
              {#each filteredTransactions as tx}
                <div class="transaction-row">
                  <div class="tx-icon">{getTransactionIcon(tx.type)}</div>
                  <div class="tx-details">
                    <div class="tx-description">{tx.description}</div>
                    <div class="tx-meta">
                      <span class="tx-category">{tx.category}</span>
                      <span class="tx-date">{formatDate(tx.date)}</span>
                      {#if tx.toAccount}
                        <span class="tx-to">→ {tx.toAccount}</span>
                      {/if}
                    </div>
                  </div>
                  <div class="tx-amount-wrapper">
                    <div class="tx-amount" class:positive={tx.amount > 0} class:negative={tx.amount < 0}>
                      {tx.amount > 0 ? '+' : ''}{formatCurrency(tx.amount)}
                    </div>
                    <span class="tx-status {tx.status}">{tx.status}</span>
                  </div>
                </div>
              {/each}
            {/if}
          </div>
        </div>
      {/if}

      <!-- Accounts Tab -->
      {#if activeTab === 'accounts'}
        <div class="accounts-page" in:fade>
          <div class="accounts-full-grid">
            {#each accounts as account}
              <div class="account-detail-card" on:click={() => viewAccountDetails(account)}>
                <div class="account-header">
                  <div class="account-icon-large">{getAccountTypeIcon(account.type)}</div>
                  <div class="account-type-badge">{account.type}</div>
                </div>
                <div class="account-name-large">{account.name}</div>
                <div class="account-number-full">{account.accountNumber}</div>
                <div class="account-balance-large" class:negative={account.balance < 0}>
                  {formatCurrency(account.balance)}
                </div>
                <div class="account-actions">
                  <button class="action-btn" on:click|stopPropagation={() => {
                    selectedAccount = account;
                    openTransferModal();
                  }}>
                    Transfer
                  </button>
                </div>
              </div>
            {/each}
          </div>
        </div>
      {/if}

      <!-- Bills Tab -->
      {#if activeTab === 'bills'}
        <div class="bills-page" in:fade>
          <div class="bills-summary">
            <div class="summary-card">
              <div class="summary-label">Total Bills Due</div>
              <div class="summary-value">{formatCurrency(totalBillsDue)}</div>
            </div>
            <div class="summary-card">
              <div class="summary-label">Due This Week</div>
              <div class="summary-value warning">{upcomingBills.length}</div>
            </div>
          </div>

          <div class="bills-full-list">
            {#each bills as bill}
              <div class="bill-card">
                <div class="bill-icon">{bill.logo}</div>
                <div class="bill-details">
                  <div class="bill-name-large">{bill.name}</div>
                  <div class="bill-meta">
                    <span class="bill-category">{bill.category}</span>
                    <span class="bill-due-date {getDueDateClass(bill.dueDate)}">
                      Due: {formatDate(bill.dueDate)}
                    </span>
                  </div>
                  <div class="bill-autopay">
                    {#if bill.autoPay}
                      <span class="autopay-badge">✓ Auto-pay enabled</span>
                    {/if}
                  </div>
                </div>
                <div class="bill-right">
                  <div class="bill-amount-large">{formatCurrency(bill.amount)}</div>
                  <button class="pay-btn" on:click={() => openBillModal(bill)}>
                    Pay Now
                  </button>
                </div>
              </div>
            {/each}

            {#if bills.length === 0}
              <div class="empty-state">
                <div class="empty-icon">✅</div>
                <h3>All bills paid!</h3>
                <p>You have no pending bills</p>
              </div>
            {/if}
          </div>
        </div>
      {/if}
    {/if}
  </main>
</div>

<!-- Transfer Modal -->
{#if showTransferModal}
  <div class="modal-overlay" on:click={closeTransferModal}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>💸 Transfer Money</h2>
        <button class="close-btn" on:click={closeTransferModal}>×</button>
      </div>
      
      {#if transferSuccess}
        <div class="success-message">
          <div class="success-icon">✅</div>
          <p>Transfer completed successfully!</p>
        </div>
      {:else}
        <div class="modal-body">
          <div class="form-group">
            <label>From Account</label>
            <select bind:value={transferForm.fromAccount}>
              {#each accounts as account}
                <option value={account.id}>
                  {account.name} - {formatCurrency(account.balance)}
                </option>
              {/each}
            </select>
          </div>
          
          <div class="form-group">
            <label>To Account Number</label>
            <input 
              type="text" 
              bind:value={transferForm.toAccount}
              placeholder="Enter account number"
            />
          </div>
          
          <div class="form-group">
            <label>Amount (VND)</label>
            <input 
              type="number" 
              bind:value={transferForm.amount}
              placeholder="Enter amount"
              min="10000"
            />
          </div>
          
          <div class="form-group">
            <label>Description (Optional)</label>
            <input 
              type="text" 
              bind:value={transferForm.description}
              placeholder="What's this for?"
            />
          </div>
          
          {#if transferError}
            <div class="error-message">{transferError}</div>
          {/if}
        </div>
        
        <div class="modal-footer">
          <button class="btn-cancel" on:click={closeTransferModal}>Cancel</button>
          <button class="btn-confirm" on:click={submitTransfer}>Transfer</button>
        </div>
      {/if}
    </div>
  </div>
{/if}

<!-- Bill Payment Modal -->
{#if showBillModal && selectedBill}
  <div class="modal-overlay" on:click={closeBillModal}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>📄 Pay {selectedBill.name}</h2>
        <button class="close-btn" on:click={closeBillModal}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="bill-preview">
          <div class="bill-logo-large">{selectedBill.logo}</div>
          <div class="bill-info">
            <div class="bill-name">{selectedBill.name}</div>
            <div class="bill-due-date">Due: {formatDate(selectedBill.dueDate)}</div>
          </div>
        </div>
        
        <div class="form-group">
          <label>Payment Amount</label>
          <input 
            type="number" 
            bind:value={paymentAmount}
            min="1000"
          />
        </div>
        
        <div class="form-group">
          <label>Pay From</label>
          <select bind:value={selectedAccount}>
            {#each accounts.filter(a => a.balance > 0) as account}
              <option value={account}>
                {account.name} - {formatCurrency(account.balance)}
              </option>
            {/each}
          </select>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn-cancel" on:click={closeBillModal}>Cancel</button>
        <button class="btn-confirm" on:click={payBill}>Pay Now</button>
      </div>
    </div>
  </div>
{/if}

<!-- Account Detail Modal -->
{#if showAccountDetail && selectedAccount}
  <div class="modal-overlay" on:click={closeAccountDetail}>
    <div class="modal modal-large" on:click|stopPropagation>
      <div class="modal-header">
        <h2>{getAccountTypeIcon(selectedAccount.type)} {selectedAccount.name}</h2>
        <button class="close-btn" on:click={closeAccountDetail}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="account-detail-balance">
          {formatCurrency(selectedAccount.balance)}
        </div>
        
        <div class="account-detail-info">
          <div class="detail-row">
            <span class="detail-label">Account Number</span>
            <span class="detail-value">{selectedAccount.accountNumber}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Account Type</span>
            <span class="detail-value capitalize">{selectedAccount.type}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Currency</span>
            <span class="detail-value">{selectedAccount.currency}</span>
          </div>
        </div>
        
        <div class="account-transactions">
          <h3>Recent Activity</h3>
          {#each transactions.filter(tx => tx.amount < 0).slice(0, 5) as tx}
            <div class="mini-tx">
              <span class="mini-tx-desc">{tx.description}</span>
              <span class="mini-tx-amount negative">{formatCurrency(tx.amount)}</span>
            </div>
          {/each}
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn-primary" on:click={() => {
          closeAccountDetail();
          openTransferModal();
        }}>Transfer</button>
        <button class="btn-secondary" on:click={closeAccountDetail}>Close</button>
      </div>
    </div>
  </div>
{/if}

<style>
  /* Base Styles */
  .banking-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  .header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    color: white;
    padding: 1.5rem 2rem;
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .header-left h1 {
    margin: 0;
    font-size: 1.75rem;
  }

  .header-left p {
    margin: 0.25rem 0 0;
    opacity: 0.85;
    font-size: 0.9rem;
  }

  .header-actions {
    display: flex;
    gap: 0.75rem;
  }

  .btn-primary {
    padding: 0.625rem 1.25rem;
    background: white;
    color: #1e3a5f;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }

  .btn-secondary {
    padding: 0.625rem 1.25rem;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
  }

  .btn-secondary:hover {
    background: rgba(255, 255, 255, 0.3);
  }

  /* Tabs */
  .tabs {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 2rem;
    display: flex;
    gap: 0.5rem;
    max-width: 1200px;
    margin: 0 auto;
  }

  .tab {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 0.95rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
  }

  .tab:hover {
    color: #1e3a5f;
  }

  .tab.active {
    color: #1e3a5f;
    border-bottom-color: #1e3a5f;
  }

  /* Main Content */
  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  /* Loading */
  .loading {
    text-align: center;
    padding: 4rem;
  }

  .spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #e2e8f0;
    border-top-color: #1e3a5f;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Overview Tab */
  .total-balance-card {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    color: white;
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 1.5rem;
  }

  .balance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .balance-label {
    font-size: 0.95rem;
    opacity: 0.9;
  }

  .balance-icon {
    font-size: 1.5rem;
  }

  .balance-amount {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .balance-breakdown {
    font-size: 0.875rem;
    opacity: 0.8;
  }

  /* Stats Grid */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .stat-card {
    background: white;
    padding: 1.25rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    text-align: center;
  }

  .stat-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }

  .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }

  .stat-label {
    font-size: 0.8rem;
    color: #64748b;
  }

  /* Sections */
  .section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .section-header h3 {
    margin: 0;
    color: #1e293b;
  }

  .view-all {
    background: none;
    border: none;
    color: #667eea;
    font-size: 0.875rem;
    cursor: pointer;
  }

  .view-all:hover {
    text-decoration: underline;
  }

  /* Account Cards */
  .accounts-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .account-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .account-card:hover {
    border-color: #667eea;
    background: #f8fafc;
  }

  .account-icon {
    font-size: 1.75rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 10px;
  }

  .account-info {
    flex: 1;
  }

  .account-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.125rem;
  }

  .account-number {
    font-size: 0.8rem;
    color: #64748b;
  }

  .account-balance {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1e293b;
  }

  .account-balance.negative {
    color: #ef4444;
  }

  /* Transactions List */
  .transactions-list {
    display: flex;
    flex-direction: column;
  }

  .transaction-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 0;
    border-bottom: 1px solid #f1f5f9;
  }

  .transaction-item:last-child {
    border-bottom: none;
  }

  .tx-icon {
    font-size: 1.25rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border-radius: 8px;
  }

  .tx-info {
    flex: 1;
  }

  .tx-description {
    font-weight: 500;
    color: #1e293b;
    margin-bottom: 0.125rem;
  }

  .tx-category {
    font-size: 0.8rem;
    color: #64748b;
  }

  .tx-amount {
    font-weight: 600;
    font-size: 1rem;
  }

  .positive { color: #059669; }
  .negative { color: #dc2626; }

  /* Bills List */
  .bills-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .bill-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .bill-item:hover {
    border-color: #667eea;
    background: #f8fafc;
  }

  .bill-logo {
    font-size: 1.5rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 10px;
  }

  .bill-info {
    flex: 1;
  }

  .bill-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.125rem;
  }

  .bill-due {
    font-size: 0.8rem;
    color: #64748b;
  }

  .due-soon {
    color: #dc2626;
    font-weight: 600;
  }

  .due-week {
    color: #f59e0b;
    font-weight: 500;
  }

  .due-later {
    color: #059669;
  }

  .bill-amount {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1e293b;
  }

  /* Transactions Page */
  .filters-bar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  .search-box input {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    width: 250px;
    font-size: 0.95rem;
  }

  .search-box input:focus {
    outline: none;
    border-color: #667eea;
  }

  .filter-group {
    display: flex;
    gap: 0.75rem;
  }

  .filter-group select {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    background: white;
    cursor: pointer;
  }

  .transactions-full-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .transaction-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s;
  }

  .transaction-row:hover {
    background: #f8fafc;
  }

  .transaction-row:last-child {
    border-bottom: none;
  }

  .tx-details {
    flex: 1;
  }

  .tx-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.25rem;
  }

  .tx-to {
    color: #667eea;
  }

  .tx-amount-wrapper {
    text-align: right;
  }

  .tx-status {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    margin-top: 0.25rem;
  }

  .tx-status.completed {
    color: #059669;
  }

  .tx-status.pending {
    color: #f59e0b;
  }

  .tx-status.failed {
    color: #dc2626;
  }

  /* Accounts Page */
  .accounts-full-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }

  .account-detail-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.2s;
  }

  .account-detail-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  }

  .account-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
  }

  .account-icon-large {
    font-size: 2.5rem;
  }

  .account-type-badge {
    padding: 0.25rem 0.75rem;
    background: #f1f5f9;
    border-radius: 20px;
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 600;
    color: #64748b;
  }

  .account-name-large {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }

  .account-number-full {
    font-size: 0.9rem;
    color: #64748b;
    margin-bottom: 1rem;
  }

  .account-balance-large {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1rem;
  }

  .account-balance-large.negative {
    color: #dc2626;
  }

  .account-actions {
    display: flex;
    gap: 0.5rem;
  }

  .action-btn {
    flex: 1;
    padding: 0.625rem;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
  }

  .action-btn:hover {
    background: #667eea;
    color: white;
  }

  /* Bills Page */
  .bills-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .summary-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .summary-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.5rem;
  }

  .summary-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
  }

  .summary-value.warning {
    color: #dc2626;
  }

  .bills-full-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .bill-card {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .bill-icon {
    font-size: 2rem;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 12px;
  }

  .bill-details {
    flex: 1;
  }

  .bill-name-large {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.375rem;
  }

  .bill-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 0.375rem;
  }

  .bill-due-date {
    font-weight: 500;
  }

  .bill-autopay {
    margin-top: 0.25rem;
  }

  .autopay-badge {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    background: #d1fae5;
    color: #065f46;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 500;
  }

  .bill-right {
    text-align: right;
  }

  .bill-amount-large {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.75rem;
  }

  .pay-btn {
    padding: 0.625rem 1.25rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .pay-btn:hover {
    transform: translateY(-1px);
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 3rem;
  }

  .empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    color: #1e293b;
    margin: 0 0 0.5rem;
  }

  .empty-state p {
    color: #64748b;
    margin: 0;
  }

  /* Modal */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.2s;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  .modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 420px;
    max-height: 90vh;
    overflow: auto;
    animation: slideUp 0.3s;
  }

  .modal-large {
    max-width: 500px;
  }

  @keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #64748b;
    cursor: pointer;
    padding: 0;
    line-height: 1;
  }

  .close-btn:hover {
    color: #1e293b;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .form-group input,
  .form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    box-sizing: border-box;
  }

  .form-group input:focus,
  .form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .error-message {
    padding: 0.75rem;
    background: #fee2e2;
    color: #dc2626;
    border-radius: 8px;
    font-size: 0.875rem;
  }

  .success-message {
    text-align: center;
    padding: 2rem;
  }

  .success-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }

  .modal-footer {
    display: flex;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .btn-cancel {
    flex: 1;
    padding: 0.75rem;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
  }

  .btn-cancel:hover {
    background: #e2e8f0;
  }

  .btn-confirm {
    flex: 1;
    padding: 0.75rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: white;
    cursor: pointer;
  }

  .btn-confirm:hover {
    opacity: 0.9;
  }

  /* Bill Preview */
  .bill-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 10px;
    margin-bottom: 1.5rem;
  }

  .bill-logo-large {
    font-size: 2rem;
  }

  .bill-info .bill-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }

  .bill-info .bill-due-date {
    font-size: 0.875rem;
    color: #64748b;
  }

  /* Account Detail */
  .account-detail-balance {
    text-align: center;
    font-size: 2rem;
    font-weight: 700;
    color: #1e3a5f;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 12px;
    margin-bottom: 1.5rem;
  }

  .account-detail-info {
    margin-bottom: 1.5rem;
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
  }

  .detail-label {
    color: #64748b;
    font-size: 0.9rem;
  }

  .detail-value {
    font-weight: 500;
    color: #1e293b;
  }

  .capitalize {
    text-transform: capitalize;
  }

  .account-transactions h3 {
    font-size: 1rem;
    color: #1e293b;
    margin: 0 0 1rem;
  }

  .mini-tx {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.875rem;
  }

  .mini-tx-desc {
    color: #475569;
  }

  .mini-tx-amount {
    font-weight: 500;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .header-content {
      flex-direction: column;
      gap: 1rem;
      align-items: flex-start;
    }

    .tabs {
      overflow-x: auto;
      padding: 0 1rem;
    }

    .main-content {
      padding: 1rem;
    }

    .search-box input {
      width: 100%;
    }

    .filters-bar {
      flex-direction: column;
    }

    .filter-group {
      width: 100%;
    }

    .filter-group select {
      flex: 1;
    }

    .bill-card {
      flex-direction: column;
      text-align: center;
    }

    .bill-right {
      text-align: center;
      width: 100%;
    }

    .bill-meta {
      flex-direction: column;
      gap: 0.25rem;
    }

    .modal {
      margin: 1rem;
      max-width: calc(100% - 2rem);
    }
  }
</style>
