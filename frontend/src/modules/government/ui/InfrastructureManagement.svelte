<script lang="ts">
  import { writable } from 'svelte/store';

  interface Infrastructure {
    id: string;
    name: string;
    type: string;
    location: string;
    builtYear: number;
    status: 'good' | 'fair' | 'poor';
    lastInspection: string;
    nextInspection: string;
  }

  let infrastructure = writable<Infrastructure[]>([
    { id: '1', name: 'Cầu Bến Tre', type: 'Cầu', location: 'Đường Nguyễn Trãi', builtYear: 2015, status: 'good', lastInspection: '2026-01-15', nextInspection: '2026-07-15' },
    { id: '2', name: 'Trường Tiểu học X', type: 'Trường học', location: 'Khu vực 1', builtYear: 2010, status: 'good', lastInspection: '2026-01-20', nextInspection: '2026-07-20' },
    { id: '3', name: 'Đường Lê Lợi', type: 'Đường giao thông', location: 'Khu vực 2', builtYear: 2018, status: 'fair', lastInspection: '2026-01-10', nextInspection: '2026-04-10' },
    { id: '4', name: 'Trạm xử lý nước', type: 'Cấp thoát nước', location: 'Khu vực 3', builtYear: 2020, status: 'good', lastInspection: '2026-02-01', nextInspection: '2026-08-01' },
  ]);

  $: stats = { total: $infrastructure.length, good: $infrastructure.filter(i => i.status === 'good').length, fair: $infrastructure.filter(i => i.status === 'fair').length };
</script>

<div class="infra-container">
  <header class="infra-header">
    <div><h2>🏗️ Quản lý Hạ tầng</h2><span class="subtitle">Quản lý cơ sở hạ tầng công cộng</span></div>
    <button class="primary">➕ Thêm hạ tầng</button>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{stats.total}</span><span class="stat-label">Tổng hạ tầng</span></div>
    <div class="stat-item good"><span class="stat-value">{stats.good}</span><span class="stat-label">Tốt</span></div>
    <div class="stat-item fair"><span class="stat-value">{stats.fair}</span><span class="stat-label">Trung bình</span></div>
  </div>
  <div class="infra-content">
    <div class="infra-grid">
      {#each $infrastructure as item}
        <div class="infra-card">
          <div class="infra-header-row">
            <span class="infra-icon">
              {item.type === 'Cầu' ? '🌉' : item.type === 'Trường học' ? '🏫' : item.type === 'Đường giao thông' ? '🛣️' : '💧'}
            </span>
            <div class="infra-info">
              <h4>{item.name}</h4>
              <p>{item.type} • {item.location}</p>
            </div>
          </div>
          <div class="infra-details">
            <div class="detail-row">
              <span class="label">Năm xây dựng:</span>
              <span class="value">{item.builtYear}</span>
            </div>
            <div class="detail-row">
              <span class="label">Kiểm tra gần nhất:</span>
              <span class="value">{item.lastInspection}</span>
            </div>
            <div class="detail-row">
              <span class="label">Kiểm tra tiếp:</span>
              <span class="value">{item.nextInspection}</span>
            </div>
          </div>
          <div class="infra-footer">
            <span class="status-badge {item.status}">
              {item.status === 'good' ? 'Tốt' : item.status === 'fair' ? 'Trung bình' : 'Kém'}
            </span>
            <button class="inspect-btn">Kiểm tra</button>
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .infra-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .infra-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .infra-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-item.good { background: #dcfce7; }
  .stat-item.fair { background: #fef3c7; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .infra-content { flex: 1; overflow-y: auto; padding: 20px; }
  .infra-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
  .infra-card { background: white; border-radius: 12px; padding: 16px; }
  .infra-header-row { display: flex; gap: 12px; margin-bottom: 12px; }
  .infra-icon { font-size: 2rem; }
  .infra-info h4 { margin: 0; font-size: 1rem; }
  .infra-info p { margin: 4px 0 0; font-size: 0.8rem; color: #64748b; }
  .infra-details { margin-bottom: 12px; }
  .detail-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.85rem; }
  .detail-row .label { color: #64748b; }
  .detail-row .value { font-weight: 500; }
  .infra-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #e2e8f0; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; }
  .status-badge.good { background: #dcfce7; color: #166534; }
  .status-badge.fair { background: #fef3c7; color: #92400e; }
  .status-badge.poor { background: #fee2e2; color: #991b1b; }
  .inspect-btn { padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
</style>
