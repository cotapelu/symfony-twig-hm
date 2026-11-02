<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { governmentService, type Citizen, type Officer, type Procedure } from '$lib/services/government';

  let activeTab = 'citizens';
  let loading = false;
  let error = '';

  let citizens: Citizen[] = [];
  let officers: Officer[] = [];
  let procedures: Procedure[] = [];
  let documents: any[] = [];
  let stats: any = {
    totalCitizens: 0,
    totalOfficers: 0,
    totalProcedures: 0,
    pendingDocuments: 0
  };

  let showModal = false;
  let modalType = '';
  let formData: any = {};

  onMount(() => {
    loadData();
  });

  async function loadData() {
    loading = true;
    error = '';
    try {
      if (activeTab === 'citizens') {
        citizens = await governmentService.getCitizens().catch(() => generateMockCitizens());
      } else if (activeTab === 'officers') {
        officers = await governmentService.getOfficers().catch(() => generateMockOfficers());
      } else if (activeTab === 'procedures') {
        procedures = await governmentService.getProcedures().catch(() => generateMockProcedures());
      }
    } catch (err) {
      console.error('Failed to load data:', err);
    }
    loading = false;
  }

  function generateMockCitizens(): Citizen[] {
    return [
      { id: '1', name: 'Nguyen Van A', cccd: '012345678901', dob: '1990-01-15', gender: 'Male', address: '123 Dong Da, Ha Noi', ward: 'Dong Da', district: 'Dong Da', phone: '0912345678' },
      { id: '2', name: 'Tran Thi B', cccd: '012345678902', dob: '1985-03-22', gender: 'Female', address: '456 Ba Dinh, Ha Noi', ward: 'Ba Dinh', district: 'Ba Dinh', phone: '0912345679' }
    ];
  }

  function generateMockOfficers(): Officer[] {
    return [
      { id: '1', name: 'Nguyen Van X', position: 'Chu tich', department: 'Ban lanh dao', phone: '0912345678', email: 'nx@example.com', wards: [] },
      { id: '2', name: 'Tran Thi Y', position: 'Pho Chu tich', department: 'Ban lanh dao', phone: '0912345679', email: 'ty@example.com', wards: [] }
    ];
  }

  function generateMockProcedures(): Procedure[] {
    return [
      { id: '1', name: 'Dang ky khai sinh', category: 'Civil', description: 'Register birth', requirements: [], processingTime: '30 days', fee: 0, status: 'active' },
      { id: '2', name: 'Cap CCCD', category: 'Identity', description: 'Apply for ID card', requirements: [], processingTime: '30 days', fee: 30000, status: 'active' }
    ];
  }

  function generateMockDocuments() {
    return [
      { id: '1', title: 'Quyết định thành lập', type: 'QUYET_DINH', status: 'APPROVED', createdAt: '2026-01-15', author: 'Nguyen Van X' },
      { id: '2', title: 'Thông báo họp chi bộ', type: 'THONG_BAO', status: 'PENDING', createdAt: '2026-02-01', author: 'Tran Thi Y' },
      { id: '3', title: 'Báo cáo công tác tháng', type: 'BAO_CAO', status: 'APPROVED', createdAt: '2026-01-28', author: 'Le Van Z' },
      { id: '4', title: 'Đề xuất khen thưởng', type: 'DE_XUAT', status: 'DRAFT', createdAt: '2026-02-10', author: 'Pham Van W' },
      { id: '5', title: 'Kế hoạch năm 2026', type: 'KE_HOACH', status: 'PENDING', createdAt: '2026-02-12', author: 'Nguyen Van X' }
    ];
  }

  function openModal(type: string, item: any = null) {
    modalType = type;
    if (item) {
      formData = { ...item };
    } else {
      formData = {};
    }
    showModal = true;
  }

  function closeModal() {
    showModal = false;
    formData = {};
    modalType = '';
  }

  async function saveForm() {
    loading = true;
    try {
      await new Promise(resolve => setTimeout(resolve, 500));
      closeModal();
      loadData();
    } finally {
      loading = false;
    }
  }

  function getStatusClass(status: string) {
    switch (status?.toUpperCase()) {
      case 'ACTIVE': return 'status-active';
      case 'INACTIVE': return 'status-inactive';
      case 'PENDING': return 'status-pending';
      case 'APPROVED': return 'status-approved';
      case 'DRAFT': return 'status-draft';
      case 'REJECTED': return 'status-rejected';
      default: return 'status-default';
    }
  }

  function formatDate(dateStr: string) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('vi-VN');
  }

  function handleTabChange(tab: string) {
    activeTab = tab;
    loadData();
  }
