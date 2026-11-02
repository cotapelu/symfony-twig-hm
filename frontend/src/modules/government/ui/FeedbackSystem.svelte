<script lang="ts">
  import { writable } from 'svelte/store';

  interface Feedback {
    id: string;
    type: 'complaint' | 'suggestion' | 'compliment' | 'inquiry';
    title: string;
    description: string;
    status: 'pending' | 'processing' | 'resolved' | 'rejected';
    category: string;
    createdAt: Date;
    updatedAt: Date;
    citizenName: string;
    citizenPhone: string;
    ward: string;
    rating?: number;
    response?: string;
  }

  let feedbacks = writable<Feedback[]>([
    { id: '1', type: 'complaint', title: 'Đường xá hư hỏng nặng', description: 'Đường Nguyễn Trãi có nhiều ổ gà, gây nguy hiểm cho người đi đường. Mong UBND sớm sửa chữa.', status: 'processing', category: 'Hạ tầng', createdAt: new Date(Date.now() - 86400000), updatedAt: new Date(Date.now() - 3600000), citizenName: 'Nguyễn Văn A', citizenPhone: '0912xxx', ward: 'Phường X', rating: 3 },
    { id: '2', type: 'suggestion', title: 'Nên có thêm công viên', description: 'Khu vực này thiếu không gian xanh. Mong UBND xem xét trồng thêm cây xanh, làm công viên.', status: 'pending', category: 'Môi trường', createdAt: new Date(Date.now() - 172800000), updatedAt: new Date(Date.now() - 172800000), citizenName: 'Trần Thị B', citizenPhone: '0903xxx', ward: 'Phường X' },
    { id: '3', type: 'compliment', title: 'Cảm ơn cán bộ UBND', description: 'Xin cảm ơn cán bộ phường đã hỗ trợ gia đình tôi làm thủ tục hộ khẩu rất nhanh chóng.', status: 'resolved', category: 'Khác', createdAt: new Date(Date.now() - 259200000), updatedAt: new Date(Date.now() - 86400000), citizenName: 'Lê Văn C', citizenPhone: '0932xxx', ward: 'Phường X', rating: 5, response: 'Cảm ơn ý kiến phản hồi tích cực của quý công dân. Chúc gia đình sức khỏe!' },
    { id: '4', type: 'inquiry', title: 'Hỏi về thủ tục làm CCCD', description: 'Tôi muốn hỏi thủ tục làm CCCD ở đâu? Cần chuẩn bị những gì?', status: 'resolved', category: 'TTHC', createdAt: new Date(Date.now() - 345600000), updatedAt: new Date(Date.now() - 172800000), citizenName: 'Phạm Thị D', citizenPhone: '0987xxx', ward: 'Phường Y', response: 'Để làm CCCD, bạn đến Công an huyện/quận nơi đăng ký thường trú. Mang theo CMND hoặc Sổ hộ khẩu và ảnh thẻ 3x4.' },
    { id: '5', type: 'complaint', title: 'Ô nhiễm tiếng ồn', description: 'Khu vực chợ có nhiều quán karaoke gây ồn ào đến 23h, ảnh hưởng đến cuộc sống người dân.', status: 'pending', category: 'Môi trường', createdAt: new Date(Date.now() - 43200000), updatedAt: new Date(Date.now() - 43200000), citizenName: 'Nguyễn Văn E', citizenPhone: '0919xxx', ward: 'Phường X' },
  ]);

  let activeTab: 'all' | 'pending' | 'processing' | 'resolved' = 'all';
  let selectedFeedback: Feedback | null = null;
  let responseText = '';
  let showFeedbackModal = false;
  let newFeedback = { type: 'complaint', title: '', description: '', category: 'Hạ tầng', citizenName: '', citizenPhone: '', ward: '' };

  const typeIcons: Record<string, string> = {
    complaint: '😤',
    suggestion: '💡',
    compliment: '🙏',
    inquiry: '❓',
  };

  const typeColors: Record<string, string> = {
    complaint: '#ef4444',
    suggestion: '#3b82f6',
    compliment: '#10b981',
    inquiry: '#f59e0b',
  };

  const statusLabels: Record<string, string> = {
    pending: 'Chờ xử lý',
    processing: 'Đang xử lý',
    resolved: 'Đã giải quyết',
    rejected: 'Từ chối',
  };

  function formatDate(date: Date): string {
    return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }

  function updateStatus(id: string, status: string) {
    feedbacks.update(f => f.map(fb => 
      fb.id === id ? { ...fb, status: status as Feedback['status'], updatedAt: new Date() } : fb
    ));
  }

  function addResponse() {
    if (!selectedFeedback || !responseText.trim()) return;
    feedbacks.update(f => f.map(fb => 
      fb.id === selectedFeedback?.id ? { ...fb, response: responseText, status: 'resolved' as const, updatedAt: new Date() } : fb
    ));
    responseText = '';
    selectedFeedback = null;
  }

  function submitFeedback() {
    const feedback: Feedback = {
      id: Math.random().toString(36).substring(2, 15),
      ...newFeedback,
      status: 'pending',
      createdAt: new Date(),
      updatedAt: new Date(),
    };
    feedbacks.update(f => [feedback, ...f]);
    showFeedbackModal = false;
    newFeedback = { type: 'complaint', title: '', description: '', category: 'Hạ tầng', citizenName: '', citizenPhone: '', ward: '' };
  }

  $: filteredFeedbacks = $feedbacks.filter(f => {
    if (activeTab === 'pending') return f.status === 'pending';
    if (activeTab === 'processing') return f.status === 'processing';
    if (activeTab === 'resolved') return f.status === 'resolved';
    return true;
  });

  $: stats = {
    total: $feedbacks.length,
    pending: $feedbacks.filter(f => f.status === 'pending').length,
    processing: $feedbacks.filter(f => f.status === 'processing').length,
    resolved: $feedbacks.filter(f => f.status === 'resolved').length,
  };
