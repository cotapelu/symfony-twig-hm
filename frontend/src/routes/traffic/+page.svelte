<script lang="ts">
  import { onMount } from 'svelte';

  let activeTab = 'traffic';
  let loading = false;

  let roads: any[] = [];
  let incidents: any[] = [];
  let maintenance: any[] = [];
  let signs: any[] = [];

  let stats = {
    totalRoads: 0,
    totalIncidents: 0,
    pendingMaintenance: 0,
    totalSigns: 0
  };

  const API_BASE = 'http://localhost:8080/api/infra';

  onMount(() => {
    loadData();
  });

  async function loadData() {
    loading = true;
    roads = generateMockRoads();
    incidents = generateMockIncidents();
    maintenance = generateMockMaintenance();
    signs = generateMockSigns();
    calculateStats();
    loading = false;
  }

  function generateMockRoads() {
    return [
      { id: '1', name: 'Đường Nguyễn Trãi', length: 2500, width: 12, lanes: 2, status: 'GOOD', lastInspection: '2026-01-15', type: 'Đường chính' },
      { id: '2', name: 'Đường Lý Thường Kiệt', length: 1800, width: 10, lanes: 2, status: 'FAIR', lastInspection: '2026-01-10', type: 'Đường chính' },
      { id: '3', name: 'Đường Hai Bà Trưng', length: 3200, width: 14, lanes: 3, status: 'GOOD', lastInspection: '2026-01-20', type: 'Đường chính' },
      { id: '4', name: 'Đường Đội Cấn', length: 950, width: 6, lanes: 1, status: 'POOR', lastInspection: '2025-12-28', type: 'Đường nội bộ' },
      { id: '5', name: 'Đường Kim Mã', length: 2100, width: 8, lanes: 2, status: 'GOOD', lastInspection: '2026-01-18', type: 'Đường nội bộ' }
    ];
  }

  function generateMockIncidents() {
    return [
      { id: '1', type: 'accident', location: 'Ngã tư Nguyễn Trãi - Lý Thường Kiệt', time: '2026-02-14 08:30', status: 'RESOLVED', vehicles: 2, injuries: 1 },
      { id: '2', type: 'congestion', location: 'Đường Hai Bà Trưng', time: '2026-02-14 07:15', status: 'RESOLVED', vehicles: 0, injuries: 0 },
      { id: '3', type: 'damage', location: 'Đường Đội Cấn', time: '2026-02-13 16:20', status: 'PENDING', vehicles: 0, injuries: 0 },
      { id: '4', type: 'accident', location: 'Ngã 3 Kim Mã', time: '2026-02-13 14:45', status: 'PROCESSING', vehicles: 3, injuries: 2 }
    ];
  }

  function generateMockMaintenance() {
    return [
      { id: '1', roadName: 'Đường Nguyễn Trãi', type: 'Asphalt', description: 'Trám ổ gà', status: 'PENDING', cost: 5000000, scheduledDate: '2026-02-20' },
      { id: '2', roadName: 'Đường Lý Thường Kiệt', type: 'Drainage', description: 'Thông cống rãnh', status: 'IN_PROGRESS', cost: 3000000, scheduledDate: '2026-02-15' },
      { id: '3', roadName: 'Đường Đội Cấn', type: 'Surface', description: 'Sửa mặt đường', status: 'COMPLETED', cost: 8000000, scheduledDate: '2026-02-10' }
    ];
  }

  function generateMockSigns() {
    return [
      { id: '1', type: 'speed', location: 'Đường Nguyễn Trãi', status: 'GOOD', lastMaintenance: '2025-12-01' },
      { id: '2', type: 'stop', location: 'Ngã tư Lý Thường Kiệt', status: 'DAMAGED', lastMaintenance: '2024-08-15' },
      { id: '3', type: 'warning', location: 'Đường Hai Bà Trưng', status: 'GOOD', lastMaintenance: '2025-11-20' },
      { id: '4', type: 'parking', location: 'Đường Kim Mã', status: 'MISSING', lastMaintenance: null }
    ];
  }

  function calculateStats() {
    stats = {
      totalRoads: roads.length,
      totalIncidents: incidents.length,
      pendingMaintenance: maintenance.filter(m => m.status === 'PENDING').length,
      totalSigns: signs.length
    };
  }

  function getStatusColor(status: string) {
    switch (status) {
      case 'GOOD': return '#10b981';
      case 'FAIR': return '#f59e0b';
      case 'POOR': return '#ef4444';
      case 'DAMAGED': return '#dc2626';
      case 'MISSING': return '#7c3aed';
      case 'PENDING': return '#f59e0b';
      case 'IN_PROGRESS': return '#3b82f6';
      case 'COMPLETED': return '#10b981';
      case 'RESOLVED': return '#10b981';
      case 'PROCESSING': return '#3b82f6';
      default: return '#6b7280';
    }
  }

  function getIncidentIcon(type: string) {
    switch (type) {
      case 'accident': return '🚗';
      case 'congestion': return '🚦';
      case 'damage': return '🛑';
      default: return '⚠️';
    }
  }

  function getSignIcon(type: string) {
    switch (type) {
      case 'speed': return '⏱️';
      case 'stop': return '🛑';
      case 'warning': return '⚠️';
      case 'parking': return '🅿️';
      default: return '🚏';
    }
  }

  function getStatusText(status: string) {
    switch (status) {
      case 'GOOD': return 'Tốt';
      case 'FAIR': return 'Trung bình';
      case 'POOR': return 'Kém';
      case 'DAMAGED': return 'Hư hỏng';
      case 'MISSING': return 'Thiếu';
      case 'PENDING': return 'Chờ xử lý';
      case 'IN_PROGRESS': return 'Đang làm';
      case 'COMPLETED': return 'Hoàn thành';
      case 'RESOLVED': return 'Đã xử lý';
      case 'PROCESSING': return 'Đang xử lý';
      default: return status;
    }
  }
