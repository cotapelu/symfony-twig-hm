<script lang="ts">
  import { onMount } from 'svelte';

  let selectedLayer = 'all';
  let searchQuery = '';
  let selectedLocation: any = null;

  let mapMarkers = [
    { id: 1, name: 'UBND Phường Đống Đa', lat: 21.0285, lng: 105.8042, type: 'government', icon: '🏛️' },
    { id: 2, name: 'Trạm Y tế', lat: 21.0290, lng: 105.8050, type: 'health', icon: '🏥' },
    { id: 3, name: 'Công an Phường', lat: 21.0275, lng: 105.8035, type: 'police', icon: '👮' },
    { id: 4, name: 'Trường Tiểu học', lat: 21.0300, lng: 105.8060, type: 'school', icon: '🏫' },
    { id: 5, name: 'Chợ Đống Đa', lat: 21.0260, lng: 105.8020, type: 'market', icon: '🏪' },
    { id: 6, name: 'Bưu điện', lat: 21.0280, lng: 105.8045, type: 'post', icon: '📮' },
    { id: 7, name: 'Ngân hàng BIDV', lat: 21.0270, lng: 105.8030, type: 'bank', icon: '🏦' },
    { id: 8, name: 'Công viên', lat: 21.0310, lng: 105.8070, type: 'park', icon: '🌳' }
  ];

  let incidentReports = [
    { id: 1, type: 'traffic', location: 'Ngã tư Nguyễn Trãi', status: 'active', time: '10 phút trước' },
    { id: 2, type: 'environment', location: 'Đường Lý Thường Kiệt', status: 'resolved', time: '1 giờ trước' }
  ];

  let layers = [
    { id: 'all', name: 'Tất cả', icon: '🗺️' },
    { id: 'government', name: 'Cơ quan nhà nước', icon: '🏛️' },
    { id: 'health', name: 'Y tế', icon: '🏥' },
    { id: 'education', name: 'Giáo dục', icon: '🏫' },
    { id: 'commerce', name: 'Thương mại', icon: '🏪' }
  ];

  function selectLocation(marker: any) {
    selectedLocation = marker;
  }

  function getMarkerColor(type: string) {
    switch (type) {
      case 'government': return '#3b82f6';
      case 'health': return '#10b981';
      case 'police': return '#ef4444';
      case 'school': return '#8b5cf6';
      case 'market': return '#f59e0b';
      default: return '#6b7280';
    }
  }

  $: filteredMarkers = selectedLayer === 'all' 
    ? mapMarkers 
    : mapMarkers.filter(m => m.type === selectedLayer);
</script>

