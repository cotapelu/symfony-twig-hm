<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';

  interface WardFeature {
    id: string;
    name: string;
    type: 'residential' | 'commercial' | 'government' | 'park' | 'school' | 'hospital';
    coordinates: [number, number];
    description?: string;
  }

  interface IncidentMarker {
    id: string;
    type: 'traffic' | 'security' | 'infrastructure' | 'environmental' | 'disaster';
    status: 'pending' | 'investigating' | 'resolved';
    coordinates: [number, number];
    description: string;
    reportedAt: Date;
    reportedBy: string;
  }

  let mapContainer: HTMLDivElement;
  let selectedFeature: WardFeature | null = null;
  let showIncidents = true;
  let mapStyle: 'streets' | 'satellite' | 'terrain' = 'streets';

  const wardFeatures: WardFeature[] = [
    { id: '1', name: 'UBND Phường X', type: 'government', coordinates: [10.8231, 106.6297], description: 'Trụ sở UBND phường/xã' },
    { id: '2', name: 'Trường Tiểu học Y', type: 'school', coordinates: [10.8235, 106.6300], description: 'Trường Tiểu học Y' },
    { id: '3', name: 'Bệnh viện Z', type: 'hospital', coordinates: [10.8225, 106.6290], description: 'Bệnh viện đa khoa' },
    { id: '4', name: 'Công viên A', type: 'park', coordinates: [10.8240, 106.6285], description: 'Công viên phường' },
    { id: '5', name: 'Chợ B', type: 'commercial', coordinates: [10.8215, 106.6310], description: 'Chợ đầu mối' },
    { id: '6', name: 'Khu dân cư C', type: 'residential', coordinates: [10.8205, 106.6305], description: 'Khu dân cư phường' },
  ];

  const incidents: IncidentMarker[] = [
    { id: '1', type: 'traffic', status: 'pending', coordinates: [10.8220, 106.6302], description: 'Đèn tín hiệu giao thông hỏng', reportedAt: new Date(), reportedBy: 'Nguyễn Văn A' },
    { id: '2', type: 'security', status: 'investigating', coordinates: [10.8235, 106.6295], description: 'Có dấu hiệu đột nhập', reportedAt: new Date(Date.now() - 3600000), reportedBy: 'Trần Thị B' },
    { id: '3', type: 'infrastructure', status: 'resolved', coordinates: [10.8210, 106.6298], description: 'Đường xá hư hỏng đã sửa', reportedAt: new Date(Date.now() - 86400000), reportedBy: 'Lê Văn C' },
  ];

  const featureIcons: Record<string, string> = {
    government: '🏛️',
    school: '🏫',
    hospital: '🏥',
    park: '🌳',
    commercial: '🏪',
    residential: '🏠',
  };

  const incidentIcons: Record<string, string> = {
    traffic: '🚦',
    security: '🔒',
    infrastructure: '🔧',
    environmental: '🌿',
    disaster: '⚠️',
  };

  const incidentColors: Record<string, string> = {
    traffic: '#f59e0b',
    security: '#ef4444',
    infrastructure: '#3b82f6',
    environmental: '#10b981',
    disaster: '#dc2626',
  };

  function handleFeatureClick(feature: WardFeature) {
    selectedFeature = feature;
  }

  function closeFeaturePanel() {
    selectedFeature = null;
  }

  onMount(() => {
    // Initialize map placeholder
    // In production, integrate with Leaflet, Mapbox, or Google Maps
  });
</script>

