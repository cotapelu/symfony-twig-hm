<script lang="ts">
  import { writable } from 'svelte/store';

  interface Asset {
    id: string;
    name: string;
    category: string;
    purchaseDate: string;
    value: number;
    location: string;
    status: 'good' | 'maintenance' | 'damaged';
    responsible: string;
  }

  let assets = writable<Asset[]>([
    { id: '1', name: 'Máy chiếu Sony', category: 'Thiết bị văn phòng', purchaseDate: '2024-01-15', value: 15000000, location: 'Phòng họp A', status: 'good', responsible: 'Nguyễn Văn A' },
    { id: '2', name: 'Ô tô Toyota', category: 'Phương tiện', purchaseDate: '2023-06-01', value: 500000000, location: 'Bãi xe UBND', status: 'good', responsible: 'Trần Văn B' },
    { id: '3', name: 'Máy tính Dell', category: 'Thiết bị CNTT', purchaseDate: '2025-03-20', value: 12000000, location: 'Phòng TCHC', status: 'maintenance', responsible: 'Lê Thị C' },
    { id: '4', name: 'Điều hòa LG', category: 'Thiết bị văn phòng', purchaseDate: '2024-08-10', value: 8000000, location: 'Phòng chính', status: 'damaged', responsible: 'Phạm Văn D' },
  ]);

  $: stats = { total: $assets.length, totalValue: $assets.reduce((a, b) => a + b.value, 0), good: $assets.filter(a => a.status === 'good').length };
</script>

<div class="asset-container">
  <header class="asset-header">
    <div><h2>🏢 Quản lý Tài sản Công</h2><span class="subtitle">Quản lý tài sản và trang thiết bị</span></div>
    <div class="header-actions">
      <button class="primary">➕ Thêm tài sản</button>
      <button class="secondary">📋 Kiểm kê</button>
    </div>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{stats.total}</span><span class="stat-label">Tổng tài sản</span></div>
    <div class="stat-item"><span class="stat-value">{(stats.totalValue / 1000000).toFixed(0)}M</span><span class="stat-label">Giá trị (VNĐ)</span></div>
    <div class="stat-item"><span class="stat-value">{stats.good}</span><span class="stat-label">Tốt</span></div>
  </div>
  <div class="asset-content">
    <div class="assets-grid">
      {#each $assets as asset}
        <div class="asset-card">
          <div class="asset-header-row">
            <span class="asset-icon">🏢</span>
            <div class="asset-info">
              <h4>{asset.name}</h4>
              <p>{asset.category}</p>
            </div>
          </div>
          <div class="asset-details">
            <div class="detail-row">
              <span class="label">Giá trị:</span>
              <span class="value">{asset.value.toLocaleString()} VNĐ</span>
            </div>
            <div class="detail-row">
              <span class="label">Vị trí:</span>
              <span class="value">{asset.location}</span>
            </div>
            <div class="detail-row">
              <span class="label">Người QL:</span>
              <span class="value">{asset.responsible}</span>
            </div>
          </div>
          <div class="asset-footer">
            <span class="status-badge {asset.status}">
              {asset.status === 'good' ? 'Tốt' : asset.status === 'maintenance' ? 'Bảo trì' : 'Hỏng'}
            </span>
            <span class="date">{asset.purchaseDate}</span>
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .asset-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .asset-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .asset-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .header-actions { display: flex; gap: 8px; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .secondary { padding: 10px 16px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .asset-content { flex: 1; overflow-y: auto; padding: 20px; }
  .assets-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
  .asset-card { background: white; border-radius: 12px; padding: 16px; }
  .asset-header-row { display: flex; gap: 12px; margin-bottom: 12px; }
  .asset-icon { font-size: 2rem; }
  .asset-info h4 { margin: 0; font-size: 1rem; }
  .asset-info p { margin: 4px 0 0; font-size: 0.8rem; color: #64748b; }
  .asset-details { margin-bottom: 12px; }
  .detail-row { display: flex; justify-content: space-between; padding: 6px 0; }
  .detail-row .label { font-size: 0.8rem; color: #64748b; }
  .detail-row .value { font-size: 0.85rem; font-weight: 500; }
  .asset-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #e2e8f0; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; }
  .status-badge.good { background: #dcfce7; color: #166534; }
  .status-badge.maintenance { background: #fef3c7; color: #92400e; }
  .status-badge.damaged { background: #fee2e2; color: #991b1b; }
  .date { font-size: 0.75rem; color: #64748b; }
</style>
