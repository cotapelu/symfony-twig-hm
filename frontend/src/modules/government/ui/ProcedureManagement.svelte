<script lang="ts">
  import { onMount } from 'svelte';
  import { 
    procedureStore, 
    isProcedureLoading,
    isProcedureSaving,
    procedureError,
    activeProcedures,
    proceduresByCategory,
    pendingApplications,
    applicationsByStatus,
    type AdministrativeProcedure,
    type ProcedureApplication,
    type CreateProcedureRequest,
    type CreateApplicationRequest,
    type ProcedureCategory,
    type ApplicationStatus
  } from '../index';
  
  // Props
  export let mode: 'procedures' | 'applications' = 'procedures';
  
  // Local state
  let activeTab: 'procedures' | 'applications' = mode;
  let searchQuery = '';
  let selectedCategory: ProcedureCategory | 'all' = 'all';
  let selectedStatus: ApplicationStatus | 'all' = 'all';
  let showCreateModal = false;
  let showApplyModal = false;
  let selectedProcedure: AdministrativeProcedure | null = null;
  let selectedApplication: ProcedureApplication | null = null;
  
  // Form data
  let procedureForm: Partial<CreateProcedureRequest> = {
    category: 'household',
    level: 'ward',
    requiredDocuments: [],
    legalBasis: [],
    processingTimeDays: 3,
    fee: 0,
  };
  
  let applicationForm: Partial<CreateApplicationRequest> = {
    submittedDocuments: [],
  };
  
  let docInput = '';
  let legalInput = '';
  let appDocInput = '';
  
  // Categories
  const categories: { value: ProcedureCategory; label: string }[] = [
    { value: 'household', label: 'Hộ khẩu' },
    { value: 'civil_status', label: 'Hộ tịch' },
    { value: 'land', label: 'Đất đai' },
    { value: 'construction', label: 'Xây dựng' },
    { value: 'business', label: 'Kinh doanh' },
    { value: 'social', label: 'Xã hội' },
    { value: 'other', label: 'Khác' },
  ];
  
  const levels = [
    { value: 'ward', label: 'Phường/Xã' },
    { value: 'district', label: 'Quận/Huyện' },
    { value: 'province', label: 'Tỉnh/Thành phố' },
    { value: 'central', label: 'Trung ương' },
  ];
  
  const applicationStatuses: { value: ApplicationStatus; label: string; color: string }[] = [
    { value: 'draft', label: 'Bản nháp', color: '#6b7280' },
    { value: 'submitted', label: 'Đã nộp', color: '#3b82f6' },
    { value: 'received', label: 'Đã tiếp nhận', color: '#8b5cf6' },
    { value: 'processing', label: 'Đang xử lý', color: '#f59e0b' },
    { value: 'awaiting_additional_docs', label: 'Chờ bổ sung', color: '#f97316' },
    { value: 'approved', label: 'Đã duyệt', color: '#10b981' },
    { value: 'rejected', label: 'Từ chối', color: '#ef4444' },
    { value: 'completed', label: 'Hoàn thành', color: '#059669' },
  ];
  
  // Load data on mount
  onMount(async () => {
    await Promise.all([
      procedureStore.loadProcedures(true),
      procedureStore.loadPendingApplications(),
    ]);
  });
  
  // Filter procedures
  $: filteredProcedures = selectedCategory === 'all' 
    ? $activeProcedures 
    : $activeProcedures.filter(p => p.category === selectedCategory);
  
  // Filter applications
  $: filteredApplications = selectedStatus === 'all'
    ? $procedureStore.applications
    : $procedureStore.applications.filter(a => a.status === selectedStatus);
  
  // Create procedure
  async function handleCreateProcedure() {
    if (!procedureForm.code || !procedureForm.name || !procedureForm.description) return;
    
    try {
      await procedureStore.createProcedure(procedureForm as CreateProcedureRequest);
      showCreateModal = false;
      resetProcedureForm();
    } catch (err) {
      // Error handled by store
    }
  }
  
  // Create application
  async function handleCreateApplication() {
    if (!selectedProcedure || !applicationForm.citizenId) return;
    
    try {
      await procedureStore.createApplication({
        procedureId: selectedProcedure.id,
        citizenId: applicationForm.citizenId,
        submittedDocuments: applicationForm.submittedDocuments || [],
        notes: applicationForm.notes,
      });
      showApplyModal = false;
      resetApplicationForm();
    } catch (err) {
      // Error handled by store
    }
  }
  
  // Update application status
  async function handleUpdateStatus(status: ApplicationStatus) {
    if (!selectedApplication) return;
    
    try {
      await procedureStore.updateApplicationStatus(selectedApplication.id, status);
      selectedApplication = null;
    } catch (err) {
      // Error handled by store
    }
  }
  
  function resetProcedureForm() {
    procedureForm = {
      category: 'household',
      level: 'ward',
      requiredDocuments: [],
      legalBasis: [],
      processingTimeDays: 3,
      fee: 0,
    };
    docInput = '';
    legalInput = '';
  }
  
  function resetApplicationForm() {
    applicationForm = {
      submittedDocuments: [],
    };
    appDocInput = '';
  }
  
  function addRequiredDoc() {
    if (docInput.trim() && !procedureForm.requiredDocuments?.includes(docInput.trim())) {
      procedureForm.requiredDocuments = [...(procedureForm.requiredDocuments || []), docInput.trim()];
      docInput = '';
    }
  }
  
  function removeRequiredDoc(doc: string) {
    procedureForm.requiredDocuments = procedureForm.requiredDocuments?.filter(d => d !== doc) || [];
  }
  
  function addLegalBasis() {
    if (legalInput.trim() && !procedureForm.legalBasis?.includes(legalInput.trim())) {
      procedureForm.legalBasis = [...(procedureForm.legalBasis || []), legalInput.trim()];
      legalInput = '';
    }
  }
  
  function removeLegalBasis(basis: string) {
    procedureForm.legalBasis = procedureForm.legalBasis?.filter(b => b !== basis) || [];
  }
  
  function addAppDoc() {
    if (appDocInput.trim() && !applicationForm.submittedDocuments?.includes(appDocInput.trim())) {
      applicationForm.submittedDocuments = [...(applicationForm.submittedDocuments || []), appDocInput.trim()];
      appDocInput = '';
    }
  }
  
  function removeAppDoc(doc: string) {
    applicationForm.submittedDocuments = applicationForm.submittedDocuments?.filter(d => d !== doc) || [];
  }
  
  function openApplyModal(procedure: AdministrativeProcedure) {
    selectedProcedure = procedure;
    applicationForm = {
      procedureId: procedure.id,
      submittedDocuments: [...procedure.requiredDocuments],
    };
    showApplyModal = true;
  }
  
  function openStatusModal(application: ProcedureApplication) {
    selectedApplication = application;
  }
  
  function getCategoryLabel(category: ProcedureCategory) {
    return categories.find(c => c.value === category)?.label || category;
  }
  
  function getLevelLabel(level: string) {
    return levels.find(l => l.value === level)?.label || level;
  }
  
  function getStatusLabel(status: ApplicationStatus) {
    return applicationStatuses.find(s => s.value === status)?.label || status;
  }
  
  function getStatusColor(status: ApplicationStatus) {
    return applicationStatuses.find(s => s.value === status)?.color || '#6b7280';
  }
  
  function formatDate(dateStr: string | null) {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('vi-VN');
  }
  
  function formatCurrency(amount: number) {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
      minimumFractionDigits: 0,
    }).format(amount);
  }
