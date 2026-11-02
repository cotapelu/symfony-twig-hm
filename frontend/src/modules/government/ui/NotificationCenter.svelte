<script lang="ts">
  import { writable } from 'svelte/store';

  interface Notification {
    id: string;
    type: 'info' | 'warning' | 'alert' | 'success' | 'system';
    title: string;
    message: string;
    timestamp: Date;
    read: boolean;
    priority: 'high' | 'normal' | 'low';
    category: string;
  }

  let notifications = writable<Notification[]>([
    { id: '1', type: 'warning', title: 'Cảnh báo thời tiết', message: 'Dự báo mưa to gió giật mạnh trong 24h tới. Khuyến cáo người dân hạn chế ra ngoài.', timestamp: new Date(Date.now() - 1800000), read: false, priority: 'high', category: 'Thời tiết' },
    { id: '2', type: 'info', title: 'Thông báo lịch tiếp dân', message: 'UBND phường tiếp công dân vào ngày thứ 7 hàng tuần từ 8h-11h.', timestamp: new Date(Date.now() - 3600000), read: false, priority: 'normal', category: 'Thông báo' },
    { id: '3', type: 'alert', title: 'Báo cáo sự cố mới', message: 'Có báo cáo sự cố hạ tầng tại đường Nguyễn Trãi - Cần xử lý khẩn.', timestamp: new Date(Date.now() - 7200000), read: false, priority: 'high', category: 'Sự cố' },
    { id: '4', type: 'success', title: 'Hồ sơ đã duyệt', message: 'Hồ sơ đăng ký thường trú của ông Nguyễn Văn A đã được duyệt.', timestamp: new Date(Date.now() - 14400000), read: true, priority: 'normal', category: 'TTHC' },
    { id: '5', type: 'system', title: 'Cập nhật hệ thống', message: 'Hệ thống sẽ bảo trì vào 02:00-04:00 ngày mai.', timestamp: new Date(Date.now() - 86400000), read: true, priority: 'low', category: 'Hệ thống' },
    { id: '6', type: 'info', title: 'Lịch họp tháng', message: 'Cuộc họp tháng 02/2026 sẽ diễn ra vào ngày 15/02/2026.', timestamp: new Date(Date.now() - 172800000), read: true, priority: 'normal', category: 'Lịch' },
  ]);

  let activeTab: 'all' | 'unread' | 'high-priority' = 'all';
  let showComposeModal = false;
  let newNotification = { title: '', message: '', type: 'info' as const, priority: 'normal' as const, category: 'Thông báo' };

  const typeIcons: Record<string, string> = {
    info: 'ℹ️',
    warning: '⚠️',
    alert: '🚨',
    success: '✅',
    system: '🔧',
  };

  const typeColors: Record<string, string> = {
    info: '#3b82f6',
    warning: '#f59e0b',
    alert: '#ef4444',
    success: '#10b981',
    system: '#64748b',
  };

  const priorityLabels: Record<string, string> = {
    high: 'Khẩn',
    normal: 'Thường',
    low: 'Thấp',
  };

  function formatTime(date: Date): string {
    const diff = Date.now() - date.getTime();
    if (diff < 60000) return 'Vừa xong';
    if (diff < 3600000) return `${Math.floor(diff / 60000)} phút trước`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)} giờ trước`;
    return date.toLocaleDateString('vi-VN');
  }

  function markAsRead(id: string) {
    notifications.update(n => n.map(notif => 
      notif.id === id ? { ...notif, read: true } : notif
    ));
  }

  function markAllAsRead() {
    notifications.update(n => n.map(notif => ({ ...notif, read: true })));
  }

  function deleteNotification(id: string) {
    notifications.update(n => n.filter(notif => notif.id !== id));
  }

  function sendNotification() {
    const notification: Notification = {
      id: Math.random().toString(36).substring(2, 15),
      ...newNotification,
      timestamp: new Date(),
      read: false,
    };
    notifications.update(n => [notification, ...n]);
    showComposeModal = false;
    newNotification = { title: '', message: '', type: 'info', priority: 'normal', category: 'Thông báo' };
  }

  $: filteredNotifications = $notifications.filter(n => {
    if (activeTab === 'unread') return !n.read;
    if (activeTab === 'high-priority') return n.priority === 'high';
    return true;
  });

  $: unreadCount = $notifications.filter(n => !n.read).length;
  $: highPriorityCount = $notifications.filter(n => n.priority === 'high' && !n.read).length;
</script>

<div class="notification-container">
  <!-- Header -->
  <header class="notification-header">
    <div class="header-left">
      <h2>🔔 Trung tâm Thông báo</h2>
      <span class="subtitle">Quản lý thông báo và cảnh báo</span>
    </div>
    <div class="header-actions">
      <button class="header-btn" on:click={markAllAsRead}>
        ✅ Đánh dấu đã đọc
      </button>
      <button class="header-btn primary" on:click={() => showComposeModal = true}>
        ➕ Gửi thông báo
      </button>
    </div>
  </header>

  <!-- Stats Bar -->
  <div class="stats-bar">
    <div class="stat-item" class:active={activeTab === 'all'} on:click={() => activeTab = 'all'}>
      <span class="stat-value">{$notifications.length}</span>
      <span class="stat-label">Tất cả</span>
    </div>
    <div class="stat-item unread" class:active={activeTab === 'unread'} on:click={() => activeTab = 'unread'}>
      <span class="stat-value">{unreadCount}</span>
      <span class="stat-label">Chưa đọc</span>
    </div>
    <div class="stat-item urgent" class:active={activeTab === 'high-priority'} on:click={() => activeTab = 'high-priority'}>
      <span class="stat-value">{highPriorityCount}</span>
      <span class="stat-label">Khẩn cấp</span>
    </div>
  </div>

  <!-- Notification List -->
  <div class="notification-list">
    {#each filteredNotifications as notification (notification.id)}
      <div 
        class="notification-card" 
        class:unread={!notification.read}
        class:high-priority={notification.priority === 'high'}
        on:click={() => markAsRead(notification.id)}
      >
        <div class="notification-icon" style="background: {typeColors[notification.type]}">
          {typeIcons[notification.type]}
        </div>
        <div class="notification-content">
          <div class="notification-header-row">
            <h4 class="notification-title">{notification.title}</h4>
            {#if notification.priority === 'high'}
              <span class="priority-badge">{priorityLabels[notification.priority]}</span>
            {/if}
          </div>
          <p class="notification-message">{notification.message}</p>
          <div class="notification-meta">
            <span class="category">{notification.category}</span>
            <span class="time">{formatTime(notification.timestamp)}</span>
          </div>
        </div>
        <div class="notification-actions">
          <button class="action-btn" title="Xóa" on:click|stopPropagation={() => deleteNotification(notification.id)}>
            🗑️
          </button>
        </div>
      </div>
    {:else}
      <div class="empty-state">
        <span class="empty-icon">🔔</span>
        <p>Không có thông báo nào</p>
      </div>
    {/each}
  </div>
</div>

<!-- Compose Modal -->
{#if showComposeModal}
  <div class="modal-overlay" on:click={() => showComposeModal = false}>
    <div class="modal-content" on:click|stopPropagation>
      <div class="modal-header">
        <h3>📢 Gửi thông báo mới</h3>
        <button class="close-btn" on:click={() => showComposeModal = false}>✕</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Tiêu đề</label>
          <input type="text" bind:value={newNotification.title} placeholder="Nhập tiêu đề...">
        </div>
        <div class="form-group">
          <label>Nội dung</label>
          <textarea bind:value={newNotification.message} placeholder="Nhập nội dung..." rows="4"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Loại</label>
            <select bind:value={newNotification.type}>
              <option value="info">ℹ️ Thông tin</option>
              <option value="warning">⚠️ Cảnh báo</option>
              <option value="alert">🚨 Khẩn cấp</option>
              <option value="success">✅ Thành công</option>
              <option value="system">🔧 Hệ thống</option>
            </select>
          </div>
          <div class="form-group">
            <label>Độ ưu tiên</label>
            <select bind:value={newNotification.priority}>
              <option value="high">Khẩn</option>
              <option value="normal">Thường</option>
              <option value="low">Thấp</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Danh mục</label>
          <select bind:value={newNotification.category}>
            <option value="Thông báo">Thông báo</option>
            <option value="TTHC">TTHC</option>
            <option value="Thời tiết">Thời tiết</option>
            <option value="Sự cố">Sự cố</option>
            <option value="Lịch">Lịch</option>
            <option value="Hệ thống">Hệ thống</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn secondary" on:click={() => showComposeModal = false}>Hủy</button>
        <button class="btn primary" on:click={sendNotification}>Gửi thông báo</button>
      </div>
    </div>
  </div>
{/if}

<style>
  .notification-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #f1f5f9;
  }

  .notification-header {
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

  .header-actions {
    display: flex;
    gap: 12px;
  }

  .header-btn {
    padding: 10px 16px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
  }

  .header-btn:hover {
    background: #f1f5f9;
  }

  .header-btn.primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
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
    flex-direction: column;
    align-items: center;
    padding: 12px 24px;
    background: #f8fafc;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 100px;
  }

  .stat-item:hover, .stat-item.active {
    background: #eff6ff;
  }

  .stat-item.unread {
    background: #fef3c7;
  }

  .stat-item.unread.active {
    background: #fde68a;
  }

  .stat-item.urgent {
    background: #fee2e2;
  }

  .stat-item.urgent.active {
    background: #fecaca;
  }

  .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  .notification-list {
    flex: 1;
    overflow-y: auto;
    padding: 16px 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .notification-card {
    display: flex;
    gap: 16px;
    padding: 16px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.2s;
  }

  .notification-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .notification-card.unread {
    border-left: 4px solid #3b82f6;
    background: #f8fafc;
  }

  .notification-card.high-priority {
    border-left: 4px solid #ef4444;
  }

  .notification-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
  }

  .notification-content {
    flex: 1;
  }

  .notification-header-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
  }

  .notification-title {
    margin: 0;
    font-size: 0.9375rem;
    color: #1e293b;
    font-weight: 600;
  }

  .priority-badge {
    padding: 2px 8px;
    background: #ef4444;
    color: white;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
  }

  .notification-message {
    margin: 0 0 8px;
    font-size: 0.875rem;
    color: #475569;
    line-height: 1.5;
  }

  .notification-meta {
    display: flex;
    gap: 12px;
    font-size: 0.75rem;
    color: #64748b;
  }

  .category {
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 4px;
  }

  .notification-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .action-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    padding: 4px;
    opacity: 0;
    transition: opacity 0.2s;
  }

  .notification-card:hover .action-btn {
    opacity: 1;
  }

  .action-btn:hover {
    transform: scale(1.1);
  }

  .empty-state {
    text-align: center;
    padding: 60px;
    color: #64748b;
  }

  .empty-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 16px;
  }

  /* Modal Styles */
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
    max-height: 90vh;
    overflow-y: auto;
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
    font-size: 1.125rem;
  }

  .close-btn {
    background: transparent;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    color: #64748b;
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
    color: #374151;
    margin-bottom: 6px;
  }

  .form-group input,
  .form-group textarea,
  .form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9375rem;
    font-family: inherit;
  }

  .form-group textarea {
    resize: vertical;
  }

  .form-row {
    display: flex;
    gap: 16px;
  }

  .form-row .form-group {
    flex: 1;
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
    font-weight: 500;
    transition: all 0.2s;
  }

  .btn.secondary {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
  }

  .btn.primary {
    background: #3b82f6;
    border: none;
    color: white;
  }

  .btn:hover {
    opacity: 0.9;
  }
</style>
