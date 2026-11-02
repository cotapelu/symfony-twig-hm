<script lang="ts">
  import { goto } from '$app/navigation';

  interface Transaction {
    id: string;
    type: string;
    category: string;
    amount: number;
    date: string;
    status: string;
  }

  interface BudgetItem {
    category: string;
    allocated: number;
    spent: number;
    remaining: number;
  }

  let selectedMonth = '2026-02';

  const transactions: Transaction[] = [
    { id: '1', type: 'Thu', category: 'Thuế', amount: 150000000, date: '2026-02-10', status: 'Hoàn thành' },
    { id: '2', type: 'Chi', category: 'Xây dựng cơ bản', amount: 85000000, date: '2026-02-08', status: 'Hoàn thành' },
    { id: '3', type: 'Thu', category: 'Phí dịch vụ', amount: 25000000, date: '2026-02-05', status: 'Hoàn thành' },
    { id: '4', type: 'Chi', category: 'Chi trả lương', amount: 120000000, date: '2026-02-01', status: 'Hoàn thành' },
    { id: '5', type: 'Chi', category: 'Phát triển CNTT', amount: 35000000, date: '2026-01-28', status: 'Hoàn thành' }
  ];

  const budgetItems: BudgetItem[] = [
    { category: 'Xây dựng cơ bản', allocated: 500000000, spent: 325000000, remaining: 175000000 },
    { category: 'Chi phí hành chính', allocated: 300000000, spent: 245000000, remaining: 55000000 },
    { category: 'Giáo dục', allocated: 200000000, spent: 180000000, remaining: 20000000 },
    { category: 'Y tế', allocated: 150000000, spent: 125000000, remaining: 25000000 },
    { category: 'Văn hóa - Thể thao', allocated: 100000000, spent: 75000000, remaining: 25000000 },
    { category: 'An ninh - Quốc phòng', allocated: 80000000, spent: 65000000, remaining: 15000000 }
  ];

  const summary = {
    totalIncome: 525000000,
    totalExpense: 485000000,
    balance: 40000000,
    collected: 175000000,
    pending: 50000000
  };

  function navigateTo(path: string) {
    goto(path);
  }

  function formatCurrency(value: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
  }
</script>

<svelte:head>
  <title>Finance & Budget - Phường/Xã Super App</title>
</svelte:head>