</script>

<div class="procedure-management">
  <!-- Header -->
  <header class="pm-header">
    <div class="pm-title">
      <h2>⚙️ Quản lý TTHC</h2>
      <p class="subtitle">Thủ tục hành chính và hồ sơ đăng ký</p>
    </div>
    <button class="btn btn-primary" on:click={() => showCreateModal = true}>
      ➕ Thêm TTHC
    </button>
  </header>
  
  <!-- Tabs -->
  <div class="tabs">
    <button 
      class="tab-btn" 
      class:active={activeTab === 'procedures'}
      on:click={() => activeTab = 'procedures'}
    >
      📋 Danh sách TTHC
    </button>
    <button 
      class="tab-btn" 
      class:active={activeTab === 'applications'}
      on:click={() => activeTab = 'applications'}
    >
      📁 Hồ sơ đăng ký
      {#if $pendingApplications.length > 0}
        <span class="badge">{$pendingApplications.length}</span>
      {/if}
    </button>
  </div>
  
  {#if activeTab === 'procedures'}
    <!-- Procedures Filters -->
    <div class="filters-bar">
      <div class="search-box">
        <input
          type="text"
          placeholder="🔍 Tìm theo tên, mã TTHC..."
          bind:value={searchQuery}
          class="search-input"
        />
      </div>
      
      <div class="filter-group">
        <label>Danh mục:</label>
        <select bind:value={selectedCategory} class="filter-select">
          <option value="all">Tất cả</option>
          {#each categories as cat}
            <option value={cat.value}>{cat.label}</option>
          {/each}
        </select>
      </div>
    </div>
    
    <!-- Error Alert -->
    {#if $procedureError}
      <div class="error-alert">
        <span>⚠️ {$procedureError}</span>
        <button class="btn-close" on:click={() => procedureStore.clearError()}>×</button>
      </div>
    {/if}
    
    <!-- Loading State -->
    {#if $isProcedureLoading && filteredProcedures.length === 0}
      <div class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải danh sách TTHC...</p>
      </div>
    
    <!-- Empty State -->
    {:else if filteredProcedures.length === 0}
      <div class="empty-state">
        <div class="empty-icon">⚙️</div>
        <h3>Chưa có TTHC nào</h3>
        <p>Thêm thủ tục hành chính mới</p>
      </div>
    
    <!-- Procedures List -->
    {:else}
      <div class="procedures-list">
        {#each filteredProcedures as procedure (procedure.id)}
          <div class="procedure-card">
            <div class="card-main">
              <div class="proc-header">
                <span class="proc-code">{procedure.code}</span>
                <span class="proc-category">{getCategoryLabel(procedure.category)}</span>
              </div>
              
              <h3 class="proc-name">{procedure.name}</h3>
              
              <p class="proc-description">{procedure.description}</p>
              
              <div class="proc-meta">
                <span class="meta-item">
                  <span class="meta-icon">⏱️</span>
                  {procedure.processingTimeDays} ngày
                </span>
                <span class="meta-item">
                  <span class="meta-icon">💰</span>
                  {procedure.fee > 0 ? formatCurrency(procedure.fee) : 'Miễn phí'}
                </span>
                <span class="meta-item">
                  <span class="meta-icon">🏛️</span>
                  {getLevelLabel(procedure.level)}
                </span>
              </div>
              
              {#if procedure.requiredDocuments.length > 0}
                <div class="proc-docs">
                  <span class="docs-label">Giấy tờ cần thiết:</span>
                  <div class="docs-list">
                    {#each procedure.requiredDocuments.slice(0, 3) as doc}
                      <span class="doc-tag">{doc}</span>
                    {/each}
                    {#if procedure.requiredDocuments.length > 3}
                      <span class="doc-tag more">+{procedure.requiredDocuments.length - 3}</span>
                    {/if}
                  </div>
                </div>
              {/if}
            </div>
            
            <div class="card-actions">
              <button class="btn btn-primary" on:click={() => openApplyModal(procedure)}>
                📝 Nộp hồ sơ
              </button>
              <button class="btn btn-secondary" on:click={() => procedureStore.getProcedure(procedure.id)}>
                👁️ Chi tiết
              </button>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  
  {:else}
    <!-- Applications Filters -->
    <div class="filters-bar">
      <div class="search-box">
        <input
          type="text"
          placeholder="🔍 Tìm theo mã hồ sơ, tên..."
          bind:value={searchQuery}
          class="search-input"
        />
      </div>
      
      <div class="filter-group">
        <label>Trạng thái:</label>
        <select bind:value={selectedStatus} class="filter-select">
          <option value="all">Tất cả</option>
          {#each applicationStatuses as status}
            <option value={status.value}>{status.label}</option>
          {/each}
        </select>
      </div>
    </div>
    
    <!-- Applications List -->
    {#if $isProcedureLoading && filteredApplications.length === 0}
      <div class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải danh sách hồ sơ...</p>
      </div>
    {:else if filteredApplications.length === 0}
      <div class="empty-state">
        <div class="empty-icon">📁</div>
        <h3>Chưa có hồ sơ nào</h3>
        <p>Chọn TTHC để nộp hồ sơ mới</p>
      </div>
    {:else}
      <div class="applications-list">
        {#each filteredApplications as app (app.id)}
          <div class="application-item">
            <div class="app-status-bar" style="background: {getStatusColor(app.status)}"></div>
            
            <div class="app-content">
              <div class="app-header">
                <div class="app-info">
                  <h4 class="app-proc-name">{app.procedureName}</h4>
                  <span class="app-code">Mã: {app.id.slice(0, 8)}</span>
                </div>
                <span 
                  class="status-badge" 
                  style="background: {getStatusColor(app.status)}20; color: {getStatusColor(app.status)}"
                >
                  {getStatusLabel(app.status)}
                </span>
              </div>
              
              <div class="app-details">
                <div class="detail-item">
                  <span class="detail-label">Người nộp:</span>
                  <span class="detail-value">{app.citizenName}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Ngày nộp:</span>
                  <span class="detail-value">{formatDate(app.submittedAt)}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Dự kiến hoàn thành:</span>
                  <span class="detail-value">{formatDate(app.expectedCompletionDate)}</span>
                </div>
              </div>
              
              {#if app.processedBy}
                <div class="app-processed">
                  <span>👤 Xử lý bởi: {app.processedBy}</span>
                  {#if app.processedAt}
                    <span>• {formatDate(app.processedAt)}</span>
                  {/if}
                </div>
              {/if}
            </div>
            
            <div class="app-actions">
              <button class="btn btn-sm" on:click={() => openStatusModal(app)}>
                🔄 Cập nhật
              </button>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  {/if}
</div>

<!-- Create Procedure Modal -->
{#if showCreateModal}
  <div class="modal-overlay" on:click|self={() => showCreateModal = false}>
    <div class="modal">
      <div class="modal-header">
        <h3>➕ Thêm TTHC mới</h3>
        <button class="btn-close" on:click={() => showCreateModal = false}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Mã TTHC *</label>
            <input type="text" bind:value={procedureForm.code} placeholder="VD: TTHC-001" required />
          </div>
          
          <div class="form-group">
            <label>Tên thủ tục *</label>
            <input type="text" bind:value={procedureForm.name} required />
          </div>
          
          <div class="form-group">
            <label>Danh mục *</label>
            <select bind:value={procedureForm.category} required>
              {#each categories as cat}
                <option value={cat.value}>{cat.label}</option>
              {/each}
            </select>
          </div>
          
          <div class="form-group">
            <label>Cấp thực hiện *</label>
            <select bind:value={procedureForm.level} required>
              {#each levels as level}
                <option value={level.value}>{level.label}</option>
              {/each}
            </select>
          </div>
          
          <div class="form-group">
            <label>Thời gian xử lý (ngày) *</label>
            <input type="number" bind:value={procedureForm.processingTimeDays} min="1" required />
          </div>
          
          <div class="form-group">
            <label>Lệ phí (VNĐ)</label>
            <input type="number" bind:value={procedureForm.fee} min="0" />
          </div>
          
          <div class="form-group full-width">
            <label>Mô tả *</label>
            <textarea bind:value={procedureForm.description} rows="3" required></textarea>
          </div>
          
          <div class="form-group full-width">
            <label>Giấy tờ cần thiết</label>
            <div class="tag-input-wrapper">
              <input 
                type="text" 
                bind:value={docInput}
                on:keydown={(e) => e.key === 'Enter' && (e.preventDefault(), addRequiredDoc())}
                placeholder="Nhập giấy tờ và nhấn Enter"
              />
              <button type="button" class="btn btn-sm" on:click={addRequiredDoc}>Thêm</button>
            </div>
            <div class="tags-list">
              {#each procedureForm.requiredDocuments || [] as doc}
                <span class="tag removable">
                  {doc}
                  <button type="button" on:click={() => removeRequiredDoc(doc)}>×</button>
                </span>
              {/each}
            </div>
          </div>
          
          <div class="form-group full-width">
            <label>Cơ sở pháp lý</label>
            <div class="tag-input-wrapper">
              <input 
                type="text" 
                bind:value={legalInput}
                on:keydown={(e) => e.key === 'Enter' && (e.preventDefault(), addLegalBasis())}
                placeholder="Nhập văn bản pháp lý"
              />
              <button type="button" class="btn btn-sm" on:click={addLegalBasis}>Thêm</button>
            </div>
            <div class="tags-list">
              {#each procedureForm.legalBasis || [] as basis}
                <span class="tag removable">
                  {basis}
                  <button type="button" on:click={() => removeLegalBasis(basis)}>×</button>
                </span>
              {/each}
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn btn-secondary" on:click={() => showCreateModal = false}>
          Hủy
        </button>
        <button class="btn btn-primary" on:click={handleCreateProcedure} disabled={$isProcedureSaving}>
          {$isProcedureSaving ? '⏳ Đang lưu...' : '💾 Lưu TTHC'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Apply Modal -->
{#if showApplyModal && selectedProcedure}
  <div class="modal-overlay" on:click|self={() => showApplyModal = false}>
    <div class="modal">
      <div class="modal-header">
        <h3>📝 Nộp hồ sơ: {selectedProcedure.name}</h3>
        <button class="btn-close" on:click={() => showApplyModal = false}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="apply-info">
          <p><strong>Mã TTHC:</strong> {selectedProcedure.code}</p>
          <p><strong>Thời gian xử lý:</strong> {selectedProcedure.processingTimeDays} ngày</p>
          <p><strong>Lệ phí:</strong> {selectedProcedure.fee > 0 ? formatCurrency(selectedProcedure.fee) : 'Miễn phí'}</p>
        </div>
        
        <div class="form-grid">
          <div class="form-group full-width">
            <label>Mã công dân *</label>
            <input type="text" bind:value={applicationForm.citizenId} placeholder="Nhập ID công dân" required />
          </div>
          
          <div class="form-group full-width">
            <label>Giấy tờ đã nộp</label>
            <div class="tag-input-wrapper">
              <input 
                type="text" 
                bind:value={appDocInput}
                on:keydown={(e) => e.key === 'Enter' && (e.preventDefault(), addAppDoc())}
                placeholder="Nhập tên giấy tờ"
              />
              <button type="button" class="btn btn-sm" on:click={addAppDoc}>Thêm</button>
            </div>
            <div class="tags-list">
              {#each applicationForm.submittedDocuments || [] as doc}
                <span class="tag removable">
                  {doc}
                  <button type="button" on:click={() => removeAppDoc(doc)}>×</button>
                </span>
              {/each}
            </div>
          </div>
          
          <div class="form-group full-width">
            <label>Ghi chú</label>
            <textarea bind:value={applicationForm.notes} rows="3" placeholder="Ghi chú thêm nếu cần"></textarea>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn btn-secondary" on:click={() => showApplyModal = false}>
          Hủy
        </button>
        <button class="btn btn-primary" on:click={handleCreateApplication} disabled={$isProcedureSaving}>
          {$isProcedureSaving ? '⏳ Đang lưu...' : '📤 Nộp hồ sơ'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Status Update Modal -->
{#if selectedApplication}
  <div class="modal-overlay" on:click|self={() => selectedApplication = null}>
    <div class="modal">
      <div class="modal-header">
        <h3>🔄 Cập nhật trạng thái hồ sơ</h3>
        <button class="btn-close" on:click={() => selectedApplication = null}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="current-status">
          <p>Trạng thái hiện tại: 
            <span 
              class="status-badge" 
              style="background: {getStatusColor(selectedApplication.status)}20; color: {getStatusColor(selectedApplication.status)}"
            >
              {getStatusLabel(selectedApplication.status)}
            </span>
          </p>
        </div>
        
        <div class="status-options">
          <p>Chuyển sang:</p>
          <div class="status-grid">
            {#each applicationStatuses.filter(s => s.value !== selectedApplication?.status) as status}
              <button 
                class="status-option" 
                style="border-color: {status.color}; color: {status.color}"
                on:click={() => handleUpdateStatus(status.value)}
                disabled={$isProcedureSaving}
              >
                {status.label}
              </button>
            {/each}
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn btn-secondary" on:click={() => selectedApplication = null}>
          Đóng
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .procedure-management {
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
  }
  
  .pm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }
  
  .pm-title h2 {
    margin: 0 0 4px;
    font-size: 1.5rem;
    color: #1e293b;
  }
  
  .subtitle {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
  }
  
  /* Tabs */
  .tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    border-bottom: 2px solid #e2e8f0;
  }
  
  .tab-btn {
    padding: 12px 24px;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .tab-btn.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
  }
  
  .badge {
    padding: 2px 8px;
    background: #ef4444;
    color: white;
    border-radius: 12px;
    font-size: 0.75rem;
  }
  
  /* Filters */
  .filters-bar {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-bottom: 24px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
  }
  
  .search-box {
    flex: 1;
  }
  
  .search-input {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
  }
  
  .search-input:focus {
    outline: none;
    border-color: #3b82f6;
  }
  
  .filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .filter-select {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    min-width: 140px;
  }
  
  /* Error, Loading, Empty */
  .error-alert {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #fee2e2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    color: #991b1b;
    margin-bottom: 16px;
  }
  
  .loading-state, .empty-state {
    text-align: center;
    padding: 60px 20px;
  }
  
  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
  }
  
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
  
  .empty-icon {
    font-size: 4rem;
    margin-bottom: 16px;
    opacity: 0.5;
  }
  
  /* Procedures List */
  .procedures-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  
  .procedure-card {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid #3b82f6;
  }
  
  .card-main {
    flex: 1;
  }
  
  .proc-header {
    display: flex;
    gap: 12px;
    margin-bottom: 8px;
  }
  
  .proc-code {
    padding: 2px 8px;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
  }
  
  .proc-category {
    padding: 2px 8px;
    background: #f3e8ff;
    color: #7c3aed;
    border-radius: 4px;
    font-size: 0.75rem;
  }
  
  .proc-name {
    margin: 0 0 8px;
    font-size: 1.125rem;
    color: #1e293b;
  }
  
  .proc-description {
    margin: 0 0 12px;
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.5;
  }
  
  .proc-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
  }
  
  .meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.875rem;
    color: #64748b;
  }
  
  .meta-icon {
    font-size: 1rem;
  }
  
  .proc-docs {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  
  .docs-label {
    font-size: 0.8125rem;
    color: #64748b;
  }
  
  .docs-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  
  .doc-tag {
    padding: 2px 8px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 4px;
    font-size: 0.75rem;
  }
  
  .doc-tag.more {
    background: #dbeafe;
    color: #1d4ed8;
  }
  
  .card-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  
  /* Applications List */
  .applications-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .application-item {
    display: flex;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  
  .app-status-bar {
    width: 4px;
  }
  
  .app-content {
    flex: 1;
    padding: 16px;
  }
  
  .app-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
  }
  
  .app-info {
    flex: 1;
  }
  
  .app-proc-name {
    margin: 0 0 4px;
    font-size: 1rem;
    color: #1e293b;
  }
  
  .app-code {
    font-size: 0.8125rem;
    color: #64748b;
    font-family: monospace;
  }
  
  .status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
  }
  
  .app-details {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 12px;
  }
  
  .detail-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }
  
  .detail-label {
    font-size: 0.75rem;
    color: #64748b;
  }
  
  .detail-value {
    font-size: 0.875rem;
    color: #1e293b;
    font-weight: 500;
  }
  
  .app-processed {
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 6px;
    font-size: 0.8125rem;
    color: #64748b;
  }
  
  .app-actions {
    padding: 16px;
    display: flex;
    align-items: center;
  }
  
  /* Buttons */
  .btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  
  .btn-primary {
    background: #3b82f6;
    color: white;
  }
  
  .btn-primary:hover:not(:disabled) {
    background: #2563eb;
  }
  
  .btn-secondary {
    background: #f1f5f9;
    color: #374151;
  }
  
  .btn-secondary:hover:not(:disabled) {
    background: #e2e8f0;
  }
  
  .btn-sm {
    padding: 6px 12px;
    font-size: 0.8125rem;
  }
  
  /* Modal */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
  }
  
  .modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .modal-header h3 {
    margin: 0;
    font-size: 1.125rem;
  }
  
  .btn-close {
    padding: 8px;
    border: none;
    background: transparent;
    font-size: 1.5rem;
    cursor: pointer;
    border-radius: 8px;
    color: #64748b;
  }
  
  .modal-body {
    padding: 24px;
    overflow-y: auto;
  }
  
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
  }
  
  /* Form */
  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }
  
  .form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  
  .form-group.full-width {
    grid-column: span 2;
  }
  
  .form-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
  }
  
  .form-group input,
  .form-group select,
  .form-group textarea {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    font-family: inherit;
  }
  
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
  }
  
  .tag-input-wrapper {
    display: flex;
    gap: 8px;
  }
  
  .tag-input-wrapper input {
    flex: 1;
  }
  
  .tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
  }
  
  .tag.removable {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: #e0e7ff;
    color: #4338ca;
    border-radius: 4px;
    font-size: 0.875rem;
  }
  
  .tag.removable button {
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1rem;
    color: #4338ca;
    padding: 0;
    line-height: 1;
  }
  
  /* Apply Info */
  .apply-info {
    padding: 16px;
    background: #f0fdf4;
    border-radius: 8px;
    margin-bottom: 20px;
  }
  
  .apply-info p {
    margin: 4px 0;
  }
  
  /* Status Update */
  .current-status {
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 20px;
  }
  
  .status-options p {
    margin-bottom: 12px;
    font-weight: 500;
  }
  
  .status-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  
  .status-option {
    padding: 12px;
    border: 2px solid;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 500;
  }
  
  .status-option:hover:not(:disabled) {
    background: #f8fafc;
  }
</style>
