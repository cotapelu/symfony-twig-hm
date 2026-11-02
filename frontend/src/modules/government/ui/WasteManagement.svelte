<script lang="ts">
  import { writable } from 'svelte/store';

  interface CollectionPoint {
    id: string;
    name: string;
    location: string;
    type: 'residential' | 'commercial' | 'industrial';
    fillLevel: number;
    lastCollection: Date;
    status: 'normal' | 'warning' | 'critical';
    ward: string;
  }

  interface CollectionSchedule {
    id: string;
    area: string;
    dayOfWeek: string;
    time: string;
    vehicle: string;
    status: 'scheduled' | 'in_progress' | 'completed';
  }

  let collectionPoints = writable<CollectionPoint[]>([
    { id: '1', name: 'Điểm tập kết A1', location: 'Đường Nguyễn Trãi', type: 'residential', fillLevel: 75, lastCollection: new Date(Date.now() - 86400000), status: 'warning', ward: 'Khu vực 1' },
    { id: '2', name: 'Điểm tập kết A2', location: 'Đường Lê Lợi', type: 'residential', fillLevel: 45, lastCollection: new Date(Date.now() - 43200000), status: 'normal', ward: 'Khu vực 1' },
    { id: '3', name: 'Điểm tập kết B1', location: 'Chợ B', type: 'commercial', fillLevel: 90, lastCollection: new Date(Date.now() - 172800000), status: 'critical', ward: 'Khu vực 2' },
    { id: '4', name: 'Điểm tập kết C1', location: 'KCN X', type: 'industrial', fillLevel: 60, lastCollection: new Date(Date.now() - 259200000), status: 'normal', ward: 'Khu vực 3' },
    { id: '5', name: 'Điểm tập kết A3', location: 'Công viên A', type: 'residential', fillLevel: 30, lastCollection: new Date(Date.now() - 21600000), status: 'normal', ward: 'Khu vực 1' },
  ]);

  let schedules = writable<CollectionSchedule[]>([
    { id: '1', area: 'Khu vực 1', dayOfWeek: 'Thứ 2', time: '06:00 - 08:00', vehicle: 'Xe A1', status: 'completed' },
    { id: '2', area: 'Khu vực 2', dayOfWeek: 'Thứ 2', time: '08:00 - 10:00', vehicle: 'Xe A2', status: 'completed' },
    { id: '3', area: 'Khu vực 3', dayOfWeek: 'Thứ 3', time: '06:00 - 08:00', vehicle: 'Xe A1', status: 'scheduled' },
    { id: '4', area: 'Khu vực 1', dayOfWeek: 'Thứ 4', time: '06:00 - 08:00', vehicle: 'Xe A2', status: 'scheduled' },
    { id: '5', area: 'Khu vực 2', dayOfWeek: 'Thứ 5', time: '08:00 - 10:00', vehicle: 'Xe A1', status: 'scheduled' },
  ]);

  let activeTab: 'points' | 'schedule' | 'reports' = 'points';

  const statusColors: Record<string, string> = {
    normal: '#10b981',
    warning: '#f59e0b',
    critical: '#ef4444',
  };

  function getFillLevelColor(level: number): string {
    if (level >= 80) return '#ef4444';
    if (level >= 60) return '#f59e0b';
    return '#10b981';
  }

  $: stats = {
    total: $collectionPoints.length,
    critical: $collectionPoints.filter(p => p.status === 'critical').length,
    warning: $collectionPoints.filter(p => p.status === 'warning').length,
    todayCollections: $schedules.filter(s => s.status === 'completed').length,
  };
</script>

