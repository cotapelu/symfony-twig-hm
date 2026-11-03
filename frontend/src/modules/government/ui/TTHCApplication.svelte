<script lang="ts">
  import { onMount } from 'svelte';
  import { procedureStore, citizenStore } from '../../government';
  import type { AdministrativeProcedure, ProcedureApplication } from '../../government';

  let activeTab: 'apply' | 'track' | 'history' = 'apply';
  let selectedProcedure: AdministrativeProcedure | null = null;
  let showApplicationForm = false;
  let showTrackingModal = false;
  let trackingApplication: ProcedureApplication | null = null;
  
  // Form data
  let applicantName = '';
  let applicantId = '';
  let applicantPhone = '';
  let applicantEmail = '';
  let notes = '';
  let uploadedDocuments: File[] = [];
  
  let loading = false;
  let errorMessage = '';
  let successMessage = '';

  $: procedures = $procedureStore.procedures;
  $: applications = $procedureStore.applications;
  $: loadingState = $procedureStore.loading;

  onMount(() => {
    loadProcedures();
    loadApplications();
  });

  async function loadProcedures() {
    try {
      await procedureStore.loadActiveProcedures();
    } catch (err) {
      console.error('Failed to load procedures:', err);
    }
  }

  async function loadApplications() {
    try {
      await procedureStore.loadApplications();
    } catch (err) {
      console.error('Failed to load applications:', err);
    }
  }

  function selectProcedure(procedure: AdministrativeProcedure) {
    selectedProcedure = procedure;
    showApplicationForm = true;
    // Reset form
    applicantName = '';
    applicantId = '';
    applicantPhone = '';
    applicantEmail = '';
    notes = '';
    uploadedDocuments = [];
  }

  function closeApplicationForm() {
    showApplicationForm = false;
    selectedProcedure = null;
  }

  async function submitApplication() {
    if (!selectedProcedure || !applicantName || !applicantId) {
      errorMessage = 'Vui lòng điền đầy đủ thông tin bắt buộc';
      return;
    }

    loading = true;
    errorMessage = '';
    successMessage = '';

    try {
      await procedureStore.createApplication({
        procedureId: selectedProcedure.id,
        citizenId: applicantId,
        notes: notes,
        submittedDocuments: uploadedDocuments.map(d => d.name)
      });

      successMessage = 'Nộp hồ sơ thành công! Mã hồ sơ của bạn sẽ được gửi qua SMS.';
      closeApplicationForm();
      await loadApplications();
    } catch (err) {
      errorMessage = err instanceof Error ? err.message : 'Nộp hồ sơ thất bại';
    } finally {
      loading = false;
    }
  }

  function viewTracking(application: ProcedureApplication) {
    trackingApplication = application;
    showTrackingModal = true;
  }

  function closeTrackingModal() {
    showTrackingModal = false;
    trackingApplication = null;
  }

  function getStatusColor(status: string): string {
    const colors: Record<string, string> = {
      pending: 'bg-yellow-100 text-yellow-800',
      processing: 'bg-blue-100 text-blue-800',
      awaiting_additional_docs: 'bg-orange-100 text-orange-800',
      approved: 'bg-green-100 text-green-800',
      rejected: 'bg-red-100 text-red-800',
      completed: 'bg-purple-100 text-purple-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  }

  function getStatusText(status: string): string {
    const texts: Record<string, string> = {
      pending: 'Chờ xử lý',
      processing: 'Đang xử lý',
      awaiting_additional_docs: 'Chờ bổ sung',
      approved: 'Đã duyệt',
      rejected: 'Bị từ chối',
      completed: 'Hoàn thành'
    };
    return texts[status] || status;
  }

  function handleFileUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files) {
      uploadedDocuments = Array.from(target.files);
    }
  }

  function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('vi-VN', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }

  function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND'
    }).format(amount);
  }
</script>

