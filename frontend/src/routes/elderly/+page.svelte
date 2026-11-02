<script lang="ts">
  import { onMount } from 'svelte';

  let activeTab = 'list';
  let loading = false;
  let showModal = false;
  let selectedElderly: any = null;

  let elderlyList: any[] = [];
  let supportRequests: any[] = [];

  let stats = {
    total: 0,
    livingAlone: 0,
    needingCare: 0,
    receivingAid: 0
  };

  let formData = {
    fullName: '',
    birthYear: '',
    gender: '',
    address: '',
    phone: '',
    livingAlone: false,
    healthStatus: '',
    monthlyIncome: 0,
    notes: ''
  };

  const API_BASE = 'http://localhost:8080/api/elderly';

  onMount(() => {
    loadData();
  });

  async function loadData() {
    loading = true;
    elderlyList = generateMockElderly();
    supportRequests = generateMockRequests();
    calculateStats();
    loading = false;
  }

  function generateMockElderly() {
    return [
      { id: '1', fullName: 'Nguyễn Văn A', birthYear: 1945, gender: 'Nam', address: '12 Nguyễn Trãi', phone: '0912345678', livingAlone: true, healthStatus: 'Khỏe mạnh', monthlyIncome: 2500000, status: 'ACTIVE' },
      { id: '2', fullName: 'Trần Thị B', birthYear: 1948, gender: 'Nữ', address: '34 Lý Thường Kiệt', phone: '0912345679', livingAlone: true, healthStatus: 'Cần hỗ trợ', monthlyIncome: 2000000, status: 'ACTIVE' },
      { id: '3', fullName: 'Lê Văn C', birthYear: 1950, gender: 'Nam', address: '56 Hai Bà Trưng', phone: '0912345680', livingAlone: false, healthStatus: 'Bệnh mãn tính', monthlyIncome: 3500000, status: 'ACTIVE' },
      { id: '4', fullName: 'Phạm Thị D', birthYear: 1952, gender: 'Nữ', address: '78 Đội Cấn', phone: '0912345681', livingAlone: true, healthStatus: 'Khỏe mạnh', monthlyIncome: 1800000, status: 'ACTIVE' },
      { id: '5', fullName: 'Hoàng Văn E', birthYear: 1946, gender: 'Nam', address: '90 Ngọc Hà', phone: '0912345682', livingAlone: false, healthStatus: 'Cần hỗ trợ', monthlyIncome: 3000000, status: 'ACTIVE' },
      { id: '6', fullName: 'Vũ Thị F', birthYear: 1955, gender: 'Nữ', address: '11 Kim Mã', phone: '0912345683', livingAlone: true, healthStatus: 'Khỏe mạnh', monthlyIncome: 2200000, status: 'ACTIVE' }
    ];
  }

  function generateMockRequests() {
    return [
      { id: '1', elderlyName: 'Nguyễn Văn A', type: 'health', description: 'Cần hỗ trợ khám bệnh định kỳ', status: 'PENDING', createdAt: '2026-02-14' },
      { id: '2', elderlyName: 'Trần Thị B', type: 'living', description: 'Cần sửa chữa nhà cửa', status: 'PROCESSING', createdAt: '2026-02-13' },
      { id: '3', elderlyName: 'Phạm Thị D', type: 'financial', description: 'Xin hỗ trợ chi phí thuốc', status: 'RESOLVED', createdAt: '2026-02-10' }
    ];
  }

  function calculateStats() {
    stats = {
      total: elderlyList.length,
      livingAlone: elderlyList.filter(e => e.livingAlone).length,
      needingCare: elderlyList.filter(e => e.healthStatus !== 'Khỏe mạnh').length,
      receivingAid: supportRequests.filter(r => r.status === 'RESOLVED').length
    };
  }

  async function createElderly() {
    const newElderly = {
      id: String(Date.now()),
      ...formData,
      status: 'ACTIVE'
    };
    elderlyList = [newElderly, ...elderlyList];
    calculateStats();
    showModal = false;
    formData = { fullName: '', birthYear: '', gender: '', address: '', phone: '', livingAlone: false, healthStatus: '', monthlyIncome: 0, notes: '' };
  }

  function getStatusBadge(status: string) {
    switch (status) {
      case 'ACTIVE': return { bg: '#10b981', text: 'Đang theo dõi' };
      case 'INACTIVE': return { bg: '#6b7280', text: 'Ngừng theo dõi' };
      default: return { bg: '#6b7280', text: status };
    }
  }

  function getHealthColor(status: string) {
    switch (status) {
      case 'Khỏe mạnh': return '#10b981';
      case 'Cần hỗ trợ': return '#f59e0b';
      case 'Bệnh mãn tính': return '#ef4444';
      default: return '#6b7280';
    }
  }

  function formatCurrency(amount: number) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
  }

  function getAge(birthYear: number) {
    return new Date().getFullYear() - birthYear;
  }
