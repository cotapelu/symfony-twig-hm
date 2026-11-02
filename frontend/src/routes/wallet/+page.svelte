<script lang="ts">
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';
  import { walletService, type Wallet, type WalletTransaction } from '$lib/services/wallet';
  import { userStore } from '$lib/stores';

  interface Transaction {
    id: string;
    type: 'topup' | 'transfer' | 'payment' | 'withdraw' | 'refund';
    amount: number;
    status: 'completed' | 'pending' | 'failed';
    recipient?: string;
    description: string;
    date: string;
  }

  interface WalletSettings {
    notifications: boolean;
    emailAlerts: boolean;
    dailyLimit: number;
    minBalance: number;
  }

  let wallet: Wallet | null = null;
  let transactions: Transaction[] = [];
  let loading = true;
  let error = '';
  let balance = 0;
  let settings: WalletSettings = {
    notifications: true,
    emailAlerts: true,
    dailyLimit: 10000000,
    minBalance: 500000
  };

  // Modal states
  let showTopUpModal = false;
  let showTransferModal = false;
  let showSettingsModal = false;

  // Form data
  let topUpAmount = 100000;
  let transferRecipient = '';
  let transferAmount = 0;
  let transferNote = '';

  // Filters
  let filterType = 'all';
  let filterStatus = 'all';
  let filterDate = '';

  const topUpAmountInputs = [100000, 250000, 500000, 1000000, 2000000, 5000000];

  onMount(async () => {
    await loadWalletData();
  });

  async function loadWalletData() {
    loading = true;
    error = '';
    try {
      const userId = $userStore?.user?.id || 'user_1';
      const [walletData, txData] = await Promise.all([
        walletService.getWallet(userId).catch(() => null),
        walletService.getTransactions(userId).catch(() => [])
      ]);
      
      if (walletData) {
        wallet = walletData;
        balance = walletData.balance;
      } else {
        loadFromStorage();
      }
      
      if (txData.length > 0) {
        transactions = txData.map((tx: WalletTransaction) => ({
          id: tx.id,
          type: tx.type as any,
          amount: tx.amount,
          status: tx.status as any,
          description: tx.description,
          date: tx.createdAt
        }));
      } else {
        loadFromStorage();
      }
    } catch (e) {
      console.error('Failed to load wallet data:', e);
      loadFromStorage();
    }
    loading = false;
  }

  function loadFromStorage() {
    if (!browser) return;

    const savedBalance = localStorage.getItem('wallet_balance');
    const savedTransactions = localStorage.getItem('wallet_transactions');
    const savedSettings = localStorage.getItem('wallet_settings');

    if (savedBalance) {
      balance = parseInt(savedBalance);
    } else {
      balance = 500000;
      saveBalance();
    }

    if (savedTransactions) {
      transactions = JSON.parse(savedTransactions);
    } else {
      transactions = [
        {
          id: '1',
          type: 'topup',
          amount: 500000,
          status: 'completed',
          description: 'Bank Transfer',
          date: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString()
        },
        {
          id: '2',
          type: 'payment',
          amount: -250000,
          status: 'completed',
          description: 'Hotel Booking #1234',
          date: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString()
        },
        {
          id: '3',
          type: 'transfer',
          amount: -100000,
          status: 'completed',
          recipient: 'John Doe',
          description: 'Money Transfer',
          date: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString()
        },
        {
          id: '4',
          type: 'refund',
          amount: 50000,
          status: 'completed',
          description: 'Booking Cancellation Refund',
          date: new Date(Date.now() - 10 * 24 * 60 * 60 * 1000).toISOString()
        }
      ];
      saveTransactions();
    }

    if (savedSettings) {
      settings = JSON.parse(savedSettings);
    }
  }

  function saveBalance() {
    if (!browser) return;
    localStorage.setItem('wallet_balance', balance.toString());
  }

  function saveTransactions() {
    if (!browser) return;
    localStorage.setItem('wallet_transactions', JSON.stringify(transactions));
  }

  function saveSettings() {
    if (!browser) return;
    localStorage.setItem('wallet_settings', JSON.stringify(settings));
  }

  function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('vi-VN').format(amount);
  }

  function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getTransactionIcon(type: string): string {
    switch (type) {
      case 'topup': return '💰';
      case 'transfer': return '💸';
      case 'payment': return '💳';
      case 'withdraw': return '🏦';
      case 'refund': return '↩️';
      default: return '💵';
    }
  }

  function getStatusColor(status: string): string {
    switch (status) {
      case 'completed': return 'status-completed';
      case 'pending': return 'status-pending';
      case 'failed': return 'status-failed';
      default: return 'status-default';
    }
  }

  function getFilteredTransactions(): Transaction[] {
    return transactions.filter(t => {
      if (filterType !== 'all' && t.type !== filterType) return false;
      if (filterStatus !== 'all' && t.status !== filterStatus) return false;
      if (filterDate) {
        const txDate = new Date(t.date).toISOString().split('T')[0];
        if (txDate !== filterDate) return false;
      }
      return true;
    }).sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
  }

  async function handleTopUp() {
    if (topUpAmount <= 0) return;

    try {
      const userId = $userStore?.user?.id || 'user_1';
      const tx = await walletService.topUp({
        userId,
        amount: topUpAmount,
        method: 'bank_transfer'
      });

      transactions = [{
        id: tx.id,
        type: 'topup',
        amount: tx.amount,
        status: 'completed' as const,
        description: tx.description,
        date: tx.createdAt
      }, ...transactions];
      balance += topUpAmount;
    } catch (e) {
      const newTx: Transaction = {
        id: Date.now().toString(),
        type: 'topup',
        amount: topUpAmount,
        status: 'completed',
        description: 'Wallet Top-up',
        date: new Date().toISOString()
      };
      transactions = [newTx, ...transactions];
      balance += topUpAmount;
    }
    
    showTopUpModal = false;
    topUpAmount = 100000;
  }

  async function handleTransfer() {
    if (transferAmount <= 0 || !transferRecipient.trim()) return;
    if (transferAmount > balance) {
      alert('Insufficient balance');
      return;
    }
    if (transferAmount > settings.dailyLimit) {
      alert(`Daily limit exceeded. Maximum: ${formatCurrency(settings.dailyLimit)} VND`);
      return;
    }

    try {
      const userId = $userStore?.user?.id || 'user_1';
      const tx = await walletService.transfer({
        fromUserId: userId,
        toUserId: transferRecipient,
        amount: transferAmount,
        description: transferNote || 'Money Transfer'
      });

      transactions = [{
        id: tx.id,
        type: 'transfer',
        amount: -tx.amount,
        status: 'completed' as const,
        recipient: transferRecipient,
        description: tx.description,
        date: tx.createdAt
      }, ...transactions];
      balance -= transferAmount;
    } catch (e) {
      const newTx: Transaction = {
        id: Date.now().toString(),
        type: 'transfer',
        amount: -transferAmount,
        status: 'completed',
        recipient: transferRecipient,
        description: transferNote || 'Money Transfer',
        date: new Date().toISOString()
      };
      transactions = [newTx, ...transactions];
      balance -= transferAmount;
    }

    showTransferModal = false;
    transferRecipient = '';
    transferAmount = 0;
    transferNote = '';
  }

  function handleSaveSettings() {
    saveSettings();
    showSettingsModal = false;
  }

  $: filteredTransactions = getFilteredTransactions();
