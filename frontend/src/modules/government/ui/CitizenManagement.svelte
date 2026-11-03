<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import { 
    citizenStore, 
    citizenLoading,
    citizenSaving,
    citizenError,
    citizenStatistics,
    activeCitizens,
    type Citizen,
    type CreateCitizenRequest
  } from '../index';
  
  // Props
  export let mode: 'grid' | 'list' = 'grid';
  export let showStats = true;
  
  // Local state
  let searchQuery = '';
  let selectedGender: 'all' | 'male' | 'female' | 'other' = 'all';
  let showCreateModal = false;
  let editingCitizen: Citizen | null = null;
  let debounceTimer: ReturnType<typeof setTimeout>;
  
  // Form data
  let formData: Partial<CreateCitizenRequest> = {
    gender: 'other',
    nationality: 'Vietnam',
  };
  
  // Load data on mount
  onMount(async () => {
    await citizenStore.loadCitizens(true);
    await citizenStore.loadStatistics();
  });
  
  onDestroy(() => {
    if (debounceTimer) clearTimeout(debounceTimer);
  });
  
  // Search with debounce
  function handleSearch() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      if (searchQuery.trim()) {
        citizenStore.searchCitizens(searchQuery);
      } else {
        citizenStore.loadCitizens(true);
      }
    }, 300);
  }
  
  // Filter citizens
  $: filteredCitizens = selectedGender === 'all' 
    ? $activeCitizens 
    : $activeCitizens.filter(c => c.gender === selectedGender);
  
  // Create citizen
  async function handleCreate() {
    if (!formData.fullName || !formData.identityNumber) return;
    
    try {
      await citizenStore.createCitizen(formData as CreateCitizenRequest);
      showCreateModal = false;
      resetForm();
    } catch (err) {
      // Error handled by store
    }
  }
  
  // Update citizen
  async function handleUpdate() {
    if (!editingCitizen) return;
    
    try {
      await citizenStore.updateCitizen(editingCitizen.id, {
        fullName: editingCitizen.fullName,
        email: editingCitizen.email || undefined,
        phone: editingCitizen.phone || undefined,
        occupation: editingCitizen.occupation || undefined,
      });
      editingCitizen = null;
    } catch (err) {
      // Error handled by store
    }
  }
  
  // Toggle citizen status
  async function toggleStatus(citizen: Citizen) {
    if (citizen.isActive) {
      await citizenStore.deactivateCitizen(citizen.id);
    } else {
      await citizenStore.activateCitizen(citizen.id);
    }
  }
  
  function resetForm() {
    formData = {
      gender: 'other',
      nationality: 'Vietnam',
    };
  }
  
  function openEditModal(citizen: Citizen) {
    editingCitizen = { ...citizen };
  }
  
  function closeEditModal() {
    editingCitizen = null;
  }
  
  // Format date
  function formatDate(dateStr: string | null) {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('vi-VN');
  }
  
  // Gender label
  function getGenderLabel(gender: string) {
    const labels: Record<string, string> = {
      male: 'Nam',
      female: 'Nữ',
      other: 'Khác',
    };
    return labels[gender] || gender;
  }
</script>