</script>

<div class="feedback-container">
  <!-- Header -->
  <header class="feedback-header">
    <div class="header-left">
      <h2>💬 Phản hồi Công dân</h2>
      <span class="subtitle">Quản lý khiếu nại, đề xuất, góp ý</span>
    </div>
    <button class="header-btn primary" on:click={() => showFeedbackModal = true}>
      ➕ Tiếp nhận phản hồi
    </button>
  </header>

  <!-- Stats Bar -->
  <div class="stats-bar">
    <div class="stat-item" class:active={activeTab === 'all'} on:click={() => activeTab = 'all'}>
      <span class="stat-value">{stats.total}</span>
      <span class="stat-label">Tất cả</span>
    </div>
    <div class="stat-item pending" class:active={activeTab === 'pending'} on:click={() => activeTab = 'pending'}>
      <span class="stat-value">{stats.pending}</span>
      <span class="stat-label">Chờ xử lý</span>
    </div>
    <div class="stat-item processing" class:active={activeTab === 'processing'} on:click={() => activeTab = 'processing'}>
      <span class="stat-value">{stats.processing}</span>
      <span class="stat-label">Đang xử lý</span>
    </div>
    <div class="stat-item resolved" class:active={activeTab === 'resolved'} on:click={() => activeTab = 'resolved'}>
      <span class="stat-value">{stats.resolved}</span>
      <span class="stat-label">Đã giải quyết</span>
    </div>
  </div>

  <!-- Main Content -->
  <div class="feedback-content">
    <!-- List -->
    <div class="feedback-list">
      {#each filteredFeedbacks as feedback (feedback.id)}
        <button 
          class="feedback-card"
          class:selected={selectedFeedback?.id === feedback.id}
          class:pending={feedback.status === 'pending'}
          class:processing={feedback.status === 'processing'}
          on:click={() => selectedFeedback = feedback}
        >
          <div class="feedback-icon" style="background: {typeColors[feedback.type]}">
            {typeIcons[feedback.type]}
          </div>
          <div class="feedback-info">
            <h4>{feedback.title}</h4>
            <p class="feedback-preview">{feedback.description.substring(0, 80)}...</p>
            <div class="feedback-meta">
              <span class="category">{feedback.category}</span>
              <span class="date">{formatDate(feedback.createdAt)}</span>
              <span class="status-badge {feedback.status}">{statusLabels[feedback.status]}</span>
            </div>
          </div>
        </button>
      {:else}
        <div class="empty-state">
          <span>📭</span>
          <p>Không có phản hồi nào</p>
        </div>
      {/each}
    </div>

    <!-- Detail Panel -->
    <div class="feedback-detail">
      {#if selectedFeedback}
        <div class="detail-header">
          <div class="detail-type" style="background: {typeColors[selectedFeedback.type]}">
            {typeIcons[selectedFeedback.type]} {selectedFeedback.type}
          </div>
          <span class="detail-status {selectedFeedback.status}">{statusLabels[selectedFeedback.status]}</span>
        </div>

        <div class="detail-content">
          <h3>{selectedFeedback.title}</h3>
          <p class="detail-desc">{selectedFeedback.description}</p>

          <div class="detail-section">
            <h5>Thông tin công dân</h5>
            <div class="info-row">
              <span class="label">Họ tên:</span>
              <span class="value">{selectedFeedback.citizenName}</span>
            </div>
            <div class="info-row">
              <span class="label">Điện thoại:</span>
              <span class="value">{selectedFeedback.citizenPhone}</span>
            </div>
            <div class="info-row">
              <span class="label">Phường/Xã:</span>
              <span class="value">{selectedFeedback.ward}</span>
            </div>
          </div>

          {#if selectedFeedback.rating}
            <div class="detail-section">
              <h5>Đánh giá</h5>
              <div class="rating">
                {#each Array(5) as _, i}
                  <span class="star" class:filled={i < selectedFeedback.rating!}>⭐</span>
                {/each}
              </div>
            </div>
          {/if}

          {#if selectedFeedback.response}
            <div class="detail-section response">
              <h5>Phản hồi</h5>
              <p>{selectedFeedback.response}</p>
            </div>
          {:else if selectedFeedback.status !== 'rejected'}
            <div class="detail-section">
              <h5>Gửi phản hồi</h5>
              <textarea 
                bind:value={responseText} 
                placeholder="Nhập phản hồi..."
                rows="4"
              ></textarea>
              <div class="detail-actions">
                <button class="action-btn" on:click={() => updateStatus(selectedFeedback!.id, 'rejected')}>
                  Từ chối
                </button>
                <button class="action-btn" on:click={() => updateStatus(selectedFeedback!.id, 'processing')}>
                  Đang xử lý
                </button>
                <button class="action-btn primary" on:click={addResponse}>
                  Gửi phản hồi & Đóng
                </button>
              </div>
            </div>
          {/if}
        </div>
      {:else}
        <div class="no-selection">
          <span>👈</span>
          <p>Chọn một phản hồi để xem chi tiết</p>
        </div>
      {/if}
    </div>
  </div>
</div>

<!-- New Feedback Modal -->
{#if showFeedbackModal}
  <div class="modal-overlay" on:click={() => showFeedbackModal = false}>
    <div class="modal-content" on:click|stopPropagation>
      <div class="modal-header">
        <h3>💬 Tiếp nhận phản hồi mới</h3>
        <button class="close-btn" on:click={() => showFeedbackModal = false}>✕</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Loại phản hồi</label>
          <select bind:value={newFeedback.type}>
            <option value="complaint">😤 Khiếu nại</option>
            <option value="suggestion">💡 Đề xuất</option>
            <option value="compliment">🙏 Khen ngợi</option>
            <option value="inquiry">❓ Hỏi thông tin</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tiêu đề</label>
          <input type="text" bind:value={newFeedback.title} placeholder="Nhập tiêu đề...">
        </div>
        <div class="form-group">
          <label>Nội dung</label>
          <textarea bind:value={newFeedback.description} placeholder="Mô tả chi tiết..." rows="4"></textarea>
        </div>
        <div class="form-group">
          <label>Danh mục</label>
          <select bind:value={newFeedback.category}>
            <option value="Hạ tầng">Hạ tầng</option>
            <option value="Môi trường">Môi trường</option>
            <option value="TTHC">Thủ tục hành chính</option>
            <option value="An ninh">An ninh</option>
            <option value="Khác">Khác</option>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Họ tên</label>
            <input type="text" bind:value={newFeedback.citizenName} placeholder="Họ tên công dân">
          </div>
          <div class="form-group">
            <label>Điện thoại</label>
            <input type="text" bind:value={newFeedback.citizenPhone} placeholder="Số điện thoại">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn secondary" on:click={() => showFeedbackModal = false}>Hủy</button>
        <button class="btn primary" on:click={submitFeedback}>Tiếp nhận</button>
      </div>
    </div>
  </div>
{/if}

<style>
  .feedback-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #f1f5f9;
  }

  .feedback-header {
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

  .header-btn {
    padding: 10px 16px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
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
    min-width: 100px;
  }

  .stat-item.active {
    background: #eff6ff;
  }

  .stat-item.pending { background: #fef3c7; }
  .stat-item.processing { background: #dbeafe; }
  .stat-item.resolved { background: #d1fae5; }

  .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  .feedback-content {
    display: flex;
    flex: 1;
    overflow: hidden;
  }

  .feedback-list {
    width: 400px;
    overflow-y: auto;
    padding: 16px;
    background: white;
    border-right: 1px solid #e2e8f0;
  }

  .feedback-card {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border: none;
    border-radius: 12px;
    margin-bottom: 8px;
    cursor: pointer;
    width: 100%;
    text-align: left;
    transition: all 0.2s;
  }

  .feedback-card:hover {
    background: #f1f5f9;
  }

  .feedback-card.selected {
    background: #eff6ff;
    border: 2px solid #3b82f6;
  }

  .feedback-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
  }

  .feedback-info {
    flex: 1;
    min-width: 0;
  }

  .feedback-info h4 {
    margin: 0 0 4px;
    font-size: 0.9375rem;
    color: #1e293b;
  }

  .feedback-preview {
    margin: 0 0 8px;
    font-size: 0.8rem;
    color: #64748b;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .feedback-meta {
    display: flex;
    gap: 8px;
    font-size: 0.7rem;
    align-items: center;
  }

  .category {
    background: #e2e8f0;
    padding: 2px 6px;
    border-radius: 4px;
  }

  .date {
    color: #64748b;
  }

  .status-badge {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.7rem;
  }

  .status-badge.pending { background: #fef3c7; color: #b45309; }
  .status-badge.processing { background: #dbeafe; color: #1d4ed8; }
  .status-badge.resolved { background: #d1fae5; color: #047857; }
  .status-badge.rejected { background: #fee2e2; color: #dc2626; }

  .feedback-detail {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    background: #f8fafc;
  }

  .detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }

  .detail-type {
    padding: 8px 16px;
    border-radius: 20px;
    color: white;
    font-size: 0.875rem;
    text-transform: capitalize;
  }

  .detail-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
  }

  .detail-status.pending { background: #fef3c7; color: #b45309; }
  .detail-status.processing { background: #dbeafe; color: #1d4ed8; }
  .detail-status.resolved { background: #d1fae5; color: #047857; }

  .detail-content h3 {
    margin: 0 0 12px;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .detail-desc {
    margin: 0 0 20px;
    color: #475569;
    line-height: 1.6;
  }

  .detail-section {
    background: white;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 16px;
  }

  .detail-section h5 {
    margin: 0 0 12px;
    font-size: 0.875rem;
    color: #64748b;
  }

  .info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
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

  .rating {
    display: flex;
    gap: 4px;
  }

  .star {
    font-size: 1.25rem;
    opacity: 0.3;
  }

  .star.filled {
    opacity: 1;
  }

  .detail-section.response {
    background: #d1fae5;
  }

  .detail-section textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-family: inherit;
    resize: vertical;
    margin-bottom: 12px;
  }

  .detail-actions {
    display: flex;
    gap: 8px;
  }

  .action-btn {
    padding: 8px 16px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .action-btn.primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
  }

  .no-selection {
    text-align: center;
    padding: 60px;
    color: #64748b;
  }

  .no-selection span {
    font-size: 2rem;
    display: block;
    margin-bottom: 16px;
  }

  .empty-state {
    text-align: center;
    padding: 40px;
    color: #64748b;
  }

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
  }

  .btn.secondary {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
  }

  .btn.primary {
    background: #3b82f6;
    color: white;
    border: none;
  }
</style>
