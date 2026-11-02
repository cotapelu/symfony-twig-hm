<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';

  interface Device {
    id: string;
    name: string;
    type: 'camera' | 'sensor' | 'light' | 'gate' | 'speaker';
    location: string;
    status: 'online' | 'offline' | 'warning';
    lastActive: Date;
    battery?: number;
    signal?: number;
  }

  interface SensorData {
    temperature: number;
    humidity: number;
    airQuality: number;
    noise: number;
    timestamp: Date;
  }

  interface Camera {
    id: string;
    name: string;
    location: string;
    status: 'recording' | 'idle' | 'offline';
    lastMotion: Date | null;
    resolution: string;
  }

  let devices = writable<Device[]>([
    { id: '1', name: 'Camera Cổng chính', type: 'camera', location: 'Cổng UBND', status: 'online', lastActive: new Date(), signal: 85 },
    { id: '2', name: 'Camera Sân trường', type: 'camera', location: 'Trường Tiểu học', status: 'online', lastActive: new Date(), signal: 72 },
    { id: '3', name: 'Cảm biến A1', type: 'sensor', location: 'Khu vực Chợ', status: 'online', lastActive: new Date(), battery: 85 },
    { id: '4', name: 'Cảm biến A2', type: 'sensor', location: 'Công viên', status: 'warning', lastActive: new Date(Date.now() - 3600000), battery: 12 },
    { id: '5', name: 'Đèn B1', type: 'light', location: 'Đường Nguyễn Trãi', status: 'online', lastActive: new Date() },
    { id: '6', name: 'Đèn B2', type: 'light', location: 'Đường Lê Lợi', status: 'online', lastActive: new Date() },
    { id: '7', name: 'Cổng ra vào', type: 'gate', location: 'UBND Phường', status: 'online', lastActive: new Date() },
    { id: '8', name: 'Loa phát thanh', type: 'speaker', location: 'Trung tâm', status: 'online', lastActive: new Date() },
  ]);

  let cameras = writable<Camera[]>([
    { id: '1', name: 'Camera Cổng chính', location: 'Cổng UBND', status: 'recording', lastMotion: new Date(Date.now() - 300000), resolution: '1080p' },
    { id: '2', name: 'Camera Sân trường', location: 'Trường Tiểu học', status: 'recording', lastMotion: new Date(Date.now() - 600000), resolution: '1080p' },
    { id: '3', name: 'Camera Chợ', location: 'Chợ B', status: 'idle', lastMotion: new Date(Date.now() - 3600000), resolution: '720p' },
    { id: '4', name: 'Camera Công viên', location: 'Công viên A', status: 'recording', lastMotion: new Date(Date.now() - 180000), resolution: '1080p' },
  ]);

  let sensorData = writable<SensorData>({
    temperature: 28.5,
    humidity: 75,
    airQuality: 85,
    noise: 55,
    timestamp: new Date(),
  });

  let selectedDevice: Device | null = null;
  let activeView: 'overview' | 'cameras' | 'sensors' | 'controls' = 'overview';

  const deviceIcons: Record<string, string> = {
    camera: '📹',
    sensor: '📡',
    light: '💡',
    gate: '🚪',
    speaker: '📢',
  };

  const statusColors: Record<string, string> = {
    online: '#10b981',
    offline: '#ef4444',
    warning: '#f59e0b',
    recording: '#ef4444',
    idle: '#64748b',
  };

  function formatLastActive(date: Date): string {
    const diff = Date.now() - date.getTime();
    if (diff < 60000) return 'Vừa xong';
    if (diff < 3600000) return `${Math.floor(diff / 60000)} phút trước`;
    return `${Math.floor(diff / 3600000)} giờ trước`;
  }

  function getDeviceCountByStatus(devices: Device[]): { online: number; offline: number; warning: number } {
    return {
      online: devices.filter(d => d.status === 'online').length,
      offline: devices.filter(d => d.status === 'offline').length,
      warning: devices.filter(d => d.status === 'warning').length,
    };
  }

  onMount(() => {
    // Simulate sensor data updates
    const interval = setInterval(() => {
      sensorData.update(d => ({
        ...d,
        temperature: 26 + Math.random() * 4,
        humidity: 70 + Math.random() * 15,
        airQuality: 80 + Math.random() * 15,
        noise: 45 + Math.random() * 20,
        timestamp: new Date(),
      }));
    }, 5000);

    return () => clearInterval(interval);
  });