<div class="tthc-page">
  <div class="tthc-container">
    <div class="tthc-header">
      <h1>📋 Thủ tục hành chính</h1>
      <p class="subtitle">Nộp hồ sơ và theo dõi tiến độ xử lý trực tuyến</p>
    </div>

    <div class="tabs">
      <button 
        class="tab {activeTab === 'apply' ? 'active' : ''}"
        on:click={() => activeTab = 'apply'}
      >
        📝 Nộp hồ sơ
      </button>
      <button 
        class="tab {activeTab === 'track' ? 'active' : ''}"
        on:click={() => activeTab = 'track'}
      >
        🔍 Tra cứu
      </button>
      <button 
        class="tab {activeTab === 'history' ? 'active' : ''}"
        on:click={() => activeTab = 'history'}
      >
        📚 Lịch sử
      </button>
    </div>

    {#if successMessage}
      <div class="success-message">
        ✅ {successMessage}
      </div>
    {/if}

    {#if errorMessage}
      <div class="error-message">
        ❌ {errorMessage}
      </div>
    {/if}

    {#if activeTab === 'apply'}
      <div class="apply-section">
        <h2>Chọn thủ tục cần thực hiện</h2>
        
        {#if loadingState}
          <div class="loading">
            <div class="spinner"></div>
            <p>Đang tải danh sách thủ tục...</p>
          </div>
        {:else if procedures.length === 0}
          <p class="no-data">Không có thủ tục nào</p>
        {:else}
          <div class="procedures-grid">
            {#each procedures as procedure}
              <div class="procedure-card" on:click={() => selectProcedure(procedure)} role="button" tabindex="0">
                <div class="procedure-header">
                  <span class="procedure-code">{procedure.code}</span>
                  <span class="procedure-category">{procedure.category}</span>
                </div>
                <h3 class="procedure-name">{procedure.name}</h3>
                <p class="procedure-description">{procedure.description}</p>
                <div class="procedure-meta">
                  <span class="meta-item">
                    ⏱️ {procedure.processingTimeDays} ngày
                  </span>
                  <span class="meta-item">
                    💰 {procedure.fee === 0 ? 'Miễn phí' : formatCurrency(procedure.fee)}
                  </span>
                </div>
                <div class="procedure-documents">
                  <strong>Giấy tờ cần thiết:</strong>
                  <ul>
                    {#each procedure.requiredDocuments as doc}
                      <li>• {doc}</li>
                    {/each}
                  </ul>
                </div>
                <button class="btn-apply">
                  Nộp hồ sơ →
                </button>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    {:else if activeTab === 'track'}
      <div class="track-section">
        <h2>Tra cứu hồ sơ</h2>
        <div class="search-box">
          <input 
            type="text" 
            placeholder="Nhập mã hồ sơ hoặc số CCCD..."
            class="search-input"
          />
          <button class="btn-search">🔍 Tra cứu</button>
        </div>

        <div class="tracking-info">
          <h3>Hướng dẫn tra cứu</h3>
          <ul>
            <li>Nhập mã hồ sơ được cấp khi nộp trực tuyến</li>
            <li>Hoặc nhập số CCCD để xem tất cả hồ sơ của bạn</li>
            <li>Hệ thống sẽ hiển thị trạng thái và tiến độ xử lý</li>
          </ul>
        </div>
      </div>
    {:else}
      <div class="history-section">
        <h2>Lịch sử hồ sơ</h2>
        {#if applications.length === 0}
          <p class="no-data">Bạn chưa có hồ sơ nào</p>
        {:else}
          <div class="applications-list">
            {#each applications as app}
              <div class="application-item">
                <div class="application-info">
                  <span class="application-id">Mã: {app.id}</span>
                  <h4>{app.procedureName || 'Thủ tục'}</h4>
                  <span class="application-date">Nộp ngày: {formatDate(app.submittedAt)}</span>
                </div>
                <div class="application-status">
                  <span class="status-badge {getStatusColor(app.status)}">
                    {getStatusText(app.status)}
                  </span>
                  <button 
                    class="btn-track"
                    on:click={() => viewTracking(app)}
                  >
                    Theo dõi
                  </button>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    {/if}
  </div>
</div>

{#if showApplicationForm && selectedProcedure}
  <div class="modal-overlay" on:click={closeApplicationForm}>
    <div class="modal modal-large" on:click|stopPropagation>
      <div class="modal-header">
        <h2>📝 Nộp hồ sơ: {selectedProcedure.name}</h2>
        <button class="btn-close" on:click={closeApplicationForm}>✕</button>
      </div>

      <div class="modal-body">
        <div class="procedure-summary">
          <p><strong>Mã thủ tục:</strong> {selectedProcedure.code}</p>
          <p><strong>Thời gian xử lý:</strong> {selectedProcedure.processingTimeDays} ngày</p>
          <p><strong>Phí:</strong> {selectedProcedure.fee === 0 ? 'Miễn phí' : formatCurrency(selectedProcedure.fee)}</p>
        </div>

        <form class="application-form">
          <h3>Thông tin người nộp</h3>
          
          <div class="form-group">
            <label>Họ và tên *</label>
            <input 
              type="text" 
              bind:value={applicantName}
              placeholder="Nhập họ và tên"
              required
            />
          </div>

          <div class="form-group">
            <label>Số CCCD *</label>
            <input 
              type="text" 
              bind:value={applicantId}
              placeholder="Nhập số CCCD"
              required
            />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Số điện thoại</label>
              <input 
                type="tel" 
                bind:value={applicantPhone}
                placeholder="Nhập số điện thoại"
              />
            </div>

            <div class="form-group">
              <label>Email</label>
              <input 
                type="email" 
                bind:value={applicantEmail}
                placeholder="Nhập email"
              />
            </div>
          </div>

          <div class="form-group">
            <label>Ghi chú</label>
            <textarea 
              bind:value={notes}
              placeholder="Nhập ghi chú nếu có..."
              rows="3"
            ></textarea>
          </div>

          <div class="form-group">
            <label>Tải lên giấy tờ</label>
            <input 
              type="file" 
              multiple
              accept=".pdf,.jpg,.jpeg,.png"
              on:change={handleFileUpload}
            />
            {#if uploadedDocuments.length > 0}
              <div class="uploaded-files">
                <p>Đã chọn {uploadedDocuments.length} file:</p>
                <ul>
                  {#each uploadedDocuments as file}
                    <li>📄 {file.name}</li>
                  {/each}
                </ul>
              </div>
            {/if}
          </div>

          <div class="required-documents">
            <h4>📋 Giấy tờ cần thiết:</h4>
            <ul>
              {#each selectedProcedure.requiredDocuments as doc}
                <li>✓ {doc}</li>
              {/each}
            </ul>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn-secondary" on:click={closeApplicationForm}>
          Hủy
        </button>
        <button 
          class="btn-primary" 
          on:click={submitApplication}
          disabled={loading || !applicantName || !applicantId}
        >
          {loading ? 'Đang xử lý...' : 'Nộp hồ sơ'}
        </button>
      </div>
    </div>
  </div>
{/if}

{#if showTrackingModal && trackingApplication}
  <div class="modal-overlay" on:click={closeTrackingModal}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>🔍 Theo dõi hồ sơ</h2>
        <button class="btn-close" on:click={closeTrackingModal}>✕</button>
      </div>

      <div class="modal-body">
        <div class="tracking-info">
          <p><strong>Mã hồ sơ:</strong> {trackingApplication.id}</p>
          <p><strong>Thủ tục:</strong> {trackingApplication.procedureName}</p>
          <p><strong>Ngày nộp:</strong> {formatDate(trackingApplication.submittedAt)}</p>
        </div>

        <div class="tracking-timeline">
          <h4>📊 Tiến độ xử lý</h4>
          <div class="timeline">
            <div class="timeline-item active">
              <div class="timeline-marker"></div>
              <div class="timeline-content">
                <span class="timeline-title">Nhận hồ sơ</span>
                <span class="timeline-date">{formatDate(trackingApplication.submittedAt)}</span>
              </div>
            </div>
            
            <div class="timeline-item {['processing', 'awaiting_additional_docs', 'approved', 'completed'].includes(trackingApplication.status) ? 'active' : ''}">
              <div class="timeline-marker"></div>
              <div class="timeline-content">
                <span class="timeline-title">Đang xử lý</span>
              </div>
            </div>

            {#if trackingApplication.status === 'awaiting_additional_docs'}
              <div class="timeline-item active warning">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                  <span class="timeline-title">Chờ bổ sung giấy tờ</span>
                  <span class="timeline-note">{trackingApplication.notes}</span>
                </div>
              </div>
            {/if}

            <div class="timeline-item {['approved', 'completed'].includes(trackingApplication.status) ? 'active' : ''} {trackingApplication.status === 'rejected' ? 'rejected' : ''}">
              <div class="timeline-marker"></div>
              <div class="timeline-content">
                <span class="timeline-title">
                  {trackingApplication.status === 'rejected' ? 'Bị từ chối' : 'Hoàn thành'}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="current-status">
          <h4>📋 Trạng thái hiện tại</h4>
          <span class="status-badge-large {getStatusColor(trackingApplication.status)}">
            {getStatusText(trackingApplication.status)}
          </span>
          {#if trackingApplication.processedBy}
            <p class="officer-info">
              👤 Cán bộ phụ trách: {trackingApplication.processedBy}
            </p>
          {/if}
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-secondary" on:click={closeTrackingModal}>
          Đóng
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .tthc-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 2rem;
  }

  .tthc-container {
    max-width: 1200px;
    margin: 0 auto;
  }

  .tthc-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .tthc-header h1 {
    color: #1e3a8a;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
  }

  .subtitle {
    color: #64748b;
    font-size: 1.1rem;
  }

  .tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    background: white;
    padding: 0.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }

  .tab {
    flex: 1;
    padding: 1rem 2rem;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 1rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.3s ease;
  }

  .tab:hover {
    background: #f1f5f9;
  }

  .tab.active {
    background: #3b82f6;
    color: white;
  }

  .success-message {
    background: #dcfce7;
    border: 1px solid #4ade80;
    color: #166534;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
  }

  .error-message {
    background: #fee2e2;
    border: 1px solid #f87171;
    color: #991b1b;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
  }

  .apply-section h2,
  .track-section h2,
  .history-section h2 {
    color: #1e3a8a;
    margin-bottom: 1.5rem;
  }

  .procedures-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
  }

  .procedure-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .procedure-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
  }

  .procedure-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
  }

  .procedure-code {
    background: #dbeafe;
    color: #1e40af;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
  }

  .procedure-category {
    background: #f3e8ff;
    color: #7c3aed;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
  }

  .procedure-name {
    color: #1e293b;
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
  }

  .procedure-description {
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 1rem;
  }

  .procedure-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .meta-item {
    color: #475569;
    font-size: 0.875rem;
  }

  .procedure-documents {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
  }

  .procedure-documents ul {
    margin: 0.5rem 0 0;
    padding-left: 1rem;
  }

  .procedure-documents li {
    color: #64748b;
    font-size: 0.875rem;
  }

  .btn-apply {
    width: 100%;
    padding: 0.75rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
  }

  .btn-apply:hover {
    background: #2563eb;
  }

  .search-box {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .search-input {
    flex: 1;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
  }

  .btn-search {
    padding: 1rem 2rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
  }

  .tracking-info {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }

  .tracking-info ul {
    color: #64748b;
  }

  .applications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .application-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }

  .application-info h4 {
    margin: 0.25rem 0;
    color: #1e293b;
  }

  .application-id {
    color: #3b82f6;
    font-weight: 600;
    font-size: 0.875rem;
  }

  .application-date {
    color: #64748b;
    font-size: 0.875rem;
  }

  .application-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
  }

  .status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
  }

  .btn-track {
    padding: 0.5rem 1rem;
    background: transparent;
    border: 1px solid #3b82f6;
    color: #3b82f6;
    border-radius: 6px;
    cursor: pointer;
  }

  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-large {
    max-width: 700px;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .modal-header h2 {
    margin: 0;
    color: #1e293b;
  }

  .btn-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .procedure-summary {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
  }

  .procedure-summary p {
    margin: 0.25rem 0;
    color: #475569;
  }

  .application-form h3 {
    color: #1e3a8a;
    margin-bottom: 1rem;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    color: #374151;
    font-weight: 500;
    margin-bottom: 0.5rem;
  }

  .form-group input,
  .form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  .uploaded-files {
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: #f0fdf4;
    border-radius: 8px;
  }

  .uploaded-files ul {
    margin: 0.25rem 0 0;
    padding-left: 1rem;
  }

  .required-documents {
    background: #fef3c7;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
  }

  .required-documents ul {
    margin: 0.5rem 0 0;
    padding-left: 1.5rem;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .btn-primary,
  .btn-secondary {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    border: none;
  }

  .btn-primary {
    background: #3b82f6;
    color: white;
  }

  .btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .btn-secondary {
    background: #e2e8f0;
    color: #475569;
  }

  .tracking-timeline {
    margin: 1.5rem 0;
  }

  .timeline {
    position: relative;
    padding-left: 2rem;
  }

  .timeline::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e2e8f0;
  }

  .timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
  }

  .timeline-marker {
    position: absolute;
    left: -1.75rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #e2e8f0;
    border: 2px solid white;
  }

  .timeline-item.active .timeline-marker {
    background: #3b82f6;
  }

  .timeline-item.rejected .timeline-marker {
    background: #ef4444;
  }

  .timeline-item.warning .timeline-marker {
    background: #f59e0b;
  }

  .timeline-content {
    display: flex;
    flex-direction: column;
  }

  .timeline-title {
    font-weight: 600;
    color: #1e293b;
  }

  .timeline-date,
  .timeline-note {
    font-size: 0.875rem;
    color: #64748b;
  }

  .current-status {
    text-align: center;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 12px;
  }

  .status-badge-large {
    display: inline-block;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
  }

  .officer-info {
    color: #64748b;
    margin: 0;
  }

  .loading {
    text-align: center;
    padding: 3rem;
  }

  .spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .no-data {
    text-align: center;
    color: #64748b;
    padding: 3rem;
  }
</style>
