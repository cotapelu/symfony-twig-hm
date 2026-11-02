<script lang="ts">
  import { goto } from '$app/navigation';

  interface PopulationStats {
    total: number;
    male: number;
    female: number;
    urban: number;
    rural: number;
    growth: number;
  }

  interface AgeGroup {
    range: string;
    count: number;
    percentage: number;
  }

  interface PopulationRecord {
    id: string;
    name: string;
    birthDate: string;
    gender: string;
    address: string;
    householdId: string;
    status: string;
    type: string;
  }

  let searchQuery = '';
  let selectedFilter = 'all';
  let showAddModal = false;

  const stats: PopulationStats = {
    total: 15847,
    male: 7623,
    female: 8224,
    urban: 12450,
    rural: 3397,
    growth: 2.3
  };

  const ageGroups: AgeGroup[] = [
    { range: '0-17', count: 4120, percentage: 26 },
    { range: '18-35', count: 4235, percentage: 27 },
    { range: '36-55', count: 4120, percentage: 26 },
    { range: '56+', count: 3372, percentage: 21 }
  ];

  const records: PopulationRecord[] = [
    { id: '1', name: 'Nguyễn Văn A', birthDate: '1990-05-15', gender: 'Nam', address: '123 Đường Láng', householdId: 'HH001', status: 'Thường trú', type: 'Công dân' },
    { id: '2', name: 'Trần Thị B', birthDate: '1985-08-20', gender: 'Nữ', address: '456 Nguyễn Trãi', householdId: 'HH002', status: 'Thường trú', type: 'Công dân' },
    { id: '3', name: 'Lê Văn C', birthDate: '2010-03-10', gender: 'Nam', address: '789 Lý Thường Kiệt', householdId: 'HH003', status: 'Tạm trú', type: 'Trẻ em' },
    { id: '4', name: 'Phạm Thị D', birthDate: '1978-11-25', gender: 'Nữ', address: '321 Hoàng Quốc Việt', householdId: 'HH001', status: 'Thường trú', type: 'Công dân' },
    { id: '5', name: 'Hoàng Văn E', birthDate: '1955-07-08', gender: 'Nam', address: '654 Cầu Giấy', householdId: 'HH004', status: 'Thường trú', type: 'Người cao tuổi' }
  ];

  function navigateTo(path: string) {
    goto(path);
  }

  function handleSearch() {
    console.log('Searching:', searchQuery);
  }
</script>

<svelte:head>
  <title>Population Management - Phường/Xã Super App</title>
</svelte:head>