</script>

<div class="iot-container">
  <!-- Header -->
  <header class="iot-header">
    <div class="header-left">
      <h2>📡 IoT Dashboard</h2>
      <span class="subtitle">Giám sát thiết bị thông minh</span>
    </div>
    <div class="header-right">
      <div class="sync-status">
        <span class="sync-dot"></span>
        Đồng bộ: {new Date().toLocaleTimeString('vi-VN')}
      </div>
    </div>
  </header>

  <!-- Stats Overview -->
  <div class="stats-bar">
    <div class="stat-item">
      <span class="stat-icon">📹</span>
      <div class="stat-content">
        <span class="stat-value">{$cameras.filter(c => c.status === 'recording').length}</span>
        <span class="stat-label">Đang ghi</span>
      </div>
    </div>
    <div class="stat-item">
      <span class="stat-icon">📡</span>
      <div class="stat-content">
        <span class="stat-value">{$devices.filter(d => d.type === 'sensor').length}</span>
        <span class="stat-label">Cảm biến</span>
      </div>
    </div>
    <div class="stat-item">
      <span class="stat-icon">💡</span>
      <div class="stat-content">
        <span class="stat-value">{$devices.filter(d => d.type === 'light').length}</span>
        <span class="stat-label">Đèn</span>
      </div>
    </div>
    <div class="stat-item warning">
      <span class="stat-icon">⚠️</span>
      <div class="stat-content">
        <span class="stat-value">{getDeviceCountByStatus($devices).warning}</span>
        <span class="stat-label">Cảnh báo</span>
      </div>
    </div>
    <div class="stat-item offline">
      <span class="stat-icon">❌</span>
      <div class="stat-content">
        <span class="stat-value">{getDeviceCountByStatus($devices).offline}</span>
        <span class="stat-label">Offline</span>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="iot-content">
    <!-- Sidebar -->
    <aside class="iot-sidebar">
      <nav class="view-nav">
        <button 
          class="nav-btn" 
          class:active={activeView === 'overview'}
          on:click={() => activeView = 'overview'}
        >
          📊 Tổng quan
        </button>
        <button 
          class="nav-btn" 
          class:active={activeView === 'cameras'}
          on:click={() => activeView = 'cameras'}
        >
          📹 Camera ({$cameras.length})
        </button>
        <button 
          class="nav-btn" 
          class:active={activeView === 'sensors'}
          on:click={() => activeView = 'sensors'}
        >
          📡 Cảm biến ({$devices.filter(d => d.type === 'sensor').length})
        </button>
        <button 
          class="nav-btn" 
          class:active={activeView === 'controls'}
          on:click={() => activeView = 'controls'}
        >
          🎛️ Điều khiển
        </button>
      </nav>

      <!-- Device List -->
      <div class="device-list">
        <h4>Thiết bị</h4>
        {#each $devices as device}
          <button 
            class="device-item"
            class:selected={selectedDevice?.id === device.id}
            class:warning={device.status === 'warning'}
            class:offline={device.status === 'offline'}
            on:click={() => selectedDevice = device}
          >
            <span class="device-icon">{deviceIcons[device.type]}</span>
            <div class="device-info">
              <span class="device-name">{device.name}</span>
              <span class="device-location">{device.location}</span>
            </div>
            <span 
              class="device-status" 
              style="background: {statusColors[device.status]}"
            ></span>
          </button>
        {/each}
      </div>
    </aside>

    <!-- Main Area -->
    <main class="iot-main">
      {#if activeView === 'overview'}
        <div class="overview-grid">
          <!-- Sensor Data -->
          <div class="sensor-panel">
            <h3>📊 Dữ liệu cảm biến</h3>
            <div class="sensor-grid">
              <div class="sensor-card">
                <span class="sensor-icon">🌡️</span>
                <span class="sensor-value">{$sensorData.temperature.toFixed(1)}°C</span>
                <span class="sensor-label">Nhiệt độ</span>
              </div>
              <div class="sensor-card">
                <span class="sensor-icon">💧</span>
                <span class="sensor-value">{$sensorData.humidity.toFixed(0)}%</span>
                <span class="sensor-label">Độ ẩm</span>
              </div>
              <div class="sensor-card">
                <span class="sensor-icon">🌬️</span>
                <span class="sensor-value">{$sensorData.airQuality.toFixed(0)}</span>
                <span class="sensor-label">Chất lượng KK</span>
              </div>
              <div class="sensor-card">
                <span class="sensor-icon">🔊</span>
                <span class="sensor-value">{$sensorData.noise.toFixed(0)} dB</span>
                <span class="sensor-label">Tiếng ồn</span>
              </div>
            </div>
          </div>

          <!-- Camera Grid -->
          <div class="camera-panel">
            <h3>📹 Camera ({$cameras.filter(c => c.status === 'recording').length} đang ghi)</h3>
            <div class="camera-grid">
              {#each $cameras as camera}
                <div class="camera-card">
                  <div class="camera-preview">
                    <span class="camera-placeholder">📹</span>
                    <span class="camera-status" style="background: {statusColors[camera.status]}">
                      {camera.status}
                    </span>
                  </div>
                  <div class="camera-info">
                    <span class="camera-name">{camera.name}</span>
                    <span class="camera-location">{camera.location}</span>
                    {#if camera.lastMotion}
                      <span class="camera-motion">Chuyển động: {formatLastActive(camera.lastMotion)}</span>
                    {/if}
                  </div>
                </div>
              {/each}
            </div>
          </div>
        </div>

      {:else if activeView === 'cameras'}
        <div class="camera-view">
          <h3>📹 Quản lý Camera</h3>
          <div class="camera-list">
            {#each $cameras as camera}
              <div class="camera-full-card">
                <div class="camera-feed">
                  <span class="feed-placeholder">📹 {camera.name}</span>
                </div>
                <div class="camera-details">
                  <h4>{camera.name}</h4>
                  <p>📍 {camera.location}</p>
                  <p>📺 {camera.resolution}</p>
                  <p>🕐 {camera.lastMotion ? `Chuyển động cuối: ${formatLastActive(camera.lastMotion)}` : 'Không có chuyển động'}</p>
                  <div class="camera-actions">
                    <button class="action-btn">▶️ Xem live</button>
                    <button class="action-btn">📹 Ghi lại</button>
                    <button class="action-btn">⚙️ Cài đặt</button>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        </div>

      {:else if activeView === 'sensors'}
        <div class="sensors-view">
          <h3>📡 Quản lý Cảm biến</h3>
          <div class="sensors-grid">
            {#each $devices.filter(d => d.type === 'sensor') as sensor}
              <div class="sensor-full-card" class:warning={sensor.status === 'warning'}>
                <div class="sensor-header">
                  <span class="sensor-icon-large">📡</span>
                  <div class="sensor-title">
                    <h4>{sensor.name}</h4>
                    <span>{sensor.location}</span>
                  </div>
                  <span class="sensor-badge" style="background: {statusColors[sensor.status]}">
                    {sensor.status}
                  </span>
                </div>
                <div class="sensor-stats">
                  <div class="sensor-stat">
                    <span class="label">Pin</span>
                    <div class="battery-bar">
                      <div class="battery-fill" style="width: {sensor.battery || 0}%; background: {sensor.battery && sensor.battery < 20 ? '#ef4444' : '#10b981'}"></div>
                    </div>
                    <span class="value">{sensor.battery || 0}%</span>
                  </div>
                  <div class="sensor-stat">
                    <span class="label">Tín hiệu</span>
                    <div class="signal-bars">
                      <div class="signal-bar" class:active={(sensor.signal || 0) > 20}></div>
                      <div class="signal-bar" class:active={(sensor.signal || 0) > 40}></div>
                      <div class="signal-bar" class:active={(sensor.signal || 0) > 60}></div>
                      <div class="signal-bar" class:active={(sensor.signal || 0) > 80}></div>
                    </div>
                    <span class="value">{sensor.signal || 0}%</span>
                  </div>
                </div>
                <div class="sensor-actions">
                  <button class="action-btn">📊 Xem dữ liệu</button>
                  <button class="action-btn">⚙️ Cấu hình</button>
                </div>
              </div>
            {/each}
          </div>
        </div>

      {:else if activeView === 'controls'}
        <div class="controls-view">
          <h3>🎛️ Điều khiển thiết bị</h3>
          
          <!-- Lights Control -->
          <div class="control-section">
            <h4>💡 Điều khiển đèn</h4>
            <div class="controls-grid">
              {#each $devices.filter(d => d.type === 'light') as light}
                <div class="control-card">
                  <span class="control-icon">💡</span>
                  <div class="control-info">
                    <span class="control-name">{light.name}</span>
                    <span class="control-location">{light.location}</span>
                  </div>
                  <div class="control-toggle">
                    <label class="switch">
                      <input type="checkbox" checked={light.status === 'online'}>
                      <span class="slider"></span>
                    </label>
                  </div>
                  <div class="control-actions">
                    <button class="small-btn">💡</button>
                    <button class="small-btn">🌙</button>
                    <button class="small-btn">☀️</button>
                  </div>
                </div>
              {/each}
            </div>
          </div>

          <!-- Gates Control -->
          <div class="control-section">
            <h4>🚪 Điều khiển cổng</h4>
            <div class="controls-grid">
              {#each $devices.filter(d => d.type === 'gate') as gate}
                <div class="control-card">
                  <span class="control-icon">🚪</span>
                  <div class="control-info">
                    <span class="control-name">{gate.name}</span>
                    <span class="control-location">{gate.location}</span>
                  </div>
                  <div class="gate-controls">
                    <button class="action-btn primary">Mở cổng</button>
                    <button class="action-btn">Đóng cổng</button>
                  </div>
                </div>
              {/each}
            </div>
          </div>

          <!-- Speaker Control -->
          <div class="control-section">
            <h4>📢 Loa phát thanh</h4>
            <div class="controls-grid">
              {#each $devices.filter(d => d.type === 'speaker') as speaker}
                <div class="control-card wide">
                  <span class="control-icon">📢</span>
                  <div class="control-info">
                    <span class="control-name">{speaker.name}</span>
                    <span class="control-location">{speaker.location}</span>
                  </div>
                  <div class="speaker-controls">
                    <button class="action-btn">🔊 Phát thông báo</button>
                    <button class="action-btn">🎵 Phát nhạc</button>
                    <button class="action-btn warning">🛑 Dừng</button>
                  </div>
                </div>
              {/each}
            </div>
          </div>
        </div>
      {/if}
    </main>
  </div>
</div>

<style>
  .iot-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #f1f5f9;
  }

  .iot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
  }

  .header-left h2 {
    margin: 0;
    font-size: 1.25rem;
  }

  .subtitle {
    font-size: 0.875rem;
    opacity: 0.9;
  }

  .sync-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
  }

  .sync-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }

  .stats-bar {
    display: flex;
    gap: 16px;
    padding: 16px 24px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
  }

  .stat-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f8fafc;
    border-radius: 8px;
    flex: 1;
  }

  .stat-item.warning { background: #fef3c7; }
  .stat-item.offline { background: #fee2e2; }

  .stat-icon {
    font-size: 1.5rem;
  }

  .stat-content {
    display: flex;
    flex-direction: column;
  }

  .stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  .iot-content {
    display: flex;
    flex: 1;
    overflow: hidden;
  }

  .iot-sidebar {
    width: 280px;
    background: white;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
  }

  .view-nav {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    border-bottom: 1px solid #e2e8f0;
  }

  .nav-btn {
    padding: 10px 16px;
    border: none;
    background: transparent;
    text-align: left;
    cursor: pointer;
    border-radius: 8px;
    font-size: 0.9375rem;
    color: #475569;
    transition: all 0.2s;
  }

  .nav-btn:hover, .nav-btn.active {
    background: #eff6ff;
    color: #1d4ed8;
  }

  .device-list {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
  }

  .device-list h4 {
    margin: 0 0 12px;
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
  }

  .device-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
    cursor: pointer;
    border-radius: 8px;
    margin-bottom: 4px;
    transition: background 0.2s;
  }

  .device-item:hover {
    background: #f1f5f9;
  }

  .device-item.selected {
    background: #eff6ff;
  }

  .device-item.warning {
    background: #fef3c7;
  }

  .device-item.offline {
    background: #fee2e2;
    opacity: 0.7;
  }

  .device-icon {
    font-size: 1.25rem;
  }

  .device-info {
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .device-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #1e293b;
  }

  .device-location {
    font-size: 0.75rem;
    color: #64748b;
  }

  .device-status {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  .iot-main {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
  }

  .overview-grid {
    display: grid;
    gap: 24px;
  }

  .sensor-panel, .camera-panel {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .sensor-panel h3, .camera-panel h3, .camera-view h3, .sensors-view h3, .controls-view h3 {
    margin: 0 0 16px;
    font-size: 1rem;
    color: #1e293b;
  }

  .sensor-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
  }

  .sensor-card {
    text-align: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
  }

  .sensor-icon {
    font-size: 1.5rem;
    display: block;
    margin-bottom: 8px;
  }

  .sensor-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    display: block;
  }

  .sensor-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  .camera-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }

  .camera-card {
    background: #f8fafc;
    border-radius: 8px;
    overflow: hidden;
  }

  .camera-preview {
    height: 120px;
    background: #1e293b;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
  }

  .camera-placeholder {
    font-size: 2rem;
    opacity: 0.5;
  }

  .camera-status {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    color: white;
    text-transform: uppercase;
  }

  .camera-info {
    padding: 12px;
  }

  .camera-name {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    color: #1e293b;
  }

  .camera-location {
    font-size: 0.75rem;
    color: #64748b;
  }

  .camera-motion {
    font-size: 0.7rem;
    color: #f59e0b;
  }

  .camera-view, .sensors-view, .controls-view {
    background: white;
    border-radius: 12px;
    padding: 20px;
  }

  .camera-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .camera-full-card {
    display: flex;
    gap: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
  }

  .camera-feed {
    width: 320px;
    height: 180px;
    background: #1e293b;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .feed-placeholder {
    color: #64748b;
    font-size: 0.875rem;
  }

  .camera-details h4 {
    margin: 0 0 8px;
    font-size: 1rem;
  }

  .camera-details p {
    margin: 4px 0;
    font-size: 0.875rem;
    color: #64748b;
  }

  .camera-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
  }

  .action-btn {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.2s;
  }

  .action-btn:hover {
    background: #f1f5f9;
  }

  .action-btn.primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
  }

  .action-btn.warning {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
  }

  .sensors-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }

  .sensor-full-card {
    background: #f8fafc;
    border-radius: 8px;
    padding: 16px;
  }

  .sensor-full-card.warning {
    background: #fef3c7;
  }

  .sensor-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
  }

  .sensor-icon-large {
    font-size: 1.5rem;
  }

  .sensor-title {
    flex: 1;
  }

  .sensor-title h4 {
    margin: 0;
    font-size: 0.9375rem;
  }

  .sensor-title span {
    font-size: 0.75rem;
    color: #64748b;
  }

  .sensor-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    color: white;
    text-transform: uppercase;
  }

  .sensor-stats {
    display: flex;
    gap: 24px;
    margin-bottom: 12px;
  }

  .sensor-stat {
    flex: 1;
  }

  .sensor-stat .label {
    font-size: 0.75rem;
    color: #64748b;
    display: block;
    margin-bottom: 4px;
  }

  .sensor-stat .value {
    font-size: 0.875rem;
    font-weight: 600;
  }

  .battery-bar {
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    margin-bottom: 4px;
    overflow: hidden;
  }

  .battery-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
  }

  .signal-bars {
    display: flex;
    gap: 2px;
    align-items: flex-end;
    height: 16px;
    margin-bottom: 4px;
  }

  .signal-bar {
    width: 4px;
    background: #e2e8f0;
    border-radius: 1px;
  }

  .signal-bar:nth-child(1) { height: 4px; }
  .signal-bar:nth-child(2) { height: 8px; }
  .signal-bar:nth-child(3) { height: 12px; }
  .signal-bar:nth-child(4) { height: 16px; }

  .signal-bar.active {
    background: #10b981;
  }

  .sensor-actions {
    display: flex;
    gap: 8px;
  }

  .control-section {
    margin-bottom: 24px;
  }

  .control-section h4 {
    margin: 0 0 12px;
    font-size: 0.9375rem;
    color: #1e293b;
  }

  .controls-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
  }

  .control-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
  }

  .control-card.wide {
    grid-column: span 2;
  }

  .control-icon {
    font-size: 1.5rem;
  }

  .control-info {
    flex: 1;
  }

  .control-name {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
  }

  .control-location {
    font-size: 0.75rem;
    color: #64748b;
  }

  .switch {
    position: relative;
    width: 44px;
    height: 24px;
  }

  .switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #cbd5e1;
    border-radius: 24px;
    transition: 0.3s;
  }

  .slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: 0.3s;
  }

  input:checked + .slider {
    background: #3b82f6;
  }

  input:checked + .slider:before {
    transform: translateX(20px);
  }

  .control-actions, .gate-controls, .speaker-controls {
    display: flex;
    gap: 8px;
  }

  .small-btn {
    padding: 6px 8px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.75rem;
  }

  .small-btn:hover {
    background: #f1f5f9;
  }
</style>
