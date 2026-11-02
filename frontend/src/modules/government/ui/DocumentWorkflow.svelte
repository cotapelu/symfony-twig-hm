<script lang="ts">
  import { onMount } from 'svelte';
  import { slide } from 'svelte/transition';
  import DOMPurify from 'dompurify';

  // Props
  export let documentId: string | null = null;
  
  // State
  let documents: any[] = [];
  let selectedDocument: any = null;
  let loading = false;
  let error: string | null = null;
  let activeTab: 'pending' | 'approved' | 'rejected' | 'all' = 'pending';
  let showApprovalModal = false;
  let approvalComment = '';
  let documentHistory: any[] = [];
  
  // Workflow status config
  const statusConfig: Record<string, { label: string; color: string; icon: string }> = {
    draft: { label: 'Bản nháp', color: '#6b7280', icon: '📝' },
    pending: { label: 'Chờ duyệt', color: '#f59e0b', icon: '⏳' },
    approved: { label: 'Đã duyệt', color: '#10b981', icon: '✅' },
    rejected: { label: 'Từ chối', color: '#ef4444', icon: '❌' },
    published: { label: 'Đã ban hành', color: '#3b82f6', icon: '📢' },
    archived: { label: 'Đã lưu trữ', color: '#6b7280', icon: '📦' }
  };
  
  onMount(async () => {
    await loadDocuments();
    if (documentId) {
      await loadDocumentDetails(documentId);
    }
  });
  
  async function loadDocuments() {
    loading = true;
    try {
      const response = await fetch(`/api/government/documents/by-status/${activeTab === 'all' ? '' : activeTab}`);
      if (response.ok) {
        const data = await response.json();
        documents = data.data || [];
      }
    } catch (e) {
      error = 'Không thể tải danh sách văn bản';
    } finally {
      loading = false;
    }
  }
  
  async function loadDocumentDetails(id: string) {
    try {
      const [docRes, historyRes] = await Promise.all([
        fetch(`/api/government/documents/${id}`),
        fetch(`/api/government/documents/${id}/history`)
      ]);
      
      if (docRes.ok) selectedDocument = await docRes.json();
      if (historyRes.ok) {
        const historyData = await historyRes.json();
        documentHistory = historyData.history || [];
      }
    } catch (e) {
      error = 'Không thể tải chi tiết văn bản';
    }
  }
  
  async function submitForReview(id: string) {
    try {
      const response = await fetch(`/api/government/documents/${id}/submit`, {
        method: 'POST'
      });
      if (response.ok) {
        await loadDocuments();
        if (selectedDocument?.id === id) await loadDocumentDetails(id);
      }
    } catch (e) {
      error = 'Không thể gửi duyệt';
    }
  }
  
  async function approveDocument() {
    if (!selectedDocument) return;
    
    try {
      const response = await fetch(`/api/government/documents/${selectedDocument.id}/approve`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ comments: approvalComment })
      });
      
      if (response.ok) {
        showApprovalModal = false;
        approvalComment = '';
        await loadDocuments();
        await loadDocumentDetails(selectedDocument.id);
      }
    } catch (e) {
      error = 'Không thể phê duyệt';
    }
  }
  
  async function rejectDocument(reason: string) {
    if (!selectedDocument) return;
    
    try {
      const response = await fetch(`/api/government/documents/${selectedDocument.id}/reject`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reason })
      });
      
      if (response.ok) {
        await loadDocuments();
        await loadDocumentDetails(selectedDocument.id);
      }
    } catch (e) {
      error = 'Không thể từ chối';
    }
  }
  
  function getStatusBadge(status: string) {
    const config = statusConfig[status] || statusConfig.draft;
    return `<span style="color: ${config.color}">${config.icon} ${config.label}</span>`;
  }
  
  function switchTab(tab: typeof activeTab) {
    activeTab = tab;
    loadDocuments();
  }
</script>

