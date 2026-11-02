<script lang="ts">
  import { writable } from 'svelte/store';

  interface Service {
    id: string;
    name: string;
    category: string;
    description: string;
    processingTime: string;
    fee: number;
    online: boolean;
    status: 'active' | 'inactive';
  }

  let services = writable<Service[]>([
    { id: '1', name: 'Cấp CCCD', category: 'Tài liệu', description: 'Cấp căn cước công dân lần đầu', processingTime: '7 ngày', fee: 30000, online: true, status: 'active' },
    { id: '2', name: 'Đăng ký thường trú', category: 'Hộ khẩu', description: 'Đăng ký nơi thường trú mới', processingTime: '15 ngày', fee: 0, online: true, status: 'active' },
    { id: '3', name: 'Khai sinh', category: 'Hộ tịch', description: 'Đăng ký khai sinh cho trẻ', processingTime: '5 ngày', fee: 0, online: true, status: 'active' },
    { id: '4', name: 'Khai tử', category: 'Hộ tịch', description: 'Đăng ký khai tử', processingTime: '3 ngày', fee: 0, online: false, status: 'active' },
    { id: '5', name: 'Cấp GPLX', category: 'Giấy phép', description: 'Cấp giấy phép lái xe', processingTime: '10 ngày', fee: 500000, online: false, status: 'active' },
    { id: '6', name: 'Đăng ký kinh doanh', category: 'Kinh doanh', description: 'Đăng ký kinh doanh hộ gia đình', processingTime: '20 ngày', fee: 100000, online: true, status: 'active' },
  ]);

  $: stats = { total: $services.length, online: $services.filter(s => s.online).length };
</script>

<div class="service-container">
  <header class="service-header">
    <div><h2>🌐 Dịch vụ Công trực tuyến</h2><span class="subtitle">Danh mục dịch vụ hành chính công</span></div>
    <button class="primary">➕ Thêm dịch vụ</button>
  </header>
  <div class="stats-bar">
    <div class="stat-item"><span class="stat-value">{stats.total}</span><span class="stat-label">Tổng dịch vụ</span></div>
    <div class="stat-item"><span class="stat-value">{stats.online}</span><span class="stat-label">Trực tuyến</span></div>
  </div>
  <div class="service-content">
    <div class="services-grid">
      {#each $services as service}
        <div class="service-card">
          <div class="service-header-row">
            <span class="service-icon">📋</span>
            <div class="service-info">
              <h4>{service.name}</h4>
              <p class="category">{service.category}</p>
            </div>
          </div>
          <p class="description">{service.description}</p>
          <div class="service-meta">
            <div class="meta-item">
              <span class="label">⏱️ Thời gian:</span>
              <span class="value">{service.processingTime}</span>
            </div>
            <div class="meta-item">
              <span class="label">💵 Phí:</span>
              <span class="value">{service.fee === 0 ? 'Miễn phí' : service.fee.toLocaleString() + ' VNĐ'}</span>
            </div>
          </div>
          <div class="service-footer">
            {#if service.online}
              <span class="online-badge">🌐 Trực tuyến</span>
            {:else}
              <span class="offline-badge">📍 Trực tiếp</span>
            {/if}
            <button class="apply-btn">Nộp hồ sơ</button>
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .service-container { display: flex; flex-direction: column; height: 100%; background: #f1f5f9; }
  .service-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .service-header h2 { margin: 0; font-size: 1.25rem; }
  .subtitle { font-size: 0.875rem; color: #64748b; }
  .primary { padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .stats-bar { display: flex; gap: 16px; padding: 16px 24px; background: white; border-bottom: 1px solid #e2e8f0; }
  .stat-item { flex: 1; text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
  .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
  .stat-label { font-size: 0.75rem; color: #64748b; }
  .service-content { flex: 1; overflow-y: auto; padding: 20px; }
  .services-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
  .service-card { background: white; border-radius: 12px; padding: 16px; }
  .service-header-row { display: flex; gap: 12px; margin-bottom: 12px; }
  .service-icon { font-size: 2rem; }
  .service-info h4 { margin: 0; font-size: 1rem; }
  .service-info .category { margin: 4px 0 0; font-size: 0.8rem; color: #64748b; }
  .description { margin: 0 0 12px; font-size: 0.85rem; color: #374151; }
  .service-meta { margin-bottom: 12px; }
  .meta-item { display: flex; gap: 8px; padding: 4px 0; font-size: 0.85rem; }
  .meta-item .label { color: #64748b; }
  .meta-item .value { font-weight: 500; }
  .service-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #e2e8f0; }
  .online-badge { padding: 4px 10px; background: #dcfce7; color: #166534; border-radius: 12px; font-size: 0.75rem; }
  .offline-badge { padding: 4px 10px; background: #f1f5f9; color: #64748b; border-radius: 12px; font-size: 0.75rem; }
  .apply-btn { padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; }
</style>
