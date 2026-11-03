<script>
  // sample-module UI Component - Svelte
  // Replace sample-module with the actual module name during generation

  import { onMount } from 'svelte';
  import { SampleModuleStore, selectedSampleModuleItemisLoading, isSaving, SampleModuleError } from '../state/sample-module.store';
  import { sampleModuleApi } from '../services/sample-module.service';
  import { SampleModuleEventBus } from '../events/sample-module.events';
  import type { SampleModuleItem, CreateSampleModuleRequest, UpdateSampleModuleRequest } from '../contracts/sample-module.types';

  let items = [];
  let selectedItem = null;
  let statistics = null;
  let loading = false;
  let saving = false;
  let error = null;
  let searchQuery = '';

  // Form state for create/edit
  let formMode = 'create'; // 'create' or 'edit'
  let formData = {
    name: '',
    description: ''
  };

  // ============================================
  // Lifecycle
  // ============================================
  onMount(async () => {
    await loadItems();
    await loadStatistics();
    
    // Subscribe to events for real-time updates
    const unsubscribeCreated = SampleModuleEventBus.subscribe(
      'sample-module.{{#snakeCase moduleName}}.created',
      (event) => {
        // Add new item to the list
        items = [event.payload.item, ...items];
      }
    );
    
    const unsubscribeUpdated = SampleModuleEventBus.subscribe(
      'sample-module.{{#snakeCase moduleName}}.updated',
      (event) => {
        // Update existing item
        items = items.map(item => 
          item.id === event.payload.item.id ? event.payload.item : item
        );
        // If viewing the updated item, update selected item
        if (selectedItem && selectedItem.id === event.payload.item.id) {
          selectedItem = event.payload.item;
        }
      }
    );
    
    const unsubscribeDeleted = SampleModuleEventBus.subscribe(
      'sample-module.{{#snakeCase moduleName}}.deleted',
      (event) => {
        // Remove deleted item
        items = items.filter(item => item.id !== event.payload.id);
        // Clear selected item if it was deleted
        if (selectedItem && selectedItem.id === event.payload.id) {
          selectedItem = null;
        }
      }
    );
    
    // Cleanup on destroy
    return () => {
      unsubscribeCreated();
      unsubscribeUpdated();
      unsubscribeDeleted();
    };
  });

  // ============================================
  // Actions
  // ============================================
  async loadItems() {
    loading = true;
    error = null;
    try {
      items = await sampleModuleApi.list();
    } catch (err) {
      error = err instanceof Error ? err.message : 'Failed to load items';
      console.error(error);
    } finally {
      loading = false;
    }
  }

  async loadStatistics() {
    try {
      statistics = await sampleModuleApi.getStatistics();
    } catch (err) {
      console.error('Failed to load statistics:', err);
    }
  }

  async handleSearch() {
    if (searchQuery.trim() === '') {
      await loadItems();
      return;
    }
    
    loading = true;
    error = null;
    try {
      items = await sampleModuleApi.search(searchQuery);
    } catch (err) {
      error = err instanceof Error ? err.message : 'Failed to search items';
      console.error(error);
    } finally {
      loading = false;
    }
  }

  async handleCreateItem() {
    saving = true;
    error = null;
    try {
      const newItem = await sampleModuleApi.create(formData);
      // Form will be reset by the event subscription
      formMode = 'create';
      formData = { name: '', description: '' };
    } catch (err) {
      error = err instanceof Error ? err.message : 'Failed to create item';
      console.error(error);
    } finally {
      saving = false;
    }
  }

  async handleUpdateItem() {
    if (!selectedItem) return;
    
    saving = true;
    error = null;
    try {
      const updatedItem = await sampleModuleApi.update(selectedItem.id, formData);
      // Form will be reset by the event subscription
      formMode = 'create';
      formData = { name: '', description: '' };
      selectItem(null);
    } catch (err) {
      error = err instanceof Error ? err.message : 'Failed to update item';
      console.error(error);
    } finally {
      saving = false;
    }
  }

  async handleDeleteItem() {
    if (!selectedItem) return;
    
    if (!window.confirm(`Are you sure you want to delete "${selectedItem.name}"?`)) {
      return;
    }
    
    saving = true;
    error = null;
    try {
      await sampleModuleApi.delete(selectedItem.id);
      // Item will be removed by the event subscription
      selectItem(null);
    } catch (err) {
      error = err instanceof Error ? err.message : 'Failed to delete item';
      console.error(error);
    } finally {
      saving = false;
    }
  }

  function selectItem(item: SampleModuleItem | null) {
    selectedItem = item;
    if (item) {
      formMode = 'edit';
      formData = {
        name: item.name,
        description: item.description ?? ''
      };
    } else {
      formMode = 'create';
      formData = { name: '', description: '' };
    }
  }

  // ============================================
  // Helper Functions
  // ============================================
  function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString();
  }
</script>