<div class="map-page">
  <header class="page-header">
    <h1>🗺️ Bản Đồ Số</h1>
    <button class="btn-icon">🔍</button>
  </header>

  <div class="search-box">
    <input type="text" placeholder="Tìm kiếm địa điểm..." bind:value={searchQuery} />
  </div>

  <div class="layers-bar">
    {#each layers as layer}
      <button class="layer-btn" class:active={selectedLayer === layer.id} on:click={() => selectedLayer = layer.id}>
        <span class="layer-icon">{layer.icon}</span>
        <span class="layer-name">{layer.name}</span>
      </button>
    {/each}
  </div>

  <div class="map-container">
    <div class="map-placeholder">
      <div class="map-grid">
        {#each filteredMarkers as marker}
          <div 
            class="map-marker" 
            style="left: {(marker.lng - 105.80) * 1000 + 50}%; top: {(21.035 - marker.lat) * 1000 + 50}%; background: {getMarkerColor(marker.type)}"
            on:click={() => selectLocation(marker)}
          >
            <span class="marker-icon">{marker.icon}</span>
          </div>
        {/each}
      </div>
      <div class="map-overlay-text">
        <span>🗺️ Bản đồ GIS tương tác</span>
        <small>Tap marker để xem chi tiết</small>
      </div>
    </div>
  </div>

  {#if selectedLocation}
    <div class="location-card">
      <button class="close-btn" on:click={() => selectedLocation = null}>×</button>
      <div class="location-icon">{selectedLocation.icon}</div>
      <h3>{selectedLocation.name}</h3>
      <div class="location-info">
        <p>📍 {selectedLocation.lat.toFixed(4)}, {selectedLocation.lng.toFixed(4)}</p>
        <p>📞 Liên hệ: 0243XXX</p>
        <p>⏰ Giờ mở cửa: 7h00 - 17h00</p>
      </div>
      <div class="location-actions">
        <button class="action-btn">📍 Chỉ đường</button>
        <button class="action-btn">📞 Gọi</button>
      </div>
    </div>
  {/if}

  <div class="incidents-panel">
    <h3>🚨 Sự cố trên bản đồ</h3>
    <div class="incidents-list">
      {#each incidentReports as incident}
        <div class="incident-item">
          <span class="incident-icon">
            {incident.type === 'traffic' ? '🚗' : '🌿'}
          </span>
          <div class="incident-info">
            <div class="incident-location">{incident.location}</div>
            <div class="incident-time">{incident.time}</div>
          </div>
          <span class="incident-status" class:active={incident.status === 'active'}>
            {incident.status === 'active' ? 'Đang xử lý' : 'Đã xử lý'}
          </span>
        </div>
      {/each}
    </div>
  </div>

  <div class="quick-locations">
    <h3>Địa điểm gần đây</h3>
    <div class="locations-grid">
      <div class="location-chip" on:click={() => selectLocation(mapMarkers[0])}>
        🏛️ UBND Phường
      </div>
      <div class="location-chip" on:click={() => selectLocation(mapMarkers[1])}>
        🏥 Trạm Y tế
      </div>
      <div class="location-chip" on:click={() => selectLocation(mapMarkers[3])}>
        🏫 Trường học
      </div>
      <div class="location-chip" on:click={() => selectLocation(mapMarkers[4])}>
        🏪 Chợ
      </div>
    </div>
  </div>
</div>

<style>
  .map-page {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
    background: #f5f5f5;
    min-height: 100vh;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
  }

  .page-header h1 {
    font-size: 22px;
    font-weight: 700;
    margin: 0;
  }

  .btn-icon {
    background: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 18px;
  }

  .search-box {
    margin-bottom: 16px;
  }

  .search-box input {
    width: 100%;
    padding: 14px 16px;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    box-sizing: border-box;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }

  .layers-bar {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 12px;
    margin-bottom: 16px;
  }

  .layer-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px 14px;
    background: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .layer-btn.active {
    background: #3b82f6;
    color: white;
  }

  .layer-icon {
    font-size: 20px;
    margin-bottom: 4px;
  }

  .layer-name {
    font-size: 11px;
    font-weight: 500;
  }

  .map-container {
    background: #e5e7eb;
    border-radius: 16px;
    height: 300px;
    position: relative;
    overflow: hidden;
    margin-bottom: 16px;
  }

  .map-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #d1fae5 0%, #dbeafe 100%);
    position: relative;
  }

  .map-grid {
    position: absolute;
    inset: 0;
  }

  .map-marker {
    position: absolute;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transform: translate(-50%, -50%);
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    transition: transform 0.2s;
  }

  .map-marker:hover {
    transform: translate(-50%, -50%) scale(1.2);
    z-index: 10;
  }

  .marker-icon {
    font-size: 18px;
  }

  .map-overlay-text {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255,255,255,0.9);
    padding: 8px 16px;
    border-radius: 20px;
    text-align: center;
  }

  .map-overlay-text span {
    font-weight: 600;
    color: #1f2937;
  }

  .map-overlay-text small {
    display: block;
    color: #6b7280;
    font-size: 12px;
  }

  .location-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    position: relative;
  }

  .close-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #9ca3af;
  }

  .location-icon {
    font-size: 40px;
    margin-bottom: 12px;
  }

  .location-card h3 {
    margin: 0 0 12px 0;
    font-size: 18px;
    color: #1f2937;
  }

  .location-info p {
    margin: 6px 0;
    font-size: 14px;
    color: #6b7280;
  }

  .location-actions {
    display: flex;
    gap: 10px;
    margin-top: 16px;
  }

  .action-btn {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: #3b82f6;
    color: white;
    font-weight: 600;
    cursor: pointer;
  }

  .incidents-panel {
    background: white;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .incidents-panel h3 {
    margin: 0 0 12px 0;
    font-size: 16px;
    font-weight: 600;
  }

  .incidents-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .incident-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f9fafb;
    border-radius: 8px;
  }

  .incident-icon {
    font-size: 20px;
  }

  .incident-info {
    flex: 1;
  }

  .incident-location {
    font-weight: 500;
    font-size: 14px;
    color: #1f2937;
  }

  .incident-time {
    font-size: 12px;
    color: #9ca3af;
  }

  .incident-status {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 8px;
    background: #d1fae5;
    color: #059669;
  }

  .incident-status.active {
    background: #fef3c7;
    color: #d97706;
  }

  .quick-locations h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 12px 0;
  }

  .locations-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .location-chip {
    padding: 10px 14px;
    background: white;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
</style>