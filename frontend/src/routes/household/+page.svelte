<script lang="ts">
  let households = [
    { id: 'HK001', address: '12 Nguyễn Trãi', head: 'Nguyễn Văn A', members: 4, ward: 'Đống Đa', status: 'permanent' },
    { id: 'HK002', address: '34 Lý Thường Kiệt', head: 'Trần Thị B', members: 3, ward: 'Đống Đa', status: 'permanent' },
    { id: 'HK003', address: '56 Hai Bà Trưng', head: 'Lê Văn C', members: 5, ward: 'Đống Đa', status: 'temporary' },
    { id: 'HK004', address: '78 Đội Cấn', head: 'Phạm Thị D', members: 2, ward: 'Ba Đình', status: 'permanent' }
  ];

  let stats = { total: 0, permanent: 0, temporary: 0, members: 0 };
  
  $: {
    stats.total = households.length;
    stats.permanent = households.filter(h => h.status === 'permanent').length;
    stats.temporary = households.filter(h => h.status === 'temporary').length;
    stats.members = households.reduce((sum, h) => sum + h.members, 0);
  }
</script>

<div class="household-page">
  <header><h1>👨‍👩‍👧‍👦 Quản Lý Hộ Khẩu</h1></header>

  <div class="stats-grid">
    <div class="stat-card"><div class="stat-value">{stats.total}</div><div class="stat-label">Tổng hộ</div></div>
    <div class="stat-card"><div class="stat-value">{stats.permanent}</div><div class="stat-label">Thường trú</div></div>
    <div class="stat-card"><div class="stat-value">{stats.temporary}</div><div class="stat-label">Tạm trú</div></div>
    <div class="stat-card"><div class="stat-value">{stats.members}</div><div class="stat-label">Nhân khẩu</div></div>
  </div>

  <div class="search-box">
    <input type="text" placeholder="🔍 Tìm kiếm hộ khẩu..." />
  </div>

  <div class="household-list">
    {#each households as hh}
      <div class="household-card">
        <div class="hh-icon">🏠</div>
        <div class="hh-info">
          <div class="hh-head">{hh.head}</div>
          <div class="hh-address">📍 {hh.address}</div>
          <div class="hh-meta">
            <span>👥 {hh.members} người</span>
            <span class="status" class:permanent={hh.status === 'permanent'}>{hh.status === 'permanent' ? 'Thường trú' : 'Tạm trú'}</span>
          </div>
        </div>
      </div>
    {/each}
  </div>

  <button class="fab">+</button>
</div>

<style>
  .household-page { padding: 20px; max-width: 600px; margin: 0 auto; background: #f5f5f5; min-height: 100vh; padding-bottom: 80px; }
  header h1 { font-size: 22px; font-weight: 700; margin: 0 0 16px 0; }
  .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 16px; }
  .stat-card { background: white; padding: 14px; border-radius: 10px; text-align: center; }
  .stat-value { font-size: 20px; font-weight: 700; color: #1f2937; }
  .stat-label { font-size: 11px; color: #6b7280; }
  .search-box { margin-bottom: 16px; }
  .search-box input { width: 100%; padding: 12px; border: none; border-radius: 10px; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); box-sizing: border-box; }
  .household-list { display: flex; flex-direction: column; gap: 10px; }
  .household-card { background: white; border-radius: 12px; padding: 14px; display: flex; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .hh-icon { font-size: 32px; }
  .hh-head { font-weight: 600; color: #1f2937; margin-bottom: 4px; }
  .hh-address { font-size: 13px; color: #6b7280; margin-bottom: 4px; }
  .hh-meta { font-size: 12px; color: #9ca3af; display: flex; gap: 10px; }
  .status { padding: 2px 8px; border-radius: 8px; background: #fef3c7; color: #d97706; }
  .status.permanent { background: #d1fae5; color: #059669; }
  .fab { position: fixed; bottom: 20px; right: 20px; width: 56px; height: 56px; border-radius: 50%; background: #3b82f6; color: white; border: none; font-size: 24px; cursor: pointer; }
</style>