<div class="document-workflow">
  <header class="workflow-header">
    <h2>📄 Quy trình phê duyệt văn bản</h2>
    <div class="tabs">
      {#each Object.entries({ pending: '⏳ Chờ duyệt', approved: '✅ Đã duyệt', rejected: '❌ Từ chối', all: '📋 Tất cả' }) as [key, label]}
        <button 
          class="tab-btn {activeTab === key ? 'active' : ''}"
          on:click={() => switchTab(key as typeof activeTab)}
        >
          {label}
        </button>
      {/each}
    </div>
  </header>
  
  {#if error}
    <div class="error-message" transition:slide>
      ⚠️ {error}
      <button on:click={() => error = null}>✕</button>
    </div>
  {/if}
  
  <div class="workflow-content">
    <div class="document-list">
      {#if loading}
        <div class="loading">Đang tải...</div>
      {:else if documents.length === 0}
        <div class="empty-state">
          <p>Không có văn bản nào</p>
        </div>
      {:else}
        {#each documents as doc}
          <div 
            class="document-card {selectedDocument?.id === doc.id ? 'selected' : ''}"
            on:click={() => loadDocumentDetails(doc.id)}
          >
            <div class="doc-header">
              <h4>{doc.title}</h4>
              <span class="status-badge">
                {@html getStatusBadge(doc.status)}
              </span>
            </div>
            <div class="doc-meta">
              <span>📅 {doc.createdAt}</span>
              <span>👤 {doc.issuedBy}</span>
            </div>
            {#if doc.status === 'draft'}
              <button class="action-btn submit" on:click|stopPropagation={() => submitForReview(doc.id)}>
                Gửi duyệt
              </button>
            {/if}
          </div>
        {/each}
      {/if}
    </div>
    
    {#if selectedDocument}
      <div class="document-detail" transition:slide>
        <h3>{selectedDocument.title}</h3>
        <div class="detail-meta">
          <p><strong>Trạng thái:</strong> {@html getStatusBadge(selectedDocument.status)}</p>
          <p><strong>Ngườí tạo:</strong> {selectedDocument.issuedBy}</p>
          <p><strong>Ngày tạo:</strong> {selectedDocument.createdAt}</p>
          <p><strong>Số hiệu:</strong> {selectedDocument.documentNumber || 'Chưa có'}</p>
        </div>
        
        {#if selectedDocument.status === 'pending'}
          <div class="approval-actions">
            <button class="btn-approve" on:click={() => showApprovalModal = true}>
              ✅ Phê duyệt
            </button>
            <button class="btn-reject" on:click={() => rejectDocument('Không đạt yêu cầu')}>
              ❌ Từ chối
            </button>
          </div>
        {/if}
        
        {#if documentHistory.length > 0}
          <div class="history-section">
            <h4>📜 Lịch sử</h4>
            {#each documentHistory as item}
              <div class="history-item">
                <span class="history-action">{item.action}</span>
                <span class="history-time">{item.timestamp}</span>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    {/if}
  </div>
</div>

{#if showApprovalModal}
  <div class="modal-overlay" on:click|self={() => showApprovalModal = false}>
    <div class="modal">
      <h3>Phê duyệt văn bản</h3>
      <textarea
        bind:value={approvalComment}
        placeholder="Nhập nhận xét (tùy chọn)..."
        rows="4"
      ></textarea>
      <div class="modal-actions">
        <button class="btn-secondary" on:click={() => showApprovalModal = false}>Hủy</button>
        <button class="btn-primary" on:click={approveDocument}>Xác nhận phê duyệt</button>
      </div>
    </div>
  </div>
{/if}

<style>
  .document-workflow {
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
  }
  
  .workflow-header {
    margin-bottom: 20px;
  }
  
  .workflow-header h2 {
    margin: 0 0 16px 0;
    color: #1e293b;
  }
  
  .tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  
  .tab-btn {
    padding: 8px 16px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .tab-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
  }
  
  .workflow-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
  
  @media (max-width: 1024px) {
    .workflow-content {
      grid-template-columns: 1fr;
    }
  }
  
  .document-list {
    background: white;
    border-radius: 12px;
    padding: 16px;
    max-height: 600px;
    overflow-y: auto;
  }
  
  .document-card {
    padding: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .document-card:hover,
  .document-card.selected {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
  }
  
  .doc-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 8px;
  }
  
  .doc-header h4 {
    margin: 0;
    font-size: 14px;
    color: #1e293b;
  }
  
  .doc-meta {
    font-size: 12px;
    color: #64748b;
    display: flex;
    gap: 16px;
  }
  
  .document-detail {
    background: white;
    border-radius: 12px;
    padding: 24px;
  }
  
  .document-detail h3 {
    margin: 0 0 16px 0;
    color: #1e293b;
  }
  
  .detail-meta p {
    margin: 8px 0;
    color: #475569;
  }
  
  .approval-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
  }
  
  .btn-approve,
  .btn-reject {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
  }
  
  .btn-approve {
    background: #10b981;
    color: white;
  }
  
  .btn-reject {
    background: #ef4444;
    color: white;
  }
  
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }
  
  .modal {
    background: white;
    padding: 24px;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
  }
  
  .modal h3 {
    margin: 0 0 16px 0;
  }
  
  .modal textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 16px;
    resize: vertical;
  }
  
  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }
  
  .error-message {
    background: #fef2f2;
    color: #ef4444;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .loading,
  .empty-state {
    text-align: center;
    padding: 40px;
    color: #64748b;
  }
</style>
