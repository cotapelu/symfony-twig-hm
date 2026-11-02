<script lang="ts">
  import { writable } from 'svelte/store';

  interface RevenueRecord {
    id: string;
    taxpayer: string;
    taxType: string;
    amount: number;
    paymentDate: string;
    status: 'paid' | 'pending' | 'overdue';
    note: string;
  }

  let revenues = writable<RevenueRecord[]>([
    { id: '1', taxpayer: 'Công ty TNHH A', taxType: 'Thuế môn bài', amount: 1500000, paymentDate: '2026-02-10', status: 'paid', note: 'Năm 2026' },
    { id: '2', taxpayer: 'Hộ kinh doanh B', taxType: 'Thuế khoán', amount: 500000, paymentDate: '', status: 'pending', note: 'Q1/2026' },
    { id: '3', taxpayer: 'Công ty Cổ phần C', taxType: 'Thuế TNDN', amount: 5000000, paymentDate: '2026-02-08', status: 'paid', note: 'Tạm tính' },
    { id: '4', taxpayer: 'Hộ kinh doanh D', taxType: 'Thuế khoán', amount: 750000, paymentDate: '', status: 'overdue', note: 'Q4/2025' },
  ]);

  $: stats = { total: $revenues.reduce((a, b) => a + b.amount, 0), collected: $revenues.filter(r => r.status === 'paid').reduce((a, b) => a + b.amount, 0), pending: $revenues.filter(r => r.status === 'pending').length };
</script>

<div class="revenue-container">
  <header class="revenue-header">
    <div><h2>💰 Quản lý Thu ngân sách</h2><span class="subtitle">Thu thuế và các khoản thu</span></div>
    <div class="header-actions">
      <button class="primary">➕ Thu mới</button>
      <button class="secondary">📊 Báo cáo</button>
    </div>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{stats.total.toLocaleString()}</span><span class="stat-label">Tổng thu (VNĐ)</span></div>
    <div class="stat-item"><span class="stat-value">{stats.collected.toLocaleString()}</span><span class="stat-label">Đã thu (VNĐ)</span></div>
    <div class="stat-item"><span class="stat-value">{stats.pending}</span><span class="stat-label">Chờ thu</span></div>
  </div>
  <div class="revenue-content">
    <table class="data-table">
      <thead>
        <tr>
          <th>Người nộp thuế</th>
          <th>Loại thuế</th>
          <th>Số tiền (VNĐ)</th>
          <th>Ngày nộp</th>
          <th>Ghi chú</th>
          <th>Trạng thái</th>
        </tr>
      </thead>
      <tbody>
        {#each $revenues as revenue}
          <tr>
            <td><strong>{revenue.taxpayer}</strong></td>
            <td>{revenue.taxType}</td>
            <td>{revenue.amount.toLocaleString()}</td>
            <td>{revenue.paymentDate || '-'}</td>
            <td>{revenue.note}</td>
            <td>
              <span class="status-badge {revenue.status}">
                {revenue.status === 'paid' ? 'Đã nộp' : revenue.status === 'pending' ? 'Chờ nộp' : 'Quá hạn'}
              </span>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</div>

<style>
  .revenue-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .revenue-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .revenue-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .header-actions { display: flex; gap: 8px; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .secondary { padding: 10px 16px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-value { display: block; font-size: 1.25rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .revenue-content { flex: 1; overflow-y: auto; padding: 20px; }
  .data-table { width: 100%; background: white; border-radius: 8px; border-collapse: collapse; }
  .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
  .data-table th { background: #f8fafc; font-weight: 600; font-size: 0.875rem; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; }
  .status-badge.paid { background: #dcfce7; color: #166534; }
  .status-badge.pending { background: #fef3c7; color: #92400e; }
  .status-badge.overdue { background: #fee2e2; color: #991b1b; }
</style>