<div class="citizen-management">
  <!-- Header -->
  <header class="cm-header">
    <div class="cm-title">
      <h2>👥 Quản lý công dân</h2>
      <p class="subtitle">Danh sách công dân trên địa bàn phường/xã</p>
    </div>
    <button class="btn btn-primary" on:click={() => showCreateModal = true}>
      ➕ Thêm công dân
    </button>
  </header>
  
  <!-- Statistics Cards -->
  {#if showStats && $citizenStatistics}
    <div class="stats-cards">
      <div class="stat-card primary">
        <div class="stat-icon">👥</div>
        <div class="stat-value">{$citizenStatistics.total}</div>
        <div class="stat-label">Tổng công dân</div>
      </div>
      <div class="stat-card success">
        <div class="stat-icon">✅</div>
        <div class="stat-value">{$citizenStatistics.active}</div>
        <div class="stat-label">Đang hoạt động</div>
      </div>
      <div class="stat-card info">
        <div class="stat-icon">♂️</div>
        <div class="stat-value">{$citizenStatistics.byGender.male}</div>
        <div class="stat-label">Nam</div>
      </div>
      <div class="stat-card warning">
        <div class="stat-icon">♀️</div>
        <div class="stat-value">{$citizenStatistics.byGender.female}</div>
        <div class="stat-label">Nữ</div>
      </div>
    </div>
  {/if}
  
  <!-- Filters -->
  <div class="filters-bar">
    <div class="search-box">
      <input
        type="text"
        placeholder="🔍 Tìm theo tên, CCCD..."
        bind:value={searchQuery}
        on:input={handleSearch}
        class="search-input"
      />
    </div>
    
    <div class="filter-group">
      <label>Giới tính:</label>
      <select bind:value={selectedGender} class="filter-select">
        <option value="all">Tất cả</option>
        <option value="male">Nam</option>
        <option value="female">Nữ</option>
        <option value="other">Khác</option>
      </select>
    </div>
  </div>
  
  <!-- Error Alert -->
  {#if $citizenError}
    <div class="error-alert">
      <span>⚠️ {$citizenError}</span>
      <button class="btn-close" on:click={() => citizenStore.clearError()}>×</button>
    </div>
  {/if}
  
  <!-- Loading State -->
  {#if $citizenLoading && filteredCitizens.length === 0}
    <div class="loading-state">
      <div class="spinner"></div>
      <p>Đang tải danh sách công dân...</p>
    </div>
  
  <!-- Empty State -->
  {:else if filteredCitizens.length === 0}
    <div class="empty-state">
      <div class="empty-icon">👤</div>
      <h3>Chưa có công dân nào</h3>
      <p>Thêm công dân mới để bắt đầu quản lý</p>
    </div>
  
  <!-- Citizens Grid -->
  {:else if mode === 'grid'}
    <div class="citizens-grid">
      {#each filteredCitizens as citizen (citizen.id)}
        <div class="citizen-card" class:inactive={!citizen.isActive}>
          <div class="card-header">
            <div class="avatar">
              {citizen.fullName.charAt(0).toUpperCase()}
            </div>
            <div class="status-badge" class:active={citizen.isActive}>
              {citizen.isActive ? 'Hoạt động' : 'Không hoạt động'}
            </div>
          </div>
          
          <div class="card-body">
            <h3 class="citizen-name">{citizen.fullName}</h3>
            <p class="citizen-id">🆔 {citizen.identityNumber}</p>
            
            <div class="info-row">
              <span class="info-label">Giới tính:</span>
              <span class="info-value">{getGenderLabel(citizen.gender)}</span>
            </div>
            
            <div class="info-row">
              <span class="info-label">Ngày sinh:</span>
              <span class="info-value">{formatDate(citizen.dateOfBirth)}</span>
            </div>
            
            <div class="info-row">
              <span class="info-label">Địa chỉ:</span>
              <span class="info-value" title={citizen.street}>
                {citizen.street.length > 30 ? citizen.street.slice(0, 30) + '...' : citizen.street}
              </span>
            </div>
            
            {#if citizen.phone}
              <div class="info-row">
                <span class="info-label">Điện thoại:</span>
                <span class="info-value">{citizen.phone}</span>
              </div>
            {/if}
          </div>
          
          <div class="card-actions">
            <button class="btn btn-sm btn-secondary" on:click={() => openEditModal(citizen)}>
              ✏️ Sửa
            </button>
            <button 
              class="btn btn-sm" 
              class:btn-danger={citizen.isActive}
              class:btn-success={!citizen.isActive}
              on:click={() => toggleStatus(citizen)}
              disabled={$citizenSaving}
            >
              {citizen.isActive ? '🚫 Vô hiệu' : '✅ Kích hoạt'}
            </button>
          </div>
        </div>
      {/each}
    </div>
  
  <!-- Citizens List -->
  {:else}
    <div class="citizens-table-wrapper">
      <table class="citizens-table">
        <thead>
          <tr>
            <th>Họ tên</th>
            <th>CCCD/CMND</th>
            <th>Giới tính</th>
            <th>Ngày sinh</th>
            <th>Địa chỉ</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          {#each filteredCitizens as citizen (citizen.id)}
            <tr class:inactive={!citizen.isActive}>
              <td>
                <div class="citizen-cell">
                  <div class="mini-avatar">{citizen.fullName.charAt(0).toUpperCase()}</div>
                  <span>{citizen.fullName}</span>
                </div>
              </td>
              <td>{citizen.identityNumber}</td>
              <td>{getGenderLabel(citizen.gender)}</td>
              <td>{formatDate(citizen.dateOfBirth)}</td>
              <td title={citizen.street}>
                {citizen.street.length > 40 ? citizen.street.slice(0, 40) + '...' : citizen.street}
              </td>
              <td>
                <span class="status-pill" class:active={citizen.isActive}>
                  {citizen.isActive ? 'Hoạt động' : 'Không hoạt động'}
                </span>
              </td>
              <td>
                <div class="action-btns">
                  <button class="btn-icon" on:click={() => openEditModal(citizen)} title="Sửa">
                    ✏️
                  </button>
                  <button 
                    class="btn-icon" 
                    on:click={() => toggleStatus(citizen)}
                    title={citizen.isActive ? 'Vô hiệu hóa' : 'Kích hoạt'}
                    disabled={$citizenSaving}
                  >
                    {citizen.isActive ? '🚫' : '✅'}
                  </button>
                </div>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {/if}
  
  <!-- Load More -->
  {#if filteredCitizens.length > 0}
    <div class="load-more">
      <button 
        class="btn btn-outline" 
        on:click={() => citizenStore.loadCitizens()}
        disabled={$citizenLoading}
      >
        {$citizenLoading ? '⏳ Đang tải...' : 'Tải thêm'}
      </button>
    </div>
  {/if}
</div>

<!-- Create Modal -->
{#if showCreateModal}
  <div class="modal-overlay" on:click|self={() => showCreateModal = false}>
    <div class="modal">
      <div class="modal-header">
        <h3>➕ Thêm công dân mới</h3>
        <button class="btn-close" on:click={() => showCreateModal = false}>×</button>
      </div>
      
      <form on:submit|preventDefault={handleCreate} class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Họ và tên *</label>
            <input type="text" bind:value={formData.fullName} required />
          </div>
          
          <div class="form-group">
            <label>CCCD/CMND *</label>
            <input type="text" bind:value={formData.identityNumber} required />
          </div>
          
          <div class="form-group">
            <label>Ngày sinh</label>
            <input type="date" bind:value={formData.dateOfBirth} />
          </div>
          
          <div class="form-group">
            <label>Giới tính</label>
            <select bind:value={formData.gender}>
              <option value="male">Nam</option>
              <option value="female">Nữ</option>
              <option value="other">Khác</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Email</label>
            <input type="email" bind:value={formData.email} />
          </div>
          
          <div class="form-group">
            <label>Điện thoại</label>
            <input type="tel" bind:value={formData.phone} />
          </div>
          
          <div class="form-group">
            <label>Tỉnh/Thành phố *</label>
            <input type="text" bind:value={formData.province} required />
          </div>
          
          <div class="form-group">
            <label>Quận/Huyện *</label>
            <input type="text" bind:value={formData.district} required />
          </div>
          
          <div class="form-group">
            <label>Phường/Xã *</label>
            <input type="text" bind:value={formData.ward} required />
          </div>
          
          <div class="form-group full-width">
            <label>Địa chỉ *</label>
            <input type="text" bind:value={formData.street} required />
          </div>
          
          <div class="form-group">
            <label>Số nhà/Căn hộ</label>
            <input type="text" bind:value={formData.apartmentNumber} />
          </div>
          
          <div class="form-group">
            <label>Nghề nghiệp</label>
            <input type="text" bind:value={formData.occupation} />
          </div>
          
          <div class="form-group">
            <label>Dân tộc</label>
            <input type="text" bind:value={formData.ethnicity} />
          </div>
          
          <div class="form-group">
            <label>Quốc tịch</label>
            <input type="text" bind:value={formData.nationality} />
          </div>
        </div>
      </form>
      
      <div class="modal-footer">
        <button class="btn btn-secondary" on:click={() => showCreateModal = false}>
          Hủy
        </button>
        <button class="btn btn-primary" on:click={handleCreate} disabled={$citizenSaving}>
          {$citizenSaving ? '⏳ Đang lưu...' : '💾 Lưu công dân'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Edit Modal -->
{#if editingCitizen}
  <div class="modal-overlay" on:click|self={closeEditModal}>
    <div class="modal">
      <div class="modal-header">
        <h3>✏️ Cập nhật công dân</h3>
        <button class="btn-close" on:click={closeEditModal}>×</button>
      </div>
      
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Họ và tên</label>
            <input type="text" bind:value={editingCitizen.fullName} />
          </div>
          
          <div class="form-group">
            <label>Email</label>
            <input type="email" bind:value={editingCitizen.email} />
          </div>
          
          <div class="form-group">
            <label>Điện thoại</label>
            <input type="tel" bind:value={editingCitizen.phone} />
          </div>
          
          <div class="form-group">
            <label>Nghề nghiệp</label>
            <input type="text" bind:value={editingCitizen.occupation} />
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="btn btn-secondary" on:click={closeEditModal}>
          Hủy
        </button>
        <button class="btn btn-primary" on:click={handleUpdate} disabled={$citizenSaving}>
          {$citizenSaving ? '⏳ Đang lưu...' : '💾 Cập nhật'}
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .citizen-management {
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
  }
  
  .cm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }
  
  .cm-title h2 {
    margin: 0 0 4px;
    font-size: 1.5rem;
    color: #1e293b;
  }
  
  .subtitle {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
  }
  
  /* Stats Cards */
  .stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }
  
  .stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-top: 4px solid #3b82f6;
  }
  
  .stat-card.success { border-top-color: #10b981; }
  .stat-card.info { border-top-color: #06b6d4; }
  .stat-card.warning { border-top-color: #f59e0b; }
  
  .stat-icon {
    font-size: 2rem;
    margin-bottom: 8px;
  }
  
  .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
  }
  
  .stat-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 4px;
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
    min-width: 120px;
  }
  
  /* Error Alert */
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
  
  /* Loading & Empty States */
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
  
  /* Grid Layout */
  .citizens-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
  }
  
  .citizen-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  
  .citizen-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  
  .citizen-card.inactive {
    opacity: 0.7;
  }
  
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }
  
  .avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: white;
    color: #667eea;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 600;
  }
  
  .status-badge {
    padding: 4px 12px;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    font-size: 0.75rem;
  }
  
  .status-badge.active {
    background: rgba(16,185,129,0.3);
  }
  
  .card-body {
    padding: 16px;
  }
  
  .citizen-name {
    margin: 0 0 8px;
    font-size: 1.125rem;
    color: #1e293b;
  }
  
  .citizen-id {
    margin: 0 0 16px;
    font-size: 0.875rem;
    color: #64748b;
    font-family: monospace;
  }
  
  .info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 0.875rem;
  }
  
  .info-label {
    color: #64748b;
  }
  
  .info-value {
    color: #1e293b;
    font-weight: 500;
    max-width: 60%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  
  .card-actions {
    display: flex;
    gap: 8px;
    padding: 12px 16px;
    border-top: 1px solid #f1f5f9;
  }
  
  /* Table Layout */
  .citizens-table-wrapper {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  
  .citizens-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .citizens-table th,
  .citizens-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #f1f5f9;
  }
  
  .citizens-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
  }
  
  .citizens-table tr:hover {
    background: #f8fafc;
  }
  
  .citizens-table tr.inactive {
    opacity: 0.7;
  }
  
  .citizen-cell {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .mini-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
  }
  
  .status-pill {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    background: #fee2e2;
    color: #991b1b;
  }
  
  .status-pill.active {
    background: #d1fae5;
    color: #065f46;
  }
  
  .action-btns {
    display: flex;
    gap: 8px;
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
  
  .btn-danger {
    background: #fee2e2;
    color: #991b1b;
  }
  
  .btn-success {
    background: #d1fae5;
    color: #065f46;
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
    max-width: 600px;
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
  .form-group select {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
  }
  
  .form-group input:focus,
  .form-group select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
  }
</style>
