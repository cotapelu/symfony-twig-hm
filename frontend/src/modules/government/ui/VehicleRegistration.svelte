<script lang="ts">
  import { writable } from 'svelte/store';

  interface Vehicle {
    id: string;
    licensePlate: string;
    ownerName: string;
    vehicleType: 'car' | 'motorcycle' | 'truck' | 'other';
    brand: string;
    model: string;
    year: number;
    registrationDate: Date;
    expirationDate: Date;
    status: 'active' | 'expired' | 'pending';
    ward: string;
  }

  let vehicles = writable<Vehicle[]>([
    { id: '1', licensePlate: '51A-123.45', ownerName: 'Nguyễn Văn A', vehicleType: 'car', brand: 'Toyota', model: 'Camry', year: 2020, registrationDate: new Date('2020-01-15'), expirationDate: new Date('2026-01-15'), status: 'active', ward: 'Phường X' },
    { id: '2', licensePlate: '51B-678.90', ownerName: 'Trần Thị B', vehicleType: 'motorcycle', brand: 'Honda', model: 'Vision', year: 2022, registrationDate: new Date('2022-06-20'), expirationDate: new Date('2027-06-20'), status: 'active', ward: 'Phường X' },
    { id: '3', licensePlate: '51C-111.22', ownerName: 'Lê Văn C', vehicleType: 'truck', brand: 'Isuzu', model: 'NPR', year: 2019, registrationDate: new Date('2019-03-10'), expirationDate: new Date('2025-03-10'), status: 'expired', ward: 'Phường Y' },
    { id: '4', licensePlate: '51D-333.44', ownerName: 'Phạm Thị D', vehicleType: 'car', brand: 'Hyundai', model: 'Accent', year: 2021, registrationDate: new Date('2021-08-05'), expirationDate: new Date('2026-08-05'), status: 'active', ward: 'Phường X' },
  ]);

  let activeTab: 'all' | 'active' | 'expired' | 'pending' = 'all';
  let selectedVehicle: Vehicle | null = null;
  let showAddModal = false;
  let newVehicle = { licensePlate: '', ownerName: '', vehicleType: 'car', brand: '', model: '', year: 2024 };

  const typeIcons: Record<string, string> = {
    car: '🚗',
    motorcycle: '🏍️',
    truck: '🚚',
    other: '🚙',
  };

  const statusColors: Record<string, string> = {
    active: '#10b981',
    expired: '#ef4444',
    pending: '#f59e0b',
  };

  function formatDate(date: Date): string {
    return date.toLocaleDateString('vi-VN');
  }

  function addVehicle() {
    const vehicle: Vehicle = {
      id: Math.random().toString(36).substring(2, 15),
      ...newVehicle,
      registrationDate: new Date(),
      expirationDate: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000),
      status: 'pending',
      ward: 'Phường X',
    };
    vehicles.update(v => [vehicle, ...v]);
    showAddModal = false;
    newVehicle = { licensePlate: '', ownerName: '', vehicleType: 'car', brand: '', model: '', year: 2024 };
  }

  $: filteredVehicles = $vehicles.filter(v => {
    if (activeTab === 'active') return v.status === 'active';
    if (activeTab === 'expired') return v.status === 'expired';
    if (activeTab === 'pending') return v.status === 'pending';
    return true;
  });

  $: stats = {
    total: $vehicles.length,
    active: $vehicles.filter(v => v.status === 'active').length,
    expired: $vehicles.filter(v => v.status === 'expired').length,
    pending: $vehicles.filter(v => v.status === 'pending').length,
  };
</script>

