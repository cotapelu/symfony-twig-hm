<script lang="ts">
  import { onMount } from 'svelte';
  
  interface Officer {
    id: string;
    fullName: string;
    position: string;
    department: string;
    phone: string;
    email: string;
    status: 'active' | 'inactive' | 'on_leave';
    joinDate: string;
    avatar?: string;
  }

  let officers: Officer[] = [];
  let loading = true;
  let searchQuery = '';
  let selectedDepartment = 'all';
  let selectedStatus = 'all';
  let showAddModal = false;
  let editingOfficer: Officer | null = null;

  const departments = [
    'Văn phòng UBND',
    'Tài chính - Kế toán',
    'Tư pháp',
    'Xây dựng',
    'Địa chính',
    'Y tế',
    'Giáo dục',
    'Văn hóa - Thông tin',
    'An ninh - Trật tự',
    'Phát triển kinh tế'
  ];

  const positions = [
    'Chủ tịch',
    'Phó Chủ tịch',
    'Thư ký',
    'Chuyên viên',
    'Cán bộ',
    'Nhân viên'
  ];

  onMount(() => {
    loadOfficers();
  });

  async function loadOfficers() {
    loading = true;
    try {
      const response = await fetch('/api/government/officers', {
        headers: { 'X-User-ID': 'admin' }
      });
      if (response.ok) {
        const data = await response.json();
        officers = data.officers || [];
      } else {
        officers = generateMockOfficers();
      }
    } catch {
      officers = generateMockOfficers();
    } finally {
      loading = false;
    }
  }

  function generateMockOfficers(): Officer[] {
    return [
      { id: '1', fullName: 'Nguyễn Văn A', position: 'Chủ tịch', department: 'Văn phòng UBND', phone: '0912345678', email: 'nvana@phuongxa.vn', status: 'active', joinDate: '2020-01-15' },
      { id: '2', fullName: 'Trần Thị B', position: 'Phó Chủ tịch', department: 'Tài chính - Kế toán', phone: '0912345679', email: 'ttb@phuongxa.vn', status: 'active', joinDate: '2019-03-20' },
      { id: '3', fullName: 'Lê Văn C', position: 'Chuyên viên', department: 'Tư pháp', phone: '0912345680', email: 'lvc@phuongxa.vn', status: 'active', joinDate: '2021-06-10' },
      { id: '4', fullName: 'Phạm Thị D', position: 'Cán bộ', department: 'Y tế', phone: '0912345681', email: 'ptd@phuongxa.vn', status: 'on_leave', joinDate: '2022-02-01' },
      { id: '5', fullName: 'Hoàng Văn E', position: 'Nhân viên', department: 'Văn hóa - Thông tin', phone: '0912345682', email: 'hve@phuongxa.vn', status: 'active', joinDate: '2023-01-10' },
    ];
  }

  $: filteredOfficers = officers.filter(officer => {
    const matchSearch = !searchQuery || 
      officer.fullName.toLowerCase().includes(searchQuery.toLowerCase()) ||
      officer.email.toLowerCase().includes(searchQuery.toLowerCase()) ||
      officer.phone.includes(searchQuery);
    const matchDept = selectedDepartment === 'all' || officer.department === selectedDepartment;
    const matchStatus = selectedStatus === 'all' || officer.status === selectedStatus;
    return matchSearch && matchDept && matchStatus;
  });

  function getStatusColor(status: string): string {
    switch (status) {
      case 'active': return '#10b981';
      case 'inactive': return '#6b7280';
      case 'on_leave': return '#f59e0b';
      default: return '#6b7280';
    }
  }

  function getStatusLabel(status: string): string {
    switch (status) {
      case 'active': return 'Đang hoạt động';
      case 'inactive': return 'Không hoạt động';
      case 'on_leave': return 'Nghỉ phép';
      default: return status;
    }
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('vi-VN');
  }

  function openAddModal() {
    showAddModal = true;
    editingOfficer = null;
  }

  function openEditModal(officer: Officer) {
    editingOfficer = { ...officer };
    showAddModal = true;
  }

  function closeModal() {
    showAddModal = false;
    editingOfficer = null;
  }

  async function saveOfficer(event: SubmitEvent) {
    const formData = new FormData(event.target as HTMLFormElement);
    const officerData = {
      fullName: formData.get('fullName'),
      position: formData.get('position'),
      department: formData.get('department'),
      phone: formData.get('phone'),
      email: formData.get('email'),
    };

    if (editingOfficer) {
      officers = officers.map(o => o.id === editingOfficer!.id ? { ...o, ...officerData } : o);
    } else {
      const newOfficer: Officer = {
        ...officerData as any,
        id: String(Date.now()),
        status: 'active',
        joinDate: new Date().toISOString().split('T')[0]
      };
      officers = [...officers, newOfficer];
    }

    closeModal();
  }

  async function toggleStatus(officer: Officer) {
    const newStatus = officer.status === 'active' ? 'inactive' : 'active';
    officers = officers.map(o => o.id === officer.id ? { ...o, status: newStatus } : o);
  }
