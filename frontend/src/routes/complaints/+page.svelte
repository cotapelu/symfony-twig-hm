<script lang="ts">
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';

  let activeTab = 'overview';
  let loading = false;
  let showModal = false;

  let complaints: any[] = [];
  let citizens: any[] = [];
  let officers: any[] = [];

  let stats = {
    total: 0,
    pending: 0,
    processing: 0,
    resolved: 0,
    rejected: 0
  };

  let formData = {
    title: '',
    content: '',
    category: '',
    citizenId: '',
    priority: 'normal'
  };

  const API_BASE = 'http://localhost:8080/api/complaints';

  const categories = [
    { value: 'administrative', label: 'Hành chính' },
    { value: 'security', label: 'An ninh trật tự' },
    { value: 'environment', label: 'Môi trường' },
    { value: 'infrastructure', label: 'Hạ tầng' },
    { value: 'traffic', label: 'Giao thông' },
    { value: 'public', label: 'Công trình công cộng' },
    { value: 'other', label: 'Khác' }
  ];

  onMount(() => {
    loadData();
  });

  async function loadData() {
    loading = true;
    try {
      const response = await fetch(API_BASE);
      if (response.ok) {
        const data = await response.json();
        complaints = data.complaints || [];
      } else {
        complaints = generateMockComplaints();
      }
    } catch {
      complaints = generateMockComplaints();
    }
    calculateStats();
    loading = false;
  }

  function generateMockComplaints() {
    return [
      { id: '1', title: 'Đường xá hư hỏng', content: 'Đường Nguyễn Trãi có nhiều ổ gà', category: 'infrastructure', status: 'PENDING', priority: 'high', citizenName: 'Nguyễn Văn A', createdAt: '2026-02-14 08:30', assignedTo: null },
      { id: '2', title: 'Ô nhiễm tiếng ồn', content: 'Công trường xây dựng gây ồn ào', category: 'environment', status: 'PROCESSING', priority: 'normal', citizenName: 'Trần Thị B', createdAt: '2026-02-13 14:20', assignedTo: 'Ông Nguyễn Văn X' },
      { id: '3', title: 'Vi phạm trật tự xây dựng', content: 'Xây dựng không phép trên đường Lý Thường Kiệt', category: 'administrative', status: 'RESOLVED', priority: 'high', citizenName: 'Lê Văn C', createdAt: '2026-02-12 10:15', assignedTo: 'Bà Trần Thị Y' },
      { id: '4', title: 'An ninh khu phố', content: 'Khu vực phường Đống Đa ban đêm không an toàn', category: 'security', status: 'PENDING', priority: 'urgent', citizenName: 'Phạm Thị D', createdAt: '2026-02-14 09:00', assignedTo: null },
      { id: '5', title: 'Vệ sinh môi trường', content: 'Rác thải tại ngõ 123 chưa được thu gom', category: 'environment', status: 'RESOLVED', priority: 'low', citizenName: 'Hoàng Văn E', createdAt: '2026-02-11 16:45', assignedTo: 'Ông Lê Văn Z' }
    ];
  }

  function calculateStats() {
    stats = {
      total: complaints.length,
      pending: complaints.filter(c => c.status === 'PENDING').length,
      processing: complaints.filter(c => c.status === 'PROCESSING').length,
      resolved: complaints.filter(c => c.status === 'RESOLVED').length,
      rejected: complaints.filter(c => c.status === 'REJECTED').length
    };
  }

  async function createComplaint() {
    const newComplaint = {
      id: String(Date.now()),
      ...formData,
      status: 'PENDING',
      createdAt: new Date().toISOString(),
      assignedTo: null
    };
    complaints = [newComplaint, ...complaints];
    calculateStats();
    showModal = false;
    formData = { title: '', content: '', category: '', citizenId: '', priority: 'normal' };
  }

  async function updateStatus(id: string, status: string) {
    complaints = complaints.map(c => c.id === id ? { ...c, status } : c);
    calculateStats();
  }

  function getStatusColor(status: string) {
    switch (status) {
      case 'PENDING': return '#f59e0b';
      case 'PROCESSING': return '#3b82f6';
      case 'RESOLVED': return '#10b981';
      case 'REJECTED': return '#ef4444';
      default: return '#6b7280';
    }
  }

  function getPriorityColor(priority: string) {
    switch (priority) {
      case 'urgent': return '#dc2626';
      case 'high': return '#f59e0b';
      case 'normal': return '#3b82f6';
      case 'low': return '#6b7280';
      default: return '#6b7280';
    }
  }

  function getCategoryLabel(value: string) {
    return categories.find(c => c.value === value)?.label || value;
  }

  function formatDate(dateStr: string) {
    return new Date(dateStr).toLocaleDateString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
</script>

<div class="complaints-page">
  <header class="page-header">
    <h1>📝 Quản Lý Khiếu Nại - Phản Ánh</h1>
    <button class="btn-primary" on:click={() => showModal = true}>
      + Tiếp Nhận Mới
    </button>
  </header>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-value">{stats.total}</div>
      <div class="stat-label">Tổng số</div>
    </div>
    <div class="stat-card pending">
      <div class="stat-value">{stats.pending}</div>
      <div class="stat-label">Chờ xử lý</div>
    </div>
    <div class="stat-card processing">
      <div class="stat-value">{stats.processing}</div>
      <div class="stat-label">Đang xử lý</div>
    </div>
    <div class="stat-card resolved">
      <div class="stat-value">{stats.resolved}</div>
      <div class="stat-label">Đã giải quyết</div>
    </div>
    <div class="stat-card rejected">
      <div class="stat-value">{stats.rejected}</div>
      <div class="stat-label">Từ chối</div>
    </div>
  </div>

  <div class="filters">
    <input type="text" placeholder="Tìm kiếm..." class="search-input" />
    <select class="filter-select">
      <option value="">Tất cả trạng thái</option>
      <option value="PENDING">Chờ xử lý</option>
      <option value="PROCESSING">Đang xử lý</option>
      <option value="RESOLVED">Đã giải quyết</option>
      <option value="REJECTED">Từ chối</option>
    </select>
    <select class="filter-select">
      <option value="">Tất cả loại</option>
      {#each categories as cat}
        <option value={cat.value}>{cat.label}</option>
      {/each}
    </select>
  </div>

  <div class="complaints-list">
    {#each complaints as complaint}
      <div class="complaint-card">
        <div class="complaint-header">
          <div class="complaint-title">
            <span class="priority-badge" style="background: {getPriorityColor(complaint.priority)}">
              {complaint.priority.toUpperCase()}
            </span>
            <h3>{complaint.title}</h3>
          </div>
          <div class="complaint-id">#{complaint.id}</div>
        </div>
        
        <div class="complaint-content">
          <p>{complaint.content}</p>
        </div>

        <div class="complaint-meta">
          <div class="meta-item">
            <span class="meta-label">Loại:</span>
            <span class="meta-value category">{getCategoryLabel(complaint.category)}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Công dân:</span>
            <span class="meta-value">{complaint.citizenName}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Ngày tiếp nhận:</span>
            <span class="meta-value">{formatDate(complaint.createdAt)}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Người xử lý:</span>
            <span class="meta-value">{complaint.assignedTo || 'Chưa phân công'}</span>
          </div>
        </div>

        <div class="complaint-footer">
          <div class="status-badge" style="background: {getStatusColor(complaint.status)}">
            {complaint.status === 'PENDING' ? 'Chờ xử lý' : 
             complaint.status === 'PROCESSING' ? 'Đang xử lý' :
             complaint.status === 'RESOLVED' ? 'Đã giải quyết' : 'Từ chối'}
          </div>
          <div class="actions">
            {#if complaint.status === 'PENDING'}
              <button class="btn-action" on:click={() => updateStatus(complaint.id, 'PROCESSING')}>
                Bắt đầu xử lý
              </button>
            {:else if complaint.status === 'PROCESSING'}
              <button class="btn-action success" on:click={() => updateStatus(complaint.id, 'RESOLVED')}>
                Đánh dấu hoàn thành
              </button>
            {/if}
            <button class="btn-action secondary">Xem chi tiết</button>
          </div>
        </div>
      </div>
    {/each}
  </div>

  {#if showModal}
    <div class="modal-overlay" on:click={() => showModal = false}>
      <div class="modal" on:click|stopPropagation>
        <div class="modal-header">
          <h2>Tiếp Nhận Khiếu Nại Mới</h2>
          <button class="close-btn" on:click={() => showModal = false}>×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tiêu đề *</label>
            <input type="text" bind:value={formData.title} placeholder="Nhập tiêu đề" />
          </div>
          <div class="form-group">
            <label>Nội dung *</label>
            <textarea bind:value={formData.content} rows="4" placeholder="Mô tả chi tiết vấn đề"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Loại khiếu nại *</label>
              <select bind:value={formData.category}>
                <option value="">Chọn loại</option>
                {#each categories as cat}
                  <option value={cat.value}>{cat.label}</option>
                {/each}
              </select>
            </div>
            <div class="form-group">
              <label>Mức độ ưu tiên</label>
              <select bind:value={formData.priority}>
                <option value="low">Thấp</option>
                <option value="normal">Bình thường</option>
                <option value="high">Cao</option>
                <option value="urgent">Khẩn cấp</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" on:click={() => showModal = false}>Hủy</button>
          <button class="btn-primary" on:click={createComplaint}>Gửi khiếu nại</button>
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .complaints-page {
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }

  .page-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
  }

  .btn-primary {
    background: #3b82f6;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
  }

  .btn-primary:hover {
    background: #2563eb;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
  }

  .stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1f2937;
  }

  .stat-label {
    font-size: 14px;
    color: #6b7280;
    margin-top: 4px;
  }

  .stat-card.pending .stat-value { color: #f59e0b; }
  .stat-card.processing .stat-value { color: #3b82f6; }
  .stat-card.resolved .stat-value { color: #10b981; }
  .stat-card.rejected .stat-value { color: #ef4444; }

  .filters {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
  }

  .search-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
  }

  .filter-select {
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    min-width: 180px;
  }

  .complaints-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .complaint-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .complaint-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
  }

  .complaint-title {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .complaint-title h3 {
    margin: 0;
    font-size: 18px;
    color: #1f2937;
  }

  .priority-badge {
    padding: 4px 8px;
    border-radius: 4px;
    color: white;
    font-size: 11px;
    font-weight: 600;
  }

  .complaint-id {
    color: #9ca3af;
    font-size: 14px;
  }

  .complaint-content p {
    color: #4b5563;
    margin: 0 0 16px 0;
    line-height: 1.6;
  }

  .complaint-meta {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 16px;
  }

  .meta-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .meta-label {
    font-size: 12px;
    color: #6b7280;
  }

  .meta-value {
    font-size: 14px;
    color: #1f2937;
    font-weight: 500;
  }

  .meta-value.category {
    color: #3b82f6;
  }

  .complaint-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    color: white;
    font-size: 13px;
    font-weight: 600;
  }

  .actions {
    display: flex;
    gap: 8px;
  }

  .btn-action {
    padding: 8px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
  }

  .btn-action:hover {
    background: #f9fafb;
  }

  .btn-action.success {
    background: #10b981;
    color: white;
    border-color: #10b981;
  }

  .btn-action.success:hover {
    background: #059669;
  }

  .btn-action.secondary {
    color: #6b7280;
  }

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

  .modal {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
  }

  .modal-header h2 {
    margin: 0;
    font-size: 20px;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #9ca3af;
  }

  .modal-body {
    padding: 24px;
  }

  .form-group {
    margin-bottom: 16px;
  }

  .form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
  }

  .btn-secondary {
    padding: 10px 20px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    cursor: pointer;
  }
</style>