<div class="gis-container">
  <!-- Header -->
  <header class="gis-header">
    <div class="header-left">
      <h2>🗺️ Bản đồ GIS Phường/Xã</h2>
      <span class="subtitle">Quản lý địa lý và theo dõi sự cố</span>
    </div>
    <div class="header-controls">
      <div class="control-group">
        <button 
          class="control-btn" 
          class:active={showIncidents}
          on:click={() => showIncidents = !showIncidents}
        >
          {showIncidents ? '👁️' : '👁️‍🗨️'} Sự cố
        </button>
        <button 
          class="control-btn" 
          class:active={mapStyle === 'streets'}
          on:click={() => mapStyle = 'streets'}
        >
          🛣️ Đường
        </button>
        <button 
          class="control-btn" 
          class:active={mapStyle === 'satellite'}
          on:click={() => mapStyle = 'satellite'}
        >
          🛰️ Vệ tinh
        </button>
        <button 
          class="control-btn" 
          class:active={mapStyle === 'terrain'}
          on:click={() => mapStyle = 'terrain'}
        >
          ⛰️ Địa hình
        </button>
      </div>
    </div>
  </header>

  <div class="gis-content">
    <!-- Map Area -->
    <div class="map-area" bind:this={mapContainer}>
      <!-- Map Placeholder - In production, use Leaflet/Mapbox/Google Maps -->
      <div class="map-placeholder">
        <div class="map-grid">
          {#each wardFeatures as feature}
            <button
              class="map-marker feature-marker"
              style="left: {50 + (feature.coordinates[0] - 10.822) * 1000}%; top: {50 + (feature.coordinates[1] - 106.629) * 1000}%"
              on:click={() => handleFeatureClick(feature)}
              title={feature.name}
            >
              {featureIcons[feature.type]}
            </button>
          {/each}

          {#if showIncidents}
            {#each incidents as incident}
              <button
                class="map-marker incident-marker"
                class:pending={incident.status === 'pending'}
                class:investigating={incident.status === 'investigating'}
                class:resolved={incident.status === 'resolved'}
                style="left: {50 + (incident.coordinates[0] - 10.822) * 1000}%; top: {50 + (incident.coordinates[1] - 106.629) * 1000}%; background: {incidentColors[incident.type]}"
                title={incident.description}
              >
                {incidentIcons[incident.type]}
              </button>
            {/each}
          {/if}
        </div>
        
        <div class="map-legend">
          <h4>📍 Địa điểm</h4>
          <div class="legend-items">
            {#each Object.entries(featureIcons) as [type, icon]}
              <div class="legend-item">
                <span class="legend-icon">{icon}</span>
                <span class="legend-label">{type}</span>
              </div>
            {/each}
          </div>
          
          {#if showIncidents}
            <h4>⚠️ Sự cố</h4>
            <div class="legend-items">
              {#each Object.entries(incidentIcons) as [type, icon]}
                <div class="legend-item">
                  <span class="legend-icon" style="background: {incidentColors[type]}">{icon}</span>
                  <span class="legend-label">{type}</span>
                </div>
              {/each}
            </div>
            <h4>📊 Trạng thái</h4>
            <div class="legend-items">
              <div class="legend-item"><span class="legend-dot pending"></span> Chờ xử lý</div>
              <div class="legend-item"><span class="legend-dot investigating"></span> Đang điều tra</div>
              <div class="legend-item"><span class="legend-dot resolved"></span> Đã xử lý</div>
            </div>
          {/if}
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <aside class="gis-sidebar">
      {#if selectedFeature}
        <div class="feature-panel">
          <div class="panel-header">
            <span class="feature-icon">{featureIcons[selectedFeature.type]}</span>
            <h3>{selectedFeature.name}</h3>
            <button class="close-btn" on:click={closeFeaturePanel}>✕</button>
          </div>
          <div class="panel-content">
            <div class="info-row">
              <span class="label">Loại:</span>
              <span class="value">{selectedFeature.type}</span>
            </div>
            {#if selectedFeature.description}
              <div class="info-row">
                <span class="label">Mô tả:</span>
                <span class="value">{selectedFeature.description}</span>
              </div>
            {/if}
            <div class="info-row">
              <span class="label">Tọa độ:</span>
              <span class="value">{selectedFeature.coordinates[0]}, {selectedFeature.coordinates[1]}</span>
            </div>
          </div>
          <div class="panel-actions">
            <button class="action-btn primary">📍 Chỉ đường</button>
            <button class="action-btn">📞 Liên hệ</button>
          </div>
        </div>
      {:else}
        <div class="incidents-list">
          <h3>📋 Danh sách sự cố</h3>
          {#each incidents as incident}
            <div class="incident-card {incident.status}">
              <div class="incident-header">
                <span class="incident-icon" style="background: {incidentColors[incident.type]}">
                  {incidentIcons[incident.type]}
                </span>
                <div class="incident-info">
                  <span class="incident-type">{incident.type}</span>
                  <span class="incident-time">{incident.reportedAt.toLocaleString('vi-VN')}</span>
                </div>
                <span class="incident-status {incident.status}">{incident.status}</span>
              </div>
              <p class="incident-desc">{incident.description}</p>
              <div class="incident-footer">
                <span class="reporter">👤 {incident.reportedBy}</span>
                <button class="detail-btn">Chi tiết →</button>
              </div>
            </div>
          {/each}
          
          <button class="report-btn">
            ➕ Báo cáo sự cố mới
          </button>
        </div>
      {/if}
    </aside>
  </div>
</div>

<style>
  .gis-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #f1f5f9;
  }

  .gis-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
  }

  .header-left h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .subtitle {
    font-size: 0.875rem;
    color: #64748b;
  }

  .control-group {
    display: flex;
    gap: 8px;
  }

  .control-btn {
    padding: 8px 16px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .control-btn:hover, .control-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .gis-content {
    display: flex;
    flex: 1;
    overflow: hidden;
  }

  .map-area {
    flex: 1;
    position: relative;
    background: #e2e8f0;
  }

  .map-placeholder {
    width: 100%;
    height: 100%;
    position: relative;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
  }

  .map-grid {
    width: 100%;
    height: 100%;
    position: relative;
  }

  .map-marker {
    position: absolute;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transform: translate(-50%, -50%);
    transition: transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
  }

  .map-marker:hover {
    transform: translate(-50%, -50%) scale(1.2);
    z-index: 10;
  }

  .feature-marker {
    background: white;
  }

  .incident-marker {
    border-radius: 8px;
    animation: pulse 2s infinite;
  }

  .incident-marker.pending {
    animation: pulse-pending 1.5s infinite;
  }

  @keyframes pulse {
    0%, 100% { box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); }
    50% { box-shadow: 0 2px 16px rgba(0, 0, 0, 0.4); }
  }

  @keyframes pulse-pending {
    0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
    50% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
  }

  .map-legend {
    position: absolute;
    bottom: 20px;
    left: 20px;
    background: white;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
    max-width: 200px;
  }

  .map-legend h4 {
    margin: 0 0 8px;
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
  }

  .legend-items {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 12px;
  }

  .legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    color: #475569;
  }

  .legend-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
  }

  .legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
  }

  .legend-dot.pending { background: #f59e0b; }
  .legend-dot.investigating { background: #3b82f6; }
  .legend-dot.resolved { background: #10b981; }

  .gis-sidebar {
    width: 360px;
    background: white;
    border-left: 1px solid #e2e8f0;
    overflow-y: auto;
    padding: 16px;
  }

  .feature-panel {
    background: #f8fafc;
    border-radius: 12px;
    overflow: hidden;
  }

  .panel-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .feature-icon {
    font-size: 1.5rem;
  }

  .panel-header h3 {
    flex: 1;
    margin: 0;
    font-size: 1rem;
  }

  .close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    color: white;
    cursor: pointer;
  }

  .panel-content {
    padding: 16px;
  }

  .info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e2e8f0;
  }

  .info-row:last-child {
    border-bottom: none;
  }

  .label {
    color: #64748b;
    font-size: 0.875rem;
  }

  .value {
    color: #1e293b;
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: capitalize;
  }

  .panel-actions {
    display: flex;
    gap: 8px;
    padding: 16px;
    border-top: 1px solid #e2e8f0;
  }

  .action-btn {
    flex: 1;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
  }

  .action-btn:hover {
    background: #f1f5f9;
  }

  .action-btn.primary {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .incidents-list h3 {
    margin: 0 0 16px;
    font-size: 1rem;
    color: #1e293b;
  }

  .incident-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    border-left: 4px solid #e2e8f0;
  }

  .incident-card.pending { border-left-color: #f59e0b; }
  .incident-card.investigating { border-left-color: #3b82f6; }
  .incident-card.resolved { border-left-color: #10b981; }

  .incident-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
  }

  .incident-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
  }

  .incident-info {
    flex: 1;
  }

  .incident-type {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    color: #1e293b;
    text-transform: capitalize;
  }

  .incident-time {
    font-size: 0.75rem;
    color: #64748b;
  }

  .incident-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
  }

  .incident-status.pending { background: #fef3c7; color: #b45309; }
  .incident-status.investigating { background: #dbeafe; color: #1d4ed8; }
  .incident-status.resolved { background: #d1fae5; color: #047857; }

  .incident-desc {
    margin: 0 0 12px;
    font-size: 0.875rem;
    color: #475569;
  }

  .incident-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .reporter {
    font-size: 0.75rem;
    color: #64748b;
  }

  .detail-btn {
    background: none;
    border: none;
    color: #667eea;
    font-size: 0.875rem;
    cursor: pointer;
    font-weight: 500;
  }

  .report-btn {
    width: 100%;
    padding: 14px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
  }

  .report-btn:hover {
    background: #5a67d8;
  }
</style>
