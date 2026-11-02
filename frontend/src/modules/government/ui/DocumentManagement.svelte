<script lang="ts">
  import { onMount } from 'svelte';
  import { 
    documentStore, 
    isDocumentLoading,
    isDocumentSaving,
    documentError,
    publishedDocuments,
    documentsByType,
    recentDocuments,
    type GovernmentDocument,
    type CreateDocumentRequest,
    type DocumentType,
    type DocumentStatus
  } from '../index';
  
  // Props
  export let mode: 'grid' | 'list' = 'list';
  
  // Local state
  let searchQuery = '';
  let selectedType: DocumentType | 'all' = 'all';
  let selectedStatus: DocumentStatus | 'all' = 'all';
  let showCreateModal = false;
  let viewingDocument: GovernmentDocument | null = null;
  let debounceTimer: ReturnType<typeof setTimeout>;
  
  // Form data
  let formData: Partial<CreateDocumentRequest> = {
    type: 'decision',
    tags: [],
  };
  let tagInput = '';
  
  // Document types
  const documentTypes: { value: DocumentType; label: string; icon: string }[] = [
    { value: 'decision', label: 'Quyết định', icon: '📋' },
    { value: 'notice', label: 'Thông báo', icon: '📢' },
    { value: 'instruction', label: 'Hướng dẫn', icon: '📖' },
    { value: 'report', label: 'Báo cáo', icon: '📊' },
    { value: 'letter', label: 'Công văn', icon: '✉️' },
    { value: 'circular', label: 'Thông tư', icon: '📄' },
    { value: 'other', label: 'Khác', icon: '📎' },
  ];
  
  const documentStatuses: { value: DocumentStatus; label: string; color: string }[] = [
    { value: 'draft', label: 'Bản nháp', color: '#6b7280' },
    { value: 'pending_approval', label: 'Chờ duyệt', color: '#f59e0b' },
    { value: 'approved', label: 'Đã duyệt', color: '#3b82f6' },
    { value: 'published', label: 'Đã ban hành', color: '#10b981' },
    { value: 'archived', label: 'Lưu trữ', color: '#8b5cf6' },
  ];
  
  // Load data on mount
  onMount(async () => {
    await documentStore.loadDocuments(true);
    await documentStore.loadStatistics();
  });
  
  // Search with debounce
  function handleSearch() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      if (searchQuery.trim()) {
        documentStore.searchDocuments(searchQuery);
      } else {
        documentStore.loadDocuments(true);
      }
    }, 300);
  }
  
  // Filter documents
  $: filteredDocuments = $documentStore.documents.filter(doc => {
    const matchType = selectedType === 'all' || doc.type === selectedType;
    const matchStatus = selectedStatus === 'all' || doc.status === selectedStatus;
    return matchType && matchStatus;
  });
  
  // Create document
  async function handleCreate() {
    if (!formData.title || !formData.content) return;
    
    try {
      await documentStore.createDocument(formData as CreateDocumentRequest);
      showCreateModal = false;
      resetForm();
    } catch (err) {
      // Error handled by store
    }
  }
  
  // Publish document
  async function handlePublish(doc: GovernmentDocument) {
    try {
      await documentStore.publishDocument(doc.id);
    } catch (err) {
      // Error handled by store
    }
  }
  
  // Archive document
  async function handleArchive(doc: GovernmentDocument) {
    try {
      await documentStore.archiveDocument(doc.id);
    } catch (err) {
      // Error handled by store
    }
  }
  
  function resetForm() {
    formData = {
      type: 'decision',
      tags: [],
    };
    tagInput = '';
  }
  
  function addTag() {
    if (tagInput.trim() && !formData.tags?.includes(tagInput.trim())) {
      formData.tags = [...(formData.tags || []), tagInput.trim()];
      tagInput = '';
    }
  }
  
  function removeTag(tag: string) {
    formData.tags = formData.tags?.filter(t => t !== tag) || [];
  }
  
  function openViewModal(doc: GovernmentDocument) {
    viewingDocument = doc;
  }
  
  function closeViewModal() {
    viewingDocument = null;
  }
  
  function getStatusLabel(status: DocumentStatus) {
    return documentStatuses.find(s => s.value === status)?.label || status;
  }
  
  function getStatusColor(status: DocumentStatus) {
    return documentStatuses.find(s => s.value === status)?.color || '#6b7280';
  }
  
  function getTypeLabel(type: DocumentType) {
    return documentTypes.find(t => t.value === type)?.label || type;
  }
  
  function getTypeIcon(type: DocumentType) {
    return documentTypes.find(t => t.value === type)?.icon || '📄';
  }
  
  function formatDate(dateStr: string | null) {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('vi-VN');
  }
