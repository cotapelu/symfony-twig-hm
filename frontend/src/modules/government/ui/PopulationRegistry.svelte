<script lang="ts">
  import { writable } from 'svelte/store';

  interface Household {
    id: string;
    householdNumber: string;
    address: string;
    headOfHousehold: string;
    members: number;
    registrationDate: string;
    status: 'active' | 'moved_out' | 'temporary';
    ward: string;
  }

  let households = writable<Household[]>([
    { id: '1', householdNumber: 'HK-001', address: '123 Nguyễn Trãi', headOfHousehold: 'Nguyễn Văn A', members: 4, registrationDate: '2020-01-15', status: 'active', ward: 'Khu vực 1' },
    { id: '2', householdNumber: 'HK-002', address: '45 Lê Lợi', headOfHousehold: 'Trần Thị B', members: 3, registrationDate: '2021-03-20', status: 'active', ward: 'Khu vực 1' },
    { id: '3', householdNumber: 'HK-003', address: '78 Hoàng Văn Thụ', headOfHousehold: 'Lê Văn C', members: 5, registrationDate: '2019-11-10', status: 'temporary', ward: 'Khu vực 2' },
    { id: '4', householdNumber: 'HK-004', address: '90 Nguyễn Du', headOfHousehold: 'Phạm Thị D', members: 2, registrationDate: '2022-06-05', status: 'moved_out', ward: 'Khu vực 3' },
  ]);

  $: stats = { total: $households.length, active: $households.filter(h => h.status === 'active').length, totalMembers: $households.reduce((a, b) => a + b.members, 0) };
</script>

<div class="registry-container">
  <header class="registry-header">
    <div><h2>🏠 Quản lý Hộ khẩu</h2><span class="subtitle">Đăng ký và quản lý hộ khẩu</span></div>
    <div class="header-actions">
      <button class="primary">➕ Thêm hộ</button>
      <button class="secondary">📤 Xuất danh sách</button>
    </div>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{stats.total}</span><span class="stat-label">Tổng hộ</span></div>
    <div class="stat-item"><span class="stat-value">{stats.active}</span><span class="stat-label">Hộ thường trú</span></div>
    <div class="stat-item"><span class="stat-value">{stats.totalMembers}</span><span class="stat-label">Tổng nhân khẩu</span></div>
  </div>
  <div class="registry-content">
    <table class="data-table">
      <thead>
        <tr>
          <th>Số hộ khẩu</th>
          <th>Chủ hộ</th>
          <th>Địa chỉ</th>
          <th>Khu vực</th>
          <th>Thành viên</th>
          <th>Ngày đăng ký</th>
          <th>Trạng thái</th>
        </tr>
      </thead>
      <tbody>
        {#each $households as household}
          <tr>
            <td><strong>{household.householdNumber}</strong></td>
            <td>{household.headOfHousehold}</td>
            <td>{household.address}</td>
            <td>{household.ward}</td>
            <td>{household.members}</td>
            <td>{household.registrationDate}</td>
            <td>
              <span class="status-badge {household.status}">
                {household.status === 'active' ? 'Thường trú' : household.status === 'temporary' ? 'Tạm trú' : 'Đã chuyển'}
              </span>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</div>

<style>
  .registry-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .registry-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .registry-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .header-actions { display: flex; gap: 8px; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .secondary { padding: 10px 16px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .registry-content { flex: 1; overflow-y: auto; padding: 20px; }
  .data-table { width: 100%; background: white; border-radius: 8px; border-collapse: collapse; }
  .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
  .data-table th { background: #f8fafc; font-weight: 600; font-size: 0.875rem; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; }
  .status-badge.active { background: #dcfce7; color: #166534; }
  .status-badge.temporary { background: #dbeafe; color: #1e40af; }
  .status-badge.moved_out { background: #f1f5f9; color: #64748b; }
</style>