<div class="module-container">
  <header class="module-header">
    <h1>SampleModule Management</h1>
    <div class="module-actions">
      <input 
        type="text" 
        bind:value={searchQuery}
        placeholder="Search SampleModules..."
        class="search-input"
        on:keydown={(e) => e.key === 'Enter' && handleSearch()}
      />
      <button 
        on:click={() => selectItem(null)}
        class="btn-primary"
        :disabled={saving}
      >
        New SampleModule
      </button>
    </div>
  </header>

  {#if error}
    <div class="alert alert-error">
      {error}
    </div>
  {/if}

  {#if statistics}
    <div class="stats-grid">
      <div class="stat-card">
        <h3>Total</h3>
        <p class="stat-value">{statistics.total}</p>
      </div>
      <div class="stat-card">
        <h3>Active</h3>
        <p class="stat-value">{statistics.active}</p>
      </div>
      <div class="stat-card">
        <h3>Inactive</h3>
        <p class="stat-value">{statistics.inactive}</p>
      </div>
    </div>
  {/if}

  <section class="module-content">
    <div class="two-column-layout">
      <!-- List View -->
      <div class="list-panel">
        <h2>All SampleModules</h2>
        
        {#if loading}
          <div class="loading-indicator">Loading...</div>
        {:else if items.length === 0}
          <div class="empty-state">
            <p>No SampleModules found.</p>
          </div>
        {/else}
          <div class="item-list">
            {#each items as item (item.id)}
              <div 
                class="item-item {selectedItem?.id === item.id ? 'selected' : ''}"
                on:click={() => selectItem(item)}
              >
                <div class="item-content">
                  <h3>{item.name}</h3>
                  {#if item.description}
                    <p class="item-description">{item.description}</p>
                  {/if}
                  <div class="item-meta">
                    <span class="item-status {item.isActive ? 'active' : 'inactive'}">
                      {item.isActive ? 'Active' : 'Inactive'}
                    </span>
                    <span class="item-date">
                      Updated: {formatDate(item.updatedAt)}
                    </span>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>

      <!-- Detail/Form View -->
      <div class="detail-panel">
        {#if selectedItem}
          <div class="item-detail">
            <h2>Edit SampleModule</h2>
            <div class="detail-fields">
              <div class="field-group">
                <label>Name</label>
                <input 
                  type="text" 
                  bind:value={formData.name}
                  required
                />
              </div>
              <div class="field-group">
                <label>Description</label>
                <textarea 
                  bind:value={formData.description}
                  rows="3"
                ></textarea>
              </div>
            </div>
            <div class="form-actions">
              <button 
                on:click={handleUpdateItem}
                class="btn-primary"
                :disabled={saving || !(formData.name && formData.name.trim())}
              >
                Update SampleModule
              </button>
              <button 
                on:click={handleDeleteItem}
                class="btn-danger"
                :disabled={saving}
              >
                Delete
              </button>
              <button 
                on:click={() => selectItem(null)}
                class="btn-secondary"
              >
                Cancel
              </button>
            </div>
          </div>
        {:else}
          <div class="item-detail">
            <h2>Create New SampleModule</h2>
            <div class="detail-fields">
              <div class="field-group">
                <label>Name</label>
                <input 
                  type="text" 
                  bind:value={formData.name}
                  required
                />
              </div>
              <div class="field-group">
                <label>Description</label>
                <textarea 
                  bind:value={formData.description}
                  rows="3"
                ></textarea>
              </div>
            </div>
            <div class="form-actions">
              <button 
                on:click={handleCreateItem}
                class="btn-primary"
                :disabled={saving || !(formData.name && formData.name.trim())}
              >
                Create SampleModule
              </button>
              <button 
                on:click={() => selectItem(null)}
                class="btn-secondary"
              >
                Cancel
              </button>
            </div>
          </div>
        {/if}
      </div>
    </div>
  </section>
</div>

<style>
  .module-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
  }
  
  .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .module-header h1 {
    margin: 0;
    font-size: 1.75rem;
  }
  
  .module-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  
  .search-input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
    width: 250px;
    max-width: 100%;
  }
  
  .btn-primary {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background-color 0.2s;
  }
  
  .btn-primary:hover:not(:disabled) {
    background-color: #0056b3;
  }
  
  .btn-primary:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background-color 0.2s;
  }
  
  .btn-secondary:hover {
    background-color: #545b62;
  }
  
  .btn-danger {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background-color 0.2s;
  }
  
  .btn-danger:hover {
    background-color: #c82333;
  }
  
  .alert {
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
  }
  
  .alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }
  
  .stat-card {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 1rem;
    text-align: center;
  }
  
  .stat-card h3 {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
    color: #6c757d;
  }
  
  .stat-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #212529;
    margin: 0;
  }
  
  .module-content {
    display: flex;
    gap: 1.5rem;
  }
  
  .two-column-layout {
    display: flex;
    gap: 1.5rem;
  }
  
  .list-panel {
    flex: 1;
    min-width: 300px;
  }
  
  .list-panel h2 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.25rem;
  }
  
  .loading-indicator,
  .empty-state {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
  }
  
  .item-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .item-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border: 1px solid #eee;
    border-radius: 4px;
    background-color: white;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .item-item:hover {
    background-color: #f8f9fa;
    border-color: #ddd;
  }
  
  .item-item.selected {
    background-color: #e3f2fd;
    border-color: #007bff;
  }
  
  .item-content {
    flex: 1;
  }
  
  .item-content h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
  }
  
  .item-description {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
    color: #6c757d;
    line-height: 1.4;
  }
  
  .item-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #6c757d;
  }
  
  .item-status {
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-weight: bold;
    text-transform: capitalize;
  }
  
  .item-status.active {
    background-color: #d4edda;
    color: #155724;
  }
  
  .item-status.inactive {
    background-color: #f8d7da;
    color: #721c24;
  }
  
  .detail-panel {
    flex: 0.8;
    max-width: 400px;
  }
  
  .item-detail h2 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
  }
  
  .detail-fields {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  
  .field-group {
    display: flex;
    flex-direction: column;
  }
  
  .field-group label {
    margin-bottom: 0.25rem;
    font-weight: bold;
    font-size: 0.9rem;
  }
  
  .field-group input,
  .field-group textarea {
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.9rem;
  }
  
  .field-group input:focus,
  .field-group textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
  }
  
  .form-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  
  @media (max-width: 768px) {
    .module-content {
      flex-direction: column;
    }
    
    .two-column-layout {
      flex-direction: column;
    }
    
    .list-panel,
    .detail-panel {
      width: 100%;
      max-width: none;
    }
  }
</style>