</script>

<div class="elderly-page">
  <header class="page-header">
    <h1>👴 Quản Lý Người Cao Tuổi</h1>
    <button class="btn-primary" on:click={() => showModal = true}>
      + Thêm Mới
    </button>
  </header>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-value">{stats.total}</div>
      <div class="stat-label">Tổng số NCT</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🏠</div>
      <div class="stat-value">{stats.livingAlone}</div>
      <div class="stat-label">Sống một mình</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💊</div>
      <div class="stat-value">{stats.needingCare}</div>
      <div class="stat-label">Cần chăm sóc</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💰</div>
      <div class="stat-value">{stats.receivingAid}</div>
      <div class="stat-label">Đã hỗ trợ</div>
    </div>
  </div>

  <div class="tabs">
    <button class="tab" class:active={activeTab === 'list'} on:click={() => activeTab = 'list'}>
      Danh sách
    </button>
    <button class="tab" class:active={activeTab === 'requests'} on:click={() => activeTab = 'requests'}>
      Yêu cầu hỗ trợ
    </button>
    <button class="tab" class:active={activeTab === 'visits'} on:click={() => activeTab = 'visits'}>
      Lịch thăm nom
    </button>
  </div>

  {#if activeTab === 'list'}
    <div class="content-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Họ tên</th>
            <th>Tuổi</th>
            <th>Giới</th>
            <th>Địa chỉ</th>
            <th>Sống một mình</th>
            <th>Sức khỏe</th>
            <th>Thu nhập</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          {#each elderlyList as elderly}
            <tr>
              <td>
                <div class="person-cell">
                  <div class="avatar">{elderly.fullName.charAt(0)}</div>
                  <div>
                    <div class="name">{elderly.fullName}</div>
                    <div class="phone">{elderly.phone}</div>
                  </div>
                </div>
              </td>
              <td>{getAge(elderly.birthYear)}</td>
              <td>{elderly.gender}</td>
              <td>{elderly.address}</td>
              <td>
                {#if elderly.livingAlone}
                  <span class="badge warning">Có</span>
                {:else}
                  <span class="badge">Không</span>
                {/if}
              </td>
              <td>
                <span class="health-badge" style="background: {getHealthColor(elderly.healthStatus)}">
                  {elderly.healthStatus}
                </span>
              </td>
              <td>{formatCurrency(elderly.monthlyIncome)}</td>
              <td>
                <span class="status-badge" style="background: {getStatusBadge(elderly.status).bg}">
                  {getStatusBadge(elderly.status).text}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button class="btn-icon" title="Xem chi tiết">👁️</button>
                  <button class="btn-icon" title="Chỉnh sửa">✏️</button>
                  <button class="btn-icon" title="Lên lịch thăm nom">📅</button>
                </div>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {:else if activeTab === 'requests'}
    <div class="content-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Người cao tuổi</th>
            <th>Loại yêu cầu</th>
            <th>Mô tả</th>
            <th>Ngày gửi</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          {#each supportRequests as request}
            <tr>
              <td>{request.elderlyName}</td>
              <td>
                <span class="type-badge">
                  {request.type === 'health' ? 'Y tế' : 
                   request.type === 'living' ? 'Sinh hoạt' : 'Tài chính'}
                </span>
              </td>
              <td>{request.description}</td>
              <td>{request.createdAt}</td>
              <td>
                <span class="status-badge" style="background: 
                  {request.status === 'PENDING' ? '#f59e0b' : 
                   request.status === 'PROCESSING' ? '#3b82f6' : '#10b981'}">
                  {request.status === 'PENDING' ? 'Chờ' : 
                   request.status === 'PROCESSING' ? 'Đang xử lý' : 'Hoàn thành'}
                </span>
              </td>
              <td>
                <button class="btn-action">Xử lý</button>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {:else}
    <div class="content-card">
      <div class="empty-state">
        <div class="empty-icon">📅</div>
        <h3>Lịch thăm nom</h3>
        <p>Lên lịch thăm nom định kỳ cho người cao tuổi</p>
        <button class="btn-primary">+ Tạo lịch thăm nom</button>
      </div>
    </div>
  {/if}

  {#if showModal}
    <div class="modal-overlay" on:click={() => showModal = false}>
      <div class="modal" on:click|stopPropagation>
        <div class="modal-header">
          <h2>Thêm Người Cao Tuổi Mới</h2>
          <button class="close-btn" on:click={() => showModal = false}>×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Họ tên *</label>
            <input type="text" bind:value={formData.fullName} placeholder="Nhập họ tên" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Năm sinh *</label>
              <input type="number" bind:value={formData.birthYear} placeholder="1960" />
            </div>
            <div class="form-group">
              <label>Giới tính</label>
              <select bind:value={formData.gender}>
                <option value="">Chọn</option>
                <option value="Nam">Nam</option>
                <option value="Nữ">Nữ</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Địa chỉ *</label>
            <input type="text" bind:value={formData.address} placeholder="Số nhà, đường, phường" />
          </div>
          <div class="form-group">
            <label>Số điện thoại</label>
            <input type="tel" bind:value={formData.phone} placeholder="0912345678" />
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" bind:checked={formData.livingAlone} />
              Sống một mình
            </label>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Tình trạng sức khỏe</label>
              <select bind:value={formData.healthStatus}>
                <option value="">Chọn</option>
                <option value="Khỏe mạnh">Khỏe mạnh</option>
                <option value="Cần hỗ trợ">Cần hỗ trợ</option>
                <option value="Bệnh mãn tính">Bệnh mãn tính</option>
              </select>
            </div>
            <div class="form-group">
              <label>Thu nhập (VNĐ/tháng)</label>
              <input type="number" bind:value={formData.monthlyIncome} />
            </div>
          </div>
          <div class="form-group">
            <label>Ghi chú</label>
            <textarea bind:value={formData.notes} rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" on:click={() => showModal = false}>Hủy</button>
          <button class="btn-primary" on:click={createElderly}>Lưu</button>
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .elderly-page {
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

  .page-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
  }

  .btn-primary {
    background: #3b82f6;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
  }

  .stat-icon {
    font-size: 32px;
    margin-bottom: 8px;
  }

  .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
  }

  .stat-label {
    font-size: 13px;
    color: #6b7280;
    margin-top: 4px;
  }

  .tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    background: white;
    padding: 4px;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .tab {
    flex: 1;
    padding: 12px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 8px;
    font-weight: 500;
    color: #6b7280;
  }

  .tab.active {
    background: #3b82f6;
    color: white;
  }

  .content-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
  }

  .data-table th {
    background: #f9fafb;
    padding: 14px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
  }

  .data-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
  }

  .data-table tr:hover {
    background: #f9fafb;
  }

  .person-cell {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #6b7280;
  }

  .name {
    font-weight: 500;
    color: #1f2937;
  }

  .phone {
    font-size: 12px;
    color: #9ca3af;
  }

  .badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    background: #f3f4f6;
    color: #6b7280;
  }

  .badge.warning {
    background: #fef3c7;
    color: #d97706;
  }

  .health-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    color: white;
  }

  .status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    color: white;
  }

  .type-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    background: #dbeafe;
    color: #2563eb;
  }

  .action-buttons {
    display: flex;
    gap: 8px;
  }

  .btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    font-size: 16px;
  }

  .btn-action {
    padding: 6px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    font-size: 13px;
  }

  .empty-state {
    text-align: center;
    padding: 60px 20px;
  }

  .empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
  }

  .empty-state h3 {
    margin: 0 0 8px 0;
    color: #1f2937;
  }

  .empty-state p {
    color: #6b7280;
    margin: 0 0 20px 0;
  }

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

  .modal {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 560px;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
  }

  .modal-header h2 {
    margin: 0;
    font-size: 20px;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #9ca3af;
  }

  .modal-body {
    padding: 24px;
  }

  .form-group {
    margin-bottom: 16px;
  }

  .form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .checkbox-label {
    display: flex !important;
    align-items: center;
    gap: 8px;
    cursor: pointer;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
  }

  .btn-secondary {
    padding: 10px 20px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    cursor: pointer;
  }
</style>