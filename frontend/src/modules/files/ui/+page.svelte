<script lang="ts">
  import { filesStore } from '$lib/../stores';
  import { onMount } from 'svelte';

  let fileInput;
  let uploadProgress = 0;
  let uploading = false;
  let searchQuery = '';
  let selectedCategory = 'all';

  const categories = [
    { id: 'all', name: 'All Files', icon: '📁' },
    { id: 'image', name: 'Images', icon: '🖼️' },
    { id: 'document', name: 'Documents', icon: '📄' },
    { id: 'video', name: 'Videos', icon: '🎬' },
    { id: 'audio', name: 'Audio', icon: '🎵' }
  ];

  onMount(() => {
    loadFiles();
  });

  async function loadFiles() {
    try {
      await filesStore.loadFiles();
    } catch (error) {
      console.error('Failed to load files:', error);
    }
  }

  async function handleFileUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    const files = Array.from(target.files || []);
    if (files.length === 0) return;

    uploading = true;
    uploadProgress = 0;

    try {
      for (let i = 0; i < files.length; i++) {
        await filesStore.uploadFile(files[i]);
        uploadProgress = ((i + 1) / files.length) * 100;
      }
      await loadFiles();
    } catch (error) {
      console.error('Failed to upload files:', error);
    } finally {
      uploading = false;
      uploadProgress = 0;
    }
  }

  async function handleDrop(event: DragEvent) {
    event.preventDefault();
    const files = Array.from(event.dataTransfer?.files || []);
    if (files.length === 0) return;

    uploading = true;
    uploadProgress = 0;

    try {
      for (let i = 0; i < files.length; i++) {
        await filesStore.uploadFile(files[i]);
        uploadProgress = ((i + 1) / files.length) * 100;
      }
      await loadFiles();
    } catch (error) {
      console.error('Failed to upload files:', error);
    } finally {
      uploading = false;
      uploadProgress = 0;
    }
  }

  function handleDragOver(event: DragEvent) {
    event.preventDefault();
  }

  async function deleteFile(fileId) {
    if (!confirm('Are you sure you want to delete this file?')) return;

    try {
      await filesStore.deleteFile(fileId);
      await loadFiles();
    } catch (error) {
      console.error('Failed to delete file:', error);
    }
  }

  function getFileIcon(type) {
    const icons = {
      image: '🖼️',
      document: '📄',
      video: '🎬',
      audio: '🎵',
      other: '📎'
    };
    return icons[type] || icons.other;
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }

  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric' 
    });
  }

  function getFilteredFiles() {
    let files = $filesStore.files;

    if (selectedCategory !== 'all') {
      files = files.filter(file => file.type === selectedCategory);
    }

    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      files = files.filter(file => 
        file.name.toLowerCase().includes(query)
      );
    }

    return files;
  }

  function getCategoryIcon(categoryId) {
    const category = categories.find(c => c.id === categoryId);
    return category ? category.icon : '📁';
  }
</script>

