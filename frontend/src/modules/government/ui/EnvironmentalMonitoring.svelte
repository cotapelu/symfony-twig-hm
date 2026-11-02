<script lang="ts">
  import { writable } from 'svelte/store';

  interface SensorStation {
    id: string;
    name: string;
    location: string;
    type: 'air' | 'noise' | 'water';
    aqi?: number;
    noise?: number;
    pm25?: number;
    pm10?: number;
    temperature?: number;
    humidity?: number;
    status: 'online' | 'offline' | 'maintenance';
    lastUpdate: Date;
  }

  let stations = writable<SensorStation[]>([
    { id: '1', name: 'Trạm A1', location: 'Khu vực Chợ', type: 'air', aqi: 85, pm25: 45, pm10: 65, temperature: 28, humidity: 75, status: 'online', lastUpdate: new Date() },
    { id: '2', name: 'Trạm A2', location: 'Công viên Xanh', type: 'air', aqi: 42, pm25: 18, pm10: 28, temperature: 27, humidity: 80, status: 'online', lastUpdate: new Date() },
    { id: '3', name: 'Trạm B1', location: 'Đường Nguyễn Trãi', type: 'noise', noise: 65, temperature: 29, humidity: 70, status: 'online', lastUpdate: new Date() },
    { id: '4', name: 'Trạm B2', location: 'KCN Y', type: 'noise', noise: 78, temperature: 30, humidity: 65, status: 'online', lastUpdate: new Date() },
    { id: '5', name: 'Trạm C1', location: 'Sông Z', type: 'water', status: 'offline', lastUpdate: new Date(Date.now() - 86400000) },
  ]);

  let activeType: 'all' | 'air' | 'noise' | 'water' = 'all';
  let selectedStation: SensorStation | null = null;

  function getAQILevel(aqi: number): { label: string; color: string } {
    if (aqi <= 50) return { label: 'Tốt', color: '#10b981' };
    if (aqi <= 100) return { label: 'Trung bình', color: '#f59e0b' };
    if (aqi <= 150) return { label: 'Không tốt cho nhóm nhạy cảm', color: '#f97316' };
    if (aqi <= 200) return { label: 'Không tốt', color: '#ef4444' };
    return { label: 'Rất không tốt', color: '#7f1d1d' };
  }

  function getNoiseLevel(db: number): { label: string; color: string } {
    if (db <= 50) return { label: 'Tốt', color: '#10b981' };
    if (db <= 70) return { label: 'Trung bình', color: '#f59e0b' };
    return { label: 'Nguy hiểm', color: '#ef4444' };
  }

  $: filteredStations = $stations.filter(s => {
    if (activeType === 'all') return true;
    return s.type === activeType;
  });

  $: onlineCount = $stations.filter(s => s.status === 'online').length;
  $: avgAQI = Math.round($stations.filter(s => s.aqi).reduce((a, b) => a + (b.aqi || 0), 0) / $stations.filter(s => s.aqi).length);
</script>

