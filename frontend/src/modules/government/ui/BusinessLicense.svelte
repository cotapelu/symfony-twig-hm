<script lang="ts">
  import { writable } from 'svelte/store';

  interface Business {
    id: string;
    name: string;
    owner: string;
    licenseNumber: string;
    type: 'retail' | 'restaurant' | 'service' | 'manufacturing';
    address: string;
    phone: string;
    status: 'active' | 'pending' | 'expired' | 'suspended';
    licenseDate: Date;
    expirationDate: Date;
    ward: string;
  }

  let businesses = writable<Business[]>([
    { id: '1', name: 'Cửa hàng tiện lợi ABC', owner: 'Nguyễn Văn A', licenseNumber: 'DK001', type: 'retail', address: '123 Nguyễn Trãi', phone: '0912xxx', status: 'active', licenseDate: new Date('2024-01-15'), expirationDate: new Date('2026-01-15'), ward: 'Khu vực 1' },
    { id: '2', name: 'Nhà hàng XYZ', owner: 'Trần Thị B', licenseNumber: 'DK002', type: 'restaurant', address: '45 Lê Lợi', phone: '0903xxx', status: 'active', licenseDate: new Date('2024-03-20'), expirationDate: new Date('2026-03-20'), ward: 'Khu vực 1' },
    { id: '3', name: 'Tiệm cắt tóc Mỹ', owner: 'Lê Văn C', licenseNumber: 'DK003', type: 'service', address: '78 Quang Trung', phone: '0932xxx', status: 'pending', licenseDate: new Date('2026-02-01'), expirationDate: new Date('2028-02-01'), ward: 'Khu vực 2' },
    { id: '4', name: 'Xưởng sản xuất D', owner: 'Phạm Thị D', licenseNumber: 'DK004', type: 'manufacturing', address: 'KCN X', phone: '0987xxx', status: 'active', licenseDate: new Date('2023-06-10'), expirationDate: new Date('2025-06-10'), ward: 'Khu vực 3' },
    { id: '5', name: 'Cửa hàng điện tử E', owner: 'Nguyễn Văn E', licenseNumber: 'DK005', type: 'retail', address: '90 30/4', phone: '0919xxx', status: 'expired', licenseDate: new Date('2022-08-15'), expirationDate: new Date('2024-08-15'), ward: 'Khu vực 2' },
  ]);

  let activeTab: 'all' | 'active' | 'pending' | 'expired' = 'all';

  const typeIcons: Record<string, string> = { retail: '🏪', restaurant: '🍽️', service: '✂️', manufacturing: '🏭' };
  const statusColors: Record<string, string> = { active: '#10b981', pending: '#f59e0b', expired: '#ef4444', suspended: '#64748b' };

  $: filteredBusinesses = $businesses.filter(b => {
    if (activeTab === 'active') return b.status === 'active';
    if (activeTab === 'pending') return b.status === 'pending';
    if (activeTab === 'expired') return b.status === 'expired';
    return true;
  });

  $: stats = { total: $businessies.length, active: $businesses.filter(b => b.status === 'active').length, pending: $businesses.filter(b => b.status === 'pending').length };
</script>

<div class="biz-container">
  <header class="biz-header">
    <div><h2>🏢 Quản lý Giấy phép Kinh doanh</h2><span class="subtitle">Danh sách và giấy phép kinh doanh</span></div>
    <button class="primary">➕ Cấp mới</button>
  </header>
  <div class="stats-bar">
    <div class="stat-item" class:active={activeTab === 'all'} on:click={() => activeTab = 'all'}><span class="stat-value">{stats.total}</span><span class="stat-label">Tổng</span></div>
    <div class="stat-item active" class:active={activeTab === 'active'} on:click={() => activeTab = 'active'}><span class="stat-value">{stats.active}</span><span class="stat-label">Hoạt động</span></div>
    <div class="stat-item" class:active={activeTab === 'pending'} on:click={() => activeTab = 'pending'}><span class="stat-value">{stats.pending}</span><span class="stat-label">Chờ duyệt</span></div>
  </div>
  <div class="biz-content">
    <table>
      <thead><tr><th>Mã GP</th><th>Tên DN</th><th>Chủ DN</th><th>Loại hình</th><th>Địa chỉ</th><th>Trạng thái</th><th>Hạn</th></tr></thead>
      <tbody>
        {#each filteredBusinesses as biz}
          <tr>
            <td><strong>{biz.licenseNumber}</strong></td>
            <td>{biz.name}</td>
            <td>{biz.owner}</td>
            <td>{typeIcons[biz.type]} {biz.type}</td>
            <td>{biz.address}</td>
            <td><span class="status-badge" style="background: {statusColors[biz.status]}">{biz.status === 'active' ? 'Hoạt động' : biz.status === 'pending' ? 'Chờ duyệt' : biz.status === 'expired' ? 'Hết hạn' : 'Tạm đình chỉ'}</span></td>
            <td>{biz.expirationDate.toLocaleDateString('vi-VN')}</td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</div>

<style>
  .biz-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .biz-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .biz-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; cursor: pointer; }
  .stat-item.active { background: #d1fae5; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .biz-content { flex: 1; overflow-y: auto; padding: 20px; }
  table { width: 100%; background: white; border-radius: 12px; overflow: hidden; border-collapse: collapse; }
  th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; }
  th { background: #f8fafc; font-size: 0.8rem; color: #64748b; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; color: white; }
</style>