</script>

<div class="traffic-page">
  <header class="page-header">
    <h1>🚦 Quản Lý Giao Thông - Hạ Tầng</h1>
    <div class="header-actions">
      <button class="btn-secondary">📊 Báo cáo</button>
      <button class="btn-primary">+ Báo cáo sự cố</button>
    </div>
  </header>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">🛣️</div>
      <div class="stat-value">{stats.totalRoads}</div>
      <div class="stat-label">Tổng đường</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🚨</div>
      <div class="stat-value">{stats.totalIncidents}</div>
      <div class="stat-label">Sự cố giao thông</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🔧</div>
      <div class="stat-value">{stats.pendingMaintenance}</div>
      <div class="stat-label">Chờ bảo trì</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🚏</div>
      <div class="stat-value">{stats.totalSigns}</div>
      <div class="stat-label">Biển báo</div>
    </div>
  </div>

  <div class="tabs">
    <button class="tab" class:active={activeTab === 'traffic'} on:click={() => activeTab = 'traffic'}>
      🚗 Giao thông
    </button>
    <button class="tab" class:active={activeTab === 'roads'} on:click={() => activeTab = 'roads'}>
      🛣️ Đường phố
    </button>
    <button class="tab" class:active={activeTab === 'maintenance'} on:click={() => activeTab = 'maintenance'}>
      🔧 Bảo trì
    </button>
    <button class="tab" class:active={activeTab === 'signs'} on:click={() => activeTab = 'signs'}>
      🚏 Biển báo
    </button>
  </div>

  {#if activeTab === 'traffic'}
    <div class="content-card">
      <div class="card-header">
        <h3>Sự cố giao thông</h3>
        <div class="filters">
          <select>
            <option>Tất cả</option>
            <option>Tai nạn</option>
            <option>Ùn tắc</option>
            <option>Hư hỏng</option>
          </select>
        </div>
      </div>
      <div class="incidents-list">
        {#each incidents as incident}
          <div class="incident-item">
            <div class="incident-icon">{getIncidentIcon(incident.type)}</div>
            <div class="incident-info">
              <div class="incident-type">
                {incident.type === 'accident' ? 'Tai nạn giao thông' :
                 incident.type === 'congestion' ? 'Ùn tắc giao thông' : 'Hư hỏng cơ sở hạ tầng'}
              </div>
              <div class="incident-location">{incident.location}</div>
              <div class="incident-time">{incident.time}</div>
            </div>
            <div class="incident-details">
              {#if incident.vehicles > 0}
                <span class="detail-badge">🚗 {incident.vehicles} xe</span>
              {/if}
              {#if incident.injuries > 0}
                <span class="detail-badge injury">🏥 {incident.injuries} người bị thương</span>
              {/if}
            </div>
            <div class="incident-status">
              <span class="status-badge" style="background: {getStatusColor(incident.status)}">
                {getStatusText(incident.status)}
              </span>
            </div>
            <div class="incident-actions">
              <button class="btn-icon">👁️</button>
              <button class="btn-icon">✏️</button>
            </div>
          </div>
        {/each}
      </div>
    </div>
  {:else if activeTab === 'roads'}
    <div class="content-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tên đường</th>
            <th>Chiều dài (m)</th>
            <th>Chiều rộng (m)</th>
            <th>Làn xe</th>
            <th>Loại đường</th>
            <th>Tình trạng</th>
            <th>Kiểm tra cuối</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          {#each roads as road}
            <tr>
              <td class="road-name">{road.name}</td>
              <td>{road.length}</td>
              <td>{road.width}</td>
              <td>{road.lanes}</td>
              <td>{road.type}</td>
              <td>
                <span class="status-badge" style="background: {getStatusColor(road.status)}">
                  {getStatusText(road.status)}
                </span>
              </td>
              <td>{road.lastInspection}</td>
              <td>
                <button class="btn-icon">👁️</button>
                <button class="btn-icon">✏️</button>
                <button class="btn-icon">📋</button>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {:else if activeTab === 'maintenance'}
    <div class="content-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Đường</th>
            <th>Loại bảo trì</th>
            <th>Mô tả</th>
            <th>Chi phí dự kiến</th>
            <th>Ngày dự kiến</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          {#each maintenance as item}
            <tr>
              <td class="road-name">{item.roadName}</td>
              <td>{item.type}</td>
              <td>{item.description}</td>
              <td>{new Intl.NumberFormat('vi-VN').format(item.cost)} VNĐ</td>
              <td>{item.scheduledDate}</td>
              <td>
                <span class="status-badge" style="background: {getStatusColor(item.status)}">
                  {getStatusText(item.status)}
                </span>
              </td>
              <td>
                <button class="btn-action">Xem</button>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {:else}
    <div class="content-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Loại biển</th>
            <th>Vị trí</th>
            <th>Tình trạng</th>
            <th>Bảo trì cuối</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          {#each signs as sign}
            <tr>
              <td>
                <div class="sign-cell">
                  <span class="sign-icon">{getSignIcon(sign.type)}</span>
                  <span>{sign.type === 'speed' ? 'Biển tốc độ' :
                         sign.type === 'stop' ? 'Biển dừng' :
                         sign.type === 'warning' ? 'Biển cảnh báo' : 'Biển đỗ xe'}</span>
                </div>
              </td>
              <td>{sign.location}</td>
              <td>
                <span class="status-badge" style="background: {getStatusColor(sign.status)}">
                  {getStatusText(sign.status)}
                </span>
              </td>
              <td>{sign.lastMaintenance || 'Chưa có'}</td>
              <td>
                <button class="btn-action">Sửa</button>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {/if}
</div>

<style>
  .traffic-page {
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }

  .page-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
  }

  .header-actions {
    display: flex;
    gap: 12px;
  }

  .btn-primary {
    background: #3b82f6;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
  }

  .btn-secondary {
    background: white;
    color: #374151;
    padding: 12px 20px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
  }

  .stat-icon {
    font-size: 28px;
    margin-bottom: 8px;
  }

  .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
  }

  .stat-label {
    font-size: 13px;
    color: #6b7280;
    margin-top: 4px;
  }

  .tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    background: white;
    padding: 4px;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .tab {
    flex: 1;
    padding: 12px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 8px;
    font-weight: 500;
    color: #6b7280;
  }

  .tab.active {
    background: #3b82f6;
    color: white;
  }

  .content-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
  }

  .card-header h3 {
    margin: 0;
    font-size: 16px;
  }

  .filters select {
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
  }

  .incidents-list {
    padding: 12px;
  }

  .incident-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
  }

  .incident-item:last-child {
    border-bottom: none;
  }

  .incident-icon {
    font-size: 32px;
    width: 48px;
    text-align: center;
  }

  .incident-info {
    flex: 1;
  }

  .incident-type {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
  }

  .incident-location {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 2px;
  }

  .incident-time {
    color: #9ca3af;
    font-size: 12px;
  }

  .incident-details {
    display: flex;
    gap: 8px;
  }

  .detail-badge {
    padding: 4px 10px;
    background: #f3f4f6;
    border-radius: 12px;
    font-size: 12px;
  }

  .detail-badge.injury {
    background: #fef2f2;
    color: #dc2626;
  }

  .incident-actions {
    display: flex;
    gap: 8px;
  }

  .btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    font-size: 16px;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
  }

  .data-table th {
    background: #f9fafb;
    padding: 14px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
  }

  .data-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
  }

  .road-name {
    font-weight: 500;
    color: #1f2937;
  }

  .status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    color: white;
    font-weight: 500;
  }

  .sign-cell {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .sign-icon {
    font-size: 20px;
  }

  .btn-action {
    padding: 6px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    font-size: 13px;
  }
</style>