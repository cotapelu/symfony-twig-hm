<script lang="ts">
  import { onMount } from 'svelte';
  import { fade, slide } from 'svelte/transition';

  // Types
  type BillCategory = 'electricity' | 'water' | 'internet' | 'phone' | 'insurance' | 'other';
  type BillStatus = 'unpaid' | 'paid' | 'overdue' | 'pending';

  interface Bill {
    id: string;
    provider: string;
    type: string;
    category: BillCategory;
    amount: number;
    dueDate: string;
    status: BillStatus;
    period: string;
    accountNumber?: string;
    logo: string;
    autoPay: boolean;
  }

  interface PaymentRecord {
    id: string;
    billId: string;
    billName: string;
    provider: string;
    amount: number;
    paymentDate: string;
    paymentMethod: string;
    status: 'completed' | 'pending' | 'failed';
    transactionId: string;
  }

  interface BillSettings {
    notifications: boolean;
    emailAlerts: boolean;
    defaultPaymentMethod: string;
    autoPayEnabled: boolean;
  }

  // State
  let loading = true;
  let activeTab = 'bills';
  let bills: Bill[] = [];
  let paymentHistory: PaymentRecord[] = [];
  let settings: BillSettings = {
    notifications: true,
    emailAlerts: true,
    defaultPaymentMethod: 'wallet',
    autoPayEnabled: false
  };

  // Filters & Search
  let filterCategory = 'all';
  let filterStatus = 'all';
  let searchQuery = '';
  let sortBy = 'dueDate';

  // Modal states
  let showPaymentModal = false;
  let showBillDetailModal = false;
  let showSettingsModal = false;
  let showAddBillModal = false;

  // Selected items
  let selectedBill: Bill | null = null;
  let paymentAmount = '';
  let paymentMethod = 'wallet';
  let customAmount = '';

  // Form for adding new bill
  let newBillForm = {
    provider: '',
    type: '',
    category: 'other' as BillCategory,
    amount: '',
    dueDate: '',
    period: '',
    accountNumber: ''
  };

  // LocalStorage keys
  const STORAGE_KEYS = {
    bills: 'bills_data',
    payments: 'bills_payments',
    settings: 'bills_settings'
  };

  // Category icons mapping
  const categoryIcons: Record<BillCategory, string> = {
    electricity: '⚡',
    water: '💧',
    internet: '📶',
    phone: '📱',
    insurance: '🛡️',
    other: '📄'
  };

  // Category colors
  const categoryColors: Record<BillCategory, string> = {
    electricity: '#fbbf24',
    water: '#3b82f6',
    internet: '#8b5cf6',
    phone: '#10b981',
    insurance: '#f97316',
    other: '#6b7280'
  };

  onMount(() => {
    loadData();
  });

  function loadData() {
    loading = true;

    const storedBills = localStorage.getItem(STORAGE_KEYS.bills);
    const storedPayments = localStorage.getItem(STORAGE_KEYS.payments);
    const storedSettings = localStorage.getItem(STORAGE_KEYS.settings);

    if (storedBills) {
      bills = JSON.parse(storedBills);
    } else {
      // Initialize with sample bills
      bills = [
        {
          id: 'bill_001',
          provider: 'EVN',
          type: 'Electricity',
          category: 'electricity',
          amount: 450000,
          dueDate: '2026-02-15',
          status: 'unpaid',
          period: 'January 2026',
          accountNumber: 'EVN-12345678',
          logo: '⚡',
          autoPay: true
        },
        {
          id: 'bill_002',
          provider: 'SAVACO',
          type: 'Water',
          category: 'water',
          amount: 120000,
          dueDate: '2026-02-20',
          status: 'unpaid',
          period: 'January 2026',
          accountNumber: 'WATER-9876543',
          logo: '💧',
          autoPay: false
        },
        {
          id: 'bill_003',
          provider: 'VNPT',
          type: 'Internet',
          category: 'internet',
          amount: 299000,
          dueDate: '2026-02-25',
          status: 'paid',
          period: 'February 2026',
          accountNumber: 'VNPT-5555555',
          logo: '📶',
          autoPay: true
        },
        {
          id: 'bill_004',
          provider: 'Viettel',
          type: 'Phone',
          category: 'phone',
          amount: 350000,
          dueDate: '2026-02-28',
          status: 'unpaid',
          period: 'February 2026',
          accountNumber: 'VT-0987654321',
          logo: '📱',
          autoPay: false
        },
        {
          id: 'bill_005',
          provider: 'Bao Viet',
          type: 'Insurance',
          category: 'insurance',
          amount: 1200000,
          dueDate: '2026-03-01',
          status: 'unpaid',
          period: 'Q1 2026',
          accountNumber: 'BV-INS-2026',
          logo: '🛡️',
          autoPay: true
        },
        {
          id: 'bill_006',
          provider: 'FPT',
          type: 'Internet',
          category: 'internet',
          amount: 250000,
          dueDate: '2026-02-10',
          status: 'overdue',
          period: 'February 2026',
          accountNumber: 'FPT-7777777',
          logo: '📶',
          autoPay: false
        },
        {
          id: 'bill_007',
          provider: 'MobiFone',
          type: 'Phone',
          category: 'phone',
          amount: 200000,
          dueDate: '2026-02-18',
          status: 'paid',
          period: 'January 2026',
          accountNumber: 'MF-0909123456',
          logo: '📱',
          autoPay: false
        }
      ];
      saveData();
    }

    if (storedPayments) {
      paymentHistory = JSON.parse(storedPayments);
    } else {
      // Initialize with sample payment history
      paymentHistory = [
        {
          id: 'pay_001',
          billId: 'bill_003',
          billName: 'Internet',
          provider: 'VNPT',
          amount: 299000,
          paymentDate: '2026-02-01',
          paymentMethod: 'Wallet',
          status: 'completed',
          transactionId: 'TXN-2026020112345'
        },
        {
          id: 'pay_002',
          billId: 'bill_007',
          billName: 'Phone',
          provider: 'MobiFone',
          amount: 200000,
          paymentDate: '2026-01-28',
          paymentMethod: 'Bank Transfer',
          status: 'completed',
          transactionId: 'TXN-2026012812345'
        }
      ];
      saveData();
    }

    if (storedSettings) {
      settings = JSON.parse(storedSettings);
    }

    loading = false;
  }

  function saveData() {
    localStorage.setItem(STORAGE_KEYS.bills, JSON.stringify(bills));
    localStorage.setItem(STORAGE_KEYS.payments, JSON.stringify(paymentHistory));
    localStorage.setItem(STORAGE_KEYS.settings, JSON.stringify(settings));
  }

  // Computed values
  $: totalUnpaid = bills.filter(b => b.status === 'unpaid').reduce((sum, b) => sum + b.amount, 0);
  $: totalOverdue = bills.filter(b => b.status === 'overdue').reduce((sum, b) => sum + b.amount, 0);
  $: upcomingBills = bills
    .filter(b => b.status === 'unpaid' && new Date(b.dueDate) <= new Date(Date.now() + 7 * 24 * 60 * 60 * 1000))
    .sort((a, b) => new Date(a.dueDate).getTime() - new Date(b.dueDate).getTime());

  $: filteredBills = bills
    .filter(bill => {
      if (filterCategory !== 'all' && bill.category !== filterCategory) return false;
      if (filterStatus !== 'all' && bill.status !== filterStatus) return false;
      if (searchQuery) {
        const query = searchQuery.toLowerCase();
        return (
          bill.provider.toLowerCase().includes(query) ||
          bill.type.toLowerCase().includes(query) ||
          bill.accountNumber?.toLowerCase().includes(query)
        );
      }
      return true;
    })
    .sort((a, b) => {
      if (sortBy === 'dueDate') {
        return new Date(a.dueDate).getTime() - new Date(b.dueDate).getTime();
      } else if (sortBy === 'amount') {
        return b.amount - a.amount;
      } else if (sortBy === 'provider') {
        return a.provider.localeCompare(b.provider);
      }
      return 0;
    });

  // Helper functions
  function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
      minimumFractionDigits: 0
    }).format(amount);
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function getDaysUntilDue(dateStr: string): number {
    const due = new Date(dateStr);
    const now = new Date();
    return Math.ceil((due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
  }

  function getStatusColor(status: BillStatus): string {
    switch (status) {
      case 'paid': return 'status-paid';
      case 'unpaid': return 'status-unpaid';
      case 'overdue': return 'status-overdue';
      case 'pending': return 'status-pending';
      default: return '';
    }
  }

  function getStatusLabel(status: BillStatus): string {
    switch (status) {
      case 'paid': return 'Paid';
      case 'unpaid': return 'Unpaid';
      case 'overdue': return 'Overdue';
      case 'pending': return 'Pending';
      default: return status;
    }
  }

  // Modal handlers
  function openPaymentModal(bill: Bill) {
    selectedBill = bill;
    paymentAmount = bill.amount.toString();
    paymentMethod = settings.defaultPaymentMethod;
    showPaymentModal = true;
  }

  function closePaymentModal() {
    showPaymentModal = false;
    selectedBill = null;
  }

  function openBillDetail(bill: Bill) {
    selectedBill = bill;
    showBillDetailModal = true;
  }

  function closeBillDetail() {
    showBillDetailModal = false;
    selectedBill = null;
  }

  function openAddBillModal() {
    newBillForm = {
      provider: '',
      type: '',
      category: 'other',
      amount: '',
      dueDate: '',
      period: '',
      accountNumber: ''
    };
    showAddBillModal = true;
  }

  function closeAddBillModal() {
    showAddBillModal = false;
  }

  // Payment handler
  function processPayment() {
    if (!selectedBill) return;

    const amount = parseFloat(paymentAmount);
    if (isNaN(amount) || amount <= 0) return;

    // Create payment record
    const payment: PaymentRecord = {
      id: `pay_${Date.now()}`,
      billId: selectedBill.id,
      billName: selectedBill.type,
      provider: selectedBill.provider,
      amount: amount,
      paymentDate: new Date().toISOString().split('T')[0],
      paymentMethod: paymentMethod === 'wallet' ? 'Wallet' : 'Bank Transfer',
      status: 'completed',
      transactionId: `TXN-${Date.now()}`
    };

    paymentHistory = [payment, ...paymentHistory];

    // Update bill status
    bills = bills.map(b => 
      b.id === selectedBill!.id 
        ? { ...b, status: 'paid' as BillStatus }
        : b
    );

    saveData();
    closePaymentModal();
  }

  // Add new bill handler
  function addNewBill() {
    if (!newBillForm.provider || !newBillForm.amount || !newBillForm.dueDate) return;

    const bill: Bill = {
      id: `bill_${Date.now()}`,
      provider: newBillForm.provider,
      type: newBillForm.type || newBillForm.category,
      category: newBillForm.category,
      amount: parseFloat(newBillForm.amount),
      dueDate: newBillForm.dueDate,
      status: 'unpaid',
      period: newBillForm.period || newBillForm.dueDate.substring(0, 7),
      accountNumber: newBillForm.accountNumber,
      logo: categoryIcons[newBillForm.category],
      autoPay: false
    };

    bills = [bill, ...bills];
    saveData();
    closeAddBillModal();
  }

  // Settings handler
  function saveSettings() {
    saveData();
    showSettingsModal = false;
  }
</script>

<svelte:head>
  <title>Bills Payment - Hotel Booking App</title>
</svelte:head>

<div class="bills-page">
  <!-- Header -->
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <h1>📋 Bills Payment</h1>
        <p>Manage and pay your utility bills</p>
      </div>
      <div class="header-actions">
        <button class="btn-secondary" on:click={() => showSettingsModal = true}>
          ⚙️ Settings
        </button>
        <button class="btn-primary" on:click={openAddBillModal}>
          ➕ Add Bill
        </button>
      </div>
    </div>
  </header>

  <!-- Tabs -->
  <nav class="tabs">
    <button 
      class="tab" 
      class:active={activeTab === 'bills'} 
      on:click={() => activeTab = 'bills'}
    >
      📄 All Bills
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'upcoming'} 
      on:click={() => activeTab = 'upcoming'}
    >
      📅 Upcoming
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'history'} 
      on:click={() => activeTab = 'history'}
    >
      🕐 Payment History
    </button>
  </nav>

  <main class="main-content">
    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Loading bills...</p>
      </div>
    {:else}
      <!-- All Bills Tab -->
      {#if activeTab === 'bills'}
        <div class="bills-section" in:fade>
          <!-- Summary Cards -->
          <div class="summary-grid">
            <div class="summary-card total">
              <div class="summary-icon">💰</div>
              <div class="summary-content">
                <span class="summary-label">Total Unpaid</span>
                <span class="summary-value">{formatCurrency(totalUnpaid)}</span>
              </div>
            </div>
            <div class="summary-card overdue">
              <div class="summary-icon">⚠️</div>
              <div class="summary-content">
                <span class="summary-label">Overdue</span>
                <span class="summary-value">{formatCurrency(totalOverdue)}</span>
              </div>
            </div>
            <div class="summary-card pending">
              <div class="summary-icon">⏳</div>
              <div class="summary-content">
                <span class="summary-label">Pending</span>
                <span class="summary-value">{bills.filter(b => b.status === 'unpaid').length}</span>
              </div>
            </div>
            <div class="summary-card paid">
              <div class="summary-icon">✅</div>
              <div class="summary-content">
                <span class="summary-label">This Month</span>
                <span class="summary-value">{paymentHistory.filter(p => p.status === 'completed').length}</span>
              </div>
            </div>
          </div>

          <!-- Filters -->
          <div class="filters-bar">
            <div class="search-box">
              <span class="search-icon">🔍</span>
              <input 
                type="text" 
                placeholder="Search bills..." 
                bind:value={searchQuery}
              />
            </div>
            <div class="filter-group">
              <select bind:value={filterCategory}>
                <option value="all">All Categories</option>
                <option value="electricity">⚡ Electricity</option>
                <option value="water">💧 Water</option>
                <option value="internet">📶 Internet</option>
                <option value="phone">📱 Phone</option>
                <option value="insurance">🛡️ Insurance</option>
                <option value="other">📄 Other</option>
              </select>
              <select bind:value={filterStatus}>
                <option value="all">All Status</option>
                <option value="unpaid">Unpaid</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
                <option value="pending">Pending</option>
              </select>
              <select bind:value={sortBy}>
                <option value="dueDate">Sort by Due Date</option>
                <option value="amount">Sort by Amount</option>
                <option value="provider">Sort by Provider</option>
              </select>
            </div>
          </div>

          <!-- Bills List -->
          <div class="bills-list">
            {#if filteredBills.length === 0}
              <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No bills found</h3>
                <p>Try adjusting your filters or add a new bill</p>
              </div>
            {:else}
              {#each filteredBills as bill}
                <div class="bill-card" class:paid={bill.status === 'paid'} class:overdue={bill.status === 'overdue'}>
                  <div class="bill-icon" style="background: {categoryColors[bill.category]}20; color: {categoryColors[bill.category]}">
                    {bill.logo}
                  </div>
                  <div class="bill-info" on:click={() => openBillDetail(bill)}>
                    <div class="bill-header">
                      <h3>{bill.provider}</h3>
                      <span class="bill-status {getStatusColor(bill.status)}">{getStatusLabel(bill.status)}</span>
                    </div>
                    <p class="bill-type">{bill.type}</p>
                    <div class="bill-meta">
                      <span class="bill-period">📅 {bill.period}</span>
                      {#if bill.accountNumber}
                        <span class="bill-account"># {bill.accountNumber}</span>
                      {/if}
                    </div>
                    <div class="bill-due" class:due-soon={getDaysUntilDue(bill.dueDate) <= 3 && bill.status === 'unpaid'} class:overdue={bill.status === 'overdue'}>
                      {#if bill.status === 'overdue'}
                        ⚠️ Overdue by {Math.abs(getDaysUntilDue(bill.dueDate))} days
                      {:else if bill.status === 'paid'}
                        ✅ Paid on {formatDate(paymentHistory.find(p => p.billId === bill.id)?.paymentDate || '')}
                      {:else}
                        Due in {getDaysUntilDue(bill.dueDate)} days ({formatDate(bill.dueDate)})
                      {/if}
                    </div>
                  </div>
                  <div class="bill-actions">
                    <div class="bill-amount">{formatCurrency(bill.amount)}</div>
                    {#if bill.autoPay}
                      <span class="autopay-badge">🤖 Auto-pay</span>
                    {/if}
                    {#if bill.status === 'unpaid' || bill.status === 'overdue'}
                      <button class="pay-btn" on:click={() => openPaymentModal(bill)}>
                        Pay Now
                      </button>
                    {:else}
                      <button class="view-btn" on:click={() => openBillDetail(bill)}>
                        View Details
                      </button>
                    {/if}
                  </div>
                </div>
              {/each}
            {/if}
          </div>
        </div>
      {/if}

      <!-- Upcoming Tab -->
      {#if activeTab === 'upcoming'}
        <div class="upcoming-section" in:fade>
          <div class="section-header">
            <h2>📅 Upcoming Bills</h2>
            <span class="badge">{upcomingBills.length} bills due this week</span>
          </div>

          {#if upcomingBills.length === 0}
            <div class="empty-state">
              <div class="empty-icon">🎉</div>
              <h3>No upcoming bills</h3>
              <p>You're all caught up! No bills due in the next 7 days.</p>
            </div>
          {:else}
            <div class="upcoming-list">
              {#each upcomingBills as bill, index}
                <div class="upcoming-card" style="animation-delay: {index * 0.1}s">
                  <div class="upcoming-rank">{index + 1}</div>
                  <div class="upcoming-icon" style="background: {categoryColors[bill.category]}20; color: {categoryColors[bill.category]}">
                    {bill.logo}
                  </div>
                  <div class="upcoming-info">
                    <h3>{bill.provider}</h3>
                    <p>{bill.type} • {bill.period}</p>
                  </div>
                  <div class="upcoming-due">
                    <span class="days-left" class:urgent={getDaysUntilDue(bill.dueDate) <= 3}>
                      {getDaysUntilDue(bill.dueDate)} days
                    </span>
                    <span class="due-date">{formatDate(bill.dueDate)}</span>
                  </div>
                  <div class="upcoming-amount">{formatCurrency(bill.amount)}</div>
                  <button class="pay-btn-small" on:click={() => openPaymentModal(bill)}>
                    Pay
                  </button>
                </div>
              {/each}
            </div>

            <div class="upcoming-summary">
              <div class="summary-total">
                <span>Total Due This Week</span>
                <span class="total-amount">{formatCurrency(upcomingBills.reduce((sum, b) => sum + b.amount, 0))}</span>
              </div>
            </div>
          {/if}
        </div>
      {/if}

      <!-- Payment History Tab -->
      {#if activeTab === 'history'}
        <div class="history-section" in:fade>
          <div class="section-header">
            <h2>🕐 Payment History</h2>
            <span class="badge">{paymentHistory.length} transactions</span>
          </div>

          {#if paymentHistory.length === 0}
            <div class="empty-state">
              <div class="empty-icon">📜</div>
              <h3>No payment history</h3>
              <p>Your completed payments will appear here</p>
            </div>
          {:else}
            <div class="history-list">
              {#each paymentHistory as payment}
                <div class="history-card">
                  <div class="history-icon">
                    {#if payment.status === 'completed'}✅
                    {:else if payment.status === 'pending'}⏳
                    {:else}❌{/if}
                  </div>
                  <div class="history-info">
                    <h3>{payment.billName}</h3>
                    <p>{payment.provider} • {payment.paymentMethod}</p>
                    <span class="transaction-id">TXN: {payment.transactionId}</span>
                  </div>
                  <div class="history-details">
                    <div class="history-amount">{formatCurrency(payment.amount)}</div>
                    <span class="history-date">{formatDate(payment.paymentDate)}</span>
                    <span class="history-status {payment.status}">{payment.status}</span>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>
      {/if}
    {/if}
  </main>
</div>

<!-- Payment Modal -->
{#if showPaymentModal && selectedBill}
  <div class="modal-overlay" on:click={closePaymentModal}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>💳 Pay Bill</h2>
        <button class="close-btn" on:click={closePaymentModal}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="bill-preview-card">
          <div class="preview-icon" style="background: {categoryColors[selectedBill.category]}20; color: {categoryColors[selectedBill.category]}">
            {selectedBill.logo}
          </div>
          <div class="preview-info">
            <h3>{selectedBill.provider}</h3>
            <p>{selectedBill.type} • {selectedBill.period}</p>
            <span class="preview-account">#{selectedBill.accountNumber}</span>
          </div>
        </div>

        <div class="form-group">
          <label>Payment Amount</label>
          <div class="amount-options">
            <button 
              class="amount-btn" 
              class:selected={paymentAmount === selectedBill.amount.toString()}
              on:click={() => paymentAmount = selectedBill!.amount.toString()}
            >
              Full Amount ({formatCurrency(selectedBill.amount)})
            </button>
            <button 
              class="amount-btn"
              on:click={() => paymentAmount = customAmount || selectedBill!.amount.toString()}
            >
              Custom Amount
            </button>
          </div>
          <input 
            type="number" 
            bind:value={paymentAmount}
            min="1000"
            step="1000"
            placeholder="Enter amount"
          />
        </div>

        <div class="form-group">
          <label>Payment Method</label>
          <div class="payment-methods">
            <label class="method-option" class:selected={paymentMethod === 'wallet'}>
              <input type="radio" bind:group={paymentMethod} value="wallet" />
              <span class="method-icon">💰</span>
              <span class="method-name">Wallet Balance</span>
            </label>
            <label class="method-option" class:selected={paymentMethod === 'bank'}>
              <input type="radio" bind:group={paymentMethod} value="bank" />
              <span class="method-icon">🏦</span>
              <span class="method-name">Bank Transfer</span>
            </label>
          </div>
        </div>

        <div class="payment-summary">
          <div class="summary-row">
            <span>Bill Amount</span>
            <span>{formatCurrency(parseFloat(paymentAmount) || 0)}</span>
          </div>
          <div class="summary-row">
            <span>Processing Fee</span>
            <span>₫0</span>
          </div>
          <div class="summary-row total">
            <span>Total</span>
            <span>{formatCurrency(parseFloat(paymentAmount) || 0)}</span>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn-cancel" on:click={closePaymentModal}>Cancel</button>
        <button class="btn-confirm" on:click={processPayment}>
          Confirm Payment
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Bill Detail Modal -->
{#if showBillDetailModal && selectedBill}
  <div class="modal-overlay" on:click={closeBillDetail}>
    <div class="modal modal-large" on:click|stopPropagation>
      <div class="modal-header">
        <h2>{selectedBill.logo} {selectedBill.provider}</h2>
        <button class="close-btn" on:click={closeBillDetail}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="detail-section">
          <div class="detail-row">
            <span class="detail-label">Service Type</span>
            <span class="detail-value">{selectedBill.type}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Category</span>
            <span class="detail-value">{selectedBill.category}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Billing Period</span>
            <span class="detail-value">{selectedBill.period}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Account Number</span>
            <span class="detail-value">{selectedBill.accountNumber || 'N/A'}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Due Date</span>
            <span class="detail-value">{formatDate(selectedBill.dueDate)}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Amount Due</span>
            <span class="detail-value amount">{formatCurrency(selectedBill.amount)}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value">
              <span class="status-badge {getStatusColor(selectedBill.status)}">
                {getStatusLabel(selectedBill.status)}
              </span>
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Auto-Pay</span>
            <span class="detail-value">{selectedBill.autoPay ? '✅ Enabled' : '❌ Disabled'}</span>
          </div>
        </div>

        {#if selectedBill.status === 'unpaid'}
          <button class="btn-pay-full" on:click={() => { closeBillDetail(); openPaymentModal(selectedBill); }}>
            Pay {formatCurrency(selectedBill.amount)}
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}

<!-- Add Bill Modal -->
{#if showAddBillModal}
  <div class="modal-overlay" on:click={closeAddBillModal}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>➕ Add New Bill</h2>
        <button class="close-btn" on:click={closeAddBillModal}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="form-group">
          <label>Provider / Company</label>
          <input 
            type="text" 
            bind:value={newBillForm.provider}
            placeholder="e.g., EVN, VNPT, Viettel"
          />
        </div>

        <div class="form-group">
          <label>Service Type</label>
          <input 
            type="text" 
            bind:value={newBillForm.type}
            placeholder="e.g., Electricity, Internet"
          />
        </div>

        <div class="form-group">
          <label>Category</label>
          <select bind:value={newBillForm.category}>
            <option value="electricity">⚡ Electricity</option>
            <option value="water">💧 Water</option>
            <option value="internet">📶 Internet</option>
            <option value="phone">📱 Phone</option>
            <option value="insurance">🛡️ Insurance</option>
            <option value="other">📄 Other</option>
          </select>
        </div>

        <div class="form-group">
          <label>Amount (VND)</label>
          <input 
            type="number" 
            bind:value={newBillForm.amount}
            placeholder="Enter amount"
            min="1000"
          />
        </div>

        <div class="form-group">
          <label>Due Date</label>
          <input 
            type="date" 
            bind:value={newBillForm.dueDate}
          />
        </div>

        <div class="form-group">
          <label>Billing Period</label>
          <input 
            type="text" 
            bind:value={newBillForm.period}
            placeholder="e.g., January 2026"
          />
        </div>

        <div class="form-group">
          <label>Account Number (Optional)</label>
          <input 
            type="text" 
            bind:value={newBillForm.accountNumber}
            placeholder="Customer/Account ID"
          />
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn-cancel" on:click={closeAddBillModal}>Cancel</button>
        <button class="btn-confirm" on:click={addNewBill}>Add Bill</button>
      </div>
    </div>
  </div>
{/if}

<!-- Settings Modal -->
{#if showSettingsModal}
  <div class="modal-overlay" on:click={() => showSettingsModal = false}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>⚙️ Bill Settings</h2>
        <button class="close-btn" on:click={() => showSettingsModal = false}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="settings-section">
          <h3>Notifications</h3>
          <div class="setting-item">
            <div class="setting-info">
              <span class="setting-label">Push Notifications</span>
              <span class="setting-desc">Get notified before due dates</span>
            </div>
            <label class="toggle">
              <input type="checkbox" bind:checked={settings.notifications} />
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="setting-item">
            <div class="setting-info">
              <span class="setting-label">Email Alerts</span>
              <span class="setting-desc">Receive email for bill reminders</span>
            </div>
            <label class="toggle">
              <input type="checkbox" bind:checked={settings.emailAlerts} />
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="settings-section">
          <h3>Payment Preferences</h3>
          <div class="form-group">
            <label>Default Payment Method</label>
            <select bind:value={settings.defaultPaymentMethod}>
              <option value="wallet">💰 Wallet</option>
              <option value="bank">🏦 Bank Transfer</option>
            </select>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn-cancel" on:click={() => showSettingsModal = false}>Cancel</button>
        <button class="btn-confirm" on:click={saveSettings}>Save Settings</button>
      </div>
    </div>
  </div>
{/if}

<style>
  /* Base Styles */
  .bills-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  .header {
    background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
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
    color: #0891b2;
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
    color: #0891b2;
  }

  .tab.active {
    color: #0891b2;
    border-bottom-color: #0891b2;
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
    border-top-color: #0891b2;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Summary Grid */
  .summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .summary-card {
    background: white;
    padding: 1.25rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .summary-icon {
    font-size: 2rem;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
  }

  .summary-card.total .summary-icon {
    background: #fee04e33;
  }

  .summary-card.overdue .summary-icon {
    background: #fee2e2;
  }

  .summary-card.pending .summary-icon {
    background: #fef3c7;
  }

  .summary-card.paid .summary-icon {
    background: #dcfce7;
  }

  .summary-content {
    display: flex;
    flex-direction: column;
  }

  .summary-label {
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  .summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  /* Filters */
  .filters-bar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  .search-box {
    position: relative;
    flex: 1;
    min-width: 200px;
  }

  .search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
  }

  .search-box input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    box-sizing: border-box;
  }

  .search-box input:focus {
    outline: none;
    border-color: #0891b2;
  }

  .filter-group {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .filter-group select {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    background: white;
    cursor: pointer;
  }

  .filter-group select:focus {
    outline: none;
    border-color: #0891b2;
  }

  /* Bills List */
  .bills-list {
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
    transition: all 0.2s;
  }

  .bill-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .bill-card.paid {
    opacity: 0.75;
  }

  .bill-card.overdue {
    border-left: 4px solid #ef4444;
  }

  .bill-icon {
    font-size: 2rem;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    flex-shrink: 0;
  }

  .bill-info {
    flex: 1;
    cursor: pointer;
  }

  .bill-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
  }

  .bill-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #1e293b;
  }

  .bill-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
  }

  .status-paid {
    background: #dcfce7;
    color: #166534;
  }

  .status-unpaid {
    background: #fef3c7;
    color: #92400e;
  }

  .status-overdue {
    background: #fee2e2;
    color: #991b1b;
  }

  .status-pending {
    background: #e0e7ff;
    color: #4338ca;
  }

  .bill-type {
    margin: 0 0 0.5rem 0;
    color: #64748b;
    font-size: 0.9rem;
  }

  .bill-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #94a3b8;
    margin-bottom: 0.25rem;
  }

  .bill-due {
    font-size: 0.85rem;
    color: #64748b;
  }

  .bill-due.due-soon {
    color: #f59e0b;
    font-weight: 600;
  }

  .bill-due.overdue {
    color: #ef4444;
    font-weight: 600;
  }

  .bill-actions {
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
  }

  .bill-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .autopay-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    background: #e0e7ff;
    color: #4338ca;
    border-radius: 4px;
  }

  .pay-btn {
    padding: 0.625rem 1.25rem;
    background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
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

  .view-btn {
    padding: 0.625rem 1.25rem;
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
  }

  .view-btn:hover {
    background: #e2e8f0;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 12px;
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

  /* Upcoming Section */
  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .section-header h2 {
    margin: 0;
    color: #1e293b;
  }

  .badge {
    padding: 0.375rem 0.875rem;
    background: #e0f2fe;
    color: #0369a1;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
  }

  .upcoming-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .upcoming-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: white;
    padding: 1.25rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    animation: slideIn 0.3s ease forwards;
    opacity: 0;
  }

  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateX(-10px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  .upcoming-rank {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.875rem;
    color: #64748b;
  }

  .upcoming-icon {
    font-size: 1.5rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
  }

  .upcoming-info {
    flex: 1;
  }

  .upcoming-info h3 {
    margin: 0;
    font-size: 1rem;
    color: #1e293b;
  }

  .upcoming-info p {
    margin: 0.25rem 0 0;
    font-size: 0.8rem;
    color: #64748b;
  }

  .upcoming-due {
    text-align: right;
  }

  .days-left {
    display: block;
    font-weight: 600;
    color: #64748b;
    font-size: 0.9rem;
  }

  .days-left.urgent {
    color: #ef4444;
  }

  .due-date {
    font-size: 0.75rem;
    color: #94a3b8;
  }

  .upcoming-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    min-width: 100px;
    text-align: right;
  }

  .pay-btn-small {
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .upcoming-summary {
    margin-top: 1.5rem;
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .summary-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.1rem;
    color: #1e293b;
  }

  .total-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0891b2;
  }

  /* History Section */
  .history-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .history-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: white;
    padding: 1.25rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .history-icon {
    font-size: 1.5rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 10px;
  }

  .history-info {
    flex: 1;
  }

  .history-info h3 {
    margin: 0;
    font-size: 1rem;
    color: #1e293b;
  }

  .history-info p {
    margin: 0.25rem 0;
    font-size: 0.8rem;
    color: #64748b;
  }

  .transaction-id {
    font-size: 0.7rem;
    color: #94a3b8;
    font-family: monospace;
  }

  .history-details {
    text-align: right;
  }

  .history-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
  }

  .history-date {
    display: block;
    font-size: 0.8rem;
    color: #64748b;
  }

  .history-status {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    margin-top: 0.25rem;
  }

  .history-status.completed {
    color: #166534;
  }

  .history-status.pending {
    color: #92400e;
  }

  .history-status.failed {
    color: #991b1b;
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
    max-width: 440px;
    max-height: 90vh;
    overflow-y: auto;
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
    flex: 2;
    padding: 0.75rem;
    background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: white;
    cursor: pointer;
  }

  .btn-confirm:hover {
    opacity: 0.9;
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
    border-color: #0891b2;
    box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
  }

  /* Bill Preview Card */
  .bill-preview-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 1.5rem;
  }

  .preview-icon {
    font-size: 2rem;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
  }

  .preview-info h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #1e293b;
  }

  .preview-info p {
    margin: 0.25rem 0;
    font-size: 0.85rem;
    color: #64748b;
  }

  .preview-account {
    font-size: 0.75rem;
    color: #94a3b8;
    font-family: monospace;
  }

  /* Amount Options */
  .amount-options {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }

  .amount-btn {
    flex: 1;
    padding: 0.625rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.2s;
  }

  .amount-btn:hover {
    border-color: #0891b2;
  }

  .amount-btn.selected {
    border-color: #0891b2;
    background: #f0f9ff;
    color: #0891b2;
  }

  /* Payment Methods */
  .payment-methods {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .method-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .method-option:hover {
    border-color: #cbd5e1;
  }

  .method-option.selected {
    border-color: #0891b2;
    background: #f0f9ff;
  }

  .method-option input {
    display: none;
  }

  .method-icon {
    font-size: 1.5rem;
  }

  .method-name {
    font-weight: 500;
    color: #1e293b;
  }

  /* Payment Summary */
  .payment-summary {
    background: #f8fafc;
    border-radius: 10px;
    padding: 1rem;
    margin-top: 1rem;
  }

  .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.9rem;
    color: #64748b;
  }

  .summary-row.total {
    border-top: 1px solid #e2e8f0;
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-weight: 600;
    color: #1e293b;
    font-size: 1.1rem;
  }

  /* Detail Section */
  .detail-section {
    margin-bottom: 1.5rem;
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.875rem 0;
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

  .detail-value.amount {
    color: #0891b2;
    font-size: 1.25rem;
  }

  .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .btn-pay-full {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
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
    border-bottom: 1px solid #f1f5f9;
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
    background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
  }

  .toggle input:checked + .toggle-slider:before {
    transform: translateX(22px);
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

    .summary-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .bill-card {
      flex-direction: column;
      text-align: center;
    }

    .bill-actions {
      align-items: center;
      width: 100%;
    }

    .bill-meta {
      justify-content: center;
      flex-wrap: wrap;
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

    .upcoming-card {
      flex-wrap: wrap;
    }

    .history-card {
      flex-wrap: wrap;
    }

    .modal {
      margin: 1rem;
      max-width: calc(100% - 2rem);
    }
  }
</style>
