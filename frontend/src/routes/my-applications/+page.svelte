<script lang="ts">
  import { onMount } from 'svelte';

  let activeTab = 'all';
  let searchQuery = '';

  let applications = [
    { id: 'TTHC-001', name: 'Đăng ký khai sinh', category: 'Hộ tịch', status: 'COMPLETED', submittedDate: '2026-01-15', completedDate: '2026-01-20', progress: 100 },
    { id: 'TTHC-002', name: 'Đăng ký thường trú', category: 'Cư trú', status: 'PROCESSING', submittedDate: '2026-02-01', completedDate: null, progress: 60 },
    { id: 'TTHC-003', name: 'Cấp CCCD', category: 'CCCD', status: 'PROCESSING', submittedDate: '2026-02-10', completedDate: null, progress: 40 },
    { id: 'TTHC-004', name: 'Đăng ký kinh doanh', category: 'Kinh doanh', status: 'PENDING', submittedDate: '2026-02-13', completedDate: null, progress: 10 },
    { id: 'TTHC-005', name: 'Xin cấp giấy phép xây dựng', category: 'Xây dựng', status: 'REJECTED', submittedDate: '2026-01-20', completedDate: '2026-01-25', progress: 0, rejectReason: 'Thiếu giấy tờ chứng minh quyền sử dụng đất' }
  ];

  let notifications = [
    { id: 1, title: 'Hồ sơ đăng ký khai sinh đã hoàn thành', time: '2 giờ trước', read: false },
    { id: 2, title: 'Yêu cầu bổ sung giấy tờ cho hồ sơ CCCD', time: '1 ngày trước', read: false },
    { id: 3, title: 'Hồ sơ đăng ký kinh doanh đã được tiếp nhận', time: '2 ngày trước', read: true }
  ];

  function getStatusColor(status: string) {
    switch (status) {
      case 'COMPLETED': return '#10b981';
      case 'PROCESSING': return '#3b82f6';
      case 'PENDING': return '#f59e0b';
      case 'REJECTED': return '#ef4444';
      default: return '#6b7280';
    }
  }

  function getStatusText(status: string) {
    switch (status) {
      case 'COMPLETED': return 'Hoàn thành';
      case 'PROCESSING': return 'Đang xử lý';
      case 'PENDING': return 'Chờ tiếp nhận';
      case 'REJECTED': return 'Từ chối';
      default: return status;
    }
  }

  function getCategoryIcon(category: string) {
    switch (category) {
      case 'Hộ tịch': return '👶';
      case 'Cư trú': return '🏠';
      case 'CCCD': return '🪪';
      case 'Kinh doanh': return '📋';
      case 'Xây dựng': return '🏗️';
      default: return '📄';
    }
  }

  $: filteredApps = activeTab === 'all' 
    ? applications 
    : applications.filter(a => a.status === activeTab.toUpperCase());
</script>

<div class="applications-page">
  <header class="page-header">
    <h1>📋 Hồ Sơ Của Tôi</h1>
  </header>

  <div class="notifications-bar">
    <div class="notif-icon">🔔</div>
    <div class="notif-list">
      {#each notifications.slice(0, 2) as notif}
        <div class="notif-item" class:unread={!notif.read}>
          <span>{notif.title}</span>
          <small>{notif.time}</small>
        </div>
      {/each}
    </div>
  </div>

  <div class="tabs">
    <button class="tab" class:active={activeTab === 'all'} on:click={() => activeTab = 'all'}>
      Tất cả ({applications.length})
    </button>
    <button class="tab" class:active={activeTab === 'processing'} on:click={() => activeTab = 'processing'}>
      Đang xử lý
    </button>
    <button class="tab" class:active={activeTab === 'completed'} on:click={() => activeTab = 'completed'}>
      Hoàn thành
    </button>
  </div>

  <div class="search-bar">
    <input type="text" placeholder="Tìm kiếm hồ sơ..." bind:value={searchQuery} />
  </div>

  <div class="applications-list">
    {#each filteredApps.filter(a => searchQuery ? a.name.toLowerCase().includes(searchQuery.toLowerCase()) : true) as app}
      <div class="application-card">
        <div class="app-icon">{getCategoryIcon(app.category)}</div>
        <div class="app-info">
          <div class="app-name">{app.name}</div>
          <div class="app-meta">
            <span class="app-id">#{app.id}</span>
            <span class="app-date">Nộp: {app.submittedDate}</span>
          </div>
        </div>
        <div class="app-status">
          <span class="status-badge" style="background: {getStatusColor(app.status)}">
            {getStatusText(app.status)}
          </span>
        </div>
        <div class="app-progress">
          <div class="progress-bar">
            <div class="progress-fill" style="width: {app.progress}%; background: {getStatusColor(app.status)}"></div>
          </div>
          <div class="progress-text">{app.progress}%</div>
        </div>
        {#if app.status === 'REJECTED'}
          <div class="reject-reason">
            <strong>Lý do:</strong> {app.rejectReason}
          </div>
        {/if}
        <div class="app-actions">
          <button class="action-btn">👁️ Xem</button>
          <button class="action-btn">📥 Tải</button>
        </div>
      </div>
    {/each}
  </div>

  {#if filteredApps.length === 0}
    <div class="empty-state">
      <div class="empty-icon">📋</div>
      <p>Không có hồ sơ nào</p>
    </div>
  {/if}
</div>

<style>
  .applications-page {
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
    background: #f5f5f5;
    min-height: 100vh;
  }

  .page-header h1 {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 16px 0;
  }

  .notifications-bar {
    background: white;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .notif-icon {
    font-size: 24px;
  }

  .notif-list {
    flex: 1;
  }

  .notif-item {
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 13px;
    color: #374151;
  }

  .notif-item:last-child {
    border-bottom: none;
  }

  .notif-item.unread {
    font-weight: 600;
  }

  .notif-item small {
    display: block;
    color: #9ca3af;
    margin-top: 2px;
    font-size: 11px;
  }

  .tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
  }

  .tab {
    flex: 1;
    padding: 10px;
    border: none;
    background: white;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    color: #6b7280;
  }

  .tab.active {
    background: #3b82f6;
    color: white;
  }

  .search-bar {
    margin-bottom: 16px;
  }

  .search-bar input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    box-sizing: border-box;
  }

  .applications-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .application-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .app-icon {
    font-size: 32px;
    margin-bottom: 8px;
  }

  .app-info {
    margin-bottom: 12px;
  }

  .app-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 16px;
    margin-bottom: 4px;
  }

  .app-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #9ca3af;
  }

  .app-status {
    margin-bottom: 12px;
  }

  .status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    color: white;
    font-size: 12px;
    font-weight: 500;
  }

  .app-progress {
    margin-bottom: 12px;
  }

  .progress-bar {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 4px;
  }

  .progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
  }

  .progress-text {
    font-size: 11px;
    color: #6b7280;
    text-align: right;
  }

  .reject-reason {
    background: #fef2f2;
    padding: 10px;
    border-radius: 8px;
    font-size: 13px;
    color: #dc2626;
    margin-bottom: 12px;
  }

  .app-actions {
    display: flex;
    gap: 8px;
  }

  .action-btn {
    flex: 1;
    padding: 8px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    font-size: 13px;
  }

  .empty-state {
    text-align: center;
    padding: 40px;
  }

  .empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
  }

  .empty-state p {
    color: #6b7280;
  }
</style>