</script>

<div class="officer-management">
  <header class="page-header">
    <div class="header-content">
      <h1>👔 Quản lý Cán bộ</h1>
      <p>Quản lý thông tin cán bộ, nhân viên UBND phường/xã</p>
    </div>
    <button class="btn-primary" onclick={openAddModal}>
      <span>+</span> Thêm cán bộ
    </button>
  </header>

  <div class="filters">
    <div class="search-box">
      <svg class="search-icon" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
      </svg>
      <input 
        type="text" 
        placeholder="Tìm theo tên, email, số điện thoại..." 
        bind:value={searchQuery}
      />
    </div>

    <select bind:value={selectedDepartment}>
      <option value="all">Tất cả phòng ban</option>
      {#each departments as dept}
        <option value={dept}>{dept}</option>
      {/each}
    </select>

    <select bind:value={selectedStatus}>
      <option value="all">Tất cả trạng thái</option>
      <option value="active">Đang hoạt động</option>
      <option value="inactive">Không hoạt động</option>
      <option value="on_leave">Nghỉ phép</option>
    </select>
  </div>

  <div class="stats-row">
    <div class="stat-item">
      <span class="stat-value">{filteredOfficers.length}</span>
      <span class="stat-label">Tổng số</span>
    </div>
    <div class="stat-item active">
      <span class="stat-value">{officers.filter(o => o.status === 'active').length}</span>
      <span class="stat-label">Đang hoạt động</span>
    </div>
    <div class="stat-item on-leave">
      <span class="stat-value">{officers.filter(o => o.status === 'on_leave').length}</span>
      <span class="stat-label">Nghỉ phép</span>
    </div>
    <div class="stat-item inactive">
      <span class="stat-value">{officers.filter(o => o.status === 'inactive').length}</span>
      <span class="stat-label">Không hoạt động</span>
    </div>
  </div>

  {#if loading}
    <div class="loading">Đang tải...</div>
  {:else if filteredOfficers.length === 0}
    <div class="empty-state">
      <div class="empty-icon">👔</div>
      <h3>Không tìm thấy cán bộ</h3>
      <p>Thử thay đổi bộ lọc hoặc thêm cán bộ mới</p>
    </div>
  {:else}
    <div class="officers-grid">
      {#each filteredOfficers as officer (officer.id)}
        <div class="officer-card">
          <div class="officer-header">
            <div class="avatar">
              {officer.fullName.charAt(0)}
            </div>
            <div class="status-badge" style="background: {getStatusColor(officer.status)}">
              {getStatusLabel(officer.status)}
            </div>
          </div>
          
          <div class="officer-info">
            <h3>{officer.fullName}</h3>
            <p class="position">{officer.position}</p>
            <p class="department">{officer.department}</p>
          </div>

          <div class="officer-contact">
            <div class="contact-item">
              <svg viewBox="0 0 20 20" fill="currentColor">
                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
              </svg>
              <span>{officer.phone}</span>
            </div>
            <div class="contact-item">
              <svg viewBox="0 0 20 20" fill="currentColor">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
              </svg>
              <span>{officer.email}</span>
            </div>
          </div>

          <div class="officer-meta">
            <span>Ngày vào: {formatDate(officer.joinDate)}</span>
          </div>

          <div class="officer-actions">
            <button class="btn-icon" onclick={() => openEditModal(officer)} title="Chỉnh sửa">
              <svg viewBox="0 0 20 20" fill="currentColor">
                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
              </svg>
            </button>
            <button class="btn-icon" onclick={() => toggleStatus(officer)} title={officer.status === 'active' ? 'Tạm dừng' : 'Kích hoạt'}>
              <svg viewBox="0 0 20 20" fill="currentColor">
                {#if officer.status === 'active'}
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd" />
                {:else}
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                {/if}
              </svg>
            </button>
            <button class="btn-icon danger" title="Xóa">
              <svg viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

{#if showAddModal}
  <div class="modal-overlay" onclick={closeModal}>
    <div class="modal" onclick={(e) => e.stopPropagation()}>
      <div class="modal-header">
        <h2>{editingOfficer ? 'Chỉnh sửa cán bộ' : 'Thêm cán bộ mới'}</h2>
        <button class="close-btn" onclick={closeModal}>&times;</button>
      </div>
      
      <form onsubmit={(e) => { e.preventDefault(); saveOfficer(e); }}>
        <div class="form-group">
          <label for="fullName">Họ và tên *</label>
          <input type="text" id="fullName" name="fullName" required value={editingOfficer?.fullName || ''} />
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="position">Chức vụ *</label>
            <select id="position" name="position" required value={editingOfficer?.position || ''}>
              <option value="">Chọn chức vụ</option>
              {#each positions as pos}
                <option value={pos}>{pos}</option>
              {/each}
            </select>
          </div>

          <div class="form-group">
            <label for="department">Phòng ban *</label>
            <select id="department" name="department" required value={editingOfficer?.department || ''}>
              <option value="">Chọn phòng ban</option>
              {#each departments as dept}
                <option value={dept}>{dept}</option>
              {/each}
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="phone">Số điện thoại *</label>
            <input type="tel" id="phone" name="phone" required value={editingOfficer?.phone || ''} />
          </div>

          <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value={editingOfficer?.email || ''} />
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn-secondary" onclick={closeModal}>Hủy</button>
          <button type="submit" class="btn-primary">
            {editingOfficer ? 'Lưu thay đổi' : 'Thêm mới'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .officer-management {
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

  .header-content h1 {
    margin: 0 0 8px;
    font-size: 1.5rem;
    color: #1e293b;
  }

  .header-content p {
    margin: 0;
    color: #64748b;
  }

  .btn-primary {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .filters {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
  }

  .search-box {
    flex: 1;
    min-width: 280px;
    position: relative;
  }

  .search-box input {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
  }

  .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: #9ca3af;
  }

  .filters select {
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    font-size: 0.875rem;
    min-width: 180px;
  }

  .stats-row {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-item {
    flex: 1;
    padding: 16px;
    background: white;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .stat-item.active { border-top: 3px solid #10b981; }
  .stat-item.on-leave { border-top: 3px solid #f59e0b; }
  .stat-item.inactive { border-top: 3px solid #6b7280; }

  .stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  .loading, .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
  }

  .empty-icon {
    font-size: 4rem;
    margin-bottom: 16px;
  }

  .empty-state h3 {
    margin: 0 0 8px;
    color: #1e293b;
  }

  .officers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
  }

  .officer-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .officer-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .officer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
  }

  .avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.25rem;
  }

  .status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    color: white;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .officer-info h3 {
    margin: 0 0 4px;
    font-size: 1.125rem;
    color: #1e293b;
  }

  .officer-info .position {
    margin: 0 0 4px;
    color: #667eea;
    font-weight: 500;
  }

  .officer-info .department {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .officer-contact {
    margin: 16px 0;
    padding: 12px 0;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
  }

  .contact-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: #64748b;
    font-size: 0.875rem;
  }

  .contact-item:last-child {
    margin-bottom: 0;
  }

  .contact-item svg {
    width: 16px;
    height: 16px;
    color: #9ca3af;
  }

  .officer-meta {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-bottom: 16px;
  }

  .officer-actions {
    display: flex;
    gap: 8px;
  }

  .btn-icon {
    flex: 1;
    padding: 8px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .btn-icon svg {
    width: 18px;
    height: 18px;
    color: #64748b;
  }

  .btn-icon:hover {
    background: #f8fafc;
    border-color: #667eea;
  }

  .btn-icon.danger:hover {
    border-color: #ef4444;
    background: #fef2f2;
  }

  .btn-icon.danger:hover svg {
    color: #ef4444;
  }

  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }

  .modal {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 560px;
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
  }

  .modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #64748b;
    cursor: pointer;
  }

  form {
    padding: 24px;
  }

  .form-group {
    margin-bottom: 16px;
  }

  .form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
  }

  .form-group input,
  .form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.875rem;
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
  }

  .btn-secondary {
    padding: 10px 20px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    color: #374151;
  }

  .btn-secondary:hover {
    background: #f8fafc;
  }

  @media (max-width: 768px) {
    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 16px;
    }

    .filters {
      flex-direction: column;
    }

    .search-box {
      min-width: 100%;
    }

    .stats-row {
      flex-wrap: wrap;
    }

    .stat-item {
      min-width: calc(50% - 8px);
    }

    .form-row {
      grid-template-columns: 1fr;
    }
  }
</style>