<div class="vehicle-container">
  <header class="vehicle-header">
    <div class="header-left">
      <h2>🚗 Quản lý Phương tiện</h2>
      <span class="subtitle">Danh sách phương tiện đăng ký</span>
    </div>
    <button class="header-btn primary" on:click={() => showAddModal = true}>
      ➕ Đăng ký phương tiện
    </button>
  </header>

  <div class="stats-bar">
    <div class="stat-item" class:active={activeTab === 'all'} on:click={() => activeTab = 'all'}>
      <span class="stat-value">{stats.total}</span>
      <span class="stat-label">Tất cả</span>
    </div>
    <div class="stat-item active" class:active={activeTab === 'active'} on:click={() => activeTab = 'active'}>
      <span class="stat-value">{stats.active}</span>
      <span class="stat-label">Hoạt động</span>
    </div>
    <div class="stat-item" class:active={activeTab === 'expired'} on:click={() => activeTab = 'expired'}>
      <span class="stat-value">{stats.expired}</span>
      <span class="stat-label">Hết hạn</span>
    </div>
    <div class="stat-item" class:active={activeTab === 'pending'} on:click={() => activeTab = 'pending'}>
      <span class="stat-value">{stats.pending}</span>
      <span class="stat-label">Chờ duyệt</span>
    </div>
  </div>

  <div class="vehicle-content">
    <div class="vehicle-list">
      {#each filteredVehicles as vehicle (vehicle.id)}
        <button 
          class="vehicle-card"
          class:selected={selectedVehicle?.id === vehicle.id}
          class:expired={vehicle.status === 'expired'}
          on:click={() => selectedVehicle = vehicle}
        >
          <div class="vehicle-icon">{typeIcons[vehicle.vehicleType]}</div>
          <div class="vehicle-info">
            <h4>{vehicle.licensePlate}</h4>
            <p>{vehicle.ownerName}</p>
            <p class="vehicle-meta">{vehicle.brand} {vehicle.model} • {vehicle.year}</p>
          </div>
          <span class="status-badge" style="background: {statusColors[vehicle.status]}">
            {vehicle.status === 'active' ? 'Hoạt động' : vehicle.status === 'expired' ? 'Hết hạn' : 'Chờ duyệt'}
          </span>
        </button>
      {:else}
        <div class="empty">Không có phương tiện</div>
      {/each}
    </div>

    <div class="vehicle-detail">
      {#if selectedVehicle}
        <div class="detail-card">
          <div class="detail-header">
            <span class="detail-icon">{typeIcons[selectedVehicle.vehicleType]}</span>
            <h3>{selectedVehicle.licensePlate}</h3>
            <span class="status-badge" style="background: {statusColors[selectedVehicle.status]}">
              {selectedVehicle.status === 'active' ? 'Hoạt động' : 'Hết hạn'}
            </span>
          </div>
          <div class="detail-body">
            <div class="info-row"><span>Chủ sở hữu:</span><strong>{selectedVehicle.ownerName}</strong></div>
            <div class="info-row"><span>Loại xe:</span><strong>{selectedVehicle.vehicleType}</strong></div>
            <div class="info-row"><span>Hãng:</span><strong>{selectedVehicle.brand}</strong></div>
            <div class="info-row"><span>Dòng xe:</span><strong>{selectedVehicle.model}</strong></div>
            <div class="info-row"><span>Năm sản xuất:</span><strong>{selectedVehicle.year}</strong></div>
            <div class="info-row"><span>Ngày đăng ký:</span><strong>{formatDate(selectedVehicle.registrationDate)}</strong></div>
            <div class="info-row"><span>Ngày hết hạn:</span><strong class:expired={selectedVehicle.status === 'expired'}>{formatDate(selectedVehicle.expirationDate)}</strong></div>
            <div class="info-row"><span>Phường/Xã:</span><strong>{selectedVehicle.ward}</strong></div>
          </div>
          <div class="detail-actions">
            <button class="action-btn">✏️ Sửa</button>
            <button class="action-btn">📄 Gia hạn</button>
            <button class="action-btn danger">🗑️ Xóa</button>
          </div>
        </div>
      {:else}
        <div class="no-selection">Chọn phương tiện để xem chi tiết</div>
      {/if}
    </div>
  </div>
</div>

{#if showAddModal}
  <div class="modal-overlay" on:click={() => showAddModal = false}>
    <div class="modal-content" on:click|stopPropagation>
      <div class="modal-header">
        <h3>🚗 Đăng ký phương tiện mới</h3>
        <button on:click={() => showAddModal = false}>✕</button>
      </div>
      <div class="modal-body">
        <div class="form-group"><label>Biển số</label><input type="text" bind:value={newVehicle.licensePlate} placeholder="51A-123.45"></div>
        <div class="form-group"><label>Chủ sở hữu</label><input type="text" bind:value={newVehicle.ownerName} placeholder="Họ tên"></div>
        <div class="form-group"><label>Loại xe</label>
          <select bind:value={newVehicle.vehicleType}>
            <option value="car">🚗 Ô tô</option>
            <option value="motorcycle">🏍️ Xe máy</option>
            <option value="truck">🚚 Xe tải</option>
            <option value="other">🚙 Khác</option>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Hãng</label><input type="text" bind:value={newVehicle.brand} placeholder="Toyota"></div>
          <div class="form-group"><label>Dòng</label><input type="text" bind:value={newVehicle.model} placeholder="Camry"></div>
        </div>
        <div class="form-group"><label>Năm SX</label><input type="number" bind:value={newVehicle.year}></div>
      </div>
      <div class="modal-footer">
        <button class="btn secondary" on:click={() => showAddModal = false}>Hủy</button>
        <button class="btn primary" on:click={addVehicle}>Đăng ký</button>
      </div>
    </div>
  </div>
{/if}

<style>
  .vehicle-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .vehicle-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .header-left h2 { margin: 0; font-size: 1.25rem; color: #1e293b; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .header-btn { padding: 10px 16px; border: 1px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; font-size: 0.875rem; }
  .header-btn.primary { background: #3b82f6; color: white; border-color: #3b82f6; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; cursor: pointer; }
  .stat-item.active { background: #d1fae5; }
  .stat-item.active .stat-value { color: #059669; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .vehicle-content { display: flex; flex: 1; overflow: hidden; }
  .vehicle-list { width: 60%; overflow-y: auto; padding: 16px; background: white; border-right: 1px solid #e2e8f0; }
  .vehicle-card { display: flex; align-items: center; gap: 12px; padding: 16px; background: #f8fafc; border: none; border-radius: 12px; margin-bottom: 8px; cursor: pointer; width: 100%; text-align: left; }
  .vehicle-card:hover { background: #f1f5f9; }
  .vehicle-card.selected { background: #eff6ff; border: 2px solid #3b82f6; }
  .vehicle-card.expired { opacity: 0.7; }
  .vehicle-icon { font-size: 1.5rem; }
  .vehicle-info { flex: 1; }
  .vehicle-info h4 { margin: 0; font-size: 0.9375rem; color: #1e293b; }
  .vehicle-info p { margin: 2px 0 0; font-size: 0.8rem; color: #64748b; }
  .vehicle-meta { font-size: 0.7rem !important; }
  .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; color: white; }
  .vehicle-detail { flex: 1; padding: 20px; background: #f8fafc; }
  .detail-card { background: white; border-radius: 12px; padding: 20px; }
  .detail-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
  .detail-icon { font-size: 2rem; }
  .detail-header h3 { flex: 1; margin: 0; font-size: 1.25rem; }
  .detail-body { margin-bottom: 20px; }
  .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
  .info-row span { color: #64748b; font-size: 0.875rem; }
  .info-row strong { color: #1e293b; font-size: 0.875rem; }
  .info-row strong.expired { color: #ef4444; }
  .detail-actions { display: flex; gap: 8px; }
  .action-btn { flex: 1; padding: 10px; border: 1px solid #e2e8f0; background: white; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
  .action-btn.danger { background: #fee2e2; border-color: #ef4444; color: #ef4444; }
  .no-selection { text-align: center; padding: 60px; color: #64748b; }
  .empty { text-align: center; padding: 40px; color: #64748b; }
  .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
  .modal-content { background: white; border-radius: 16px; width: 90%; max-width: 450px; }
  .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; }
  .modal-header h3 { margin: 0; }
  .modal-header button { background: transparent; border: none; font-size: 1.25rem; cursor: pointer; }
  .modal-body { padding: 20px; }
  .form-group { margin-bottom: 12px; }
  .form-group label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; }
  .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px; }
  .form-row { display: flex; gap: 12px; }
  .form-row .form-group { flex: 1; }
  .modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding: 16px 20px; border-top: 1px solid #e2e8f0; }
  .btn { padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.875rem; }
  .btn.secondary { background: #f1f5f9; border: 1px solid #e2e8f0; }
  .btn.primary { background: #3b82f6; color: white; border: none; }
</style>