<div class="files-page">
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
      <div class="files-header">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Files</h1>
          <p class="text-gray-600 mt-1">Manage your files and documents</p>
        </div>
        <button 
          class="upload-btn"
          onclick={() => fileInput.click()}
          disabled={uploading}
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Upload Files
        </button>
        <input 
          type="file"
          bind:this={fileInput}
          multiple
          accept="image/*,.pdf,.doc,.docx,.txt,.video/*,.audio/*"
          onchange={handleFileUpload}
          style="display: none"
        />
      </div>

      <div class="drop-zone" 
           class:uploading={uploading}
           ondrop={handleDrop}
           ondragover={handleDragOver}
           ondragenter={(e) => e.preventDefault()}
           ondragleave={(e) => e.preventDefault()}>
        {#if uploading}
          <div class="uploading-content">
            <div class="spinner"></div>
            <p class="uploading-text">Uploading files... {Math.round(uploadProgress)}%</p>
            <div class="progress-bar">
              <div class="progress-fill" style="width: {uploadProgress}%"></div>
            </div>
          </div>
        {:else}
          <div class="drop-zone-content">
            <svg class="drop-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p class="drop-text">Drag and drop files here</p>
            <p class="drop-subtext">or click the upload button above</p>
          </div>
        {/if}
      </div>

      <div class="files-toolbar">
        <div class="search-bar">
          <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input 
            type="text"
            placeholder="Search files..."
            bind:value={searchQuery}
            class="search-input"
          />
        </div>

        <div class="categories">
          {#each categories as category}
            <button 
              class="category-btn {selectedCategory === category.id ? 'active' : ''}"
              onclick={() => selectedCategory = category.id}
            >
              <span class="category-icon">{category.icon}</span>
              <span>{category.name}</span>
            </button>
          {/each}
        </div>
      </div>

      {#if $filesStore.loading}
        <div class="loading-state">
          <div class="spinner"></div>
          <p>Loading files...</p>
        </div>
      {:else if getFilteredFiles().length === 0}
        <div class="empty-state">
          <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
          </svg>
          <h2>No files yet</h2>
          <p>Upload files to get started</p>
        </div>
      {:else}
        <div class="files-grid">
          {#each getFilteredFiles() as file}
            <div class="file-card">
              <div class="file-icon">
                {getFileIcon(file.type)}
              </div>
              
              <div class="file-info">
                <h3 class="file-name" title={file.name}>{file.name}</h3>
                <p class="file-meta">
                  {formatFileSize(file.size)} • {formatDate(file.createdAt)}
                </p>
              </div>

              <div class="file-actions">
                <button 
                  class="action-btn download"
                  title="Download"
                  onclick={() => window.open(file.url, '_blank')}
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                  </svg>
                </button>
                <button 
                  class="action-btn delete"
                  title="Delete"
                  onclick={() => deleteFile(file.id)}
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          {/each}
        </div>
      {/if}

      <div class="storage-info">
        <div class="storage-bar">
          <div class="storage-fill"></div>
        </div>
        <p class="storage-text">
          {$filesStore.storageUsed || 0} GB used of {$filesStore.storageTotal || 10} GB
        </p>
      </div>
    </div>
  </div>
</div>

<style>
  .files-page {
    min-height: 100vh;
    background: #f9fafb;
  }

  .files-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
  }

  .files-header h1 {
    margin: 0;
  }

  .upload-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
  }

  .upload-btn:hover:not(:disabled) {
    background: #4338ca;
  }

  .upload-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .drop-zone {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 3rem;
    text-align: center;
    margin-bottom: 2rem;
    transition: all 0.3s;
    background: white;
  }

  .drop-zone:hover {
    border-color: #4f46e5;
    background: #f9fafb;
  }

  .drop-zone.uploading {
    border-color: #4f46e5;
    background: #eff6ff;
  }

  .drop-zone-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
  }

  .drop-icon {
    width: 4rem;
    height: 4rem;
    color: #9ca3af;
  }

  .drop-text {
    font-size: 1.125rem;
    font-weight: 600;
    color: #374151;
    margin: 0;
  }

  .drop-subtext {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0;
  }

  .uploading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }

  .spinner {
    width: 3rem;
    height: 3rem;
    border: 3px solid #e5e7eb;
    border-top-color: #4f46e5;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .uploading-text {
    font-weight: 600;
    color: #4f46e5;
    margin: 0;
  }

  .progress-bar {
    width: 100%;
    max-width: 300px;
    height: 0.5rem;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
  }

  .progress-fill {
    height: 100%;
    background: #4f46e5;
    transition: width 0.3s;
  }

  .files-toolbar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .search-bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
  }

  .search-icon {
    width: 1.25rem;
    height: 1.25rem;
    color: #9ca3af;
  }

  .search-input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.875rem;
  }

  .categories {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .category-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    font-size: 0.875rem;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
  }

  .category-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
  }

  .category-btn.active {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
  }

  .category-icon {
    font-size: 1rem;
  }

  .files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
  }

  .file-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    transition: box-shadow 0.2s;
    border: 1px solid #e5e7eb;
  }

  .file-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .file-icon {
    font-size: 3rem;
    text-align: center;
  }

  .file-info {
    flex: 1;
  }

  .file-name {
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .file-meta {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
  }

  .file-actions {
    display: flex;
    gap: 0.5rem;
  }

  .action-btn {
    flex: 1;
    padding: 0.5rem;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .action-btn.download:hover {
    background: #eff6ff;
    border-color: #4f46e5;
    color: #4f46e5;
  }

  .action-btn.delete:hover {
    background: #fef2f2;
    border-color: #ef4444;
    color: #ef4444;
  }

  .loading-state,
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: #9ca3af;
  }

  .empty-icon {
    width: 5rem;
    height: 5rem;
    margin-bottom: 1rem;
  }

  .empty-state h2 {
    font-size: 1.5rem;
    color: #374151;
    margin: 0 0 0.5rem 0;
  }

  .empty-state p {
    margin: 0;
  }

  .storage-info {
    margin-top: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
  }

  .storage-bar {
    height: 0.5rem;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.75rem;
  }

  .storage-fill {
    height: 100%;
    background: #4f46e5;
    width: 50%;
  }

  .storage-text {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
  }

  @media (max-width: 768px) {
    .files-header {
      flex-direction: column;
      gap: 1rem;
      align-items: flex-start;
    }

    .files-grid {
      grid-template-columns: 1fr;
    }

    .categories {
      overflow-x: auto;
      flex-wrap: nowrap;
      padding-bottom: 0.5rem;
    }

    .category-btn {
      flex-shrink: 0;
    }
  }
</style>
