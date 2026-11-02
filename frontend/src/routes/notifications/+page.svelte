<script lang="ts">
  import { onMount } from 'svelte';
  import { pushNotificationService } from '$lib/push-notifications';
  import { notificationsService, type Notification } from '$lib/services/notifications';

  let notifications: any[] = [];
  let apiNotifications: Notification[] = [];
  let preferences: any = {};
  let activeTab = 'all';
  let loading = true;

  onMount(async () => {
    pushNotificationService.getNotifications().subscribe(n => notifications = n);
    pushNotificationService.getPreferences().subscribe(p => preferences = p);
    
    try {
      apiNotifications = await notificationsService.getNotifications();
    } catch (e) {
      console.log('Using local notifications');
    }
    loading = false;
  });

  async function markAsRead(id: string) {
    pushNotificationService.markAsRead(id);
    try {
      await notificationsService.markAsRead(id);
    } catch (e) {}
  }

  async function markAllAsRead() {
    pushNotificationService.markAllAsRead();
    try {
      await notificationsService.markAllAsRead();
    } catch (e) {}
  }

  async function deleteNotification(id: string) {
    pushNotificationService.deleteNotification(id);
    try {
      await notificationsService.deleteNotification(id);
    } catch (e) {}
  }

  function clearAll() {
    pushNotificationService.clearAll();
  }

  function updatePreference(key: string, value: boolean) {
    pushNotificationService.updatePreferences({ [key]: value });
  }

  function addTestNotifications() {
    pushNotificationService.addTestNotifications();
  }

  function formatTime(timestamp: number) {
    const diff = Date.now() - timestamp;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Vừa xong';
    if (minutes < 60) return `${minutes} phút trước`;
    if (hours < 24) return `${hours} giờ trước`;
    return `${days} ngày trước`;
  }

  let unreadCount = 0;
  let filteredNotifications: any[] = [];

  $: {
    unreadCount = notifications.filter(n => !n.read).length;
    filteredNotifications = activeTab === 'unread' 
      ? notifications.filter(n => !n.read)
      : notifications;
  }
</script>