</script>

<div class="document-management">
  <!-- Header -->
  <header class="dm-header">
    <div class="dm-title">
      <h2>📄 Quản lý văn bản</h2>
      <p class="subtitle">Hệ thống quản lý văn bản điện tử phường/xã</p>
    </div>
    <button class="btn btn-primary" on:click={() => showCreateModal = true}>
      ➕ Tạo văn bản
    </button>
  </header>
  
  <!-- Filters -->
  <div class="filters-bar">
    <div class="search-box">
      <input
        type="text"
        placeholder="🔍 Tìm theo tiêu đề, số hiệu..."
        bind:value={searchQuery}
        on:input={handleSearch}
        class="search-input"
      />
    </div>
    
    <div class="filter-group">
      <label>Loại văn bản:</label>
      <select bind:value={selectedType} class="filter-select">
        <option value="all">Tất cả</option>
        {#each documentTypes as type}
          <option value={type.value}>{type.icon} {type.label}</option>
        {/each}
      </select>
    </div>
    
    <div class="filter-group">
      <label>Trạng thái:</label>
      <select bind:value={selectedStatus} class="filter-select">
        <option value="all">Tất cả</option>
        {#each documentStatuses as status}
          <option value={status.value}>{status.label}</option>
        {/each}
      </select>
    </div>
  </div>
  
  <!-- Stats Summary -->
  {#if $documentStore.statistics}
    <div class="stats-summary">
      <div class="stat-item">
        <span class="stat-number">{$documentStore.statistics.total}</span>
        <span class="stat-label">Tổng văn bản</span>
      </div>
      <div class="stat-item published">
        <span class="stat-number">{$documentStore.statistics.byStatus.published || 0}</span>
        <span class="stat-label">Đã ban hành</span>
      </div>
      <div class="stat-item draft">
        <span class="stat-number">{$documentStore.statistics.byStatus.draft || 0}</span>
        <span class="stat-label">Bản nháp</span>
      </div>
    </div>
  {/if}
  
  <!-- Error Alert -->
  {#if $documentError}
    <div class="error-alert">
      <span>⚠️ {$documentError}</span>
      <button class="btn-close" on:click={() => documentStore.clearError()}>×</button>
    </div>
  {/if}
  
  <!-- Loading State -->
  {#if $isDocumentLoading && filteredDocuments.length === 0}
    <div class="loading-state">
      <div class="spinner"></div>
      <p>Đang tải danh sách văn bản...</p>
    </div>
  
  <!-- Empty State -->
  {:else if filteredDocuments.length === 0}
    <div class="empty-state">
      <div class="empty-icon">📄</div>
      <h3>Chưa có văn bản nào</h3>
      <p>Tạo văn bản mới để bắt đầu quản lý</p>
    </div>
  
  <!-- Documents Grid -->
  {:else if mode === 'grid'}
    <div class="documents-grid">
      {#each filteredDocuments as doc (doc.id)}
        <div class="document-card" style="--status-color: {getStatusColor(doc.status)}">
          <div class="card-header">
            <div class="doc-type-icon">{getTypeIcon(doc.type)}</div>
            <span class="status-badge" style="background: {getStatusColor(doc.status)}20; color: {getStatusColor(doc.status)}">
              {getStatusLabel(doc.status)}
            </span>
          </div>
          
          <div class="card-body">
            <h3 class="doc-title" title={doc.title}>
              {doc.title.length > 60 ? doc.title.slice(0, 60) + '...' : doc.title}
            </h3>
            
            {#if doc.documentNumber}
              <p class="doc-number">Số: {doc.documentNumber}</p>
            {/if}
            
            <div class="doc-meta">
              <span>{getTypeLabel(doc.type)}</span>
              <span>•</span>
              <span>{formatDate(doc.issuedDate)}</span>
            </div>
            
            {#if doc.tags && doc.tags.length > 0}
              <div class="doc-tags">
                {#each doc.tags.slice(0, 3) as tag}
                  <span class="tag">{tag}</span>
                {/each}
                {#if doc.tags.length > 3}
                  <span class="tag">+{doc.tags.length - 3}</span>
                {/if}
              </div>
            {/if}
          </div>
          
          <div class="card-actions">
            <button class="btn btn-sm btn-secondary" on:click={() => openViewModal(doc)}>
              👁️ Xem
            </button>
            {#if doc.status !== 'published'}
              <button 
                class="btn btn-sm btn-success" 
                on:click={() => handlePublish(doc)}
                disabled={$isDocumentSaving}
              >
                📢 Ban hành
              </button>
            {/if}
            {#if doc.status !== 'archived'}
              <button 
                class="btn btn-sm" 
                class:btn-archive={doc.status !== 'archived'}
                on:click={() => handleArchive(doc)}
                disabled={$isDocumentSaving}
              >
                📦 Lưu trữ
              </button>
            {/if}
          </div>
        </div>
      {/each}
    </div>
  
  <!-- Documents List -->
  {:else}
    <div class="documents-list">
      {#each filteredDocuments as doc (doc.id)}
        <div class="document-item" style="--status-color: {getStatusColor(doc.status)}">
          <div class="doc-icon">{getTypeIcon(doc.type)}</div>
          
          <div class="doc-content">
            <div class="doc-main">
              <h3 class="doc-title">{doc.title}</h3>
              {#if doc.documentNumber}
                <span class="doc-number">Số: {doc.documentNumber}</span>
              {/if}
            </div>
            
            <div class="doc-details">
              <span class="doc-type">{getTypeLabel(doc.type)}</span>
              <span class="separator">|</span>
              <span class="doc-date">{formatDate(doc.issuedDate)}</span>
              <span class="separator">|</span>
              <span class="doc-issuer">{doc.issuedBy}</span>
            </div>
            
            {#if doc.tags && doc.tags.length > 0}
              <div class="doc-tags">
                {#each doc.tags as tag}
                  <span class="tag">{tag}</span>
                {/each}
              </div>
            {/if}
          </div>
          
          <div class="doc-status-wrapper">
            <span class="status-badge" style="background: {getStatusColor(doc.status)}20; color: {getStatusColor(doc.status)}">
              {getStatusLabel(doc.status)}
            </span>
          </div>
          
          <div class="doc-actions">
            <button class="btn-icon" on:click={() => openViewModal(doc)} title="Xem chi tiết">
              👁️
            </button>
            {#if doc.status !== 'published'}
              <button 
                class="btn-icon" 
                on:click={() => handlePublish(doc)}
                title="Ban hành"
                disabled={$isDocumentSaving}
              >
                📢
              </button>
            {/if}
            {#if doc.status !== 'archived'}
              <button 
                class="btn-icon" 
                on:click={() => handleArchive(doc)}
                title="Lưu trữ"
                disabled={$isDocumentSaving}
              >
                📦
              </button>
            {/if}
          </div>
        </div>
      {/each}
    </div>
  {/if}
  
  <!-- Load More -->
  {#if filteredDocuments.length > 0}
    <div class="load-more">
      <button 
        class="btn btn-outline" 
        on:click={() => documentStore.loadDocuments()}
        disabled={$isDocumentLoading}
      >
        {$isDocumentLoading ? '⏳ Đang tải...' : 'Tải thêm'}
      </button>
    </div>
  {/if}
</div>

<!-- Create Modal -->
{#if showCreateModal}
  <div class="modal-overlay" on:click|self={() => showCreateModal = false}>
    <div class="modal">
      <div class="modal-header">
        <h3>➕ Tạo văn bản mới</h3>
        <button class="btn-close" on:click={() => showCreateModal = false}>×</button>
      </div>
      
      <form on:submit|preventDefault={handleCreate} class="modal-body">
        <div class="form-grid">
          <div class="form-group full-width">
            <label>Tiêu đề *</label>
            <input type="text" bind:value={formData.title} required />
          </div>
          
          <div class="form-group">
            <label>Loại văn bản *</label>
            <select bind:value={formData.type} required>
              {#each documentTypes as type}
                <option value={type.value}>{type.icon} {type.label}</option>
              {/each}
            </select>
          </div>
          
          <div class="form-group">
            <label>Số văn bản</label>
            <input type="text" bind:value={formData.documentNumber} />
          </div>
          
          <div class="form-group">
            <label>Ngày ban hành</label>
            <input type="date" bind:value={formData.issuedDate} />
          </div>
          
          <div class="form-group">
            <label>Ngày có hiệu lực</label>
            <input type="date" bind:value={formData.effectiveDate} />
          </div>
          
          <div class="form-group">
            <label>Cơ quan ban hành *</label>
            <input type="text" bind:value={formData.issuedBy} required />
          </div>
          
          <div class="form-group">
            <label>Người ký</label>
            <input type="text" bind:value={formData.signedBy} />
          </div>
          
          <div class="form-group full-width">
            <label>Nội dung *</label>
            <textarea bind:value={formData.content} rows="6" required></textarea>
          </div>
          
          <div class="form-group full-width">
            <label>Tóm tắt</label>
            <textarea bind:value={formData.summary} rows="3"></textarea>
          </div>
          
          <div class="form-group full-width">
            <label>Tags</label>
            <div class="tag-input-wrapper">
              <input 
                type="text" 
                bind:value={tagInput}
                on:keydown={(e) => e.key === 'Enter' && (e.preventDefault(), addTag())}
                placeholder="Nhập tag và nhấn Enter"
              />
              <button type="button" class="btn btn-sm" on:click={addTag}>Thêm</button>
            </div>
            <div class="tags-list">
              {#each formData.tags || [] as tag}
                <span class="tag removable">
                  {tag}
                  <button type="button" on:click={() => removeTag(tag)}>×</button>
                </span>
              {/each}
            </div>
          </div>
        </div>
      </form>
      
      <div class="modal-footer">
        <button class="btn btn-secondary" on:click={() => showCreateModal = false}>
          Hủy
        </button>
        <button class="btn btn-primary" on:click={handleCreate} disabled={$isDocumentSaving}>
          {$isDocumentSaving ? '⏳ Đang lưu...' : '💾 Tạo văn bản'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- View Modal -->
{#if viewingDocument}
  <div class="modal-overlay" on:click|self={closeViewModal}>
    <div class="modal view-modal">
      <div class="modal-header">
        <div class="doc-header-info">
          <span class="doc-type-badge">
            {getTypeIcon(viewingDocument.type)} {getTypeLabel(viewingDocument.type)}
          </span>
          <span 
            class="status-badge" 
            style="background: {getStatusColor(viewingDocument.status)}20; color: {getStatusColor(viewingDocument.status)}"
          >
            {getStatusLabel(viewingDocument.status)}
          </span>
        </div>
        <button class="btn-close" on:click={closeViewModal}>×</button>
      </div>
      
      <div class="modal-body">
        <h2 class="view-title">{viewingDocument.title}</h2>
        
        {#if viewingDocument.documentNumber}
          <p class="view-number">Số văn bản: {viewingDocument.documentNumber}</p>
        {/if}
        
        <div class="view-meta">
          <div class="meta-item">
            <span class="meta-label">Cơ quan ban hành:</span>
            <span class="meta-value">{viewingDocument.issuedBy}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Ngày ban hành:</span>
            <span class="meta-value">{formatDate(viewingDocument.issuedDate)}</span>
          </div>
          {#if viewingDocument.effectiveDate}
            <div class="meta-item">
              <span class="meta-label">Ngày có hiệu lực:</span>
              <span class="meta-value">{formatDate(viewingDocument.effectiveDate)}</span>
            </div>
          {/if}
          {#if viewingDocument.signedBy}
            <div class="meta-item">
              <span class="meta-label">Người ký:</span>
              <span class="meta-value">{viewingDocument.signedBy}</span>
            </div>
          {/if}
        </div>
        
        {#if viewingDocument.summary}
          <div class="view-section">
            <h4>Tóm tắt</h4>
            <p class="view-summary">{viewingDocument.summary}</p>
          </div>
        {/if}
        
        <div class="view-section">
          <h4>Nội dung</h4>
          <div class="view-content">{viewingDocument.content}</div>
        </div>
        
        {#if viewingDocument.tags && viewingDocument.tags.length > 0}
          <div class="view-section">
            <h4>Tags</h4>
            <div class="view-tags">
              {#each viewingDocument.tags as tag}
                <span class="tag">{tag}</span>
              {/each}
            </div>
          </div>
        {/if}
      </div>
      
      <div class="modal-footer">
        {#if viewingDocument.status !== 'published'}
          <button 
            class="btn btn-success" 
            on:click={() => handlePublish(viewingDocument!)}
            disabled={$isDocumentSaving}
          >
            📢 Ban hành
          </button>
        {/if}
        {#if viewingDocument.status !== 'archived'}
          <button 
            class="btn btn-archive" 
            on:click={() => handleArchive(viewingDocument!)}
            disabled={$isDocumentSaving}
          >
            📦 Lưu trữ
          </button>
        {/if}
        <button class="btn btn-secondary" on:click={closeViewModal}>
          Đóng
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .document-management {
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
  }
  
  .dm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }
  
  .dm-title h2 {
    margin: 0 0 4px;
    font-size: 1.5rem;
    color: #1e293b;
  }
  
  .subtitle {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
  }
  
  /* Filters */
  .filters-bar {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-bottom: 16px;
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
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
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
  
  /* Stats */
  .stats-summary {
    display: flex;
    gap: 24px;
    margin-bottom: 24px;
    padding: 16px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  
  .stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 24px;
    border-right: 1px solid #e2e8f0;
  }
  
  .stat-item:last-child {
    border-right: none;
  }
  
  .stat-item.published .stat-number {
    color: #10b981;
  }
  
  .stat-item.draft .stat-number {
    color: #6b7280;
  }
  
  .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }
  
  .stat-label {
    font-size: 0.875rem;
    color: #64748b;
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
  
  /* Grid */
  .documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
  }
  
  .document-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
    border-left: 4px solid var(--status-color);
  }
  
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #f8fafc;
  }
  
  .doc-type-icon {
    font-size: 1.5rem;
  }
  
  .status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
  }
  
  .card-body {
    padding: 16px;
  }
  
  .doc-title {
    margin: 0 0 8px;
    font-size: 1rem;
    color: #1e293b;
    line-height: 1.4;
  }
  
  .doc-number {
    margin: 0 0 12px;
    font-size: 0.875rem;
    color: #64748b;
    font-family: monospace;
  }
  
  .doc-meta {
    display: flex;
    gap: 8px;
    font-size: 0.8125rem;
    color: #64748b;
    margin-bottom: 12px;
  }
  
  .doc-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  
  .tag {
    padding: 2px 8px;
    background: #e0e7ff;
    color: #4338ca;
    border-radius: 4px;
    font-size: 0.75rem;
  }
  
  .card-actions {
    display: flex;
    gap: 8px;
    padding: 12px 16px;
    border-top: 1px solid #f1f5f9;
  }
  
  /* List */
  .documents-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .document-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid var(--status-color);
  }
  
  .doc-icon {
    font-size: 2rem;
    width: 48px;
    text-align: center;
  }
  
  .doc-content {
    flex: 1;
    min-width: 0;
  }
  
  .doc-main {
    display: flex;
    align-items: baseline;
    gap: 12px;
    margin-bottom: 8px;
  }
  
  .doc-main .doc-title {
    margin: 0;
    font-size: 1rem;
    color: #1e293b;
  }
  
  .doc-number {
    font-size: 0.8125rem;
    color: #64748b;
    font-family: monospace;
  }
  
  .doc-details {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8125rem;
    color: #64748b;
    margin-bottom: 8px;
  }
  
  .separator {
    color: #d1d5db;
  }
  
  .doc-status-wrapper {
    width: 120px;
  }
  
  .doc-actions {
    display: flex;
    gap: 4px;
  }
  
  .btn-icon {
    padding: 8px;
    border: none;
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
  }
  
  .btn-icon:hover {
    background: #f1f5f9;
  }
  
  /* Load More */
  .load-more {
    text-align: center;
    margin-top: 24px;
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
    display: inline-flex;
    align-items: center;
    gap: 6px;
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
  
  .btn-success {
    background: #10b981;
    color: white;
  }
  
  .btn-success:hover:not(:disabled) {
    background: #059669;
  }
  
  .btn-archive {
    background: #8b5cf6;
    color: white;
  }
  
  .btn-archive:hover:not(:disabled) {
    background: #7c3aed;
  }
  
  .btn-outline {
    background: white;
    border: 1px solid #d1d5db;
    color: #374151;
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
  
  .view-modal {
    max-width: 800px;
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .doc-header-info {
    display: flex;
    gap: 12px;
  }
  
  .doc-type-badge {
    padding: 6px 12px;
    background: #e0e7ff;
    color: #4338ca;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
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
  
  .btn-close:hover {
    background: #f1f5f9;
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
  }
  
  .tag.removable button {
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1rem;
    color: #4338ca;
  }
  
  /* View Modal */
  .view-title {
    margin: 0 0 16px;
    font-size: 1.5rem;
    color: #1e293b;
    line-height: 1.4;
  }
  
  .view-number {
    margin: 0 0 16px;
    color: #64748b;
    font-family: monospace;
  }
  
  .view-meta {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 24px;
  }
  
  .meta-item {
    display: flex;
    gap: 8px;
  }
  
  .meta-label {
    color: #64748b;
    font-size: 0.875rem;
  }
  
  .meta-value {
    color: #1e293b;
    font-weight: 500;
    font-size: 0.875rem;
  }
  
  .view-section {
    margin-bottom: 24px;
  }
  
  .view-section h4 {
    margin: 0 0 12px;
    color: #374151;
    font-size: 1rem;
  }
  
  .view-summary {
    color: #64748b;
    line-height: 1.6;
  }
  
  .view-content {
    color: #1e293b;
    line-height: 1.8;
    white-space: pre-wrap;
  }
  
  .view-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
</style>