<div class="env-container">
  <header class="env-header">
    <div class="header-left">
      <h2>🌿 Giám sát Môi trường</h2>
      <span class="subtitle">Theo dõi chất lượng không khí, tiếng ồn, nước</span>
    </div>
    <div class="sync-info">
      <span class="sync-dot"></span>
      Cập nhật: {new Date().toLocaleTimeString('vi-VN')}
    </div>
  </header>

  <div class="stats-bar">
    <div class="stat-item">
      <span class="stat-value">{onlineCount}/{$stations.length}</span>
      <span class="stat-label">Trạm hoạt động</span>
    </div>
    <div class="stat-item" style="background: {getAQILevel(avgAQI).color}20">
      <span class="stat-value" style="color: {getAQILevel(avgAQI).color}">{avgAQI}</span>
      <span class="stat-label">AQI Trung bình</span>
    </div>
  </div>

  <div class="tab-nav">
    <button class="tab-btn" class:active={activeType === 'all'} on:click={() => activeType = 'all'}>🌍 Tất cả</button>
    <button class="tab-btn" class:active={activeType === 'air'} on:click={() => activeType = 'air'}>💨 Không khí</button>
    <button class="tab-btn" class:active={activeType === 'noise'} on:click={() => activeType = 'noise'}>🔊 Tiếng ồn</button>
    <button class="tab-btn" class:active={activeType === 'water'} on:click={() => activeType = 'water'}>💧 Nước</button>
  </div>

  <div class="env-content">
    <div class="stations-grid">
      {#each filteredStations as station (station.id)}
        <button 
          class="station-card"
          class:selected={selectedStation?.id === station.id}
          class:offline={station.status === 'offline'}
          on:click={() => selectedStation = station}
        >
          <div class="station-header">
            <span class="station-icon">
              {station.type === 'air' ? '💨' : station.type === 'noise' ? '🔊' : '💧'}
            </span>
            <div class="station-info">
              <h4>{station.name}</h4>
              <p>{station.location}</p>
            </div>
            <span class="status-dot" class:online={station.status === 'online'} class:maintenance={station.status === 'maintenance'}></span>
          </div>

          {#if station.type === 'air' && station.aqi}
            <div class="station-metric">
              <div class="metric-main">
                <span class="metric-value" style="color: {getAQILevel(station.aqi).color}">{station.aqi}</span>
                <span class="metric-label">AQI</span>
              </div>
              <div class="metric-badge" style="background: {getAQILevel(station.aqi).color}">
                {getAQILevel(station.aqi).label}
              </div>
            </div>
            <div class="station-details">
              <span>PM2.5: {station.pm25} µg/m³</span>
              <span>PM10: {station.pm10} µg/m³</span>
            </div>
          {:else if station.type === 'noise' && station.noise !== undefined}
            <div class="station-metric">
              <div class="metric-main">
                <span class="metric-value" style="color: {getNoiseLevel(station.noise).color}">{station.noise}</span>
                <span class="metric-label">dB</span>
              </div>
              <div class="metric-badge" style="background: {getNoiseLevel(station.noise).color}">
                {getNoiseLevel(station.noise).label}
              </div>
            </div>
          {:else if station.type === 'water'}
            <div class="station-metric offline">
              <span>Trạm offline</span>
            </div>
          {/if}

          <div class="station-footer">
            <span class="update-time">Cập nhật: {station.lastUpdate.toLocaleTimeString('vi-VN')}</span>
          </div>
        </button>
      {/each}
    </div>

    {#if selectedStation}
      <div class="detail-panel">
        <div class="detail-header">
          <h3>{selectedStation.name}</h3>
          <span class="status-badge" class:online={selectedStation.status === 'online'}>
            {selectedStation.status === 'online' ? 'Hoạt động' : selectedStation.status === 'maintenance' ? 'Bảo trì' : 'Offline'}
          </span>
        </div>
        <div class="detail-content">
          <div class="detail-row"><span>📍 Vị trí:</span><strong>{selectedStation.location}</strong></div>
          <div class="detail-row"><span>🕐 Cập nhật:</span><strong>{selectedStation.lastUpdate.toLocaleString('vi-VN')}</strong></div>
          
          {#if selectedStation.type === 'air'}
            <div class="chart-section">
              <h4>Chỉ số chi tiết</h4>
              <div class="chart-bars">
                <div class="chart-bar">
                  <span class="bar-label">AQI</span>
                  <div class="bar-track"><div class="bar-fill" style="width: {selectedStation.aqi || 0}%; background: {getAQILevel(selectedStation.aqi || 0).color}"></div></div>
                  <span class="bar-value">{selectedStation.aqi || 0}</span>
                </div>
                <div class="chart-bar">
                  <span class="bar-label">PM2.5</span>
                  <div class="bar-track"><div class="bar-fill" style="width: {(selectedStation.pm25 || 0) / 2}%; background: #3b82f6"></div></div>
                  <span class="bar-value">{selectedStation.pm25 || 0} µg/m³</span>
                </div>
                <div class="chart-bar">
                  <span class="bar-label">PM10</span>
                  <div class="bar-track"><div class="bar-fill" style="width: {(selectedStation.pm10 || 0) / 3}%; background: #8b5cf6"></div></div>
                  <span class="bar-value">{selectedStation.pm10 || 0} µg/m³</span>
                </div>
              </div>
            </div>
            <div class="detail-row"><span>🌡️ Nhiệt độ:</span><strong>{selectedStation.temperature || 0}°C</strong></div>
            <div class="detail-row"><span>💧 Độ ẩm:</span><strong>{selectedStation.humidity || 0}%</strong></div>
          {:else if selectedStation.type === 'noise'}
            <div class="detail-row"><span>🔊 Mức ồn:</span><strong style="color: {getNoiseLevel(selectedStation.noise || 0).color}">{selectedStation.noise} dB</strong></div>
            <div class="detail-row"><span>🌡️ Nhiệt độ:</span><strong>{selectedStation.temperature || 0}°C</strong></div>
            <div class="detail-row"><span>💧 Độ ẩm:</span><strong>{selectedStation.humidity || 0}%</strong></div>
          {/if}
        </div>
      </div>
    {/if}
  </div>
</div>

<style>
  .env-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .env-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; }
  .header-left h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; opacity: 0.9; }
  .sync-info { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; }
  .sync-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite; }
  @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .tab-nav { display: flex; gap: 8px; padding: 12px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .tab-btn { padding: 10px 20px; border: none; background: transparent; cursor: pointer; border-radius: 8px; font-size: 0.9375rem; color: #64748b; }
  .tab-btn.active { background: #d1fae5; color: #059669; }
  .env-content { display: flex; flex: 1; overflow: hidden; }
  .stations-grid { flex: 1; overflow-y: auto; padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
  .station-card { background: white; border-radius: 12px; padding: 16px; text-align: left; border: 2px solid transparent; cursor: pointer; transition: all 0.2s; }
  .station-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
  .station-card.selected { border-color: #10b981; }
  .station-card.offline { opacity: 0.6; }
  .station-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
  .station-icon { font-size: 1.5rem; }
  .station-info { flex: 1; }
  .station-info h4 { margin: 0; font-size: 0.9375rem; }
  .station-info p { margin: 2px 0 0; font-size: 0.75rem; color: #64748b; }
  .status-dot { width: 10px; height: 10px; background: #ef4444; border-radius: 50%; }
  .status-dot.online { background: #10b981; }
  .status-dot.maintenance { background: #f59e0b; }
  .station-metric { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .station-metric.offline { justify-content: center; color: #64748b; }
  .metric-main { display: flex; align-items: baseline; gap: 4px; }
  .metric-value { font-size: 1.75rem; font-weight: 700; }
  .metric-label { font-size: 0.875rem; color: #64748b; }
  .metric-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; color: white; }
  .station-details { display: flex; gap: 16px; font-size: 0.8rem; color: #64748b; margin-bottom: 12px; }
  .station-footer { border-top: 1px solid #f1f5f9; padding-top: 8px; }
  .update-time { font-size: 0.7rem; color: #94a3b8; }
  .detail-panel { width: 350px; background: white; border-left: 1px solid #e2e8f0; padding: 20px; overflow-y: auto; }
  .detail-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
  .detail-header h3 { margin: 0; font-size: 1.125rem; }
  .status-badge { padding: 4px 10px; background: #fee2e2; color: #dc2626; border-radius: 12px; font-size: 0.75rem; }
  .status-badge.online { background: #d1fae5; color: #059669; }
  .detail-content { display: flex; flex-direction: column; gap: 12px; }
  .detail-row { display: flex; justify-content: space-between; font-size: 0.875rem; }
  .detail-row span { color: #64748b; }
  .detail-row strong { color: #1e293b; }
  .chart-section { background: #f8fafc; padding: 16px; border-radius: 8px; margin: 12px 0; }
  .chart-section h4 { margin: 0 0 12px; font-size: 0.875rem; }
  .chart-bars { display: flex; flex-direction: column; gap: 12px; }
  .chart-bar { display: flex; align-items: center; gap: 8px; }
  .bar-label { width: 50px; font-size: 0.75rem; color: #64748b; }
  .bar-track { flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
  .bar-fill { height: 100%; border-radius: 4px; }
  .bar-value { width: 80px; text-align: right; font-size: 0.75rem; }
</style>
