<script lang="ts">
  let searchQuery = '';
  let activeCategory = 'all';

  let businesses = [
    { id: 1, name: 'Cửa hàng Tiện lợi ABC', category: 'retail', address: '12 Nguyễn Trãi', phone: '0912345678', status: 'active' },
    { id: 2, name: 'Nhà hàng Ngon', category: 'restaurant', address: '34 Lý Thường Kiệt', phone: '0912345679', status: 'active' },
    { id: 3, name: 'Tiệm nail Beauty', category: 'service', address: '56 Hai Bà Trưng', phone: '0912345680', status: 'active' },
    { id: 4, name: 'Cửa hàng điện tử', category: 'retail', address: '78 Đội Cấn', phone: '0912345681', status: 'suspended' },
    { id: 5, name: 'Quán cafe', category: 'restaurant', address: '90 Ngọc Hà', phone: '0912345682', status: 'active' },
    { id: 6, name: 'Tiệm sửa xe', category: 'service', address: '11 Kim Mã', phone: '0912345683', status: 'active' }
  ];

  let categories = [
    { id: 'all', name: 'Tất cả', icon: '🏪' },
    { id: 'retail', name: 'Bán lẻ', icon: '🛒' },
    { id: 'restaurant', name: 'Nhà hàng', icon: '🍽️' },
    { id: 'service', name: 'Dịch vụ', icon: '✂️' },
    { id: 'production', name: 'Sản xuất', icon: '🏭' }
  ];

  function getCategoryIcon(id: string) {
    return categories.find(c => c.id === id)?.icon || '🏪';
  }

  function getStatusColor(status: string) {
    return status === 'active' ? '#10b981' : '#ef4444';
  }
</script>

<div class="directory-page">
  <header class="page-header">
    <h1>🏪 Doanh Nghiệp - Hộ Kinh Doanh</h1>
  </header>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-value">{businesses.length}</div>
      <div class="stat-label">Tổng số</div>
    </div>
    <div class="stat-card active">
      <div class="stat-value">{businesses.filter(b => b.status === 'active').length}</div>
      <div class="stat-label">Hoạt động</div>
    </div>
    <div class="stat-card new">
      <div class="stat-value">+12</div>
      <div class="stat-label">Tháng này</div>
    </div>
  </div>

  <div class="search-box">
    <input type="text" placeholder="🔍 Tìm kiếm doanh nghiệp..." bind:value={searchQuery} />
  </div>

  <div class="categories-bar">
    {#each categories as cat}
      <button class="category-btn" class:active={activeCategory === cat.id} on:click={() => activeCategory = cat.id}>
        <span class="cat-icon">{cat.icon}</span>
        <span class="cat-name">{cat.name}</span>
      </button>
    {/each}
  </div>

  <div class="business-list">
    {#each businesses as business}
      <div class="business-card">
        <div class="business-icon">{getCategoryIcon(business.category)}</div>
        <div class="business-info">
          <div class="business-name">{business.name}</div>
          <div class="business-address">📍 {business.address}</div>
          <div class="business-phone">📞 {business.phone}</div>
        </div>
        <div class="business-status">
          <span class="status-badge" style="background: {getStatusColor(business.status)}">
            {business.status === 'active' ? 'Hoạt động' : 'Tạm ngừng'}
          </span>
        </div>
      </div>
    {/each}
  </div>

  <button class="add-business-btn">+ Đăng ký kinh doanh mới</button>
</div>

<style>
  .directory-page {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
    background: #f5f5f5;
    min-height: 100vh;
    padding-bottom: 80px;
  }

  .page-header h1 {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 16px 0;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 16px;
  }

  .stat-card {
    background: white;
    padding: 16px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
  }

  .stat-label {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
  }

  .stat-card.active .stat-value { color: #10b981; }
  .stat-card.new .stat-value { color: #3b82f6; }

  .search-box {
    margin-bottom: 16px;
  }

  .search-box input {
    width: 100%;
    padding: 14px 16px;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    box-sizing: border-box;
  }

  .categories-bar {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 16px;
  }

  .category-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 16px;
    background: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .category-btn.active {
    background: #3b82f6;
    color: white;
  }

  .cat-icon {
    font-size: 24px;
    margin-bottom: 4px;
  }

  .cat-name {
    font-size: 12px;
    font-weight: 500;
  }

  .business-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .business-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .business-icon {
    width: 50px;
    height: 50px;
    background: #f3f4f6;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
  }

  .business-info {
    flex: 1;
  }

  .business-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
  }

  .business-address, .business-phone {
    font-size: 13px;
    color: #6b7280;
  }

  .status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    color: white;
    font-size: 11px;
    font-weight: 500;
  }

  .add-business-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #10b981;
    color: white;
    border: none;
    font-size: 24px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    cursor: pointer;
  }
</style>