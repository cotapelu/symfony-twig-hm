<script lang="ts">
  import { writable } from 'svelte/store';

  interface Permit {
    id: string;
    applicationNumber: string;
    applicant: string;
    address: string;
    permitType: string;
    area: number;
    status: 'pending' | 'approved' | 'rejected';
    submissionDate: string;
    processingDate: string;
  }

  let permits = writable<Permit[]>([
    { id: '1', applicationNumber: 'XP-001', applicant: 'Nguyễn Văn A', address: '123 Nguyễn Trãi', permitType: 'Xây dựng mới', area: 150, status: 'pending', submissionDate: '2026-02-10', processingDate: '' },
    { id: '2', applicationNumber: 'XP-002', applicant: 'Trần Thị B', address: '45 Lê Lợi', permitType: 'Sửa chữa', area: 80, status: 'approved', submissionDate: '2026-02-05', processingDate: '2026-02-08' },
    { id: '3', applicationNumber: 'XP-003', applicant: 'Lê Văn C', address: '78 Hoàng Văn Thụ', permitType: 'Xây dựng mới', area: 200, status: 'pending', submissionDate: '2026-02-12', processingDate: '' },
    { id: '4', applicationNumber: 'XP-004', applicant: 'Phạm Thị D', address: '90 Nguyễn Du', permitType: 'Cải tạo', area: 120, status: 'rejected', submissionDate: '2026-02-01', processingDate: '2026-02-07' },
  ]);

  let filterStatus = 'all';
  $: filteredPermits = $permits.filter(p => filterStatus === 'all' || p.status === filterStatus);
  $: stats = { pending: $permits.filter(p => p.status === 'pending').length, approved: $permits.filter(p => p.status === 'approved').length, rejected: $permits.filter(p => p.status === 'rejected').length };
</script>

<div class="permit-container">
  <header class="permit-header">
    <div><h2>🏗️ Giấy phép Xây dựng</h2><span class="subtitle">Quản lý cấp phép xây dựng</span></div>
    <button class="primary">➕ Hồ sơ mới</button>
  </header>
  <div class="stats-bar">
    <div class="stat-item pending"><span class="stat-value">{stats.pending}</span><span class="stat-label">Chờ duyệt</span></div>
    <div class="stat-item approved"><span class="stat-value">{stats.approved}</span><span class="stat-label">Đã duyệt</span></div>
    <div class="stat-item rejected"><span class="stat-value">{stats.rejected}</span><span class="stat-label">Từ chối</span></div>
  </div>
  <div class="filters">
    <select bind:value={filterStatus}>
      <option value="all">Tất cả</option>
      <option value="pending">Chờ duyệt</option>
      <option value="approved">Đã duyệt</option>
      <option value="rejected">Từ chối</option>
    </select>
  </div>
  <div class="permit-content">
    <table class="data-table">
      <thead>
        <tr>
          <th>Số hồ sơ</th>
          <th>Người đề nghị</th>
          <th>Địa chỉ</th>
          <th>Loại công trình</th>
          <th>Diện tích (m²)</th>
          <th>Ngày nộp</th>
          <th>Ngày xử lý</th>
          <th>Trạng thái</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
        {#each filteredPermits as permit}
          <tr>
            <td><strong>{permit.applicationNumber}</strong></td>
            <td>{permit.applicant}</td>
            <td>{permit.address}</td>
            <td>{permit.permitType}</td>
            <td>{permit.area}</td>
            <td>{permit.submissionDate}</td>
            <td>{permit.processingDate || '-'}</td>
            <td>
              <span class="status-badge {permit.status}">
                {permit.status === 'pending' ? 'Chờ duyệt' : permit.status === 'approved' ? 'Đã duyệt' : 'Từ chối'}
              </span>
            </td>
            <td>
              <button class="action-btn">👁️</button>
              <button class="action-btn">✏️</button>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</div>

<style>
  .permit-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .permit-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .permit-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; border-radius: 8px; }
  .stat-item.pending { background: #fef3c7; }
  .stat-item.approved { background: #dcfce7; }
  .stat-item.rejected { background: #fee2e2; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .filters { padding: 12px 24px; background: white; }
  .filters select { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; }
  .permit-content { flex: 1; overflow-y: auto; padding: 20px; }
  .data-table { width: 100%; background: white; border-radius: 8px; border-collapse: collapse; }
  .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
  .data-table th { background: #f8fafc; font-weight: 600; font-size: 0.875rem; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; }
  .status-badge.pending { background: #fef3c7; color: #92400e; }
  .status-badge.approved { background: #dcfce7; color: #166534; }
  .status-badge.rejected { background: #fee2e2; color: #991b1b; }
  .action-btn { padding: 4px 8px; background: transparent; border: none; cursor: pointer; }
</style>
