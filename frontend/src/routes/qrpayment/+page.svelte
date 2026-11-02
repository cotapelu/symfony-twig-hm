<script lang="ts">
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';

  // Types
  interface Transaction {
    id: string;
    type: 'receive' | 'send' | 'merchant';
    amount: number;
    status: 'completed' | 'pending' | 'failed';
    recipient?: string;
    sender?: string;
    description: string;
    date: string;
    qrData?: string;
  }

  interface Merchant {
    id: string;
    name: string;
    address: string;
    logo: string;
    category: string;
  }

  // Storage keys
  const STORAGE_KEYS = {
    transactions: 'qrpayment_transactions',
    balance: 'qrpayment_balance',
    merchants: 'qrpayment_merchants',
    userId: 'qrpayment_userid'
  };

  // State
  let loading = true;
  let activeTab: 'scan' | 'receive' | 'history' | 'merchant' = 'scan';
  let balance = 0;
  let transactions: Transaction[] = [];
  let merchants: Merchant[] = [];

  // QR Generation state
  let qrAmount = '';
  let qrNote = '';
  let generatedQrData = '';
  let showQrModal = false;

  // Scanner state
  let isScanning = false;
  let scanResult = '';
  let showScanResult = false;
  let scanError = '';

  // Payment state
  let showPaymentModal = false;
  let paymentAmount = '';
  let paymentNote = '';
  let selectedMerchant: Merchant | null = null;
  let paymentSuccess = false;
  let paymentError = '';

  // Filter state
  let filterType = 'all';
  let filterDate = '';

  // User ID for QR generation
  let userId = '';

  // Demo merchants
  const demoMerchants: Merchant[] = [
    { id: 'm1', name: 'Coffee House', address: '123 Nguyen Trai St', logo: '☕', category: 'Food & Drink' },
    { id: 'm2', name: 'Big C Supermarket', address: '456 Le Van Sy St', logo: '🛒', category: 'Grocery' },
    { id: 'm3', name: 'Grab Ride', address: 'Ride Service', logo: '🚗', category: 'Transport' },
    { id: 'm4', name: 'Circle K', address: '789 Pasteur St', logo: '🏪', category: 'Convenience' },
    { id: 'm5', name: 'The Coffee Bean', address: '321 Dong Khoi St', logo: '🍵', category: 'Food & Drink' },
    { id: 'm6', name: 'Vinmart', address: '654 Vo Van Ki St', logo: '🛍️', category: 'Grocery' }
  ];

  onMount(() => {
    loadData();
  });

  function loadData() {
    loading = true;

    // Load or generate user ID
    let storedUserId = localStorage.getItem(STORAGE_KEYS.userId);
    if (!storedUserId) {
      storedUserId = 'USER' + Math.random().toString(36).substring(2, 10).toUpperCase();
      localStorage.setItem(STORAGE_KEYS.userId, storedUserId);
    }
    userId = storedUserId;

    // Load balance
    const storedBalance = localStorage.getItem(STORAGE_KEYS.balance);
    if (storedBalance) {
      balance = parseInt(storedBalance);
    } else {
      balance = 2500000; // Default balance
      saveBalance();
    }

    // Load transactions
    const storedTransactions = localStorage.getItem(STORAGE_KEYS.transactions);
    if (storedTransactions) {
      transactions = JSON.parse(storedTransactions);
    } else {
      // Initialize with sample transactions
      transactions = [
        { id: '1', type: 'receive', amount: 500000, status: 'completed', sender: 'Coffee House', description: 'Payment received', date: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString() },
        { id: '2', type: 'merchant', amount: -85000, status: 'completed', recipient: 'Big C', description: 'Shopping', date: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString() },
        { id: '3', type: 'send', amount: -200000, status: 'completed', recipient: 'John Doe', description: 'Transfer', date: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString() },
        { id: '4', type: 'receive', amount: 150000, status: 'completed', sender: 'Mom', description: ' Allowance', date: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString() }
      ];
      saveTransactions();
    }

    // Load or set merchants
    const storedMerchants = localStorage.getItem(STORAGE_KEYS.merchants);
    if (storedMerchants) {
      merchants = JSON.parse(storedMerchants);
    } else {
      merchants = demoMerchants;
      localStorage.setItem(STORAGE_KEYS.merchants, JSON.stringify(merchants));
    }

    loading = false;
  }

  function saveBalance() {
    localStorage.setItem(STORAGE_KEYS.balance, balance.toString());
  }

  function saveTransactions() {
    localStorage.setItem(STORAGE_KEYS.transactions, JSON.stringify(transactions));
  }

  // Helper functions
  function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('vi-VN').format(Math.abs(amount));
  }

  function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    if (days === 0) {
      return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    } else if (days === 1) {
      return 'Yesterday';
    } else if (days < 7) {
      return date.toLocaleDateString('vi-VN', { weekday: 'short' });
    } else {
      return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
    }
  }

  function getTransactionIcon(type: string): string {
    switch (type) {
      case 'receive': return '📥';
      case 'send': return '📤';
      case 'merchant': return '🏪';
      default: return '💰';
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

  // QR Generation
  function generateQrCode() {
    const amount = parseInt(qrAmount);
    if (isNaN(amount) || amount <= 0) {
      alert('Please enter a valid amount');
      return;
    }

    // Create QR data string
    const qrData = JSON.stringify({
      userId: userId,
      amount: amount,
      note: qrNote,
      timestamp: Date.now()
    });

    generatedQrData = qrData;
    showQrModal = true;
  }

  function getQrImageUrl(data: string): string {
    const encoded = encodeURIComponent(data);
    return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encoded}&bgcolor=FFFFFF&color=1E293B`;
  }

  // QR Scanner Simulation
  async function startScanning() {
    isScanning = true;
    scanError = '';
    scanResult = '';

    // Simulate scanning delay
    await new Promise(resolve => setTimeout(resolve, 2000));

    // In a real app, this would use the camera
    // For demo, we'll simulate finding a merchant QR
    const randomMerchant = merchants[Math.floor(Math.random() * merchants.length)];
    const randomAmount = Math.floor(Math.random() * 500000) + 10000;

    scanResult = JSON.stringify({
      merchantId: randomMerchant.id,
      merchantName: randomMerchant.name,
      amount: randomAmount,
      timestamp: Date.now()
    });

    isScanning = false;
    showScanResult = true;
  }

  function cancelScan() {
    isScanning = false;
    scanError = '';
  }

  function processScanResult() {
    try {
      const data = JSON.parse(scanResult);
      selectedMerchant = merchants.find(m => m.id === data.merchantName) || null;
      paymentAmount = data.amount.toString();
      showScanResult = false;
      showPaymentModal = true;
    } catch (e) {
      scanError = 'Invalid QR code';
    }
  }

  // Payment to Merchant
  function openMerchantPayment(merchant: Merchant) {
    selectedMerchant = merchant;
    paymentAmount = '';
    paymentNote = '';
    paymentError = '';
    paymentSuccess = false;
    showPaymentModal = true;
  }

  function processPayment() {
    const amount = parseInt(paymentAmount);

    if (isNaN(amount) || amount <= 0) {
      paymentError = 'Please enter a valid amount';
      return;
    }

    if (amount > balance) {
      paymentError = 'Insufficient balance';
      return;
    }

    // Process payment
    balance -= amount;

    const newTx: Transaction = {
      id: Date.now().toString(),
      type: 'merchant',
      amount: -amount,
      status: 'completed',
      recipient: selectedMerchant?.name,
      description: paymentNote || `Payment to ${selectedMerchant?.name}`,
      date: new Date().toISOString()
    };

    transactions = [newTx, ...transactions];
    saveBalance();
    saveTransactions();

    paymentSuccess = true;

    setTimeout(() => {
      showPaymentModal = false;
      paymentSuccess = false;
      activeTab = 'history';
    }, 1500);
  }

  // Filter transactions
  $: filteredTransactions = transactions.filter(t => {
    if (filterType !== 'all' && t.type !== filterType) return false;
    if (filterDate) {
      const txDate = new Date(t.date).toISOString().split('T')[0];
      if (txDate !== filterDate) return false;
    }
    return true;
  });

  // Stats
  $: todaySpending = transactions
    .filter(t => {
      const today = new Date().toISOString().split('T')[0];
      const txDate = new Date(t.date).toISOString().split('T')[0];
      return t.type !== 'receive' && txDate === today;
    })
    .reduce((sum, t) => sum + Math.abs(t.amount), 0);

  $: monthSpending = transactions
    .filter(t => {
      const now = new Date();
      const txDate = new Date(t.date);
      return t.type !== 'receive' && 
             txDate.getMonth() === now.getMonth() && 
             txDate.getFullYear() === now.getFullYear();
    })
    .reduce((sum, t) => sum + Math.abs(t.amount), 0);

  $: receiveCount = transactions.filter(t => t.type === 'receive').length;
</script>

<svelte:head>
  <title>QR Payment - Hotel Booking App</title>
</svelte:head>

<div class="qrpayment-page">
  <!-- Header -->
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <h1>📱 QR Payment</h1>
        <p>Scan QR codes or generate your own</p>
      </div>
      <div class="balance-display">
        <span class="balance-label">Balance</span>
        <span class="balance-amount">₫{formatCurrency(balance)}</span>
      </div>
    </div>
  </header>

  <!-- Stats Bar -->
  <div class="stats-bar">
    <div class="stat-item">
      <span class="stat-value">₫{formatCurrency(todaySpending)}</span>
      <span class="stat-label">Today</span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-item">
      <span class="stat-value">₫{formatCurrency(monthSpending)}</span>
      <span class="stat-label">This Month</span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-item">
      <span class="stat-value">{receiveCount}</span>
      <span class="stat-label">Received</span>
    </div>
  </div>

  <!-- Tabs -->
  <nav class="tabs">
    <button 
      class="tab" 
      class:active={activeTab === 'scan'} 
      on:click={() => activeTab = 'scan'}
    >
      <span class="tab-icon">📷</span>
      <span>Scan</span>
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'receive'} 
      on:click={() => activeTab = 'receive'}
    >
      <span class="tab-icon">💳</span>
      <span>Receive</span>
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'merchant'} 
      on:click={() => activeTab = 'merchant'}
    >
      <span class="tab-icon">🏪</span>
      <span>Pay</span>
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'history'} 
      on:click={() => activeTab = 'history'}
    >
      <span class="tab-icon">📋</span>
      <span>History</span>
    </button>
  </nav>

  <main class="main-content">
    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Loading...</p>
      </div>
    {:else}
      <!-- Scan Tab -->
      {#if activeTab === 'scan'}
        <section class="scan-section">
          <div class="scan-container">
            {#if isScanning}
              <div class="scanner-active">
                <div class="scanner-frame">
                  <div class="scanner-corner tl"></div>
                  <div class="scanner-corner tr"></div>
                  <div class="scanner-corner bl"></div>
                  <div class="scanner-corner br"></div>
                  <div class="scanner-line"></div>
                </div>
                <p class="scanning-text">Scanning for QR code...</p>
                <button class="btn-cancel" on:click={cancelScan}>Cancel</button>
              </div>
            {:else if showScanResult && scanResult}
              <div class="scan-result">
                <div class="result-icon">✅</div>
                <h3>QR Code Found!</h3>
                {#await JSON.parse(scanResult) then data}
                  <div class="result-details">
                    <p><strong>Merchant:</strong> {data.merchantName}</p>
                    <p><strong>Amount:</strong> ₫{formatCurrency(data.amount)}</p>
                  </div>
                {/await}
                <div class="result-actions">
                  <button class="btn-secondary" on:click={() => { showScanResult = false; scanResult = ''; }}>
                    Cancel
                  </button>
                  <button class="btn-primary" on:click={processScanResult}>
                    Proceed to Pay
                  </button>
                </div>
              </div>
            {:else}
              <div class="scan-prompt">
                <div class="scan-icon">📷</div>
                <h3>Scan QR Code</h3>
                <p>Point your camera at a merchant's QR code to make a payment</p>
                <button class="btn-scan" on:click={startScanning}>
                  Start Scanning
                </button>
              </div>
            {/if}

            {#if scanError}
              <div class="error-message">{scanError}</div>
            {/if}
          </div>

          <!-- Quick Pay Section -->
          <div class="quick-pay-section">
            <h3>Quick Pay</h3>
            <div class="merchant-grid">
              {#each merchants.slice(0, 6) as merchant}
                <button class="merchant-quick-btn" on:click={() => openMerchantPayment(merchant)}>
                  <span class="merchant-logo">{merchant.logo}</span>
                  <span class="merchant-name">{merchant.name}</span>
                </button>
              {/each}
            </div>
          </div>
        </section>
      {/if}

      <!-- Receive Tab -->
      {#if activeTab === 'receive'}
        <section class="receive-section">
          <div class="qr-card">
            <div class="qr-header">
              <span class="user-id">ID: {userId}</span>
              <span class="badge">My QR Code</span>
            </div>
            
            <div class="qr-display">
              {#if generatedQrData}
                <img src={getQrImageUrl(generatedQrData)} alt="Payment QR Code" class="qr-image" />
              {:else}
                <div class="qr-placeholder">
                  <span>Enter amount to generate QR</span>
                </div>
              {/if}
            </div>

            <div class="qr-amount-display">
              {#if qrAmount}
                <span class="amount-currency">₫</span>
                <span class="amount-value">{formatCurrency(parseInt(qrAmount))}</span>
              {/if}
            </div>
          </div>

          <div class="generate-form">
            <h3>Generate Payment QR</h3>
            <div class="form-group">
              <label>Amount (VND)</label>
              <input 
                type="number" 
                bind:value={qrAmount}
                placeholder="Enter amount"
                min="1000"
                step="1000"
              />
            </div>
            <div class="form-group">
              <label>Note (Optional)</label>
              <input 
                type="text" 
                bind:value={qrNote}
                placeholder="What's this for?"
              />
            </div>
            <button class="btn-generate" on:click={generateQrCode}>
              Generate QR Code
            </button>
          </div>

          <div class="share-options">
            <h4>Share QR Code</h4>
            <div class="share-buttons">
              <button class="share-btn">📤 Share</button>
              <button class="share-btn">💾 Save</button>
              <button class="share-btn">📧 Email</button>
            </div>
          </div>
        </section>
      {/if}

      <!-- Merchant/Pay Tab -->
      {#if activeTab === 'merchant'}
        <section class="merchant-section">
          <div class="search-bar">
            <input type="text" placeholder="Search merchants..." />
          </div>

          <div class="category-tabs">
            <button class="category-tab active">All</button>
            <button class="category-tab">Food & Drink</button>
            <button class="category-tab">Grocery</button>
            <button class="category-tab">Transport</button>
          </div>

          <div class="merchants-list">
            {#each merchants as merchant}
              <div class="merchant-card">
                <div class="merchant-icon">{merchant.logo}</div>
                <div class="merchant-info">
                  <h4>{merchant.name}</h4>
                  <p class="merchant-address">{merchant.address}</p>
                  <span class="merchant-category">{merchant.category}</span>
                </div>
                <button class="pay-btn" on:click={() => openMerchantPayment(merchant)}>
                  Pay
                </button>
              </div>
            {/each}
          </div>
        </section>
      {/if}

      <!-- History Tab -->
      {#if activeTab === 'history'}
        <section class="history-section">
          <div class="filters">
            <div class="filter-group">
              <label>Type</label>
              <select bind:value={filterType}>
                <option value="all">All</option>
                <option value="receive">Received</option>
                <option value="send">Sent</option>
                <option value="merchant">Merchant</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Date</label>
              <input type="date" bind:value={filterDate} />
            </div>
            {#if filterType !== 'all' || filterDate}
              <button class="clear-filters" on:click={() => { filterType = 'all'; filterDate = ''; }}>
                Clear
              </button>
            {/if}
          </div>

          {#if filteredTransactions.length === 0}
            <div class="empty-state">
              <div class="empty-icon">📭</div>
              <h3>No transactions</h3>
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
                      {#if tx.sender}
                        <span>From: {tx.sender}</span>
                        <span class="separator">•</span>
                      {/if}
                      {#if tx.recipient}
                        <span>To: {tx.recipient}</span>
                        <span class="separator">•</span>
                      {/if}
                      <span>{formatDate(tx.date)}</span>
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
    {/if}
  </main>
</div>

<!-- QR Modal -->
{#if showQrModal}
  <div class="modal-overlay" on:click={() => showQrModal = false}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>Your Payment QR</h2>
        <button class="close-btn" on:click={() => showQrModal = false}>×</button>
      </div>
      <div class="modal-body">
        <div class="qr-modal-image">
          <img src={getQrImageUrl(generatedQrData)} alt="Payment QR" />
        </div>
        <div class="qr-modal-info">
          <div class="modal-amount">
            <span class="label">Amount</span>
            <span class="value">₫{formatCurrency(parseInt(qrAmount))}</span>
          </div>
          {#if qrNote}
            <div class="modal-note">
              <span class="label">Note</span>
              <span class="value">{qrNote}</span>
            </div>
          {/if}
          <div class="modal-userid">
            <span class="label">Your ID</span>
            <span class="value">{userId}</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" on:click={() => showQrModal = false}>Close</button>
        <button class="btn-primary" on:click={() => { /* Share */ }}>
          Share QR
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Payment Modal -->
{#if showPaymentModal}
  <div class="modal-overlay" on:click={() => !paymentSuccess && (showPaymentModal = false)}>
    <div class="modal" on:click|stopPropagation>
      {#if paymentSuccess}
        <div class="payment-success">
          <div class="success-icon">✅</div>
          <h3>Payment Successful!</h3>
          <p>₫{formatCurrency(parseInt(paymentAmount))} paid to {selectedMerchant?.name}</p>
        </div>
      {:else}
        <div class="modal-header">
          <h2>💳 Pay {selectedMerchant?.name}</h2>
          <button class="close-btn" on:click={() => showPaymentModal = false}>×</button>
        </div>
        <div class="modal-body">
          <div class="payment-merchant-preview">
            <span class="merchant-logo-large">{selectedMerchant?.logo}</span>
            <span class="merchant-name">{selectedMerchant?.name}</span>
            <span class="merchant-address">{selectedMerchant?.address}</span>
          </div>

          <div class="form-group">
            <label>Amount (VND)</label>
            <input 
              type="number" 
              bind:value={paymentAmount}
              placeholder="Enter amount"
              min="1000"
            />
          </div>

          <div class="form-group">
            <label>Note (Optional)</label>
            <input 
              type="text" 
              bind:value={paymentNote}
              placeholder="What's this for?"
            />
          </div>

          <div class="payment-summary">
            <div class="summary-row">
              <span>Amount</span>
              <span>₫{paymentAmount ? formatCurrency(parseInt(paymentAmount)) : 0}</span>
            </div>
            <div class="summary-row">
              <span>Fee</span>
              <span>₫0</span>
            </div>
            <div class="summary-row total">
              <span>Total</span>
              <span>₫{paymentAmount ? formatCurrency(parseInt(paymentAmount)) : 0}</span>
            </div>
          </div>

          {#if paymentError}
            <div class="error-message">{paymentError}</div>
          {/if}

          <div class="balance-info">
            Available: <strong>₫{formatCurrency(balance)}</strong>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" on:click={() => showPaymentModal = false}>Cancel</button>
          <button 
            class="btn-confirm" 
            on:click={processPayment}
            disabled={!paymentAmount || parseInt(paymentAmount) <= 0}
          >
            Pay ₫{paymentAmount ? formatCurrency(parseInt(paymentAmount)) : 0}
          </button>
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  /* Base Styles */
  .qrpayment-page {
    min-height: 100vh;
    background: #0f172a;
    color: #e2e8f0;
  }

  .header {
    background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #1e293b;
  }

  .header-content {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .header-left h1 {
    margin: 0;
    font-size: 1.5rem;
    color: #f8fafc;
  }

  .header-left p {
    margin: 0.25rem 0 0;
    color: #94a3b8;
    font-size: 0.875rem;
  }

  .balance-display {
    text-align: right;
  }

  .balance-label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
  }

  .balance-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #22c55e;
  }

  /* Stats Bar */
  .stats-bar {
    display: flex;
    justify-content: center;
    gap: 2rem;
    padding: 1rem 2rem;
    background: #1e293b;
    border-bottom: 1px solid #334155;
  }

  .stat-item {
    text-align: center;
  }

  .stat-value {
    display: block;
    font-size: 1.125rem;
    font-weight: 600;
    color: #f8fafc;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  .stat-divider {
    width: 1px;
    background: #334155;
  }

  /* Tabs */
  .tabs {
    display: flex;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #1e293b;
    overflow-x: auto;
    max-width: 800px;
    margin: 0 auto;
  }

  .tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.75rem 0.5rem;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 70px;
  }

  .tab:hover {
    background: #334155;
    color: #94a3b8;
  }

  .tab.active {
    background: #3b82f6;
    color: white;
  }

  .tab-icon {
    font-size: 1.25rem;
  }

  /* Main Content */
  .main-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 1.5rem;
  }

  /* Loading */
  .loading {
    text-align: center;
    padding: 4rem;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #334155;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Scan Section */
  .scan-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .scan-container {
    background: #1e293b;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
  }

  .scan-prompt {
    padding: 2rem;
  }

  .scan-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .scan-prompt h3 {
    margin: 0 0 0.5rem;
    color: #f8fafc;
  }

  .scan-prompt p {
    color: #64748b;
    margin: 0 0 1.5rem;
  }

  .btn-scan {
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .btn-scan:hover {
    transform: scale(1.02);
  }

  /* Scanner */
  .scanner-active {
    padding: 2rem;
  }

  .scanner-frame {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto 1.5rem;
    border: 2px solid #3b82f6;
    border-radius: 12px;
    overflow: hidden;
  }

  .scanner-corner {
    position: absolute;
    width: 30px;
    height: 30px;
    border: 3px solid #22c55e;
  }

  .scanner-corner.tl { top: 0; left: 0; border-right: none; border-bottom: none; }
  .scanner-corner.tr { top: 0; right: 0; border-left: none; border-bottom: none; }
  .scanner-corner.bl { bottom: 0; left: 0; border-right: none; border-top: none; }
  .scanner-corner.br { bottom: 0; right: 0; border-left: none; border-top: none; }

  .scanner-line {
    position: absolute;
    left: 10px;
    right: 10px;
    height: 2px;
    background: linear-gradient(90deg, transparent, #22c55e, transparent);
    animation: scanMove 2s ease-in-out infinite;
  }

  @keyframes scanMove {
    0%, 100% { top: 20px; }
    50% { top: 170px; }
  }

  .scanning-text {
    color: #64748b;
    margin-bottom: 1rem;
  }

  .btn-cancel {
    padding: 0.625rem 1.5rem;
    background: #334155;
    color: #94a3b8;
    border: none;
    border-radius: 8px;
    cursor: pointer;
  }

  /* Scan Result */
  .scan-result {
    padding: 1rem;
  }

  .result-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }

  .scan-result h3 {
    margin: 0 0 1rem;
    color: #22c55e;
  }

  .result-details {
    background: #0f172a;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    text-align: left;
  }

  .result-details p {
    margin: 0.25rem 0;
    color: #94a3b8;
  }

  .result-details strong {
    color: #e2e8f0;
  }

  .result-actions {
    display: flex;
    gap: 1rem;
  }

  .btn-primary {
    flex: 1;
    padding: 0.875rem;
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .btn-primary:hover {
    transform: translateY(-1px);
  }

  .btn-secondary {
    flex: 1;
    padding: 0.875rem;
    background: #334155;
    color: #e2e8f0;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
  }

  /* Quick Pay */
  .quick-pay-section {
    background: #1e293b;
    border-radius: 16px;
    padding: 1.5rem;
  }

  .quick-pay-section h3 {
    margin: 0 0 1rem;
    font-size: 1rem;
    color: #94a3b8;
  }

  .merchant-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
  }

  .merchant-quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .merchant-quick-btn:hover {
    border-color: #3b82f6;
    background: #1e293b;
  }

  .merchant-logo {
    font-size: 1.5rem;
  }

  .merchant-name {
    font-size: 0.75rem;
    color: #94a3b8;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
  }

  /* Receive Section */
  .receive-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .qr-card {
    background: linear-gradient(135deg, #1e3a5f 0%, #1e293b 100%);
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
  }

  .qr-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .user-id {
    font-size: 0.875rem;
    color: #64748b;
    font-family: monospace;
  }

  .badge {
    padding: 0.25rem 0.75rem;
    background: #22c55e;
    color: #0f172a;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .qr-display {
    background: white;
    padding: 1rem;
    border-radius: 12px;
    display: inline-block;
    margin-bottom: 1rem;
  }

  .qr-image {
    width: 200px;
    height: 200px;
    display: block;
  }

  .qr-placeholder {
    width: 200px;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 0.875rem;
  }

  .qr-amount-display {
    font-size: 2rem;
    font-weight: 700;
    color: #f8fafc;
  }

  .amount-currency {
    font-size: 1.5rem;
    margin-right: 0.25rem;
  }

  /* Generate Form */
  .generate-form {
    background: #1e293b;
    border-radius: 16px;
    padding: 1.5rem;
  }

  .generate-form h3 {
    margin: 0 0 1rem;
    font-size: 1rem;
    color: #94a3b8;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.5rem;
  }

  .form-group input {
    width: 100%;
    padding: 0.875rem 1rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 10px;
    color: #e2e8f0;
    font-size: 1rem;
    box-sizing: border-box;
  }

  .form-group input:focus {
    outline: none;
    border-color: #3b82f6;
  }

  .btn-generate {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .btn-generate:hover {
    transform: translateY(-1px);
  }

  /* Share Options */
  .share-options {
    background: #1e293b;
    border-radius: 16px;
    padding: 1.5rem;
  }

  .share-options h4 {
    margin: 0 0 1rem;
    font-size: 0.875rem;
    color: #64748b;
  }

  .share-buttons {
    display: flex;
    gap: 0.75rem;
  }

  .share-btn {
    flex: 1;
    padding: 0.75rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 8px;
    color: #94a3b8;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .share-btn:hover {
    border-color: #3b82f6;
    color: #e2e8f0;
  }

  /* Merchant Section */
  .search-bar {
    margin-bottom: 1rem;
  }

  .search-bar input {
    width: 100%;
    padding: 0.875rem 1rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 10px;
    color: #e2e8f0;
    font-size: 1rem;
    box-sizing: border-box;
  }

  .search-bar input:focus {
    outline: none;
    border-color: #3b82f6;
  }

  .category-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }

  .category-tab {
    padding: 0.5rem 1rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 20px;
    color: #94a3b8;
    font-size: 0.875rem;
    cursor: pointer;
    white-space: nowrap;
  }

  .category-tab.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
  }

  .merchants-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .merchant-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
  }

  .merchant-icon {
    font-size: 2rem;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0f172a;
    border-radius: 12px;
  }

  .merchant-info {
    flex: 1;
  }

  .merchant-info h4 {
    margin: 0;
    font-size: 1rem;
    color: #f8fafc;
  }

  .merchant-address {
    font-size: 0.75rem;
    color: #64748b;
  }

  .merchant-category {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    background: #334155;
    border-radius: 4px;
    font-size: 0.7rem;
    color: #94a3b8;
    margin-top: 0.25rem;
  }

  .pay-btn {
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  /* History Section */
  .filters {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .filter-group label {
    font-size: 0.7rem;
    color: #64748b;
    text-transform: uppercase;
  }

  .filter-group select,
  .filter-group input {
    padding: 0.5rem 0.75rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 8px;
    color: #e2e8f0;
    font-size: 0.875rem;
    min-width: 120px;
  }

  .clear-filters {
    align-self: flex-end;
    padding: 0.5rem 1rem;
    background: transparent;
    border: 1px solid #334155;
    border-radius: 8px;
    color: #64748b;
    font-size: 0.875rem;
    cursor: pointer;
  }

  .transactions-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .transaction-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #1e293b;
    border-radius: 12px;
  }

  .tx-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0f172a;
    border-radius: 10px;
    font-size: 1.25rem;
  }

  .tx-info {
    flex: 1;
  }

  .tx-title {
    font-weight: 500;
    color: #f8fafc;
    margin-bottom: 0.25rem;
  }

  .tx-meta {
    font-size: 0.75rem;
    color: #64748b;
  }

  .separator {
    margin: 0 0.5rem;
  }

  .tx-amount-group {
    text-align: right;
  }

  .tx-amount {
    display: block;
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .tx-amount.positive {
    color: #22c55e;
  }

  .tx-amount.negative {
    color: #f8fafc;
  }

  .tx-status {
    font-size: 0.7rem;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    text-transform: capitalize;
  }

  .status-completed {
    background: #166534;
    color: #4ade80;
  }

  .status-pending {
    background: #854d0e;
    color: #facc15;
  }

  .status-failed {
    background: #991b1b;
    color: #f87171;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 3rem;
    background: #1e293b;
    border-radius: 16px;
  }

  .empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    margin: 0 0 0.5rem;
    color: #f8fafc;
  }

  .empty-state p {
    color: #64748b;
  }

  /* Modal */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .modal {
    background: #1e293b;
    border-radius: 16px;
    width: 100%;
    max-width: 400px;
    animation: slideUp 0.3s;
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
    border-bottom: 1px solid #334155;
  }

  .modal-header h2 {
    margin: 0;
    font-size: 1.125rem;
    color: #f8fafc;
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

  .modal-body {
    padding: 1.5rem;
  }

  .modal-footer {
    display: flex;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #334155;
  }

  .btn-cancel {
    flex: 1;
    padding: 0.875rem;
    background: #334155;
    border: none;
    border-radius: 10px;
    color: #94a3b8;
    font-weight: 600;
    cursor: pointer;
  }

  .btn-confirm {
    flex: 2;
    padding: 0.875rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .btn-confirm:hover:not(:disabled) {
    transform: translateY(-1px);
  }

  .btn-confirm:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* QR Modal */
  .qr-modal-image {
    background: white;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
  }

  .qr-modal-image img {
    width: 100%;
    height: auto;
  }

  .qr-modal-info {
    text-align: center;
  }

  .modal-amount,
  .modal-note,
  .modal-userid {
    margin-bottom: 0.5rem;
  }

  .qr-modal-info .label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
  }

  .qr-modal-info .value {
    font-weight: 600;
    color: #f8fafc;
  }

  /* Payment Modal */
  .payment-merchant-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background: #0f172a;
    border-radius: 12px;
    margin-bottom: 1.5rem;
  }

  .merchant-logo-large {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
  }

  .merchant-name {
    font-weight: 600;
    color: #f8fafc;
  }

  .merchant-address {
    font-size: 0.75rem;
    color: #64748b;
  }

  .payment-summary {
    background: #0f172a;
    border-radius: 10px;
    padding: 1rem;
    margin: 1rem 0;
  }

  .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    color: #94a3b8;
  }

  .summary-row.total {
    border-top: 1px solid #334155;
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-weight: 600;
    color: #f8fafc;
  }

  .error-message {
    padding: 0.75rem;
    background: #7f1d1d;
    color: #fca5a5;
    border-radius: 8px;
    font-size: 0.875rem;
    margin-bottom: 1rem;
  }

  .balance-info {
    text-align: center;
    font-size: 0.875rem;
    color: #64748b;
  }

  .balance-info strong {
    color: #22c55e;
  }

  /* Payment Success */
  .payment-success {
    text-align: center;
    padding: 2rem;
  }

  .success-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .payment-success h3 {
    margin: 0 0 0.5rem;
    color: #22c55e;
  }

  .payment-success p {
    color: #94a3b8;
  }

  /* Responsive */
  @media (max-width: 600px) {
    .header-content {
      flex-direction: column;
      gap: 1rem;
      text-align: center;
    }

    .balance-display {
      text-align: center;
    }

    .stats-bar {
      flex-wrap: wrap;
      gap: 1rem;
    }

    .stat-divider {
      display: none;
    }

    .merchant-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .tabs {
      padding: 0.5rem;
    }

    .tab {
      padding: 0.5rem;
      min-width: 60px;
    }

    .tab span:last-child {
      font-size: 0.7rem;
    }
  }
</style>
