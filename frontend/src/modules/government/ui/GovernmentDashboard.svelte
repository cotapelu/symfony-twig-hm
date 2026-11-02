<script lang="ts">
  import { onMount } from 'svelte';
  import { 
    citizenStore, 
    documentStore, 
    procedureStore,
    citizenStatistics,
    activeProcedures,
    pendingApplications,
    recentDocuments,
  } from '../index';
  
  // Quick actions
  const quickActions = [
    { icon: '👤', label: 'Thêm công dân', action: 'addCitizen' },
    { icon: '📝', label: 'Tạo văn bản', action: 'createDoc' },
    { icon: '📋', label: 'Nộp hồ sơ', action: 'applyProcedure' },
    { icon: '🔍', label: 'Tra cứu', action: 'search' },
  ];
  
  // Recent activity (mock data - would come from API)
  let recentActivities = [
    { icon: '👤', text: 'Nguyễn Văn A vừa được thêm vào hệ thống', time: '2 phút trước', type: 'citizen' },
    { icon: '📄', text: 'Quyết định số 15/2024 đã được ban hành', time: '15 phút trước', type: 'document' },
    { icon: '📝', text: 'Hồ sơ đăng ký khai sinh đã được tiếp nhận', time: '1 giờ trước', type: 'application' },
    { icon: '✅', text: 'Hồ sơ cấp CCCD đã hoàn thành', time: '2 giờ trước', type: 'application' },
    { icon: '📋', text: 'Thêm TTHC mới: Đăng ký kinh doanh', time: '3 giờ trước', type: 'procedure' },
  ];
  
  onMount(async () => {
    await Promise.all([
      citizenStore.loadStatistics(),
      documentStore.loadStatistics(),
      procedureStore.loadProcedures(true),
      procedureStore.loadPendingApplications(),
    ]);
  });
  
  function formatNumber(num: number): string {
    return num.toLocaleString('vi-VN');
  }
  
  function getCompletionRate(): number {
    const total = $pendingApplications.length + 156; // 156 is completed this month
    return total > 0 ? Math.round((156 / total) * 100) : 0;
  }
</script>

