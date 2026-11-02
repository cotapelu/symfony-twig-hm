<script lang="ts">
  let sensors = [
    { id: 'AQI-001', name: 'Cảm biến AQI 1', location: 'Nguyễn Trãi', value: 45, status: 'good', type: 'air' },
    { id: 'AQI-002', name: 'Cảm biến AQI 2', location: 'Lý Thường Kiệt', value: 72, status: 'moderate', type: 'air' },
    { id: 'WATER-001', name: 'Cảm biến nước', location: 'Hồ Hoàn Kiếm', value: 8.2, status: 'good', type: 'water' },
    { id: 'NOISE-001', name: 'Cảm biến tiếng ồn', location: 'Đường vành đai', value: 65, status: 'warning', type: 'noise' },
    { id: 'TEMP-001', name: 'Nhiệt độ/Độ ẩm', location: 'Công viên', value: 28, humidity: 65, status: 'good', type: 'weather' }
  ];

  function getStatusColor(status: string) {
    return status === 'good' ? '#10b981' : status === 'moderate' ? '#f59e0b' : status === 'warning' ? '#ef4444' : '#6b7280';
  }

  function getStatusText(status: string) {
    return status === 'good' ? 'Tốt' : status === 'moderate' ? 'Trung bình' : status === 'warning' ? 'Cảnh báo' : 'Không xác định';
  }

  function getIcon(type: string) {
    return type === 'air' ? '💨' : type === 'water' ? '💧' : type === 'noise' ? '🔊' : '🌡️';
  }
</script>

<div class="env-page">
  <header><h1>🌿 Giám Sát Môi Trường</h1></header>

  <div class="overview-cards">
    <div class="overview-card air">
      <div class="overview-icon">💨</div>
      <div class="overview-info">
        <div class="overview-value">45</div>
        <div class="overview-label">AQI Trung bình</div>
      </div>
    </div>
    <div class="overview-card water">
      <div class="overview-icon">💧</div>
      <div class="overview-info">
        <div class="overview-value">8.2</div>
        <div class="overview-label">pH Nước</div>
      </div>
    </div>
    <div class="overview-card noise">
      <div class="overview-icon">🔊</div>
      <div class="overview-info">
        <div class="overview-value">65</div>
        <div class="overview-label">dB Tiếng ồn</div>
      </div>
    </div>
  </div>

  <h3>Cảm biến theo thời gian thực</h3>

  <div class="sensors-list">
    {#each sensors as sensor}
      <div class="sensor-card">
        <div class="sensor-icon">{getIcon(sensor.type)}</div>
        <div class="sensor-info">
          <div class="sensor-name">{sensor.name}</div>
          <div class="sensor-location">📍 {sensor.location}</div>
        </div>
        <div class="sensor-value">
          <div class="value">{sensor.value}{sensor.type === 'air' ? ' AQI' : sensor.type === 'water' ? ' pH' : sensor.type === 'noise' ? ' dB' : '°C'}</div>
          {#if sensor.type === 'weather'}
            <div class="humidity">Độ ẩm: {sensor.humidity}%</div>
          {/if}
        </div>
        <span class="sensor-status" style="background: {getStatusColor(sensor.status)}">{getStatusText(sensor.status)}</span>
      </div>
    {/each}
  </div>

  <button class="fab">+ Báo cáo sự cố</button>
</div>

<style>
  .env-page { padding: 20px; max-width: 600px; margin: 0 auto; background: #f5f5f5; min-height: 100vh; padding-bottom: 80px; }
  header h1 { font-size: 22px; font-weight: 700; margin: 0 0 16px 0; }
  h3 { font-size: 16px; font-weight: 600; margin: 20px 0 12px 0; }
  .overview-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
  .overview-card { background: white; border-radius: 16px; padding: 16px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .overview-icon { font-size: 32px; }
  .overview-value { font-size: 24px; font-weight: 700; color: #1f2937; }
  .overview-label { font-size: 11px; color: #6b7280; }
  .sensors-list { display: flex; flex-direction: column; gap: 10px; }
  .sensor-card { background: white; border-radius: 12px; padding: 14px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .sensor-icon { font-size: 28px; }
  .sensor-info { flex: 1; }
  .sensor-name { font-weight: 600; color: #1f2937; }
  .sensor-location { font-size: 12px; color: #9ca3af; }
  .sensor-value { text-align: right; }
  .value { font-size: 18px; font-weight: 700; color: #1f2937; }
  .humidity { font-size: 11px; color: #6b7280; }
  .sensor-status { padding: 4px 10px; border-radius: 12px; color: white; font-size: 11px; font-weight: 500; }
  .fab { position: fixed; bottom: 20px; right: 20px; width: 56px; height: 56px; border-radius: 50%; background: #10b981; color: white; border: none; font-size: 24px; cursor: pointer; }
</style>