<div class="waste-container">
  <header class="waste-header">
    <div class="header-left">
      <h2>🗑️ Quản lý Rác thải</h2>
      <span class="subtitle">Giám sát và thu gom rác thải</span>
    </div>
    <button class="header-btn primary">➕ Thêm điểm tập kết</button>
  </header>

  <div class="stats-bar">
    <div class="stat-item">
      <span class="stat-value">{stats.total}</span>
      <span class="stat-label">Tổng điểm</span>
    </div>
    <div class="stat-item critical">
      <span class="stat-value">{stats.critical}</span>
      <span class="stat-label">Quá tải</span>
    </div>
    <div class="stat-item warning">
      <span class="stat-value">{stats.warning}</span>
      <span class="stat-label">Cảnh báo</span>
    </div>
    <div class="stat-item">
      <span class="stat-value">{stats.todayCollections}</span>
      <span class="stat-label">Đã thu gom hôm nay</span>
    </div>
  </div>

  <div class="tab-nav">
    <button class="tab-btn" class:active={activeTab === 'points'} on:click={() => activeTab = 'points'}>📍 Điểm tập kết</button>
    <button class="tab-btn" class:active={activeTab === 'schedule'} on:click={() => activeTab = 'schedule'}>📅 Lịch thu gom</button>
    <button class="tab-btn" class:active={activeTab === 'reports'} on:click={() => activeTab = 'reports'}>📊 Báo cáo</button>
  </div>

  <div class="waste-content">
    {#if activeTab === 'points'}
      <div class="points-grid">
        {#each $collectionPoints as point (point.id)}
          <div class="point-card" class:critical={point.status === 'critical'} class:warning={point.status === 'warning'}>
            <div class="point-header">
              <span class="point-icon">🗑️</span>
              <div class="point-info">
                <h4>{point.name}</h4>
                <p>{point.location}</p>
              </div>
              <span class="status-badge" style="background: {statusColors[point.status]}">
                {point.status === 'normal' ? 'Bình thường' : point.status === 'warning' ? 'Cảnh báo' : 'Quá tải'}
              </span>
            </div>
            <div class="fill-level">
              <div class="fill-header">
                <span>Mức đầy</span>
                <span>{point.fillLevel}%</span>
              </div>
              <div class="fill-bar">
                <div class="fill-fill" style="width: {point.fillLevel}%; background: {getFillLevelColor(point.fillLevel)}"></div>
              </div>
            </div>
            <div class="point-footer">
              <span>🕐 Thu gom: {point.lastCollection.toLocaleDateString('vi-VN')}</span>
              <button class="action-btn">📞 Báo thu gom</button>
            </div>
          </div>
        {/each}
      </div>
    {:else if activeTab === 'schedule'}
      <div class="schedule-list">
        <table>
          <thead>
            <tr>
              <th>Khu vực</th>
              <th>Ngày</th>
              <th>Giờ</th>
              <th>Xe</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            {#each $schedules as schedule (schedule.id)}
              <tr>
                <td>{schedule.area}</td>
                <td>{schedule.dayOfWeek}</td>
                <td>{schedule.time}</td>
                <td>{schedule.vehicle}</td>
                <td>
                  <span class="status-badge" style="background: {schedule.status === 'completed' ? '#10b981' : schedule.status === 'in_progress' ? '#3b82f6' : '#64748b'}">
                    {schedule.status === 'completed' ? 'Hoàn thành' : schedule.status === 'in_progress' ? 'Đang thu gom' : 'Đã lên lịch'}
                  </span>
                </td>
                <td>
                  <button class="action-btn small">Xem</button>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {:else}
      <div class="reports-section">
        <div class="report-card">
          <h4>Tổng lượng rác thu gom</h4>
          <p class="report-value">125.5 tấn</p>
          <p class="report-change">+12% so với tháng trước</p>
        </div>
        <div class="report-card">
          <h4>Số chuyến thu gom</h4>
          <p class="report-value">156 chuyến</p>
          <p class="report-change">+8% so với tháng trước</p>
        </div>
        <div class="report-card">
          <h4>Tỷ lệ tái chế</h4>
          <p class="report-value">25%</p>
          <p class="report-change">+5% so với tháng trước</p>
        </div>
      </div>
    {/if}
  </div>
</div>

<style>
  .waste-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .waste-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .header-left h2 { margin: 0; font-size: 1.25rem; color: #1e293b; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .header-btn { padding: 10px 16px; border: 1px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; }
  .header-btn.primary { background: #10b981; color: white; border-color: #10b981; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-item.critical { background: #fee2e2; }
  .stat-item.warning { background: #fef3c7; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .tab-nav { display: flex; gap: 8px; padding: 12px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .tab-btn { padding: 10px 20px; border: none; background: transparent; cursor: pointer; border-radius: 8px; font-size: 0.9375rem; color: #64748b; }
  .tab-btn.active { background: #eff6ff; color: #3b82f6; }
  .waste-content { flex: 1; overflow-y: auto; padding: 20px; }
  .points-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
  .point-card { background: white; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .point-card.critical { border: 2px solid #ef4444; }
  .point-card.warning { border: 2px solid #f59e0b; }
  .point-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
  .point-icon { font-size: 1.5rem; }
  .point-info { flex: 1; }
  .point-info h4 { margin: 0; font-size: 0.9375rem; }
  .point-info p { margin: 2px 0 0; font-size: 0.8rem; color: #64748b; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; color: white; }
  .fill-level { margin-bottom: 12px; }
  .fill-header { display: flex; justify-content: space-between; font-size: 0.8rem; color: #64748b; margin-bottom: 4px; }
  .fill-bar { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
  .fill-fill { height: 100%; border-radius: 4px; transition: width 0.3s; }
  .point-footer { display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #64748b; }
  .action-btn { padding: 6px 12px; border: 1px solid #e2e8f0; background: white; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
  .action-btn.small { padding: 4px 8px; font-size: 0.75rem; }
  .schedule-list { background: white; border-radius: 12px; overflow: hidden; }
  .schedule-list table { width: 100%; border-collapse: collapse; }
  .schedule-list th, .schedule-list td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; }
  .schedule-list th { background: #f8fafc; font-size: 0.8rem; color: #64748b; }
  .schedule-list td { font-size: 0.875rem; }
  .reports-section { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
  .report-card { background: white; border-radius: 12px; padding: 20px; text-align: center; }
  .report-card h4 { margin: 0 0 12px; font-size: 0.875rem; color: #64748b; }
  .report-value { margin: 0; font-size: 2rem; font-weight: 700; color: #1e293b; }
  .report-change { margin: 8px 0 0; font-size: 0.8rem; color: #10b981; }
</style>