<div class="dashboard">
  <!-- Welcome Section -->
  <section class="welcome-section">
    <div class="welcome-content">
      <h1>🏛️ Chào mừng đến với Hệ thống Quản lý Phường/Xã</h1>
      <p>Hệ thống quản lý công dân, văn bản và thủ tục hành chính trực tuyến</p>
    </div>
    <div class="welcome-date">
      <span class="date">{new Date().toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
    </div>
  </section>
  
  <!-- Quick Actions -->
  <section class="quick-actions">
    <h3>⚡ Thao tác nhanh</h3>
    <div class="actions-grid">
      {#each quickActions as action}
        <button class="action-card">
          <span class="action-icon">{action.icon}</span>
          <span class="action-label">{action.label}</span>
        </button>
      {/each}
    </div>
  </section>
  
  <!-- Stats Overview -->
  <section class="stats-overview">
    <div class="stats-grid">
      <!-- Citizens Card -->
      <div class="stat-card citizens">
        <div class="card-header">
          <span class="card-icon">👥</span>
          <span class="card-title">Công dân</span>
        </div>
        <div class="card-body">
          <div class="stat-number">{formatNumber($citizenStatistics?.total || 0)}</div>
          <div class="stat-breakdown">
            <span class="breakdown-item">
              <span class="dot male"></span>
              Nam: {$citizenStatistics?.byGender.male || 0}
            </span>
            <span class="breakdown-item">
              <span class="dot female"></span>
              Nữ: {$citizenStatistics?.byGender.female || 0}
            </span>
          </div>
        </div>
        <div class="card-footer">
          <a href="/government?tab=citizens" class="card-link">Xem chi tiết →</a>
        </div>
      </div>
      
      <!-- Documents Card -->
      <div class="stat-card documents">
        <div class="card-header">
          <span class="card-icon">📄</span>
          <span class="card-title">Văn bản</span>
        </div>
        <div class="card-body">
          <div class="stat-number">{formatNumber($documentStore.statistics?.total || 0)}</div>
          <div class="stat-breakdown">
            <span class="breakdown-item">
              <span class="dot published"></span>
              Đã ban hành: {$documentStore.statistics?.byStatus.published || 0}
            </span>
            <span class="breakdown-item">
              <span class="dot draft"></span>
              Bản nháp: {$documentStore.statistics?.byStatus.draft || 0}
            </span>
          </div>
        </div>
        <div class="card-footer">
          <a href="/government?tab=documents" class="card-link">Xem chi tiết →</a>
        </div>
      </div>
      
      <!-- Procedures Card -->
      <div class="stat-card procedures">
        <div class="card-header">
          <span class="card-icon">⚙️</span>
          <span class="card-title">TTHC</span>
        </div>
        <div class="card-body">
          <div class="stat-number">{formatNumber($activeProcedures.length)}</div>
          <div class="stat-breakdown">
            <span class="breakdown-item">
              <span class="dot active"></span>
              Đang hoạt động: {$activeProcedures.length}
            </span>
            <span class="breakdown-item">
              <span class="dot pending"></span>
              Hồ sơ chờ: {$pendingApplications.length}
            </span>
          </div>
        </div>
        <div class="card-footer">
          <a href="/government?tab=procedures" class="card-link">Xem chi tiết →</a>
        </div>
      </div>
      
      <!-- Performance Card -->
      <div class="stat-card performance">
        <div class="card-header">
          <span class="card-icon">📊</span>
          <span class="card-title">Hiệu suất</span>
        </div>
        <div class="card-body">
          <div class="completion-rate">
            <div class="rate-circle">
              <span class="rate-number">{getCompletionRate()}%</span>
            </div>
            <span class="rate-label">Tỷ lệ hoàn thành</span>
          </div>
          <div class="stat-breakdown">
            <span class="breakdown-item">
              <span class="dot completed"></span>
              Hoàn thành: 156
            </span>
            <span class="breakdown-item">
              <span class="dot processing"></span>
              Đang xử lý: {$pendingApplications.length}
            </span>
          </div>
        </div>
        <div class="card-footer">
          <a href="/government?tab=statistics" class="card-link">Xem báo cáo →</a>
        </div>
      </div>
    </div>
  </section>
  
  <!-- Two Column Layout -->
  <div class="dashboard-columns">
    <!-- Recent Documents -->
    <section class="column-section">
      <div class="section-header">
        <h3>📄 Văn bản mới nhất</h3>
        <a href="/government?tab=documents" class="view-all">Xem tất cả</a>
      </div>
      <div class="document-list">
        {#if $recentDocuments.length === 0}
          <div class="empty-mini">
            <span>Chưa có văn bản nào</span>
          </div>
        {:else}
          {#each $recentDocuments.slice(0, 5) as doc}
            <div class="document-item">
              <div class="doc-icon-small">
                {doc.type === 'decision' ? '📋' : 
                 doc.type === 'notice' ? '📢' : 
                 doc.type === 'report' ? '📊' : '📄'}
              </div>
              <div class="doc-info">
                <span class="doc-title-small">{doc.title}</span>
                <span class="doc-meta-small">
                  {new Date(doc.createdAt).toLocaleDateString('vi-VN')} • {doc.issuedBy}
                </span>
              </div>
              <span class="doc-status-small" 
                class:published={doc.status === 'published'}
                class:draft={doc.status === 'draft'}
              >
                {doc.status === 'published' ? 'Đã ban hành' : 
                 doc.status === 'draft' ? 'Bản nháp' : 'Khác'}
              </span>
            </div>
          {/each}
        {/if}
      </div>
    </section>
    
    <!-- Pending Applications -->
    <section class="column-section">
      <div class="section-header">
        <h3>📝 Hồ sơ cần xử lý</h3>
        <a href="/government?tab=procedures" class="view-all">Xem tất cả</a>
      </div>
      <div class="application-list">
        {#if $pendingApplications.length === 0}
          <div class="empty-mini">
            <span>✅ Không có hồ sơ cần xử lý</span>
          </div>
        {:else}
          {#each $pendingApplications.slice(0, 5) as app}
            <div class="application-item">
              <div class="app-status-indicator" 
                style="background: {app.status === 'processing' ? '#f59e0b' : 
                                   app.status === 'awaiting_additional_docs' ? '#f97316' : '#3b82f6'}"
              ></div>
              <div class="app-info">
                <span class="app-proc-name">{app.procedureName}</span>
                <span class="app-meta">
                  {app.citizenName} • Nộp ngày {new Date(app.submittedAt).toLocaleDateString('vi-VN')}
                </span>
              </div>
              <span class="app-status-badge">
                {app.status === 'processing' ? 'Đang xử lý' : 
                 app.status === 'awaiting_additional_docs' ? 'Chờ bổ sung' : 'Mới'}
              </span>
            </div>
          {/each}
        {/if}
      </div>
    </section>
  </div>
  
  <!-- Recent Activity -->
  <section class="activity-section">
    <h3>🔔 Hoạt động gần đây</h3>
    <div class="activity-list">
      {#each recentActivities as activity}
        <div class="activity-item">
          <span class="activity-icon">{activity.icon}</span>
          <span class="activity-text">{activity.text}</span>
          <span class="activity-time">{activity.time}</span>
        </div>
      {/each}
    </div>
  </section>
</div>

<style>
  .dashboard {
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
  }
  
  /* Welcome Section */
  .welcome-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    padding: 32px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    color: white;
  }
  
  .welcome-content h1 {
    margin: 0 0 8px;
    font-size: 1.5rem;
    font-weight: 700;
  }
  
  .welcome-content p {
    margin: 0;
    opacity: 0.9;
  }
  
  .welcome-date {
    text-align: right;
  }
  
  .date {
    font-size: 0.9375rem;
    opacity: 0.9;
  }
  
  /* Quick Actions */
  .quick-actions {
    margin-bottom: 32px;
  }
  
  .quick-actions h3 {
    margin: 0 0 16px;
    font-size: 1.125rem;
    color: #1e293b;
  }
  
  .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
  }
  
  .action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 24px;
    background: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  
  .action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  
  .action-icon {
    font-size: 2rem;
  }
  
  .action-label {
    font-size: 0.875rem;
    color: #374151;
    font-weight: 500;
  }
  
  /* Stats Overview */
  .stats-overview {
    margin-bottom: 32px;
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
  }
  
  .stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-top: 4px solid #e2e8f0;
  }
  
  .stat-card.citizens { border-top-color: #3b82f6; }
  .stat-card.documents { border-top-color: #10b981; }
  .stat-card.procedures { border-top-color: #f59e0b; }
  .stat-card.performance { border-top-color: #8b5cf6; }
  
  .card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
  }
  
  .card-icon {
    font-size: 1.5rem;
  }
  
  .card-title {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
  }
  
  .card-body {
    margin-bottom: 16px;
  }
  
  .stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
    margin-bottom: 12px;
  }
  
  .stat-breakdown {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  
  .breakdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    color: #64748b;
  }
  
  .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }
  
  .dot.male { background: #3b82f6; }
  .dot.female { background: #ec4899; }
  .dot.published { background: #10b981; }
  .dot.draft { background: #6b7280; }
  .dot.active { background: #f59e0b; }
  .dot.pending { background: #ef4444; }
  .dot.completed { background: #10b981; }
  .dot.processing { background: #f59e0b; }
  
  .completion-rate {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
  }
  
  .rate-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 4px solid #8b5cf6;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .rate-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #8b5cf6;
  }
  
  .rate-label {
    font-size: 0.875rem;
    color: #64748b;
  }
  
  .card-footer {
    border-top: 1px solid #f1f5f9;
    padding-top: 16px;
  }
  
  .card-link {
    color: #3b82f6;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
  }
  
  .card-link:hover {
    text-decoration: underline;
  }
  
  /* Dashboard Columns */
  .dashboard-columns {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-bottom: 32px;
  }
  
  .column-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  
  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
  }
  
  .section-header h3 {
    margin: 0;
    font-size: 1rem;
    color: #1e293b;
  }
  
  .view-all {
    font-size: 0.875rem;
    color: #3b82f6;
    text-decoration: none;
  }
  
  .view-all:hover {
    text-decoration: underline;
  }
  
  /* Document List */
  .document-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .document-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
  }
  
  .doc-icon-small {
    font-size: 1.25rem;
  }
  
  .doc-info {
    flex: 1;
    min-width: 0;
  }
  
  .doc-title-small {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .doc-meta-small {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
  }
  
  .doc-status-small {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    background: #f1f5f9;
    color: #64748b;
  }
  
  .doc-status-small.published {
    background: #d1fae5;
    color: #065f46;
  }
  
  .doc-status-small.draft {
    background: #f3f4f6;
    color: #374151;
  }
  
  /* Application List */
  .application-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .application-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
  }
  
  .app-status-indicator {
    width: 4px;
    height: 40px;
    border-radius: 2px;
  }
  
  .app-info {
    flex: 1;
    min-width: 0;
  }
  
  .app-proc-name {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .app-meta {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
  }
  
  .app-status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    background: #dbeafe;
    color: #1d4ed8;
    white-space: nowrap;
  }
  
  .empty-mini {
    text-align: center;
    padding: 32px;
    color: #64748b;
    font-size: 0.875rem;
  }
  
  /* Activity Section */
  .activity-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  
  .activity-section h3 {
    margin: 0 0 16px;
    font-size: 1rem;
    color: #1e293b;
  }
  
  .activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
  }
  
  .activity-icon {
    font-size: 1.25rem;
  }
  
  .activity-text {
    flex: 1;
    font-size: 0.875rem;
    color: #374151;
  }
  
  .activity-time {
    font-size: 0.75rem;
    color: #64748b;
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .welcome-section {
      flex-direction: column;
      text-align: center;
      gap: 16px;
    }
    
    .welcome-date {
      text-align: center;
    }
    
    .dashboard-columns {
      grid-template-columns: 1fr;
    }
    
    .stats-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
