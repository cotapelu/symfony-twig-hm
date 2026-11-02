<script lang="ts">
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';

  let currentTime = new Date();
  let activeTab = 'overview';

  let stats = {
    population: 45892,
    incidents: 12,
    pendingTasks: 45,
    activeOfficers: 38,
    cameras: 156,
    sensors: 234,
    iotDevices: 89,
    coverage: 98.5
  };

  let recentIncidents = [
    { id: 1, type: 'security', location: 'Phường Đống Đa', status: 'investigating', time: '10 phút trước' },
    { id: 2, type: 'environment', location: 'Phường Ba Đình', status: 'resolved', time: '25 phút trước' },
    { id: 3, type: 'traffic', location: 'Phường Hai Bà Trưng', status: 'pending', time: '45 phút trước' },
    { id: 4, type: 'infrastructure', location: 'Phường Hoàn Kiếm', status: 'investigating', time: '1 giờ trước' }
  ];

  let iotDevices = [
    { id: 1, name: 'Camera QH-001', type: 'camera', location: 'Ngã tư Đống Đa', status: 'online', battery: 85 },
    { id: 2, name: 'Cảm biến MT-042', type: 'sensor', location: 'Công viên Thủ Lệ', status: 'online', battery: 72 },
    { id: 3, name: 'Đèn LS-123', type: 'light', location: 'Đường Lý Thường Kiệt', status: 'online', battery: 95 },
    { id: 4, name: 'Camera QH-089', type: 'camera', location: 'Bến xe Gia Lâm', status: 'offline', battery: 0 },
    { id: 5, name: 'Cảm biến MT-156', type: 'sensor', location: 'Hồ Hoàn Kiếm', status: 'online', battery: 88 }
  ];

  let environmentalData = {
    airQuality: 72,
    temperature: 28,
    humidity: 65,
    noise: 52,
    pm25: 18,
    pm10: 35
  };

  let wardData = [
    { name: 'Đống Đa', population: 12580, incidents: 4, status: 'normal' },
    { name: 'Ba Đình', population: 9832, incidents: 2, status: 'normal' },
    { name: 'Hai Bà Trưng', population: 11234, incidents: 3, status: 'warning' },
    { name: 'Hoàn Kiếm', population: 8756, incidents: 2, status: 'normal' },
    { name: 'Thanh Xuân', population: 7490, incidents: 1, status: 'normal' }
  ];

  let notifications = [
    { id: 1, title: 'Cảnh báo an ninh', message: 'Phát hiện hoạt động bất thường tại khu vực Đống Đa', time: '2 phút trước', type: 'warning' },
    { id: 2, title: 'Báo cáo môi trường', message: 'Chỉ số PM2.5 tăng cao tại khu vực Ba Đình', time: '15 phút trước', type: 'info' },
    { id: 3, title: 'Cập nhật hệ thống', message: 'Đã nâng cấp phần mềm quản lý lên phiên bản 2.5.0', time: '1 giờ trước', type: 'success' }
  ];

  let weather = {
    temperature: 28,
    humidity: 65,
    wind: 12,
    condition: 'partly_cloudy'
  };

  onMount(() => {
    const interval = setInterval(() => {
      currentTime = new Date();
      stats.incidents = Math.floor(Math.random() * 20);
      environmentalData.pm25 = Math.floor(Math.random() * 30) + 10;
    }, 5000);

    return () => clearInterval(interval);
  });

  function getIncidentIcon(type: string) {
    switch (type) {
      case 'security': return '🔒';
      case 'environment': return '🌿';
      case 'traffic': return '🚗';
      case 'infrastructure': return '🏗️';
      default: return '⚠️';
    }
  }

  function getStatusClass(status: string) {
    switch (status) {
      case 'online': return 'status-online';
      case 'offline': return 'status-offline';
      case 'investigating': return 'status-investigating';
      case 'pending': return 'status-pending';
      case 'resolved': return 'status-resolved';
      case 'normal': return 'status-normal';
      case 'warning': return 'status-warning';
      default: return 'status-default';
    }
  }

  function formatTime(date: Date) {
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }

  function getAirQualityStatus(value: number) {
    if (value <= 50) return { label: 'Tốt', color: '#22c55e' };
    if (value <= 100) return { label: 'Trung bình', color: '#f59e0b' };
    return { label: 'Xấu', color: '#ef4444' };
  }
