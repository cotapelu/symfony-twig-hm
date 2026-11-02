<script lang="ts">
  import { goto } from '$app/navigation';

  interface Report {
    id: string;
    title: string;
    category: string;
    period: string;
    status: string;
    createdAt: string;
  }

  interface StatCard {
    title: string;
    value: string;
    change: string;
    trend: 'up' | 'down' | 'neutral';
  }

  let selectedPeriod = 'month';

  const stats: StatCard[] = [
    { title: 'Total Population', value: '15,847', change: '+2.3%', trend: 'up' },
    { title: 'Households', value: '4,521', change: '+1.8%', trend: 'up' },
    { title: 'Active TTHC', value: '156', change: '+5.2%', trend: 'up' },
    { title: 'Pending Documents', value: '234', change: '-12.5%', trend: 'down' },
    { title: 'Revenue', value: '2.5B VND', change: '+8.7%', trend: 'up' },
    { title: 'Satisfaction', value: '94.2%', change: '+1.5%', trend: 'up' }
  ];

  const recentReports: Report[] = [
    { id: '1', title: 'Báo cáo dân số tháng 2/2026', category: 'Population', period: 'February 2026', status: 'Completed', createdAt: '2026-02-14' },
    { id: '2', title: 'Báo cáo TTHC quý 1/2026', category: 'Services', period: 'Q1 2026', status: 'In Progress', createdAt: '2026-02-10' },
    { id: '3', title: 'Báo cáo an ninh trật tự', category: 'Security', period: 'January 2026', status: 'Completed', createdAt: '2026-02-01' },
    { id: '4', title: 'Báo cáo tài chính năm 2025', category: 'Finance', period: '2025', status: 'Completed', createdAt: '2026-01-15' },
    { id: '5', title: 'Báo cáo môi trường', category: 'Environment', period: 'Q4 2025', status: 'Completed', createdAt: '2026-01-05' }
  ];

  const chartData = {
    population: [
      { month: 'Sep', value: 15200 },
      { month: 'Oct', value: 15350 },
      { month: 'Nov', value: 15500 },
      { month: 'Dec', value: 15650 },
      { month: 'Jan', value: 15800 },
      { month: 'Feb', value: 15847 }
    ],
    services: [
      { month: 'Sep', value: 120 },
      { month: 'Oct', value: 135 },
      { month: 'Nov', value: 142 },
      { month: 'Dec', value: 150 },
      { month: 'Jan', value: 155 },
      { month: 'Feb', value: 156 }
    ]
  };

  function navigateTo(path: string) {
    goto(path);
  }
</script>

<svelte:head>
  <title>Reports & Analytics - Phường/Xã Super App</title>
</svelte:head>