<div class="page-container">
  <header class="page-header">
    <div class="header-content">
      <button class="back-btn" on:click={() => navigateTo('/government')}>
        ← Back
      </button>
      <div>
        <h1>💰 Finance & Budget</h1>
        <p class="subtitle">Tài chính - Ngân sách và quản lý thu chi</p>
      </div>
    </div>
  </header>

  <div class="content">
    <section class="controls-section">
      <div class="month-selector">
        <label>Select Period:</label>
        <input type="month" bind:value={selectedMonth} />
      </div>
      <button class="btn-primary">+ New Transaction</button>
    </section>

    <section class="summary-section">
      <h2>💵 Financial Summary</h2>
      <div class="summary-grid">
        <div class="summary-card income">
          <div class="summary-icon">📥</div>
          <div class="summary-info">
            <span class="summary-label">Total Income</span>
            <span class="summary-value">{formatCurrency(summary.totalIncome)}</span>
          </div>
        </div>
        <div class="summary-card expense">
          <div class="summary-icon">📤</div>
          <div class="summary-info">
            <span class="summary-label">Total Expenses</span>
            <span class="summary-value">{formatCurrency(summary.totalExpense)}</span>
          </div>
        </div>
        <div class="summary-card balance">
          <div class="summary-icon">💼</div>
          <div class="summary-info">
            <span class="summary-label">Balance</span>
            <span class="summary-value">{formatCurrency(summary.balance)}</span>
          </div>
        </div>
        <div class="summary-card">
          <div class="summary-icon">✅</div>
          <div class="summary-info">
            <span class="summary-label">Collected</span>
            <span class="summary-value">{formatCurrency(summary.collected)}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="budget-section">
      <h2>📊 Budget Allocation</h2>
      <div class="budget-table">
        <table>
          <thead>
            <tr>
              <th>Category</th>
              <th>Allocated</th>
              <th>Spent</th>
              <th>Remaining</th>
              <th>Progress</th>
            </tr>
          </thead>
          <tbody>
            {#each budgetItems as item}
              {@const percentage = (item.spent / item.allocated) * 100}
              <tr>
                <td>{item.category}</td>
                <td>{formatCurrency(item.allocated)}</td>
                <td>{formatCurrency(item.spent)}</td>
                <td class:negative={item.remaining < 0}>{formatCurrency(item.remaining)}</td>
                <td>
                  <div class="progress-bar">
                    <div class="progress" style="width: {percentage}%"></div>
                  </div>
                  <span class="progress-text">{percentage.toFixed(1)}%</span>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>

    <section class="transactions-section">
      <div class="section-header">
        <h2>📋 Recent Transactions</h2>
        <button class="btn-secondary">View All</button>
      </div>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Category</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {#each transactions as tx}
              <tr>
                <td>{tx.date}</td>
                <td><span class="type-badge {tx.type === 'Thu' ? 'income' : 'expense'}">{tx.type}</span></td>
                <td>{tx.category}</td>
                <td class:income={tx.type === 'Thu'} class:expense={tx.type === 'Chi'}>{formatCurrency(tx.amount)}</td>
                <td><span class="status-badge completed">{tx.status}</span></td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>

    <section class="actions-section">
      <h2>⚡ Quick Actions</h2>
      <div class="actions-grid">
        <button class="action-card">
          <div class="action-icon">📄</div>
          <span>Tax Collection</span>
        </button>
        <button class="action-card">
          <div class="action-icon">📊</div>
          <span>Budget Report</span>
        </button>
        <button class="action-card">
          <div class="action-icon">📈</div>
          <span>Financial Statement</span>
        </button>
        <button class="action-card">
          <div class="action-icon">🧾</div>
          <span>Invoice Management</span>
        </button>
      </div>
    </section>
  </div>
</div>

<style>
  .page-container {
    min-height: 100vh;
    background: #f8fafc;
  }

  .page-header {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: white;
    padding: 2rem;
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .back-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
  }

  h1 {
    margin: 0;
    font-size: 1.75rem;
  }

  .subtitle {
    margin: 0.5rem 0 0;
    opacity: 0.9;
  }

  .content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  h2 {
    margin: 0 0 1rem;
    color: #1e293b;
    font-size: 1.25rem;
  }

  .controls-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .month-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .month-selector input {
    padding: 0.5rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
  }

  .btn-primary {
    background: #10b981;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .btn-secondary {
    background: #f1f5f9;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
  }

  .summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
  }

  .summary-card {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .summary-card.income {
    background: #ecfdf5;
    border: 2px solid #10b981;
  }

  .summary-card.expense {
    background: #fef2f2;
    border: 2px solid #ef4444;
  }

  .summary-card.balance {
    background: #eff6ff;
    border: 2px solid #3b82f6;
  }

  .summary-icon {
    font-size: 2rem;
  }

  .summary-info {
    display: flex;
    flex-direction: column;
  }

  .summary-label {
    font-size: 0.875rem;
    color: #64748b;
  }

  .summary-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
  }

  .table-container {
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
  }

  th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e293b;
  }

  .type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .type-badge.income {
    background: #dcfce7;
    color: #166534;
  }

  .type-badge.expense {
    background: #fee2e2;
    color: #991b1b;
  }

  .income {
    color: #10b981;
    font-weight: 600;
  }

  .expense {
    color: #ef4444;
    font-weight: 600;
  }

  .negative {
    color: #ef4444;
  }

  .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .status-badge.completed {
    background: #dcfce7;
    color: #166534;
  }

  .progress-bar {
    width: 100px;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    display: inline-block;
    vertical-align: middle;
    margin-right: 0.5rem;
  }

  .progress {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
    border-radius: 4px;
  }

  .progress-text {
    font-size: 0.75rem;
    color: #64748b;
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }

  .action-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
  }

  .action-card:hover {
    background: #ecfdf5;
    border-color: #10b981;
  }

  .action-icon {
    font-size: 2rem;
  }

  @media (max-width: 768px) {
    .content {
      padding: 1rem;
    }
  }
</style>
