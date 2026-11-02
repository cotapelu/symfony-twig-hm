<script lang="ts">
  import { writable } from 'svelte/store';

  interface LandPlot {
    id: string;
    plotNumber: string;
    area: number;
    owner: string;
    landType: string;
    status: 'active' | 'dispute' | 'transfer';
    location: string;
    certificateNumber: string;
  }

  let plots = writable<LandPlot[]>([
    { id: '1', plotNumber: 'L-001', area: 250, owner: 'Nguyễn Văn A', landType: 'Đất ở', status: 'active', location: 'Khu vực 1', certificateNumber: 'CH001/2020' },
    { id: '2', plotNumber: 'L-002', area: 180, owner: 'Trần Thị B', landType: 'Đất nông nghiệp', status: 'active', location: 'Khu vực 2', certificateNumber: 'CH002/2021' },
    { id: '3', plotNumber: 'L-003', area: 320, owner: 'Lê Văn C', landType: 'Đất ở', status: 'dispute', location: 'Khu vực 3', certificateNumber: 'CH003/2019' },
  ]);

  let filterStatus = 'all';
  $: filteredPlots = $plots.filter(p => filterStatus === 'all' || p.status === filterStatus);
  $: stats = { total: $plots.length, totalArea: $plots.reduce((a, b) => a + b.area, 0), disputes: $plots.filter(p => p.status === 'dispute').length };
</script>

<div class="land-container">
  <header class="land-header">
    <div><h2>🗺️ Quản lý Đất đai</h2><span class="subtitle">Quản lý quy hoạch và sử dụng đất</span></div>
    <div class="header-actions">
      <button class="primary">➕ Thêm thửa</button>
      <button class="secondary">📤 Xuất Excel</button>
    </div>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{stats.total}</span><span class="stat-label">Tổng thửa</span></div>
    <div class="stat-item"><span class="stat-value">{stats.totalArea}</span><span class="stat-label">m² Tổng diện tích</span></div>
    <div class="stat-item"><span class="stat-value">{stats.disputes}</span><span class="stat-label">Tranh chấp</span></div>
  </div>
  <div class="filters">
    <select bind:value={filterStatus}>
      <option value="all">Tất cả</option>
      <option value="active">Hoạt động</option>
      <option value="dispute">Tranh chấp</option>
      <option value="transfer">Chuyển nhượng</option>
    </select>
  </div>
  <div class="land-content">
    <table class="data-table">
      <thead>
        <tr>
          <th>Số thửa</th>
          <th>Diện tích (m²)</th>
          <th>Chủ sở hữu</th>
          <th>Loại đất</th>
          <th>Địa điểm</th>
          <th>Số GCN</th>
          <th>Trạng thái</th>
        </tr>
      </thead>
      <tbody>
        {#each filteredPlots as plot}
          <tr>
            <td><strong>{plot.plotNumber}</strong></td>
            <td>{plot.area}</td>
            <td>{plot.owner}</td>
            <td>{plot.landType}</td>
            <td>{plot.location}</td>
            <td>{plot.certificateNumber}</td>
            <td>
              <span class="status-badge {plot.status}">
                {plot.status === 'active' ? 'Hoạt động' : plot.status === 'dispute' ? 'Tranh chấp' : 'Chuyển nhượng'}
              </span>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</div>

<style>
  .land-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .land-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .land-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .header-actions { display: flex; gap: 8px; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .secondary { padding: 10px 16px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .filters { padding: 12px 24px; background: white; }
  .filters select { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; }
  .land-content { flex: 1; overflow-y: auto; padding: 20px; }
  .data-table { width: 100%; background: white; border-radius: 8px; border-collapse: collapse; }
  .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
  .data-table th { background: #f8fafc; font-weight: 600; font-size: 0.875rem; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; }
  .status-badge.active { background: #dcfce7; color: #166534; }
  .status-badge.dispute { background: #fef3c7; color: #92400e; }
  .status-badge.transfer { background: #dbeafe; color: #1e40af; }
</style>