</script>

<svelte:head>
  <title>Wallet - Hotel Booking App</title>
</svelte:head>

<div class="wallet-page">
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <h1>💰 My Wallet</h1>
        <p>Manage your digital wallet</p>
      </div>
      <div class="header-right">
        <button class="settings-btn" on:click={() => showSettingsModal = true}>
          ⚙️ Settings
        </button>
      </div>
    </div>
  </header>

  <main class="main-content">
    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Loading wallet...</p>
      </div>
    {:else}
      <!-- Wallet Card -->
      <section class="wallet-card">
        <div class="wallet-card-inner">
          <div class="wallet-info">
            <span class="wallet-label">Available Balance</span>
            <div class="wallet-balance">₫{formatCurrency(balance)}</div>
            <div class="wallet-account">
              <span>Account: ****8829</span>
              <span class="status-dot"></span>
              <span class="status-text">Active</span>
            </div>
          </div>
          <div class="wallet-actions">
            <button class="wallet-action-btn primary" on:click={() => showTopUpModal = true}>
              <span class="action-icon">⬆️</span>
              <span>Top Up</span>
            </button>
            <button class="wallet-action-btn" on:click={() => showTransferModal = true}>
              <span class="action-icon">⬇️</span>
              <span>Transfer</span>
            </button>
          </div>
        </div>
      </section>

      <!-- Quick Stats -->
      <section class="stats-row">
        <div class="stat-item">
          <span class="stat-label">Today's Spending</span>
          <span class="stat-value">₫{formatCurrency(0)}</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
          <span class="stat-label">This Month</span>
          <span class="stat-value">₫{formatCurrency(350000)}</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
          <span class="stat-label">Daily Limit</span>
          <span class="stat-value">₫{formatCurrency(settings.dailyLimit)}</span>
        </div>
      </section>

      <!-- Transaction History -->
      <section class="transactions-section">
        <div class="section-header">
          <h2>Transaction History</h2>
          <span class="transaction-count">{filteredTransactions.length} transactions</span>
        </div>

        <!-- Filters -->
        <div class="filters">
          <div class="filter-group">
            <label>Type</label>
            <select bind:value={filterType}>
              <option value="all">All Types</option>
              <option value="topup">Top Up</option>
              <option value="transfer">Transfer</option>
              <option value="payment">Payment</option>
              <option value="withdraw">Withdraw</option>
              <option value="refund">Refund</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Status</label>
            <select bind:value={filterStatus}>
              <option value="all">All Status</option>
              <option value="completed">Completed</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Date</label>
            <input type="date" bind:value={filterDate} />
          </div>
          {#if filterType !== 'all' || filterStatus !== 'all' || filterDate}
            <button class="clear-filters" on:click={() => { filterType = 'all'; filterStatus = 'all'; filterDate = ''; }}>
              Clear Filters
            </button>
          {/if}
        </div>

        <!-- Transactions List -->
        {#if filteredTransactions.length === 0}
          <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>No transactions found</h3>
            <p>Your transaction history will appear here</p>
          </div>
        {:else}
          <div class="transactions-list">
            {#each filteredTransactions as tx}
              <div class="transaction-item">
                <div class="tx-icon">{getTransactionIcon(tx.type)}</div>
                <div class="tx-info">
                  <div class="tx-title">{tx.description}</div>
                  <div class="tx-meta">
                    {#if tx.recipient}
                      <span class="tx-recipient">To: {tx.recipient}</span>
                      <span class="tx-separator">•</span>
                    {/if}
                    <span class="tx-date">{formatDate(tx.date)}</span>
                  </div>
                </div>
                <div class="tx-amount-group">
                  <div class="tx-amount" class:positive={tx.amount > 0} class:negative={tx.amount < 0}>
                    {tx.amount > 0 ? '+' : ''}₫{formatCurrency(tx.amount)}
                  </div>
                  <span class="tx-status {getStatusColor(tx.status)}">{tx.status}</span>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </section>
    {/if}
  </main>
</div>

<!-- Top Up Modal -->
{#if showTopUpModal}
  <div class="modal-overlay" on:click={() => showTopUpModal = false}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>Top Up Wallet</h2>
        <button class="close-btn" on:click={() => showTopUpModal = false}>×</button>
      </div>
      <div class="modal-body">
        <div class="current-balance-display">
          <span>Current Balance</span>
          <span class="amount">₫{formatCurrency(balance)}</span>
        </div>
        
        <div class="form-group">
          <label>Select Amount</label>
          <div class="amount-grid">
            {#each topUpAmountInputs as amount}
              <button 
                class="amount-option" 
                class:selected={topUpAmount === amount}
                on:click={() => topUpAmount = amount}
              >
                ₫{formatCurrency(amount)}
              </button>
            {/each}
          </div>
        </div>

        <div class="form-group">
          <label>Or Enter Custom Amount</label>
          <input 
            type="number" 
            bind:value={topUpAmount}
            min="10000"
            step="10000"
            placeholder="Enter amount"
          />
        </div>

        <div class="payment-methods">
          <h4>Payment Method</h4>
          <div class="method-option selected">
            <span class="method-icon">🏦</span>
            <span>Bank Transfer</span>
          </div>
          <div class="method-option">
            <span class="method-icon">💳</span>
            <span>Credit/Debit Card</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-cancel" on:click={() => showTopUpModal = false}>Cancel</button>
        <button class="btn-confirm" on:click={handleTopUp}>
          Top Up ₫{formatCurrency(topUpAmount)}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Transfer Modal -->
{#if showTransferModal}
  <div class="modal-overlay" on:click={() => showTransferModal = false}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>Transfer Money</h2>
        <button class="close-btn" on:click={() => showTransferModal = false}>×</button>
      </div>
      <div class="modal-body">
        <div class="current-balance-display">
          <span>Available Balance</span>
          <span class="amount">₫{formatCurrency(balance)}</span>
        </div>

        <div class="form-group">
          <label for="recipient">Recipient</label>
          <input 
            id="recipient"
            type="text" 
            bind:value={transferRecipient}
            placeholder="Enter recipient name or account"
          />
        </div>

        <div class="form-group">
          <label for="amount">Amount</label>
          <input 
            id="amount"
            type="number" 
            bind:value={transferAmount}
            min="10000"
            max={balance}
            step="10000"
            placeholder="Enter amount"
          />
          <div class="amount-hint">
            Min: ₫10,000 | Max: ₫{formatCurrency(Math.min(settings.dailyLimit, balance))}
          </div>
        </div>

        <div class="form-group">
          <label for="note">Note (Optional)</label>
          <input 
            id="note"
            type="text" 
            bind:value={transferNote}
            placeholder="Enter message"
          />
        </div>

        {#if transferAmount > 0}
          <div class="transfer-summary">
            <div class="summary-row">
              <span>Amount</span>
              <span>₫{formatCurrency(transferAmount)}</span>
            </div>
            <div class="summary-row">
              <span>Fee</span>
              <span>₫0</span>
            </div>
            <div class="summary-row total">
              <span>Total</span>
              <span>₫{formatCurrency(transferAmount)}</span>
            </div>
          </div>
        {/if}
      </div>
      <div class="modal-footer">
        <button class="btn-cancel" on:click={() => showTransferModal = false}>Cancel</button>
        <button 
          class="btn-confirm" 
          on:click={handleTransfer}
          disabled={!transferRecipient.trim() || transferAmount <= 0 || transferAmount > balance}
        >
          Transfer ₫{formatCurrency(transferAmount)}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Settings Modal -->
{#if showSettingsModal}
  <div class="modal-overlay" on:click={() => showSettingsModal = false}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>Wallet Settings</h2>
        <button class="close-btn" on:click={() => showSettingsModal = false}>×</button>
      </div>
      <div class="modal-body">
        <div class="settings-section">
          <h3>Notifications</h3>
          <div class="setting-item">
            <div class="setting-info">
              <span class="setting-label">Push Notifications</span>
              <span class="setting-desc">Receive notifications for transactions</span>
            </div>
            <label class="toggle">
              <input type="checkbox" bind:checked={settings.notifications} />
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="setting-item">
            <div class="setting-info">
              <span class="setting-label">Email Alerts</span>
              <span class="setting-desc">Receive email for large transactions</span>
            </div>
            <label class="toggle">
              <input type="checkbox" bind:checked={settings.emailAlerts} />
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="settings-section">
          <h3>Limits & Security</h3>
          <div class="form-group">
            <label>Daily Transfer Limit</label>
            <input 
              type="number" 
              bind:value={settings.dailyLimit}
              min="100000"
              step="100000"
            />
            <div class="amount-hint">Maximum amount you can transfer per day</div>
          </div>
          <div class="form-group">
            <label>Minimum Balance</label>
            <input 
              type="number" 
              bind:value={settings.minBalance}
              min="0"
              step="100000"
            />
            <div class="amount-hint">Minimum balance to maintain in wallet</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-cancel" on:click={() => showSettingsModal = false}>Cancel</button>
        <button class="btn-confirm" on:click={handleSaveSettings}>Save Settings</button>
      </div>
    </div>
  </div>
{/if}

<style>
  .wallet-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  .header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
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
    color: #1e293b;
  }

  .header-left p {
    margin: 0.25rem 0 0 0;
    color: #64748b;
  }

  .settings-btn {
    padding: 0.5rem 1rem;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    color: #475569;
    transition: background 0.2s;
  }

  .settings-btn:hover {
    background: #e2e8f0;
  }

  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .loading {
    text-align: center;
    padding: 4rem;
  }

  .spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #e2e8f0;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Wallet Card */
  .wallet-card {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    border-radius: 20px;
    padding: 2px;
    margin-bottom: 1.5rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  }

  .wallet-card-inner {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 18px;
    padding: 2rem;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .wallet-label {
    font-size: 0.875rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .wallet-balance {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0.5rem 0;
    background: linear-gradient(90deg, #fff 0%, #cbd5e1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .wallet-account {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #94a3b8;
    font-size: 0.875rem;
  }

  .status-dot {
    width: 8px;
    height: 8px;
    background: #22c55e;
    border-radius: 50%;
  }

  .status-text {
    color: #22c55e;
  }

  .wallet-actions {
    display: flex;
    gap: 1rem;
  }

  .wallet-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
  }

  .wallet-action-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
  }

  .wallet-action-btn.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
  }

  .action-icon {
    font-size: 1.5rem;
  }

  /* Stats Row */
  .stats-row {
    display: flex;
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .stat-item {
    flex: 1;
    text-align: center;
  }

  .stat-label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
  }

  .stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }

  .stat-divider {
    width: 1px;
    background: #e2e8f0;
    margin: 0 1.5rem;
  }

  /* Transactions Section */
  .transactions-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .section-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .transaction-count {
    font-size: 0.875rem;
    color: #64748b;
  }

  /* Filters */
  .filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .filter-group label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
  }

  .filter-group select,
  .filter-group input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    min-width: 140px;
  }

  .filter-group select:focus,
  .filter-group input:focus {
    outline: none;
    border-color: #667eea;
  }

  .clear-filters {
    align-self: flex-end;
    padding: 0.5rem 1rem;
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #64748b;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
  }

  .clear-filters:hover {
    background: #f1f5f9;
    color: #1e293b;
  }

  /* Transactions List */
  .transactions-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .transaction-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    transition: all 0.2s;
  }

  .transaction-item:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
  }

  .tx-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 12px;
    font-size: 1.5rem;
  }

  .tx-info {
    flex: 1;
  }

  .tx-title {
    font-weight: 500;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }

  .tx-meta {
    font-size: 0.75rem;
    color: #64748b;
  }

  .tx-separator {
    margin: 0 0.5rem;
  }

  .tx-amount-group {
    text-align: right;
  }

  .tx-amount {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .tx-amount.positive {
    color: #22c55e;
  }

  .tx-amount.negative {
    color: #1e293b;
  }

  .tx-status {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    text-transform: capitalize;
  }

  .status-completed {
    background: #dcfce7;
    color: #166534;
  }

  .status-pending {
    background: #fef3c7;
    color: #92400e;
  }

  .status-failed {
    background: #fee2e2;
    color: #991b1b;
  }

  .status-default {
    background: #f1f5f9;
    color: #475569;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 3rem;
  }

  .empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .empty-state p {
    color: #64748b;
  }

  /* Modal Styles */
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
    padding: 1rem;
  }

  .modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalSlide 0.3s ease;
  }

  @keyframes modalSlide {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .close-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    font-size: 1.25rem;
    cursor: pointer;
    color: #64748b;
  }

  .close-btn:hover {
    background: #e2e8f0;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .current-balance-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    color: #64748b;
  }

  .current-balance-display .amount {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
  }

  .form-group {
    margin-bottom: 1.25rem;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .form-group input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
    box-sizing: border-box;
  }

  .form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .amount-hint {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.5rem;
  }

  .amount-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
  }

  .amount-option {
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    background: white;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    color: #1e293b;
    transition: all 0.2s;
  }

  .amount-option:hover {
    border-color: #667eea;
  }

  .amount-option.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .payment-methods h4 {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    color: #374151;
  }

  .method-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .method-option:hover {
    border-color: #cbd5e1;
  }

  .method-option.selected {
    border-color: #667eea;
    background: #f8fafc;
  }

  .method-icon {
    font-size: 1.5rem;
  }

  .transfer-summary {
    background: #f8fafc;
    border-radius: 10px;
    padding: 1rem;
    margin-top: 1rem;
  }

  .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    color: #64748b;
  }

  .summary-row.total {
    border-top: 1px solid #e2e8f0;
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-weight: 600;
    color: #1e293b;
  }

  .modal-footer {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .btn-cancel {
    flex: 1;
    padding: 0.875rem;
    background: #f1f5f9;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: background 0.2s;
  }

  .btn-cancel:hover {
    background: #e2e8f0;
  }

  .btn-confirm {
    flex: 2;
    padding: 0.875rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 500;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-confirm:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .btn-confirm:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
  }

  /* Settings */
  .settings-section {
    margin-bottom: 2rem;
  }

  .settings-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    color: #1e293b;
  }

  .setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #e2e8f0;
  }

  .setting-info {
    display: flex;
    flex-direction: column;
  }

  .setting-label {
    font-weight: 500;
    color: #1e293b;
  }

  .setting-desc {
    font-size: 0.75rem;
    color: #64748b;
  }

  .toggle {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
  }

  .toggle input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #e2e8f0;
    border-radius: 26px;
    transition: 0.3s;
  }

  .toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: 0.3s;
  }

  .toggle input:checked + .toggle-slider {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }

  .toggle input:checked + .toggle-slider:before {
    transform: translateX(22px);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .main-content {
      padding: 1rem;
    }

    .wallet-card-inner {
      flex-direction: column;
      gap: 1.5rem;
      text-align: center;
    }

    .wallet-actions {
      width: 100%;
    }

    .wallet-action-btn {
      flex: 1;
    }

    .stats-row {
      flex-direction: column;
      gap: 1rem;
    }

    .stat-divider {
      width: 100%;
      height: 1px;
      margin: 0;
    }

    .filters {
      flex-direction: column;
    }

    .filter-group {
      width: 100%;
    }

    .filter-group select,
    .filter-group input {
      width: 100%;
    }

    .transaction-item {
      flex-wrap: wrap;
    }

    .tx-amount-group {
      width: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 0.5rem;
      padding-top: 0.5rem;
      border-top: 1px solid #e2e8f0;
    }

    .amount-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .modal-footer {
      flex-direction: column;
    }
  }
</style>
