<script lang="ts">
  import { writable } from 'svelte/store';

  interface Alert {
    id: string;
    type: 'weather' | 'disaster' | 'security' | 'health' | 'infrastructure';
    severity: 'info' | 'warning' | 'emergency';
    title: string;
    message: string;
    timestamp: Date;
    expiresAt: Date;
    affectedAreas: string[];
    status: 'active' | 'resolved';
  }

  let alerts = writable<Alert[]>([
    { id: '1', type: 'weather', severity: 'warning', title: 'Cảnh báo mưa to', message: 'Dự báo mưa to gió giật mạnh trong 24h tới. Khuyến cáo người dân hạn chế ra ngoài, chuẩn bị đèn pin, nước sạch dự phòng.', timestamp: new Date(), expiresAt: new Date(Date.now() + 86400000), affectedAreas: ['Toàn bộ phường'], status: 'active' },
    { id: '2', type: 'health', severity: 'info', title: 'Thông báo tiêm chủng', message: 'Đợt tiêm vaccine phòng bệnh mùa xuân sẽ bắt đầu từ ngày 20/02/2026. Công dân đăng ký tại trạm y tế phường.', timestamp: new Date(Date.now() - 172800000), expiresAt: new Date(Date.now() + 604800000), affectedAreas: ['Khu vực 1', 'Khu vực 2'], status: 'active' },
    { id: '3', type: 'infrastructure', severity: 'emergency', title: 'Cắt điện khẩn', message: 'Điện lực thông báo cắt điện khẩn để sửa chữa sự cố đường dây. Thời gian: 14:00-17:00 hôm nay.', timestamp: new Date(Date.now() - 7200000), expiresAt: new Date(Date.now() + 3600000), affectedAreas: ['Khu vực 3', 'Khu vực 4'], status: 'resolved' },
  ]);

  let activeAlert: Alert | null = null;
  let showAlertModal = false;
  let newAlert = { type: 'weather', severity: 'warning', title: '', message: '', affectedAreas: ['Toàn bộ phường'] };

  const typeIcons: Record<string, string> = {
    weather: '🌧️',
    disaster: '🌋',
    security: '🚨',
    health: '🏥',
    infrastructure: '🔧',
  };

  const severityColors: Record<string, string> = {
    info: '#3b82f6',
    warning: '#f59e0b',
    emergency: '#ef4444',
  };

  const severityLabels: Record<string, string> = {
    info: 'Thông tin',
    warning: 'Cảnh báo',
    emergency: 'Khẩn cấp',
  };

  function resolveAlert(id: string) {
    alerts.update(a => a.map(alert => 
      alert.id === id ? { ...alert, status: 'resolved' } : alert
    ));
    activeAlert = null;
  }

  function createAlert() {
    const alert: Alert = {
      id: Math.random().toString(36).substring(2, 15),
      ...newAlert,
      timestamp: new Date(),
      expiresAt: new Date(Date.now() + 86400000),
      status: 'active',
    };
    alerts.update(a => [alert, ...a]);
    showAlertModal = false;
    newAlert = { type: 'weather', severity: 'warning', title: '', message: '', affectedAreas: ['Toàn bộ phường'] };
  }

  $: activeAlerts = $alerts.filter(a => a.status === 'active');
  $: resolvedAlerts = $alerts.filter(a => a.status === 'resolved');
  $: hasEmergency = activeAlerts.some(a => a.severity === 'emergency');
</script>

