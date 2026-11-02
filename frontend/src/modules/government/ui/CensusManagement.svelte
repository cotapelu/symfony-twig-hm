<script lang="ts">
  import { writable } from 'svelte/store';

  interface CensusRecord {
    id: string;
    householdId: string;
    address: string;
    householdHead: string;
    totalMembers: number;
    males: number;
    females: number;
    under18: number;
    over60: number;
    ward: string;
    status: 'pending' | 'verified' | 'completed';
    lastUpdate: Date;
  }

  let records = writable<CensusRecord[]>([
    { id: '1', householdId: 'HH001', address: '123 Nguyễn Trãi', householdHead: 'Nguyễn Văn A', totalMembers: 4, males: 2, females: 2, under18: 1, over60: 1, ward: 'Khu vực 1', status: 'completed', lastUpdate: new Date() },
    { id: '2', householdId: 'HH002', address: '45 Lê Lợi', householdHead: 'Trần Thị B', totalMembers: 3, males: 1, females: 2, under18: 0, over60: 1, ward: 'Khu vực 1', status: 'verified', lastUpdate: new Date(Date.now() - 86400000) },
    { id: '3', householdId: 'HH003', address: '78 Quang Trung', householdHead: 'Lê Văn C', totalMembers: 5, males: 3, females: 2, under18: 2, over60: 0, ward: 'Khu vực 2', status: 'pending', lastUpdate: new Date(Date.now() - 172800000) },
    { id: '4', householdId: 'HH004', address: '90 30/4', householdHead: 'Phạm Thị D', totalMembers: 2, males: 1, females: 1, under18: 0, over60: 0, ward: 'Khu vực 2', status: 'completed', lastUpdate: new Date(Date.now() - 259200000) },
  ]);

  let activeTab: 'all' | 'pending' | 'verified' | 'completed' = 'all';
  let showAddModal = false;
  let newRecord = { address: '', householdHead: '', totalMembers: 0, ward: '' };

  $: filteredRecords = $records.filter(r => {
    if (activeTab === 'pending') return r.status === 'pending';
    if (activeTab === 'verified') return r.status === 'verified';
    if (activeTab === 'completed') return r.status === 'completed';
    return true;
  });

  $: stats = {
    total: $records.length,
    completed: $records.filter(r => r.status === 'completed').length,
    verified: $records.filter(r => r.status === 'verified').length,
    pending: $records.filter(r => r.status === 'pending').length,
    totalMembers: $records.reduce((a, b) => a + b.totalMembers, 0),
  };
</script>

<div class="census-container">
  <header class="census-header">
    <div class="header-left">
      <h2>👨‍👩‍👧‍👦 Tổng điều tra Dân số</h2>
      <span class="subtitle">Quản lý hộ khẩu và thành viên</span>
    </div>
    <button class="header-btn primary" on:click={() => showAddModal = true}>➕ Thêm hộ</button>
  </header>

  <div class="stats-bar">
    <div class="stat-item" on:click={() => activeTab = 'all'}>
      <span class="stat-value">{stats.total}</span>
      <span class="stat-label">Tổng hộ</span>
    </div>
    <div class="stat-item">
      <span class="stat-value">{stats.totalMembers}</span>
      <span class="stat-label">Tổng nhân khẩu</span>
    </div>
    <div class="stat-item completed" on:click={() => activeTab = 'completed'}>
      <span class="stat-value">{stats.completed}</span>
      <span class="stat-label">Hoàn thành</span>
    </div>
    <div class="stat-item pending" on:click={() => activeTab = 'pending'}>
      <span class="stat-value">{stats.pending}</span>
      <span class="stat-label">Chờ duyệt</span>
    </div>
  </div>

  <div class="census-content">
    <div class="records-table">
      <table>
        <thead>
          <tr>
            <th>Mã hộ</th>
            <th>Chủ hộ</th>
            <th>Địa chỉ</th>
            <th>Thành viên</th>
            <th>Nam/Nữ</th>
            <th>Khu vực</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          {#each filteredRecords as record (record.id)}
            <tr>
              <td><strong>{record.householdId}</strong></td>
              <td>{record.householdHead}</td>
              <td>{record.address}</td>
              <td>{record.totalMembers}</td>
              <td>{record.males}/{record.females}</td>
              <td>{record.ward}</td>
              <td>
                <span class="status-badge {record.status}">
                  {record.status === 'completed' ? 'Hoàn thành' : record.status === 'verified' ? 'Đã duyệt' : 'Chờ duyệt'}
                </span>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  </div>
</div>

{#if showAddModal}
  <div class="modal-overlay" on:click={() => showAddModal = false}>
    <div class="modal-content" on:click|stopPropagation>
      <h3>Thêm hộ mới</h3>
      <div class="form-group"><label>Địa chỉ</label><input type="text" bind:value={newRecord.address}></div>
      <div class="form-group"><label>Chủ hộ</label><input type="text" bind:value={newRecord.householdHead}></div>
      <div class="form-group"><label>Số nhân khẩu</label><input type="number" bind:value={newRecord.totalMembers}></div>
      <div class="form-group"><label>Khu vực</label><input type="text" bind:value={newRecord.ward}></div>
      <div class="modal-actions">
        <button on:click={() => showAddModal = false}>Hủy</button>
        <button class="primary" on:click={() => showAddModal = false}>Thêm</button>
      </div>
    </div>
  </div>
{/if}

<style>
  .census-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .census-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .header-left h2 { margin: 0; font-size: 1.25rem; color: #1e293b; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .header-btn { padding: 10px 16px; border: 1px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; }
  .header-btn.primary { background: #3b82f6; color: white; border-color: #3b82f6; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; cursor: pointer; }
  .stat-item.completed { background: #d1fae5; }
  .stat-item.pending { background: #fef3c7; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .census-content { flex: 1; overflow-y: auto; padding: 20px; }
  .records-table { background: white; border-radius: 12px; overflow: hidden; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; }
  th { background: #f8fafc; font-size: 0.8rem; color: #64748b; }
  td { font-size: 0.875rem; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; }
  .status-badge.completed { background: #d1fae5; color: #059669; }
  .status-badge.verified { background: #dbeafe; color: #1d4ed8; }
  .status-badge.pending { background: #fef3c7; color: #b45309; }
  .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
  .modal-content { background: white; border-radius: 12px; padding: 24px; width: 400px; }
  .modal-content h3 { margin: 0 0 16px; }
  .form-group { margin-bottom: 12px; }
  .form-group label { display: block; font-size: 0.875rem; margin-bottom: 4px; }
  .form-group input { width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px; }
  .modal-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 16px; }
  .modal-actions button { padding: 8px 16px; border-radius: 6px; cursor: pointer; }
  .modal-actions button.primary { background: #3b82f6; color: white; border: none; }
</style>
