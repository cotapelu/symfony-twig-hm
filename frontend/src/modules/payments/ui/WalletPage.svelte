<script lang="ts">
  import { onMount } from 'svelte';
  import { walletStore, walletBalance, walletCurrency } from '../state/walletStore';
  import { user } from '$lib/stores/global';

  let activeTab = 'overview';
  let showTopUpModal = false;
  let showTransferModal = false;
  let topUpAmount = '';
  let transferAmount = '';
  let transferToUserId = '';
  let transferDescription = '';
  let processing = false;
  let errorMessage = '';
  let successMessage = '';

  $: currentUser = $user;
  $: wallet = $walletStore.wallet;
  $: transactions = $walletStore.transactions;
  $: loading = $walletStore.loading;

  onMount(() => {
    if (currentUser?.id) {
      loadWalletData();
    }
  });

  async function loadWalletData() {
    if (!currentUser?.id) return;
    
    try {
      await walletStore.loadWallet(currentUser.id);
      await walletStore.loadTransactions(currentUser.id);
    } catch (err) {
      console.error('Failed to load wallet:', err);
    }
  }

  async function handleTopUp() {
    if (!currentUser?.id || !topUpAmount) return;
    
    processing = true;
    errorMessage = '';
    successMessage = '';
    
    try {
      await walletStore.topUp(currentUser.id, parseFloat(topUpAmount), 'bank_transfer');
      successMessage = 'Top up successful!';
      showTopUpModal = false;
      topUpAmount = '';
      await loadWalletData();
    } catch (err) {
      errorMessage = err instanceof Error ? err.message : 'Top up failed';
    } finally {
      processing = false;
    }
  }

  async function handleTransfer() {
    if (!currentUser?.id || !transferToUserId || !transferAmount) return;
    
    processing = true;
    errorMessage = '';
    successMessage = '';
    
    try {
      await walletStore.transfer(
        currentUser.id, 
        transferToUserId, 
        parseFloat(transferAmount),
        transferDescription
      );
      successMessage = 'Transfer successful!';
      showTransferModal = false;
      transferAmount = '';
      transferToUserId = '';
      transferDescription = '';
      await loadWalletData();
    } catch (err) {
      errorMessage = err instanceof Error ? err.message : 'Transfer failed';
    } finally {
      processing = false;
    }
  }

  function formatCurrency(amount: number, currency: string): string {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: currency || 'VND'
    }).format(amount);
  }

  function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('vi-VN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getTransactionIcon(type: string): string {
    const icons: Record<string, string> = {
      topup: '💰',
      transfer_in: '📥',
      transfer_out: '📤',
      withdraw: '🏧',
      payment: '💳'
    };
    return icons[type] || '💵';
  }

  function getTransactionColor(type: string): string {
    if (type === 'topup' || type === 'transfer_in') return 'text-green-400';
    if (type === 'withdraw' || type === 'transfer_out' || type === 'payment') return 'text-red-400';
    return 'text-gray-400';
  }
</script>