<div class="emergency-container">
  <!-- Header -->
  <header class="emergency-header" class:emergency={hasEmergency}>
    <div class="header-left">
      <h2>🚨 Trung tâm Cảnh báo Khẩn cấp</h2>
      <span class="subtitle">Quản lý cảnh báo và thông báo khẩn</span>
    </div>
    <button class="header-btn primary" on:click={() => showAlertModal = true}>
      ➕ Phát cảnh báo
    </button>
  </header>

  <!-- Emergency Banner -->
  {#if hasEmergency}
    <div class="emergency-banner">
      <span class="pulse">🚨</span>
      <strong>CẢNH BÁO KHẨN CẤP ĐANG HOẠT ĐỘNG</strong>
      <button class="dismiss">Tắt thông báo</button>
    </div>
  {/if}

  <!-- Stats -->
  <div class="stats-bar">
    <div class="stat-item">
      <span class="stat-value">{activeAlerts.length}</span>
      <span class="stat-label">Đang hoạt động</span>
    </div>
    <div class="stat-item warning">
      <span class="stat-value">{activeAlerts.filter(a => a.severity === 'warning').length}</span>
      <span class="stat-label">Cảnh báo</span>
    </div>
    <div class="stat-item danger">
      <span class="stat-value">{activeAlerts.filter(a => a.severity === 'emergency').length}</span>
      <span class="stat-label">Khẩn cấp</span>
    </div>
    <div class="stat-item">
      <span class="stat-value">{resolvedAlerts.length}</span>
      <span class="stat-label">Đã giải quyết</span>
    </div>
  </div>

  <!-- Main Content -->
  <div class="emergency-content">
    <!-- Active Alerts -->
    <div class="alerts-section">
      <h3>⚠️ Cảnh báo đang hoạt động</h3>
      {#each activeAlerts as alert (alert.id)}
        <div 
          class="alert-card {alert.severity}"
          on:click={() => activeAlert = alert}
        >
          <div class="alert-icon" style="background: {severityColors[alert.severity]}">
            {typeIcons[alert.type]}
          </div>
          <div class="alert-content">
            <div class="alert-header">
              <h4>{alert.title}</h4>
              <span class="severity-badge" style="background: {severityColors[alert.severity]}">
                {severityLabels[alert.severity]}
              </span>
            </div>
            <p>{alert.message.substring(0, 100)}...</p>
            <div class="alert-meta">
              <span>📍 {alert.affectedAreas.join(', ')}</span>
              <span>🕐 {alert.timestamp.toLocaleTimeString('vi-VN')}</span>
            </div>
          </div>
        </div>
      {:else}
        <div class="empty">✅ Không có cảnh báo nào đang hoạt động</div>
      {/each}
    </div>

    <!-- Alert Detail -->
    <div class="detail-section">
      {#if activeAlert}
        <div class="alert-detail {activeAlert.severity}">
          <div class="detail-header">
            <span class="detail-icon" style="background: {severityColors[activeAlert.severity]}">
              {typeIcons[activeAlert.type]}
            </span>
            <div>
              <h3>{activeAlert.title}</h3>
              <span class="severity-badge" style="background: {severityColors[activeAlert.severity]}">
                {severityLabels[activeAlert.severity]}
              </span>
            </div>
          </div>

          <div class="detail-content">
            <p>{activeAlert.message}</p>

            <div class="detail-info">
              <div class="info-row">
                <span class="label">Loại:</span>
                <span class="value">{activeAlert.type}</span>
              </div>
              <div class="info-row">
                <span class="label">Phát lúc:</span>
                <span class="value">{activeAlert.timestamp.toLocaleString('vi-VN')}</span>
              </div>
              <div class="info-row">
                <span class="label">Hết hạn:</span>
                <span class="value">{activeAlert.expiresAt.toLocaleString('vi-VN')}</span>
              </div>
              <div class="info-row">
                <span class="label">Khu vực ảnh hưởng:</span>
                <span class="value">{activeAlert.affectedAreas.join(', ')}</span>
              </div>
            </div>

            <div class="detail-actions">
              <button class="action-btn">📢 Phát lại</button>
              <button class="action-btn">📤 Chia sẻ</button>
              <button class="action-btn success" on:click={() => resolveAlert(activeAlert!.id)}>
                ✅ Đánh dấu đã giải quyết
              </button>
            </div>
          </div>
        </div>
      {:else}
        <div class="no-selection">
          <span>🚨</span>
          <p>Chọn cảnh báo để xem chi tiết</p>
        </div>
      {/if}
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="quick-actions">
    <button class="quick-btn emergency">
      <span>🚨</span>
      Báo động khẩn
    </button>
    <button class="quick-btn warning">
      <span>⚠️</span>
      Cảnh báo
    </button>
    <button class="quick-btn info">
      <span>ℹ️</span>
      Thông báo
    </button>
    <button class="quick-btn">
      <span>📢</span>
      Phát thanh
    </button>
  </div>
</div>

<!-- Create Alert Modal -->
{#if showAlertModal}
  <div class="modal-overlay" on:click={() => showAlertModal = false}>
    <div class="modal-content" on:click|stopPropagation>
      <div class="modal-header">
        <h3>🚨 Phát cảnh báo mới</h3>
        <button class="close-btn" on:click={() => showAlertModal = false}>✕</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Loại cảnh báo</label>
          <select bind:value={newAlert.type}>
            <option value="weather">🌧️ Thời tiết</option>
            <option value="disaster">🌋 Thiên tai</option>
            <option value="security">🚨 An ninh</option>
            <option value="health">🏥 Y tế</option>
            <option value="infrastructure">🔧 Hạ tầng</option>
          </select>
        </div>
        <div class="form-group">
          <label>Mức độ</label>
          <select bind:value={newAlert.severity}>
            <option value="info">ℹ️ Thông tin</option>
            <option value="warning">⚠️ Cảnh báo</option>
            <option value="emergency">🚨 Khẩn cấp</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tiêu đề</label>
          <input type="text" bind:value={newAlert.title} placeholder="Nhập tiêu đề...">
        </div>
        <div class="form-group">
          <label>Nội dung</label>
          <textarea bind:value={newAlert.message} placeholder="Nhập nội dung cảnh báo..." rows="4"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn secondary" on:click={() => showAlertModal = false}>Hủy</button>
        <button class="btn danger" on:click={createAlert}>🚨 Phát cảnh báo</button>
      </div>
    </div>
  </div>
{/if}

<style>
  .emergency-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #f1f5f9;
  }

  .emergency-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
  }

  .emergency-header.emergency {
    background: #fee2e2;
    border-bottom-color: #ef4444;
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

  .header-btn {
    padding: 10px 16px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .header-btn.primary {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
  }

  .emergency-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 24px;
    background: #ef4444;
    color: white;
    animation: flash 1s infinite;
  }

  @keyframes flash {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
  }

  .pulse {
    animation: pulse 1s infinite;
  }

  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
  }

  .dismiss {
    margin-left: auto;
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
  }

  .stats-bar {
    display: flex;
    gap: 16px;
    padding: 16px 24px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
  }

  .stat-item {
    flex: 1;
    text-align: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
  }

  .stat-item.warning { background: #fef3c7; }
  .stat-item.danger { background: #fee2e2; }

  .stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  .emergency-content {
    display: flex;
    flex: 1;
    overflow: hidden;
  }

  .alerts-section {
    width: 50%;
    padding: 20px;
    overflow-y: auto;
    border-right: 1px solid #e2e8f0;
    background: white;
  }

  .alerts-section h3 {
    margin: 0 0 16px;
    font-size: 1rem;
  }

  .alert-card {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 12px;
    cursor: pointer;
    border-left: 4px solid transparent;
  }

  .alert-card.warning { border-left-color: #f59e0b; }
  .alert-card.emergency { border-left-color: #ef4444; }
  .alert-card.info { border-left-color: #3b82f6; }

  .alert-card:hover {
    background: #f1f5f9;
  }

  .alert-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
  }

  .alert-content {
    flex: 1;
  }

  .alert-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
  }

  .alert-header h4 {
    margin: 0;
    font-size: 0.9375rem;
  }

  .severity-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    color: white;
  }

  .alert-content p {
    margin: 0 0 8px;
    font-size: 0.8rem;
    color: #64748b;
  }

  .alert-meta {
    display: flex;
    gap: 16px;
    font-size: 0.7rem;
    color: #64748b;
  }

  .detail-section {
    flex: 1;
    padding: 20px;
    background: #f8fafc;
  }

  .alert-detail {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border-left: 4px solid #3b82f6;
  }

  .alert-detail.warning { border-left-color: #f59e0b; }
  .alert-detail.emergency { border-left-color: #ef4444; }

  .detail-header {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
  }

  .detail-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
  }

  .detail-header h3 {
    margin: 0 0 4px;
    font-size: 1.125rem;
  }

  .detail-content p {
    margin: 0 0 16px;
    line-height: 1.6;
  }

  .detail-info {
    background: #f8fafc;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
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
  }

  .detail-actions {
    display: flex;
    gap: 8px;
  }

  .action-btn {
    flex: 1;
    padding: 10px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
  }

  .action-btn.success {
    background: #10b981;
    color: white;
    border-color: #10b981;
  }

  .no-selection {
    text-align: center;
    padding: 60px;
    color: #64748b;
  }

  .empty {
    text-align: center;
    padding: 40px;
    color: #10b981;
    background: #d1fae5;
    border-radius: 8px;
  }

  .quick-actions {
    display: flex;
    gap: 12px;
    padding: 16px 24px;
    background: white;
    border-top: 1px solid #e2e8f0;
  }

  .quick-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
  }

  .quick-btn.emergency { background: #fee2e2; border-color: #ef4444; color: #ef4444; }
  .quick-btn.warning { background: #fef3c7; border-color: #f59e0b; color: #f59e0b; }
  .quick-btn.info { background: #dbeafe; border-color: #3b82f6; color: #3b82f6; }

  /* Modal */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }

  .modal-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
  }

  .modal-header h3 {
    margin: 0;
  }

  .close-btn {
    background: transparent;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
  }

  .modal-body {
    padding: 20px;
  }

  .form-group {
    margin-bottom: 16px;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 6px;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9375rem;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 20px;
    border-top: 1px solid #e2e8f0;
  }

  .btn {
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9375rem;
  }

  .btn.secondary {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
  }

  .btn.danger {
    background: #ef4444;
    color: white;
    border: none;
  }
</style>