</script>

<svelte:head>
  <title>Smart City Dashboard - Phường/Xã Super App</title>
</svelte:head>

<div class="smartcity-page">
  <header class="page-header">
    <div class="header-content">
      <div class="header-left">
        <h1>🌆 Smart City Dashboard</h1>
        <p>Trung tâm điều hành thông minh - Phường/Xã Super App</p>
      </div>
      <div class="header-right">
        <div class="weather-widget">
          <span class="weather-icon">🌤️</span>
          <div class="weather-info">
            <span class="temp">{weather.temperature}°C</span>
            <span class="humidity">{weather.humidity}%</span>
          </div>
        </div>
        <div class="datetime">
          <div class="time">{formatTime(currentTime)}</div>
          <div class="date">{currentTime.toLocaleDateString('vi-VN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</div>
        </div>
      </div>
    </div>
  </header>

  <nav class="tabs">
    <button class="tab" class:active={activeTab === 'overview'} on:click={() => activeTab = 'overview'}>
      📊 Tổng Quan
    </button>
    <button class="tab" class:active={activeTab === 'iot'} on:click={() => activeTab = 'iot'}>
      📡 IoT & Thiết Bị
    </button>
    <button class="tab" class:active={activeTab === 'environment'} on:click={() => activeTab = 'environment'}>
      🌿 Môi Trường
    </button>
    <button class="tab" class:active={activeTab === 'map'} on:click={() => activeTab = 'map'}>
      🗺️ Bản Đồ
    </button>
    <button class="tab" class:active={activeTab === 'wards'} on:click={() => activeTab = 'wards'}>
      🏘️ Phường/Xã
    </button>
  </nav>

  <main class="main-content">
    {#if activeTab === 'overview'}
      <section class="stats-section">
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-info">
            <div class="stat-value">{stats.population.toLocaleString()}</div>
            <div class="stat-label">Dân Số</div>
          </div>
        </div>
        <div class="stat-card warning">
          <div class="stat-icon">⚠️</div>
          <div class="stat-info">
            <div class="stat-value">{stats.incidents}</div>
            <div class="stat-label">Sự Cố</div>
          </div>
        </div>
        <div class="stat-card info">
          <div class="stat-icon">📋</div>
          <div class="stat-info">
            <div class="stat-value">{stats.pendingTasks}</div>
            <div class="stat-label">Công Việc</div>
          </div>
        </div>
        <div class="stat-card success">
          <div class="stat-icon">👮</div>
          <div class="stat-info">
            <div class="stat-value">{stats.activeOfficers}</div>
            <div class="stat-label">Cán Bộ</div>
          </div>
        </div>
      </section>

      <div class="dashboard-grid">
        <section class="card incidents-card">
          <div class="card-header">
            <h3>📢 Sự Cố Gần Đây</h3>
            <button class="btn-view">Xem tất cả</button>
          </div>
          <div class="incidents-list">
            {#each recentIncidents as incident}
              <div class="incident-item">
                <div class="incident-icon">{getIncidentIcon(incident.type)}</div>
                <div class="incident-info">
                  <div class="incident-location">{incident.location}</div>
                  <div class="incident-time">{incident.time}</div>
                </div>
                <span class="badge {getStatusClass(incident.status)}">{incident.status}</span>
              </div>
            {/each}
          </div>
        </section>

        <section class="card iot-overview">
          <div class="card-header">
            <h3>📡 Thiết Bị IoT</h3>
            <button class="btn-view" on:click={() => activeTab = 'iot'}>Chi tiết</button>
          </div>
          <div class="iot-summary">
            <div class="iot-stat">
              <div class="iot-icon">📹</div>
              <div class="iot-value">{stats.cameras}</div>
              <div class="iot-label">Camera</div>
            </div>
            <div class="iot-stat">
              <div class="iot-icon">📊</div>
              <div class="iot-value">{stats.sensors}</div>
              <div class="iot-label">Cảm Biến</div>
            </div>
            <div class="iot-stat">
              <div class="iot-icon">💡</div>
              <div class="iot-value">{stats.iotDevices}</div>
              <div class="iot-label">Thiết Bị</div>
            </div>
            <div class="iot-stat">
              <div class="iot-icon">📶</div>
              <div class="iot-value">{stats.coverage}%</div>
              <div class="iot-label">Phủ Sóng</div>
            </div>
          </div>
        </section>

        <section class="card environment-card">
          <div class="card-header">
            <h3>🌿 Môi Trường</h3>
            <button class="btn-view" on:click={() => activeTab = 'environment'}>Chi tiết</button>
          </div>
          <div class="env-metrics">
            <div class="env-metric">
              <div class="env-label">Chất lượng không khí</div>
              <div class="env-value" style="color: {getAirQualityStatus(environmentalData.airQuality).color}">
                {environmentalData.airQuality}
              </div>
              <div class="env-status" style="color: {getAirQualityStatus(environmentalData.airQuality).color}">
                {getAirQualityStatus(environmentalData.airQuality).label}
              </div>
            </div>
            <div class="env-metric">
              <div class="env-label">PM2.5</div>
              <div class="env-value">{environmentalData.pm25}</div>
              <div class="env-status">µg/m³</div>
            </div>
            <div class="env-metric">
              <div class="env-label">Nhiệt độ</div>
              <div class="env-value">{environmentalData.temperature}°C</div>
              <div class="env-status">Bình thường</div>
            </div>
            <div class="env-metric">
              <div class="env-label">Độ ẩm</div>
              <div class="env-value">{environmentalData.humidity}%</div>
              <div class="env-status">Bình thường</div>
            </div>
          </div>
        </section>

        <section class="card notifications-card">
          <div class="card-header">
            <h3>🔔 Thông Báo</h3>
          </div>
          <div class="notifications-list">
            {#each notifications as notif}
              <div class="notification-item {notif.type}">
                <div class="notif-icon">
                  {#if notif.type === 'warning'}⚠️
                  {:else if notif.type === 'success'}✅
                  {:else}ℹ️{/if}
                </div>
                <div class="notif-content">
                  <div class="notif-title">{notif.title}</div>
                  <div class="notif-message">{notif.message}</div>
                  <div class="notif-time">{notif.time}</div>
                </div>
              </div>
            {/each}
          </div>
        </section>
      </div>

    {:else if activeTab === 'iot'}
      <section class="iot-section">
        <div class="card full-width">
          <div class="card-header">
            <h3>📡 Quản Lý Thiết Bị IoT</h3>
            <div class="header-actions">
              <button class="btn-primary">+ Thêm thiết bị</button>
            </div>
          </div>
          <div class="devices-grid">
            {#each iotDevices as device}
              <div class="device-card">
                <div class="device-header">
                  <span class="device-icon">
                    {#if device.type === 'camera'}📹
                    {:else if device.type === 'sensor'}📊
                    {:else}💡{/if}
                  </span>
                  <span class="badge {getStatusClass(device.status)}">{device.status}</span>
                </div>
                <div class="device-name">{device.name}</div>
                <div class="device-location">{device.location}</div>
                <div class="device-battery">
                  <div class="battery-bar">
                    <div class="battery-fill" style="width: {device.battery}%"></div>
                  </div>
                  <span class="battery-percent">{device.battery}%</span>
                </div>
                <div class="device-actions">
                  <button class="btn-icon">🔄</button>
                  <button class="btn-icon">⚙️</button>
                  <button class="btn-icon">📊</button>
                </div>
              </div>
            {/each}
          </div>
        </div>
      </section>

    {:else if activeTab === 'environment'}
      <section class="environment-section">
        <div class="env-dashboard">
          <div class="env-card large">
            <h3>🌬️ Chất Lượng Không Khí</h3>
            <div class="air-quality-gauge">
              <svg viewBox="0 0 200 100" class="gauge">
                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#e2e8f0" stroke-width="20"/>
                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="url(#gradient)" stroke-width="20" stroke-dasharray="251" stroke-dashoffset="88"/>
                <defs>
                  <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" style="stop-color:#22c55e"/>
                    <stop offset="50%" style="stop-color:#f59e0b"/>
                    <stop offset="100%" style="stop-color:#ef4444"/>
                  </linearGradient>
                </defs>
              </svg>
              <div class="gauge-value" style="color: {getAirQualityStatus(environmentalData.airQuality).color}">
                {environmentalData.airQuality}
              </div>
              <div class="gauge-label">{getAirQualityStatus(environmentalData.airQuality).label}</div>
            </div>
            <div class="env-details">
              <div class="env-item">
                <span>PM2.5</span>
                <strong>{environmentalData.pm25} µg/m³</strong>
              </div>
              <div class="env-item">
                <span>PM10</span>
                <strong>{environmentalData.pm10} µg/m³</strong>
              </div>
              <div class="env-item">
                <span>Tiếng ồn</span>
                <strong>{environmentalData.noise} dB</strong>
              </div>
            </div>
          </div>

          <div class="env-card">
            <h3>🌡️ Thời Tiết</h3>
            <div class="weather-display">
              <div class="temp-large">{weather.temperature}°C</div>
              <div class="weather-desc">Trời nhiều mây</div>
              <div class="weather-details">
                <div>💧 {weather.humidity}%</div>
                <div>💨 {weather.wind} km/h</div>
              </div>
            </div>
          </div>

          <div class="env-card">
            <h3>📊 Theo Dõi Realtime</h3>
            <div class="realtime-chart">
              <div class="chart-placeholder">
                {#each Array(12) as _, i}
                  <div class="chart-bar" style="height: {30 + Math.random() * 50}%"></div>
                {/each}
              </div>
              <div class="chart-labels">
                {#each ['0h', '2h', '4h', '6h', '8h', '10h', '12h', '14h', '16h', '18h', '20h', '22h'] as label}
                  <span>{label}</span>
                {/each}
              </div>
            </div>
          </div>
        </div>
      </section>

    {:else if activeTab === 'map'}
      <section class="map-section">
        <div class="card full-width">
          <div class="card-header">
            <h3>🗺️ Bản Đồ GIS</h3>
            <div class="map-controls">
              <button class="btn-secondary">+ Thêm marker</button>
              <button class="btn-secondary">📍 Định vị</button>
            </div>
          </div>
          <div class="map-placeholder">
            <div class="map-content">
              <div class="map-message">🗺️ Interactive GIS Map</div>
              <p>Bản đồ tương tác với các lớp thông tin:</p>
              <div class="map-layers">
                <div class="layer">📍 Địa điểm quan trọng</div>
                <div class="layer">🚗 Giao thông</div>
                <div class="layer">📹 Camera giám sát</div>
                <div class="layer">⚠️ Sự cố</div>
                <div class="layer">🌿 Môi trường</div>
              </div>
            </div>
          </div>
        </div>
      </section>

    {:else if activeTab === 'wards'}
      <section class="wards-section">
        <div class="card full-width">
          <div class="card-header">
            <h3>🏘️ Quản Lý Phường/Xã</h3>
            <button class="btn-primary">+ Thêm phường/xã</button>
          </div>
          <div class="wards-table">
            <table>
              <thead>
                <tr>
                  <th>Tên Phường/Xã</th>
                  <th>Dân Số</th>
                  <th>Sự Cố</th>
                  <th>Cán Bộ</th>
                  <th>Trạng Thái</th>
                  <th>Thao Tác</th>
                </tr>
              </thead>
              <tbody>
                {#each wardData as ward}
                  <tr>
                    <td><strong>{ward.name}</strong></td>
                    <td>{ward.population.toLocaleString()}</td>
                    <td>{ward.incidents}</td>
                    <td>{Math.floor(ward.population / 1000)}</td>
                    <td><span class="badge {getStatusClass(ward.status)}">{ward.status}</span></td>
                    <td>
                      <button class="btn-icon">👁️</button>
                      <button class="btn-icon">✏️</button>
                      <button class="btn-icon">📊</button>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        </div>
      </section>
    {/if}
  </main>
</div>

<style>
  .smartcity-page {
    min-height: 100vh;
    background: #0f172a;
    color: white;
  }

  .page-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    padding: 1.5rem 2rem;
  }

  .header-content {
    max-width: 1600px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .header-left h1 {
    margin: 0;
    font-size: 1.5rem;
  }

  .header-left p {
    margin: 0.25rem 0 0;
    opacity: 0.9;
    font-size: 0.875rem;
  }

  .header-right {
    display: flex;
    gap: 2rem;
    align-items: center;
  }

  .weather-widget {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(255,255,255,0.15);
    padding: 0.5rem 1rem;
    border-radius: 12px;
  }

  .weather-icon {
    font-size: 1.5rem;
  }

  .weather-info {
    display: flex;
    flex-direction: column;
  }

  .temp {
    font-weight: 700;
    font-size: 1.125rem;
  }

  .humidity {
    font-size: 0.75rem;
    opacity: 0.9;
  }

  .datetime {
    text-align: right;
  }

  .time {
    font-size: 1.25rem;
    font-weight: 700;
  }

  .date {
    font-size: 0.75rem;
    opacity: 0.9;
  }

  .tabs {
    background: #1e293b;
    padding: 0 2rem;
    display: flex;
    gap: 0.5rem;
    max-width: 1600px;
    margin: 0 auto;
    border-bottom: 1px solid #334155;
  }

  .tab {
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    color: #94a3b8;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    font-size: 0.9rem;
  }

  .tab:hover {
    color: white;
  }

  .tab.active {
    color: #60a5fa;
    border-bottom-color: #3b82f6;
  }

  .main-content {
    max-width: 1600px;
    margin: 0 auto;
    padding: 1.5rem;
  }

  .stats-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .stat-card {
    background: #1e293b;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid #334155;
  }

  .stat-card.warning { border-left: 4px solid #f59e0b; }
  .stat-card.info { border-left: 4px solid #3b82f6; }
  .stat-card.success { border-left: 4px solid #22c55e; }

  .stat-icon {
    font-size: 2rem;
  }

  .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #94a3b8;
  }

  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }

  .card {
    background: #1e293b;
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid #334155;
  }

  .card.full-width {
    grid-column: 1 / -1;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .card-header h3 {
    margin: 0;
    font-size: 1rem;
    color: #e2e8f0;
  }

  .btn-view {
    background: none;
    border: none;
    color: #60a5fa;
    cursor: pointer;
    font-size: 0.8rem;
  }

  .btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .btn-secondary {
    background: #334155;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    padding: 0.25rem;
  }

  .badge {
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
  }

  .status-online { background: #22c55e; color: white; }
  .status-offline { background: #ef4444; color: white; }
  .status-investigating { background: #f59e0b; color: white; }
  .status-pending { background: #6366f1; color: white; }
  .status-resolved { background: #22c55e; color: white; }
  .status-normal { background: #22c55e; color: white; }
  .status-warning { background: #f59e0b; color: white; }
  .status-default { background: #64748b; color: white; }

  .incidents-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .incident-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #0f172a;
    border-radius: 8px;
  }

  .incident-icon {
    font-size: 1.25rem;
  }

  .incident-info {
    flex: 1;
  }

  .incident-location {
    font-weight: 500;
  }

  .incident-time {
    font-size: 0.75rem;
    color: #94a3b8;
  }

  .iot-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
  }

  .iot-stat {
    text-align: center;
    padding: 1rem;
    background: #0f172a;
    border-radius: 8px;
  }

  .iot-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }

  .iot-value {
    font-size: 1.5rem;
    font-weight: 700;
  }

  .iot-label {
    font-size: 0.75rem;
    color: #94a3b8;
  }

  .env-metrics {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
  }

  .env-metric {
    text-align: center;
    padding: 1rem;
    background: #0f172a;
    border-radius: 8px;
  }

  .env-label {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-bottom: 0.5rem;
  }

  .env-value {
    font-size: 1.5rem;
    font-weight: 700;
  }

  .env-status {
    font-size: 0.75rem;
  }

  .notifications-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .notification-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #0f172a;
    border-radius: 8px;
    border-left: 3px solid;
  }

  .notification-item.warning { border-left-color: #f59e0b; }
  .notification-item.success { border-left-color: #22c55e; }
  .notification-item.info { border-left-color: #3b82f6; }

  .notif-icon {
    font-size: 1.25rem;
  }

  .notif-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .notif-message {
    font-size: 0.8rem;
    color: #94a3b8;
  }

  .notif-time {
    font-size: 0.7rem;
    color: #64748b;
    margin-top: 0.25rem;
  }

  .devices-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
  }

  .device-card {
    background: #0f172a;
    border-radius: 12px;
    padding: 1rem;
  }

  .device-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
  }

  .device-icon {
    font-size: 1.5rem;
  }

  .device-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .device-location {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-bottom: 0.75rem;
  }

  .device-battery {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }

  .battery-bar {
    flex: 1;
    height: 6px;
    background: #334155;
    border-radius: 3px;
    overflow: hidden;
  }

  .battery-fill {
    height: 100%;
    background: #22c55e;
    border-radius: 3px;
  }

  .battery-percent {
    font-size: 0.75rem;
    color: #94a3b8;
  }

  .device-actions {
    display: flex;
    gap: 0.5rem;
  }

  .env-dashboard {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
  }

  .env-card {
    background: #1e293b;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #334155;
  }

  .env-card.large {
    grid-column: span 2;
  }

  .air-quality-gauge {
    position: relative;
    text-align: center;
    margin: 1rem 0;
  }

  .gauge {
    width: 200px;
    height: 100px;
  }

  .gauge-value {
    font-size: 3rem;
    font-weight: 700;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
  }

  .gauge-label {
    font-size: 1rem;
    color: #94a3b8;
  }

  .env-details {
    display: flex;
    justify-content: space-around;
    margin-top: 1rem;
  }

  .env-item {
    text-align: center;
  }

  .env-item span {
    display: block;
    font-size: 0.75rem;
    color: #94a3b8;
  }

  .env-item strong {
    font-size: 1.25rem;
  }

  .weather-display {
    text-align: center;
    padding: 1rem;
  }

  .temp-large {
    font-size: 3rem;
    font-weight: 700;
  }

  .weather-desc {
    color: #94a3b8;
    margin-bottom: 1rem;
  }

  .weather-details {
    display: flex;
    justify-content: center;
    gap: 2rem;
  }

  .realtime-chart {
    margin-top: 1rem;
  }

  .chart-placeholder {
    display: flex;
    align-items: flex-end;
    height: 100px;
    gap: 4px;
    padding-bottom: 0.5rem;
  }

  .chart-bar {
    flex: 1;
    background: linear-gradient(to top, #3b82f6, #60a5fa);
    border-radius: 2px 2px 0 0;
  }

  .chart-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.65rem;
    color: #64748b;
  }

  .map-placeholder {
    height: 500px;
    background: #0f172a;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .map-content {
    text-align: center;
    color: #94a3b8;
  }

  .map-message {
    font-size: 2rem;
    margin-bottom: 1rem;
  }

  .map-layers {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
    margin-top: 1rem;
  }

  .layer {
    background: #1e293b;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
  }

  .wards-table {
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #334155;
  }

  th {
    color: #94a3b8;
    font-weight: 600;
    font-size: 0.875rem;
  }

  @media (max-width: 1024px) {
    .dashboard-grid {
      grid-template-columns: 1fr;
    }

    .env-dashboard {
      grid-template-columns: 1fr;
    }

    .env-card.large {
      grid-column: 1;
    }

    .header-content {
      flex-direction: column;
      gap: 1rem;
      text-align: center;
    }
  }
</style>