<div class="wallet-page">
  <div class="wallet-container">
    <div class="wallet-header">
      <h1>💳 My Wallet</h1>
      <p class="subtitle">Manage your funds and transactions</p>
    </div>

    {#if loading && !wallet}
      <div class="loading-state">
        <div class="spinner"></div>
        <p>Loading wallet...</p>
      </div>
    {:else if !wallet}
      <div class="empty-state">
        <p>No wallet found</p>
        <button class="btn-primary" on:click={() => walletStore.createWallet(currentUser?.id)}>
          Create Wallet
        </button>
      </div>
    {:else}
      <div class="balance-card">
        <div class="balance-info">
          <span class="balance-label">Available Balance</span>
          <span class="balance-amount">{formatCurrency($walletBalance, $walletCurrency)}</span>
          <span class="balance-currency">{$walletCurrency}</span>
        </div>
        <div class="balance-actions">
          <button class="btn-action" on:click={() => showTopUpModal = true}>
            💰 Top Up
          </button>
          <button class="btn-action" on:click={() => showTransferModal = true}>
            📤 Transfer
          </button>
        </div>
      </div>

      {#if successMessage}
        <div class="success-message">
          ✅ {successMessage}
        </div>
      {/if}

      {#if errorMessage}
        <div class="error-message">
          ❌ {errorMessage}
        </div>
      {/if}

      <div class="tabs">
        <button 
          class="tab {activeTab === 'overview' ? 'active' : ''}"
          on:click={() => activeTab = 'overview'}
        >
          Overview
        </button>
        <button 
          class="tab {activeTab === 'transactions' ? 'active' : ''}"
          on:click={() => activeTab = 'transactions'}
        >
          Transactions
        </button>
      </div>

      {#if activeTab === 'transactions'}
        <div class="transactions-section">
          <h2>Recent Transactions</h2>
          {#if transactions.length === 0}
            <p class="no-data">No transactions yet</p>
          {:else}
            <div class="transactions-list">
              {#each transactions as txn}
                <div class="transaction-item">
                  <div class="transaction-icon">
                    {getTransactionIcon(txn.type)}
                  </div>
                  <div class="transaction-details">
                    <span class="transaction-type">{txn.type.replace('_', ' ').toUpperCase()}</span>
                    <span class="transaction-desc">{txn.description}</span>
                    <span class="transaction-date">{formatDate(txn.created_at)}</span>
                  </div>
                  <div class="transaction-amount {getTransactionColor(txn.type)}">
                    {txn.amount > 0 ? '+' : ''}{formatCurrency(txn.amount, $walletCurrency)}
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>
      {:else}
        <div class="overview-section">
          <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
              <button class="action-card" on:click={() => showTopUpModal = true}>
                <span class="action-icon">💰</span>
                <span class="action-label">Top Up</span>
              </button>
              <button class="action-card" on:click={() => showTransferModal = true}>
                <span class="action-icon">📤</span>
                <span class="action-label">Transfer</span>
              </button>
              <button class="action-card" on:click={() => activeTab = 'transactions'}>
                <span class="action-icon">📊</span>
                <span class="action-label">History</span>
              </button>
              <button class="action-card">
                <span class="action-icon">⚙️</span>
                <span class="action-label">Settings</span>
              </button>
            </div>
          </div>
        </div>
      {/if}
    {/if}
  </div>
</div>

{#if showTopUpModal}
  <div class="modal-overlay" on:click={() => showTopUpModal = false}>
    <div class="modal" on:click|stopPropagation>
      <h2>💰 Top Up</h2>
      <p>Add funds to your wallet</p>
      
      <div class="form-group">
        <label>Amount ({$walletCurrency})</label>
        <input 
          type="number" 
          bind:value={topUpAmount} 
          placeholder="Enter amount"
          min="1000"
          step="1000"
        />
      </div>

      <div class="modal-actions">
        <button class="btn-secondary" on:click={() => showTopUpModal = false}>
          Cancel
        </button>
        <button 
          class="btn-primary" 
          on:click={handleTopUp}
          disabled={processing || !topUpAmount}
        >
          {processing ? 'Processing...' : 'Confirm Top Up'}
        </button>
      </div>
    </div>
  </div>
{/if}

{#if showTransferModal}
  <div class="modal-overlay" on:click={() => showTransferModal = false}>
    <div class="modal" on:click|stopPropagation>
      <h2>📤 Transfer</h2>
      <p>Send funds to another user</p>
      
      <div class="form-group">
        <label>Recipient User ID</label>
        <input 
          type="text" 
          bind:value={transferToUserId} 
          placeholder="Enter recipient ID"
        />
      </div>

      <div class="form-group">
        <label>Amount ({$walletCurrency})</label>
        <input 
          type="number" 
          bind:value={transferAmount} 
          placeholder="Enter amount"
          min="1000"
          step="1000"
        />
      </div>

      <div class="form-group">
        <label>Description (optional)</label>
        <input 
          type="text" 
          bind:value={transferDescription} 
          placeholder="What's this for?"
        />
      </div>

      <div class="modal-actions">
        <button class="btn-secondary" on:click={() => showTransferModal = false}>
          Cancel
        </button>
        <button 
          class="btn-primary" 
          on:click={handleTransfer}
          disabled={processing || !transferToUserId || !transferAmount}
        >
          {processing ? 'Processing...' : 'Confirm Transfer'}
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .wallet-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
    padding: 2rem;
    color: #ffffff;
  }

  .wallet-container {
    max-width: 800px;
    margin: 0 auto;
  }

  .wallet-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .wallet-header h1 {
    color: #00ffff;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    text-shadow: 0 0 15px #00ffff;
  }

  .subtitle {
    color: #cccccc;
    font-size: 1.1rem;
  }

  .balance-card {
    background: linear-gradient(135deg, rgba(0, 255, 255, 0.1), rgba(255, 0, 255, 0.1));
    border: 2px solid #00ffff;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: center;
    box-shadow: 0 0 30px rgba(0, 255, 255, 0.3);
  }

  .balance-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
  }

  .balance-label {
    color: #cccccc;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 2px;
  }

  .balance-amount {
    color: #00ffff;
    font-size: 3rem;
    font-weight: bold;
    text-shadow: 0 0 20px #00ffff;
  }

  .balance-currency {
    color: #ff00ff;
    font-size: 1.2rem;
    text-shadow: 0 0 10px #ff00ff;
  }

  .balance-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
  }

  .btn-action {
    padding: 0.75rem 1.5rem;
    background: rgba(0, 255, 255, 0.2);
    border: 1px solid #00ffff;
    border-radius: 10px;
    color: #00ffff;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .btn-action:hover {
    background: rgba(0, 255, 255, 0.3);
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
    transform: translateY(-2px);
  }

  .tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 1px solid rgba(0, 255, 255, 0.3);
  }

  .tab {
    padding: 1rem 2rem;
    background: transparent;
    border: none;
    color: #cccccc;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border-bottom: 2px solid transparent;
  }

  .tab:hover {
    color: #00ffff;
  }

  .tab.active {
    color: #00ffff;
    border-bottom-color: #00ffff;
    text-shadow: 0 0 10px #00ffff;
  }

  .transactions-section,
  .overview-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid rgba(0, 255, 255, 0.2);
  }

  .transactions-section h2,
  .quick-actions h2 {
    color: #00ffff;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
  }

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
    background: rgba(0, 0, 0, 0.3);
    border-radius: 10px;
    border: 1px solid rgba(0, 255, 255, 0.1);
    transition: all 0.3s ease;
  }

  .transaction-item:hover {
    border-color: rgba(0, 255, 255, 0.3);
    background: rgba(0, 255, 255, 0.05);
  }

  .transaction-icon {
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 255, 255, 0.1);
    border-radius: 10px;
  }

  .transaction-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .transaction-type {
    color: #ffffff;
    font-weight: 600;
    font-size: 0.9rem;
  }

  .transaction-desc {
    color: #999999;
    font-size: 0.85rem;
  }

  .transaction-date {
    color: #666666;
    font-size: 0.8rem;
  }

  .transaction-amount {
    font-weight: bold;
    font-size: 1.1rem;
  }

  .text-green-400 {
    color: #4ade80;
    text-shadow: 0 0 10px rgba(74, 222, 128, 0.5);
  }

  .text-red-400 {
    color: #f87171;
    text-shadow: 0 0 10px rgba(248, 113, 113, 0.5);
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
  }

  .action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.5rem;
    background: rgba(0, 255, 255, 0.05);
    border: 1px solid rgba(0, 255, 255, 0.2);
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .action-card:hover {
    background: rgba(0, 255, 255, 0.1);
    border-color: #00ffff;
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
    transform: translateY(-3px);
  }

  .action-icon {
    font-size: 2rem;
  }

  .action-label {
    color: #cccccc;
    font-size: 0.9rem;
  }

  .loading-state {
    text-align: center;
    padding: 3rem;
  }

  .spinner {
    width: 50px;
    height: 50px;
    border: 3px solid rgba(0, 255, 255, 0.3);
    border-top-color: #00ffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .empty-state {
    text-align: center;
    padding: 3rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    border: 1px dashed rgba(0, 255, 255, 0.3);
  }

  .no-data {
    text-align: center;
    color: #666666;
    padding: 2rem;
  }

  .success-message {
    background: rgba(74, 222, 128, 0.1);
    border: 1px solid #4ade80;
    color: #4ade80;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    text-align: center;
  }

  .error-message {
    background: rgba(248, 113, 113, 0.1);
    border: 1px solid #f87171;
    color: #f87171;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    text-align: center;
  }

  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }

  .modal {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    border: 2px solid #00ffff;
    border-radius: 20px;
    padding: 2rem;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 0 50px rgba(0, 255, 255, 0.3);
  }

  .modal h2 {
    color: #00ffff;
    margin-bottom: 0.5rem;
    text-shadow: 0 0 10px #00ffff;
  }

  .modal p {
    color: #cccccc;
    margin-bottom: 1.5rem;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    color: #00ffff;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
  }

  .form-group input {
    width: 100%;
    padding: 0.75rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(0, 255, 255, 0.3);
    border-radius: 8px;
    color: #ffffff;
    font-size: 1rem;
  }

  .form-group input:focus {
    outline: none;
    border-color: #00ffff;
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
  }

  .modal-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
  }

  .btn-primary,
  .btn-secondary {
    flex: 1;
    padding: 0.75rem;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .btn-primary {
    background: linear-gradient(45deg, #00ffff, #ff00ff);
    border: none;
    color: #000000;
    font-weight: bold;
  }

  .btn-primary:hover:not(:disabled) {
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
  }

  .btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .btn-secondary {
    background: transparent;
    border: 1px solid #ff00ff;
    color: #ff00ff;
  }

  .btn-secondary:hover {
    background: rgba(255, 0, 255, 0.1);
  }

  @media (max-width: 768px) {
    .wallet-page {
      padding: 1rem;
    }

    .balance-amount {
      font-size: 2rem;
    }

    .actions-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
</style>