</script>

<svelte:head>
  <title>Government Management - Phường/Xã Super App</title>
</svelte:head>

<div class="government-page">
  <header class="page-header">
    <div class="header-content">
      <div class="header-title">
        <h1>🏛️ Quản Lý Hành Chính</h1>
        <p>Phường/Xã Super App - Hệ thống quản lý hành chính cô</p>
      </div>
      <div class="header-actions">
        <button class="btn-primary" on:click={() => openModal('create')}>
          + Thêm mới
        </button>
      </div>
    </div>
  </header>

  <nav class="tabs">
    <button 
      class="tab" 
      class:active={activeTab === 'citizens'} 
      on:click={() => handleTabChange('citizens')}
    >
      👥 Công Dân
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'officers'} 
      on:click={() => handleTabChange('officers')}
    >
      👮 Cán Bộ
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'procedures'} 
      on:click={() => handleTabChange('procedures')}
    >
      📋 TTHC
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'documents'} 
      on:click={() => handleTabChange('documents')}
    >
      📄 Văn Bản
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'statistics'} 
      on:click={() => handleTabChange('statistics')}
    >
      📊 Thống Kê
    </button>
  </nav>

  <main class="main-content">
    {#if error}
      <div class="error-message">
        <span>⚠️ {error}</span>
        <button on:click={loadData}>Thử lại</button>
      </div>
    {/if}

    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Đang tải dữ liệu...</p>
      </div>
    {:else}
      {#if activeTab === 'citizens'}
        <section class="data-section">
          <div class="section-header">
            <h2>📋 Danh Sách Công Dân</h2>
            <div class="search-box">
              <input type="text" placeholder="Tìm kiếm công dân..." />
              <button class="btn-search">🔍</button>
            </div>
          </div>
          
          <div class="data-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Họ Tên</th>
                  <th>CCCD</th>
                  <th>Ngày Sinh</th>
                  <th>Địa Chỉ</th>
                  <th>Trạng Thái</th>
                  <th>Thao Tác</th>
                </tr>
              </thead>
              <tbody>
                {#each citizens as citizen}
                  <tr>
                    <td>{citizen.id}</td>
                    <td>{citizen.fullName}</td>
                    <td>{citizen.cccd}</td>
                    <td>{formatDate(citizen.birthDate)}</td>
                    <td>{citizen.address}</td>
                    <td><span class="badge {getStatusClass(citizen.status)}">{citizen.status}</span></td>
                    <td>
                      <button class="btn-action" on:click={() => openModal('view', citizen)}>👁️</button>
                      <button class="btn-action" on:click={() => openModal('edit', citizen)}>✏️</button>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        </section>

      {:else if activeTab === 'officers'}
        <section class="data-section">
          <div class="section-header">
            <h2>👮 Danh Sách Cán Bộ</h2>
            <div class="search-box">
              <input type="text" placeholder="Tìm kiếm cán bộ..." />
              <button class="btn-search">🔍</button>
            </div>
          </div>
          
          <div class="data-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Họ Tên</th>
                  <th>Chức Vụ</th>
                  <th>Phòng Ban</th>
                  <th>Điện Thoại</th>
                  <th>Email</th>
                  <th>Thao Tác</th>
                </tr>
              </thead>
              <tbody>
                {#each officers as officer}
                  <tr>
                    <td>{officer.id}</td>
                    <td>{officer.fullName}</td>
                    <td>{officer.position}</td>
                    <td>{officer.department}</td>
                    <td>{officer.phone}</td>
                    <td>{officer.email}</td>
                    <td>
                      <button class="btn-action" on:click={() => openModal('view', officer)}>👁️</button>
                      <button class="btn-action" on:click={() => openModal('edit', officer)}>✏️</button>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        </section>

      {:else if activeTab === 'procedures'}
        <section class="data-section">
          <div class="section-header">
            <h2>📋 Thủ Tục Hành Chính</h2>
            <button class="btn-primary" on:click={() => openModal('createProcedure')}>
              + Thêm TTHC
            </button>
          </div>
          
          <div class="cards-grid">
            {#each procedures as procedure}
              <div class="procedure-card">
                <div class="card-header">
                  <span class="procedure-code">{procedure.code}</span>
                  <span class="badge {getStatusClass(procedure.status)}">{procedure.status}</span>
                </div>
                <h3>{procedure.name}</h3>
                <div class="procedure-info">
                  <p><strong>Phòng ban:</strong> {procedure.department}</p>
                  <p><strong>Thời hạn:</strong> {procedure.deadline} ngày</p>
                </div>
                <div class="card-actions">
                  <button class="btn-secondary" on:click={() => openModal('view', procedure)}>Chi tiết</button>
                  <button class="btn-primary" on:click={() => openModal('edit', procedure)}>Sửa</button>
                </div>
              </div>
            {/each}
          </div>
        </section>

      {:else if activeTab === 'documents'}
        <section class="data-section">
          <div class="section-header">
            <h2>📄 Quản Lý Văn Bản</h2>
            <button class="btn-primary" on:click={() => openModal('createDocument')}>
              + Thêm Văn Bản
            </button>
          </div>
          
          <div class="documents-list">
            {#each documents as doc}
              <div class="document-item">
                <div class="doc-icon">📄</div>
                <div class="doc-info">
                  <h4>{doc.title}</h4>
                  <p>
                    <span class="doc-type">{doc.type}</span>
                    <span class="doc-author"> • {doc.author}</span>
                    <span class="doc-date"> • {formatDate(doc.createdAt)}</span>
                  </p>
                </div>
                <div class="doc-status">
                  <span class="badge {getStatusClass(doc.status)}">{doc.status}</span>
                </div>
                <div class="doc-actions">
                  <button class="btn-action" on:click={() => openModal('view', doc)}>👁️</button>
                  <button class="btn-action" on:click={() => openModal('workflow', doc)}>🔄</button>
                </div>
              </div>
            {/each}
          </div>
        </section>

      {:else if activeTab === 'statistics'}
        <section class="data-section">
          <h2>📊 Thống Kê Tổng Quan</h2>
          
          <div class="stats-grid">
            <div class="stat-card primary">
              <div class="stat-icon">👥</div>
              <div class="stat-content">
                <div class="stat-value">{stats.totalCitizens.toLocaleString()}</div>
                <div class="stat-label">Tổng Công Dân</div>
              </div>
            </div>
            
            <div class="stat-card success">
              <div class="stat-icon">👮</div>
              <div class="stat-content">
                <div class="stat-value">{stats.totalOfficers}</div>
                <div class="stat-label">Cán Bộ</div>
              </div>
            </div>
            
            <div class="stat-card warning">
              <div class="stat-icon">📋</div>
              <div class="stat-content">
                <div class="stat-value">{stats.totalProcedures}</div>
                <div class="stat-label">Thủ Tục</div>
              </div>
            </div>
            
            <div class="stat-card danger">
              <div class="stat-icon">⏳</div>
              <div class="stat-content">
                <div class="stat-value">{stats.pendingDocuments}</div>
                <div class="stat-label">Chờ Duyệt</div>
              </div>
            </div>
          </div>

          <div class="charts-section">
            <div class="chart-card">
              <h3>Biến Động Dân Cư</h3>
              <div class="chart-placeholder">
                <div class="bar-chart">
                  <div class="bar" style="height: 60%"><span>T1</span></div>
                  <div class="bar" style="height: 75%"><span>T2</span></div>
                  <div class="bar" style="height: 45%"><span>T3</span></div>
                  <div class="bar" style="height: 90%"><span>T4</span></div>
                  <div class="bar" style="height: 55%"><span>T5</span></div>
                  <div class="bar" style="height: 70%"><span>T6</span></div>
                </div>
              </div>
            </div>
            
            <div class="chart-card">
              <h3>Tình Trạng Hồ Sơ</h3>
              <div class="chart-placeholder">
                <div class="donut-chart">
                  <div class="donut-segment" style="--p: 65; --c: #22c55e">65%</div>
                  <div class="donut-label">Hoàn thành</div>
                </div>
              </div>
            </div>
          </div>
        </section>
      {/if}
    {/if}
  </main>

  {#if showModal}
    <div class="modal-overlay" on:click={closeModal}>
      <div class="modal" on:click|stopPropagation>
        <div class="modal-header">
          <h3>{modalType === 'create' ? 'Thêm mới' : modalType === 'edit' ? 'Chỉnh sửa' : 'Chi tiết'}</h3>
          <button class="modal-close" on:click={closeModal}>✕</button>
        </div>
        <div class="modal-body">
          {#if modalType === 'create' || modalType === 'edit'}
            <form on:submit|preventDefault={saveForm}>
              {#if activeTab === 'citizens'}
                <div class="form-group">
                  <label>Họ Tên</label>
                  <input type="text" bind:value={formData.fullName} required />
                </div>
                <div class="form-group">
                  <label>CCCD</label>
                  <input type="text" bind:value={formData.cccd} required />
                </div>
                <div class="form-group">
                  <label>Ngày Sinh</label>
                  <input type="date" bind:value={formData.birthDate} required />
                </div>
                <div class="form-group">
                  <label>Địa Chỉ</label>
                  <input type="text" bind:value={formData.address} required />
                </div>
              {:else if activeTab === 'officers'}
                <div class="form-group">
                  <label>Họ Tên</label>
                  <input type="text" bind:value={formData.fullName} required />
                </div>
                <div class="form-group">
                  <label>Chức Vụ</label>
                  <input type="text" bind:value={formData.position} required />
                </div>
                <div class="form-group">
                  <label>Phòng Ban</label>
                  <input type="text" bind:value={formData.department} required />
                </div>
                <div class="form-group">
                  <label>Điện Thoại</label>
                  <input type="tel" bind:value={formData.phone} required />
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" bind:value={formData.email} required />
                </div>
              {:else if activeTab === 'procedures'}
                <div class="form-group">
                  <label>Tên Thủ Tục</label>
                  <input type="text" bind:value={formData.name} required />
                </div>
                <div class="form-group">
                  <label>Mã</label>
                  <input type="text" bind:value={formData.code} required />
                </div>
                <div class="form-group">
                  <label>Phòng Ban</label>
                  <input type="text" bind:value={formData.department} required />
                </div>
                <div class="form-group">
                  <label>Thời Hạn (ngày)</label>
                  <input type="number" bind:value={formData.deadline} required />
                </div>
              {/if}
              <div class="form-actions">
                <button type="button" class="btn-secondary" on:click={closeModal}>Hủy</button>
                <button type="submit" class="btn-primary">Lưu</button>
              </div>
            </form>
          {:else}
            <div class="detail-view">
              {#each Object.entries(formData) as [key, value]}
                <div class="detail-row">
                  <span class="detail-label">{key}:</span>
                  <span class="detail-value">{value}</span>
                </div>
              {/each}
            </div>
          {/if}
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .government-page {
    min-height: 100vh;
    background: #f1f5f9;
  }

  .page-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    padding: 2rem;
  }

  .header-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .header-title h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
  }

  .header-title p {
    margin: 0;
    opacity: 0.9;
  }

  .btn-primary {
    background: white;
    color: #1e3a8a;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  .btn-secondary {
    background: #e2e8f0;
    color: #475569;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
  }

  .tabs {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 2rem;
    display: flex;
    gap: 0.5rem;
    max-width: 1400px;
    margin: 0 auto;
  }

  .tab {
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    font-size: 0.95rem;
    color: #64748b;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
  }

  .tab:hover {
    color: #1e3a8a;
  }

  .tab.active {
    color: #1e3a8a;
    border-bottom-color: #3b82f6;
    font-weight: 600;
  }

  .main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
  }

  .error-message {
    background: #fee2e2;
    color: #991b1b;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .error-message button {
    background: #991b1b;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
  }

  .loading {
    text-align: center;
    padding: 3rem;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    margin: 0 auto 1rem;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .data-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .section-header h2 {
    margin: 0;
    color: #1e293b;
    font-size: 1.25rem;
  }

  .search-box {
    display: flex;
    gap: 0.5rem;
  }

  .search-box input {
    padding: 0.5rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    width: 250px;
  }

  .btn-search {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
  }

  .data-table {
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
  }

  th {
    background: #f8fafc;
    font-weight: 600;
    color: #475569;
    font-size: 0.875rem;
  }

  td {
    color: #334155;
  }

  .badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .status-active { background: #dcfce7; color: #166534; }
  .status-inactive { background: #fee2e2; color: #991b1b; }
  .status-pending { background: #fef3c7; color: #92400e; }
  .status-approved { background: #dcfce7; color: #166534; }
  .status-draft { background: #f1f5f9; color: #475569; }
  .status-rejected { background: #fee2e2; color: #991b1b; }
  .status-default { background: #f1f5f9; color: #475569; }

  .btn-action {
    background: none;
    border: none;
    padding: 0.25rem;
    cursor: pointer;
    font-size: 1rem;
  }

  .cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }

  .procedure-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
  }

  .procedure-code {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 600;
  }

  .procedure-card h3 {
    margin: 0 0 1rem 0;
    color: #1e293b;
    font-size: 1.1rem;
  }

  .procedure-info p {
    margin: 0.25rem 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .card-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
  }

  .documents-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .document-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
  }

  .doc-icon {
    font-size: 2rem;
  }

  .doc-info {
    flex: 1;
  }

  .doc-info h4 {
    margin: 0 0 0.25rem 0;
    color: #1e293b;
  }

  .doc-info p {
    margin: 0;
    font-size: 0.875rem;
    color: #64748b;
  }

  .doc-type {
    font-weight: 600;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .stat-card.primary { border-left: 4px solid #3b82f6; }
  .stat-card.success { border-left: 4px solid #22c55e; }
  .stat-card.warning { border-left: 4px solid #f59e0b; }
  .stat-card.danger { border-left: 4px solid #ef4444; }

  .stat-icon {
    font-size: 2.5rem;
  }

  .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
  }

  .stat-label {
    color: #64748b;
    font-size: 0.875rem;
  }

  .charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1rem;
  }

  .chart-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .chart-card h3 {
    margin: 0 0 1rem 0;
    color: #1e293b;
  }

  .chart-placeholder {
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .bar-chart {
    display: flex;
    align-items: flex-end;
    gap: 1rem;
    height: 100%;
  }

  .bar {
    width: 40px;
    background: linear-gradient(to top, #3b82f6, #60a5fa);
    border-radius: 4px 4px 0 0;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-bottom: 0.5rem;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .donut-chart {
    position: relative;
    width: 120px;
    height: 120px;
  }

  .donut-segment {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: conic-gradient(#22c55e var(--p), #e2e8f0 0);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #166534;
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
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow: auto;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .modal-header h3 {
    margin: 0;
    color: #1e293b;
  }

  .modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #64748b;
    cursor: pointer;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #475569;
    font-weight: 500;
  }

  .form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
  }

  .form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
  }

  .detail-view {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .detail-row {
    display: flex;
    gap: 1rem;
  }

  .detail-label {
    font-weight: 600;
    color: #475569;
    min-width: 100px;
  }

  .detail-value {
    color: #1e293b;
  }

  @media (max-width: 768px) {
    .header-content {
      flex-direction: column;
      gap: 1rem;
      text-align: center;
    }

    .tabs {
      overflow-x: auto;
      padding: 0 1rem;
    }

    .main-content {
      padding: 1rem;
    }

    .section-header {
      flex-direction: column;
      gap: 1rem;
    }

    .search-box input {
      width: 100%;
    }

    .charts-section {
      grid-template-columns: 1fr;
    }
  }
</style>