<div class="page-container">
  <header class="page-header">
    <div class="header-content">
      <button class="back-btn" on:click={() => navigateTo('/government')}>
        ← Back
      </button>
      <div>
        <h1>📊 Reports & Analytics</h1>
        <p class="subtitle">Báo cáo và phân tích - Thống kê hành chính</p>
      </div>
    </div>
  </header>

  <div class="content">
    <section class="controls-section">
      <div class="period-selector">
        <label>Time Period:</label>
        <select bind:value={selectedPeriod}>
          <option value="week">This Week</option>
          <option value="month">This Month</option>
          <option value="quarter">This Quarter</option>
          <option value="year">This Year</option>
        </select>
      </div>
      <button class="btn-primary">+ Generate Report</button>
    </section>

    <section class="stats-section">
      <h2>📈 Key Metrics</h2>
      <div class="stats-grid">
        {#each stats as stat}
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-title">{stat.title}</span>
              <span class="stat-change {stat.trend}">
                {stat.change}
                {#if stat.trend === 'up'}↑{:else if stat.trend === 'down'}↓{/if}
              </span>
            </div>
            <span class="stat-value">{stat.value}</span>
          </div>
        {/each}
      </div>
    </section>

    <section class="charts-section">
      <div class="chart-card">
        <h3>📊 Population Growth</h3>
        <div class="chart">
          {#each chartData.population as item, i}
            <div class="bar-container">
              <div class="bar" style="height: {(item.value / 16000) * 100}%">
                <span class="bar-value">{item.value.toLocaleString()}</span>
              </div>
              <span class="bar-label">{item.month}</span>
            </div>
          {/each}
        </div>
      </div>
      <div class="chart-card">
        <h3>📈 TTHC Services</h3>
        <div class="chart">
          {#each chartData.services as item}
            <div class="bar-container">
              <div class="bar" style="height: {(item.value / 200) * 100}%">
                <span class="bar-value">{item.value}</span>
              </div>
              <span class="bar-label">{item.month}</span>
            </div>
          {/each}
        </div>
      </div>
    </section>

    <section class="reports-section">
      <div class="section-header">
        <h2>📄 Recent Reports</h2>
        <button class="btn-secondary">View All</button>
      </div>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Category</th>
              <th>Period</th>
              <th>Created</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            {#each recentReports as report}
              <tr>
                <td>{report.title}</td>
                <td><span class="category-badge">{report.category}</span></td>
                <td>{report.period}</td>
                <td>{report.createdAt}</td>
                <td><span class="status-badge {report.status === 'Completed' ? 'active' : 'pending'}">{report.status}</span></td>
                <td><button class="btn-small">Download</button></td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>

    <section class="categories-section">
      <h2>📁 Report Categories</h2>
      <div class="categories-grid">
        <button class="category-card">
          <div class="category-icon">👥</div>
          <span>Population</span>
        </button>
        <button class="category-card">
          <div class="category-icon">🏠</div>
          <span>Household</span>
        </button>
        <button class="category-card">
          <div class="category-icon">📋</div>
          <span>Services</span>
        </button>
        <button class="category-card">
          <div class="category-icon">🛡️</div>
          <span>Security</span>
        </button>
        <button class="category-card">
          <div class="category-icon">🌿</div>
          <span>Environment</span>
        </button>
        <button class="category-card">
          <div class="category-icon">💰</div>
          <span>Finance</span>
        </button>
        <button class="category-card">
          <div class="category-icon">🏗️</div>
          <span>Construction</span>
        </button>
        <button class="category-card">
          <div class="category-icon">🎭</div>
          <span>Culture</span>
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
    background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
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

  h3 {
    margin: 0 0 1rem;
    color: #1e293b;
    font-size: 1rem;
  }

  .controls-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .period-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .period-selector select {
    padding: 0.5rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    background: white;
  }

  .btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .btn-secondary {
    background: #f1f5f9;
    color: #1e293b;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
  }

  .btn-small {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
  }

  .stat-card {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
  }

  .stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .stat-title {
    font-size: 0.875rem;
    color: #64748b;
  }

  .stat-change {
    font-size: 0.75rem;
    font-weight: 600;
  }

  .stat-change.up {
    color: #10b981;
  }

  .stat-change.down {
    color: #ef4444;
  }

  .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
  }

  .chart-card {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 8px;
  }

  .chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    height: 200px;
    gap: 0.5rem;
  }

  .bar-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    height: 100%;
  }

  .bar {
    width: 100%;
    background: linear-gradient(180deg, #3b82f6, #60a5fa);
    border-radius: 4px 4px 0 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 20px;
    transition: height 0.3s;
  }

  .bar-value {
    font-size: 0.625rem;
    color: white;
    padding: 2px;
  }

  .bar-label {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #64748b;
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
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

  .category-badge {
    background: #e0f2fe;
    color: #0369a1;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .status-badge.active {
    background: #dcfce7;
    color: #166534;
  }

  .status-badge.pending {
    background: #fef3c7;
    color: #92400e;
  }

  .categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
  }

  .category-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 1rem;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
  }

  .category-card:hover {
    background: #eff6ff;
    border-color: #3b82f6;
  }

  .category-icon {
    font-size: 1.5rem;
  }

  @media (max-width: 768px) {
    .content {
      padding: 1rem;
    }

    .charts-section {
      grid-template-columns: 1fr;
    }
  }
</style>
