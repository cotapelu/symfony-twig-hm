<script lang="ts">
  import { writable } from 'svelte/store';

  interface BudgetItem {
    id: string;
    category: string;
    item: string;
    planned: number;
    actual: number;
    quarter: string;
    status: 'on_track' | 'over' | 'under';
  }

  let budget = writable<BudgetItem[]>([
    { id: '1', category: 'Giáo dục', item: 'Trang thiết bị trường học', planned: 500000000, actual: 480000000, quarter: 'Q1', status: 'on_track' },
    { id: '2', category: 'Y tế', item: 'Trạm y tế', planned: 300000000, actual: 320000000, quarter: 'Q1', status: 'over' },
    { id: '3', category: 'Hạ tầng', item: 'Sửa đường', planned: 200000000, actual: 150000000, quarter: 'Q1', status: 'under' },
    { id: '4', category: 'Văn hóa', item: 'Hoạt động văn nghệ', planned: 100000000, actual: 95000000, quarter: 'Q1', status: 'on_track' },
  ]);

  $: totalPlanned = $budget.reduce((a, b) => a + b.planned, 0);
  $: totalActual = $budget.reduce((a, b) => a + b.actual, 0);
  $: utilizationRate = ((totalActual / totalPlanned) * 100).toFixed(1);
</script>

<div class="budget-container">
  <header class="budget-header">
    <div><h2>💵 Quản lý Ngân sách</h2><span class="subtitle">Theo dõi và giải ngân ngân sách</span></div>
    <div class="header-actions">
      <button class="primary">➕ Thêm khoản</button>
      <button class="secondary">📊 Xuất báo cáo</button>
    </div>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{(totalPlanned / 1000000).toFixed(0)}M</span><span class="stat-label">Kế hoạch (VNĐ)</span></div>
    <div class="stat-item"><span class="stat-value">{(totalActual / 1000000).toFixed(0)}M</span><span class="stat-label">Thực hiện (VNĐ)</span></div>
    <div class="stat-item highlight"><span class="stat-value">{utilizationRate}%</span><span class="stat-label">Tỷ lệ giải ngân</span></div>
  </div>
  <div class="budget-content">
    <table class="data-table">
      <thead>
        <tr>
          <th>Danh mục</th>
          <th>Khoản chi</th>
          <th>Quý</th>
          <th>Kế hoạch</th>
          <th>Thực hiện</th>
          <th>Chênh lệch</th>
          <th>Trạng thái</th>
        </tr>
      </thead>
      <tbody>
        {#each $budget as item}
          {@const diff = item.actual - item.planned}
          <tr>
            <td><strong>{item.category}</strong></td>
            <td>{item.item}</td>
            <td>{item.quarter}</td>
            <td>{item.planned.toLocaleString()}</td>
            <td>{item.actual.toLocaleString()}</td>
            <td class:over={diff > 0} class:under={diff < 0}>{diff > 0 ? '+' : ''}{diff.toLocaleString()}</td>
            <td>
              <span class="status-badge {item.status}">
                {item.status === 'on_track' ? 'Đúng tiến độ' : item.status === 'over' ? 'Vượt' : 'Tiết kiệm'}
              </span>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</div>

<style>
  .budget-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .budget-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .budget-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .header-actions { display: flex; gap: 8px; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .secondary { padding: 10px 16px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-item.highlight { background: #dbeafe; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .budget-content { flex: 1; overflow-y: auto; padding: 20px; }
  .data-table { width: 100%; background: white; border-radius: 8px; border-collapse: collapse; }
  .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
  .data-table th { background: #f8fafc; font-weight: 600; font-size: 0.875rem; }
  .over { color: #dc2626; }
  .under { color: #16a34a; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; }
  .status-badge.on_track { background: #dcfce7; color: #166534; }
  .status-badge.over { background: #fee2e2; color: #991b1b; }
  .status-badge.under { background: #dbeafe; color: #1e40af; }
</style>
