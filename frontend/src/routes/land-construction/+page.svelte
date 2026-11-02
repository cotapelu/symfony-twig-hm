<script lang="ts">
  import { goto } from '$app/navigation';

  interface LandRecord {
    id: string;
    plotNumber: string;
    owner: string;
    area: number;
    address: string;
    type: string;
    status: string;
  }

  interface ConstructionPermit {
    id: string;
    applicant: string;
    address: string;
    permitType: string;
    area: number;
    status: string;
    submittedDate: string;
  }

  let searchQuery = '';
  let selectedFilter = 'all';

  const landRecords: LandRecord[] = [
    { id: '1', plotNumber: 'LĐ-001', owner: 'Nguyễn Văn A', area: 150.5, address: '123 Đường Láng', type: 'Đất ở', status: 'Đã cấp GCN' },
    { id: '2', plotNumber: 'LĐ-002', owner: 'Trần Thị B', area: 200.0, address: '456 Nguyễn Trãi', type: 'Đất nông nghiệp', status: 'Đã cấp GCN' },
    { id: '3', plotNumber: 'LĐ-003', owner: 'Lê Văn C', area: 120.0, address: '789 Lý Thường Kiệt', type: 'Đất ở', status: 'Chờ cấp' },
    { id: '4', plotNumber: 'LĐ-004', owner: 'Phạm Thị D', area: 180.0, address: '321 Hoàng Quốc Việt', type: 'Đất ở', status: 'Đã cấp GCN' },
    { id: '5', plotNumber: 'LĐ-005', owner: 'Hoàng Văn E', area: 250.0, address: '654 Cầu Giấy', type: 'Đất phi nông nghiệp', status: 'Đã cấp GCN' }
  ];

  const permits: ConstructionPermit[] = [
    { id: '1', applicant: 'Nguyễn Văn A', address: '123 Đường Láng', permitType: 'Xây dựng mới', area: 150, status: 'Đã duyệt', submittedDate: '2026-01-15' },
    { id: '2', applicant: 'Trần Thị B', address: '456 Nguyễn Trãi', permitType: 'Cải tạo', area: 80, status: 'Đang xử lý', submittedDate: '2026-02-01' },
    { id: '3', applicant: 'Lê Văn C', address: '789 Lý Thường Kiệt', permitType: 'Xây dựng mới', area: 200, status: 'Chờ duyệt', submittedDate: '2026-02-10' }
  ];

  function navigateTo(path: string) {
    goto(path);
  }

  function handleSearch() {
    console.log('Searching:', searchQuery);
  }
</script>

<svelte:head>
  <title>Land & Construction - Phường/Xã Super App</title>
</svelte:head>

<div class="page-container">
  <header class="page-header">
    <div class="header-content">
      <button class="back-btn" on:click={() => navigateTo('/government')}>
        ← Back
      </button>
      <div>
        <h1>🏗️ Land & Construction</h1>
        <p class="subtitle">Quản lý đất đai và xây dựng - Cấp phép và giám sát</p>
      </div>
    </div>
  </header>

  <div class="content">
    <section class="stats-section">
      <h2>📊 Statistics</h2>
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-icon">📐</div>
          <div class="stat-info">
            <span class="stat-value">1,245</span>
            <span class="stat-label">Total Land Plots</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📜</div>
          <div class="stat-info">
            <span class="stat-value">1,120</span>
            <span class="stat-label">Certificates Issued</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🏠</div>
          <div class="stat-info">
            <span class="stat-value">89</span>
            <span class="stat-label">Construction Permits</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⏳</div>
          <div class="stat-info">
            <span class="stat-value">12</span>
            <span class="stat-label">Pending</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">✅</div>
          <div class="stat-info">
            <span class="stat-value">77</span>
            <span class="stat-label">Approved</span>
          </div>
        </div>
      </div>
    </section>

    <section class="search-section">
      <h2>🔍 Search Records</h2>
      <div class="search-box">
        <input 
          type="text" 
          bind:value={searchQuery}
          placeholder="Search by plot number, owner, address..."
          on:keyup={(e) => e.key === 'Enter' && handleSearch()}
        />
        <select bind:value={selectedFilter}>
          <option value="all">All Types</option>
          <option value="residential">Đất ở</option>
          <option value="agricultural">Đất nông nghiệp</option>
          <option value="commercial">Đất thương mại</option>
        </select>
        <button class="btn-primary" on:click={handleSearch}>Search</button>
      </div>
    </section>

    <section class="records-section">
      <div class="section-header">
        <h2>📋 Land Records</h2>
        <button class="btn-primary">
          + New Registration
        </button>
      </div>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Plot No.</th>
              <th>Owner</th>
              <th>Area (m²)</th>
              <th>Address</th>
              <th>Type</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {#each landRecords as record}
              <tr>
                <td>{record.plotNumber}</td>
                <td>{record.owner}</td>
                <td>{record.area}</td>
                <td>{record.address}</td>
                <td>{record.type}</td>
                <td><span class="status-badge {record.status === 'Đã cấp GCN' ? 'active' : 'pending'}">{record.status}</span></td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>

    <section class="records-section">
      <div class="section-header">
        <h2>🏗️ Construction Permits</h2>
        <button class="btn-primary">
          + Apply for Permit
        </button>
      </div>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Applicant</th>
              <th>Address</th>
              <th>Permit Type</th>
              <th>Area (m²)</th>
              <th>Submitted</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {#each permits as permit}
              <tr>
                <td>{permit.id}</td>
                <td>{permit.applicant}</td>
                <td>{permit.address}</td>
                <td>{permit.permitType}</td>
                <td>{permit.area}</td>
                <td>{permit.submittedDate}</td>
                <td><span class="status-badge {permit.status === 'Đã duyệt' ? 'active' : permit.status === 'Đang xử lý' ? 'pending' : 'warning'}">{permit.status}</span></td>
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
          <span>Apply for Land Certificate</span>
        </button>
        <button class="action-card">
          <div class="action-icon">🏗️</div>
          <span>Construction Permit</span>
        </button>
        <button class="action-card">
          <div class="action-icon">📊</div>
          <span>Land Use Planning</span>
        </button>
        <button class="action-card">
          <div class="action-icon">🗺️</div>
          <span>View on Map</span>
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

  .status-badge.warning {
    background: #fee2e2;
    color: #991b1b;
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
  }
</style>
