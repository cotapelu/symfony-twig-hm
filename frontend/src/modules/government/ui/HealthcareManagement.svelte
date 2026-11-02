<script lang="ts">
  import { writable } from 'svelte/store';

  interface HealthStation {
    id: string;
    name: string;
    address: string;
    doctors: number;
    nurses: number;
    beds: number;
    services: string[];
    status: 'active' | 'inactive';
    ward: string;
  }

  let stations = writable<HealthStation[]>([
    { id: '1', name: 'Trạm Y tế Phường X', address: '123 Nguyễn Trãi', doctors: 5, nurses: 10, beds: 20, services: ['Khám bệnh', 'Tiêm chủng', 'Tư vấn'], status: 'active', ward: 'Khu vực 1' },
    { id: '2', name: 'Phòng khám Đa khoa Y', address: '45 Lê Lợi', doctors: 12, nurses: 20, beds: 50, services: ['Khám bệnh', 'Xét nghiệm', 'Siêu âm', 'Tai nạn'], status: 'active', ward: 'Khu vực 2' },
  ]);

  $: stats = { total: $stations.length, doctors: $stations.reduce((a, b) => a + b.doctors, 0), nurses: $stations.reduce((a, b) => a + b.nurses, 0), beds: $stations.reduce((a, b) => a + b.beds, 0) };
</script>

<div class="health-container">
  <header class="health-header">
    <div><h2>🏥 Quản lý Y tế</h2><span class="subtitle">Trạm y tế và cơ sở khám chữa bệnh</span></div>
    <button class="primary">➕ Thêm trạm</button>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{stats.total}</span><span class="stat-label">Trạm y tế</span></div>
    <div class="stat-item"><span class="stat-value">{stats.doctors}</span><span class="stat-label">Bác sĩ</span></div>
    <div class="stat-item"><span class="stat-value">{stats.nurses}</span><span class="stat-label">Y tá</span></div>
    <div class="stat-item"><span class="stat-value">{stats.beds}</span><span class="stat-label">Giường bệnh</span></div>
  </div>
  <div class="health-content">
    <div class="stations-grid">
      {#each $stations as station}
        <div class="station-card">
          <div class="station-header">
            <span class="station-icon">🏥</span>
            <div class="station-info">
              <h4>{station.name}</h4>
              <p>{station.address}</p>
            </div>
          </div>
          <div class="station-stats">
            <div class="stat"><span class="label">Bác sĩ</span><span class="value">{station.doctors}</span></div>
            <div class="stat"><span class="label">Y tá</span><span class="value">{station.nurses}</span></div>
            <div class="stat"><span class="label">Giường</span><span class="value">{station.beds}</span></div>
          </div>
          <div class="station-services">
            {#each station.services as service}
              <span class="service-tag">{service}</span>
            {/each}
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .health-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .health-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .health-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .health-content { flex: 1; overflow-y: auto; padding: 20px; }
  .station-card { background: white; border-radius: 12px; padding: 16px; }
  .station-header { display: flex; gap: 12px; margin-bottom: 16px; }
  .station-icon { font-size: 2rem; }
  .station-info h4 { margin: 0; font-size: 1rem; }
  .station-info p { margin: 4px 0 0; font-size: 0.8rem; color: #64748b; }
  .station-stats { display: flex; gap: 24px; margin-bottom: 16px; }
  .stat { display: flex; flex-direction: column; }
  .stat .label { font-size: 0.75rem; color: #64748b; }
  .stat .value { font-size: 1.25rem; font-weight: 700; }
  .station-services { display: flex; gap: 8px; flex-wrap: wrap; }
  .service-tag { padding: 4px 10px; background: #dbeafe; color: #1d4ed8; border-radius: 12px; font-size: 0.75rem; }
</style>