<div class="notifications-page">
  <header class="page-header">
    <h1>🔔 Thông Báo</h1>
    <button class="test-btn" on:click={addTestNotifications}>Test</button>
  </header>

  <div class="tabs">
    <button class="tab" class:active={activeTab === 'all'} on:click={() => activeTab = 'all'}>
      Tất cả ({notifications.length})
    </button>
    <button class="tab" class:active={activeTab === 'unread'} on:click={() => activeTab = 'unread'}>
      Chưa đọc ({notifications.filter(n => !n.read).length})
    </button>
    <button class="tab" class:active={activeTab === 'settings'} on:click={() => activeTab = 'settings'}>
      Cài đặt
    </button>
  </div>

  {#if activeTab !== 'settings'}
    <div class="actions-bar">
      <button class="action-btn" on:click={markAllAsRead}>✓ Đánh dấu đã đọc</button>
      <button class="action-btn danger" on:click={clearAll}>🗑️ Xóa tất cả</button>
    </div>

    <div class="notifications-list">
      {#if filteredNotifications.length === 0}
        <div class="empty-state">
          <span class="empty-icon">🔔</span>
          <p>Không có thông báo nào</p>
        </div>
      {:else}
        {#each filteredNotifications as notif}
          <div class="notification-item" class:unread={!notif.read} on:click={() => markAsRead(notif.id)}>
            <div class="notif-icon">
              {#if notif.tag === 'application'}
                📋
              {:else if notif.tag === 'news'}
                📰
              {:else if notif.tag === 'reminder'}
                ⚠️
              {:else if notif.tag === 'emergency'}
                🚨
              {:else}
                🔔
              {/if}
            </div>
            <div class="notif-content">
              <div class="notif-title">{notif.title}</div>
              <div class="notif-body">{notif.body}</div>
              <div class="notif-time">{formatTime(notif.timestamp)}</div>
            </div>
            <button class="delete-btn" on:click|stopPropagation={() => deleteNotification(notif.id)}>×</button>
          </div>
        {/each}
      {/if}
    </div>
  {:else}
    <div class="settings-section">
      <h3>Cài đặt thông báo</h3>
      
      <div class="setting-item">
        <div class="setting-info">
          <div class="setting-title">Bật thông báo</div>
          <div class="setting-desc">Nhận thông báo đẩy trên thiết bị</div>
        </div>
        <label class="toggle">
          <input type="checkbox" checked={preferences.enabled} on:change={(e) => updatePreference('enabled', e.currentTarget.checked)} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <div class="setting-title">Âm thanh</div>
          <div class="setting-desc">Phát âm thanh khi có thông báo</div>
        </div>
        <label class="toggle">
          <input type="checkbox" checked={preferences.sound} on:change={(e) => updatePreference('sound', e.currentTarget.checked)} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <div class="setting-title">Rung</div>
          <div class="setting-desc">Rung thiết bị khi có thông báo</div>
        </div>
        <label class="toggle">
          <input type="checkbox" checked={preferences.vibration} on:change={(e) => updatePreference('vibration', e.currentTarget.checked)} />
          <span class="slider"></span>
        </label>
      </div>

      <h4>Loại thông báo</h4>

      <div class="setting-item">
        <div class="setting-info">
          <div class="setting-title">Tin tức</div>
          <div class="setting-desc">Thông báo tin tức, sự kiện</div>
        </div>
        <label class="toggle">
          <input type="checkbox" checked={preferences.news} on:change={(e) => updatePreference('news', e.currentTarget.checked)} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <div class="setting-title">Hồ sơ</div>
          <div class="setting-desc">Cập nhật trạng thái hồ sơ</div>
        </div>
        <label class="toggle">
          <input type="checkbox" checked={preferences.applications} on:change={(e) => updatePreference('applications', e.currentTarget.checked)} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <div class="setting-title">Khiếu nại</div>
          <div class="setting-desc">Phản hồi khiếu nại, phản ánh</div>
        </div>
        <label class="toggle">
          <input type="checkbox" checked={preferences.complaints} on:change={(e) => updatePreference('complaints', e.currentTarget.checked)} />
          <span class="slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <div class="setting-title">Khẩn cấp</div>
          <div class="setting-desc">Thông báo khẩn cấp, cảnh báo</div>
        </div>
        <label class="toggle">
          <input type="checkbox" checked={preferences.emergency} on:change={(e) => updatePreference('emergency', e.currentTarget.checked)} />
          <span class="slider"></span>
        </label>
      </div>
    </div>
  {/if}
</div>

<style>
  .notifications-page {
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

  .test-btn {
    padding: 8px 16px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
  }

  .tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
  }

  .tab {
    flex: 1;
    padding: 12px;
    border: none;
    background: white;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
    color: #6b7280;
  }

  .tab.active {
    background: #3b82f6;
    color: white;
  }

  .actions-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
  }

  .action-btn {
    flex: 1;
    padding: 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    font-size: 13px;
  }

  .action-btn.danger {
    color: #ef4444;
    border-color: #fecaca;
  }

  .notifications-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .notification-item {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: white;
    cursor: pointer;
    position: relative;
  }

  .notification-item.unread {
    background: #eff6ff;
  }

  .notif-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
  }

  .notif-content {
    flex: 1;
  }

  .notif-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
  }

  .notif-body {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 4px;
  }

  .notif-time {
    font-size: 12px;
    color: #9ca3af;
  }

  .delete-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: none;
    border: none;
    font-size: 20px;
    color: #9ca3af;
    cursor: pointer;
  }

  .empty-state {
    text-align: center;
    padding: 60px 20px;
  }

  .empty-icon {
    font-size: 64px;
    display: block;
    margin-bottom: 16px;
  }

  .settings-section {
    background: white;
    border-radius: 16px;
    padding: 20px;
  }

  .settings-section h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
  }

  .settings-section h4 {
    margin: 24px 0 16px 0;
    font-size: 14px;
    color: #6b7280;
  }

  .setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #f3f4f6;
  }

  .setting-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
  }

  .setting-desc {
    font-size: 13px;
    color: #6b7280;
  }

  .toggle {
    position: relative;
    width: 50px;
    height: 28px;
  }

  .toggle input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #e5e7eb;
    border-radius: 28px;
    transition: 0.3s;
  }

  .slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
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
    transform: translateX(22px);
  }
</style>