<div class="page-container">
  <header class="page-header">
    <div class="header-content">
      <button class="back-btn" on:click={() => navigateTo('/government')}>
        ← Back
      </button>
      <div>
        <h1>👥 Population Management</h1>
        <p class="subtitle">Quản lý dân số - Thống kê và theo dõi cư dân</p>
      </div>
    </div>
  </header>

  <div class="content">
    <section class="stats-section">
      <h2>📊 Population Statistics</h2>
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-icon">👥</div>
          <div class="stat-info">
            <span class="stat-value">{stats.total.toLocaleString()}</span>
            <span class="stat-label">Total Population</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">♂️</div>
          <div class="stat-info">
            <span class="stat-value">{stats.male.toLocaleString()}</span>
            <span class="stat-label">Male</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">♀️</div>
          <div class="stat-info">
            <span class="stat-value">{stats.female.toLocaleString()}</span>
            <span class="stat-label">Female</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🏙️</div>
          <div class="stat-info">
            <span class="stat-value">{stats.urban.toLocaleString()}</span>
            <span class="stat-label">Urban</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🏡</div>
          <div class="stat-info">
            <span class="stat-value">{stats.rural.toLocaleString()}</span>
            <span class="stat-label">Rural</span>
          </div>
        </div>
        <div class="stat-card highlight">
          <div class="stat-icon">📈</div>
          <div class="stat-info">
            <span class="stat-value">+{stats.growth}%</span>
            <span class="stat-label">Annual Growth</span>
          </div>
        </div>
      </div>
    </section>

    <section class="age-distribution">
      <h2>📊 Age Distribution</h2>
      <div class="age-chart">
        {#each ageGroups as group}
          <div class="age-bar">
            <span class="age-range">{group.range}</span>
            <div class="bar-container">
              <div class="bar" style="width: {group.percentage}%"></div>
            </div>
            <span class="age-count">{group.count.toLocaleString()}</span>
          </div>
        {/each}
      </div>
    </section>

    <section class="search-section">
      <h2>🔍 Search Population</h2>
      <div class="search-box">
        <input 
          type="text" 
          bind:value={searchQuery}
          placeholder="Search by name, ID, address..."
          on:keyup={(e) => e.key === 'Enter' && handleSearch()}
        />
        <select bind:value={selectedFilter}>
          <option value="all">All Types</option>
          <option value="permanent">Thường trú</option>
          <option value="temporary">Tạm trú</option>
          <option value="elderly">Người cao tuổi</option>
          <option value="children">Trẻ em</option>
        </select>
        <button class="btn-primary" on:click={handleSearch}>Search</button>
      </div>
    </section>

    <section class="records-section">
      <div class="section-header">
        <h2>📋 Population Records</h2>
        <button class="btn-primary" on:click={() => showAddModal = true}>
          + Add New
        </button>
      </div>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Birth Date</th>
              <th>Gender</th>
              <th>Address</th>
              <th>Household ID</th>
              <th>Status</th>
              <th>Type</th>
            </tr>
          </thead>
          <tbody>
            {#each records as record}
              <tr>
                <td>{record.name}</td>
                <td>{record.birthDate}</td>
                <td>{record.gender}</td>
                <td>{record.address}</td>
                <td>{record.householdId}</td>
                <td><span class="status-badge {record.status === 'Thường trú' ? 'active' : 'pending'}">{record.status}</span></td>
                <td>{record.type}</td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>

    <section class="actions-section">
      <h2>⚡ Quick Actions</h2>
      <div class="actions-grid">
        <button class="action-card" on:click={() => navigateTo('/household')}>
          <div class="action-icon">🏠</div>
          <span>Household Management</span>
        </button>
        <button class="action-card" on:click={() => navigateTo('/civil')}>
          <div class="action-icon">📋</div>
          <span>Civil Registry</span>
        </button>
        <button class="action-card" on:click={() => navigateTo('/elderly')}>
          <div class="action-icon">👴</div>
          <span>Elderly Care</span>
        </button>
        <button class="action-card" on:click={() => navigateTo('/complaints')}>
          <div class="action-icon">📝</div>
          <span>Complaints</span>
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
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
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

  .back-btn:hover {
    background: rgba(255,255,255,0.3);
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

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
  }

  .stat-card {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .stat-card.primary {
    background: #eff6ff;
    border: 2px solid #3b82f6;
  }

  .stat-card.highlight {
    background: #ecfdf5;
    border: 2px solid #10b981;
  }

  .stat-icon {
    font-size: 2rem;
  }

  .stat-info {
    display: flex;
    flex-direction: column;
  }

  .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .stat-label {
    font-size: 0.875rem;
    color: #64748b;
  }

  .age-chart {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .age-bar {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .age-range {
    width: 60px;
    font-weight: 600;
    color: #1e293b;
  }

  .bar-container {
    flex: 1;
    height: 24px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
  }

  .bar {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
    border-radius: 4px;
    transition: width 0.3s;
  }

  .age-count {
    width: 80px;
    text-align: right;
    color: #64748b;
  }

  .search-box {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .search-box input {
    flex: 1;
    min-width: 250px;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
  }

  .search-box select {
    padding: 0.75rem 1rem;
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
    transition: background 0.2s;
  }

  .btn-primary:hover {
    background: #2563eb;
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .section-header h2 {
    margin: 0;
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
    background: #eff6ff;
    border-color: #3b82f6;
  }

  .action-icon {
    font-size: 2rem;
  }

  @media (max-width: 768px) {
    .header-content {
      flex-direction: column;
      align-items: flex-start;
    }

    .content {
      padding: 1rem;
    }

    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
</style>
