<script lang="ts">
  import { goto } from '$app/navigation';

  interface TTHCService {
    id: string;
    name: string;
    category: string;
    description: string;
    requirements: string[];
    processingTime: string;
    fee: string;
    status: string;
  }

  interface MyApplication {
    id: string;
    serviceName: string;
    submittedDate: string;
    status: string;
    progress: number;
  }

  const services: TTHCService[] = [
    { id: '1', name: 'Đăng ký thường trú', category: 'Hộ khẩu', description: 'Đăng ký thường trú tại phường', requirements: ['CMND/CCCD', 'Sổ hộ khẩu', 'Giấy chuyển khẩu'], processingTime: '15 ngày làm việc', fee: 'Miễn phí', status: 'active' },
    { id: '2', name: 'Cấp CCCD', category: 'CCCD', description: 'Cấp Căn cước công dân', requirements: ['CMND cũ (nếu có)', 'Giấy khai sinh', 'Ảnh thẻ'], processingTime: '30 ngày', fee: '30.000 VND', status: 'active' },
    { id: '3', name: 'Đăng ký kết hôn', category: 'Hộ tịch', description: 'Đăng ký kết hôn tại UBND phường', requirements: ['CMND/CCCD hai bên', 'Giấy xác nhận tình trạng hôn nhân', 'Ảnh 4x6'], processingTime: '10 ngày làm việc', fee: 'Miễn phí', status: 'active' },
    { id: '4', name: 'Khai sinh cho trẻ', category: 'Hộ tịch', description: 'Đăng ký khai sinh', requirements: ['Giấy chứng sinh', 'CMND/CCCD cha mẹ', 'Sổ hộ khẩu'], processingTime: '5 ngày làm việc', fee: 'Miễn phí', status: 'active' },
    { id: '5', name: 'Xác nhận tình trạng hôn nhân', category: 'Hộ tịch', description: 'Xác nhận hiện tại không có vợ/chồng', requirements: ['CMND/CCCD', 'Sổ hộ khẩu'], processingTime: '3 ngày làm việc', fee: 'Miễn phí', status: 'active' },
    { id: '6', name: 'Đăng ký tạm trú', category: 'Hộ khẩu', description: 'Đăng ký tạm trú tạm thời', requirements: ['CMND/CCCD', 'Hợp đồng thuê nhà', 'Đơn xin tạm trú'], processingTime: '7 ngày làm việc', fee: 'Miễn phí', status: 'active' },
    { id: '7', name: 'Cấp giấy xác nhận', category: 'Xác nhận', description: 'Cấp các loại giấy xác nhận khác', requirements: ['CMND/CCCD', 'Yêu cầu cụ thể'], processingTime: '5 ngày làm việc', fee: 'Theo quy định', status: 'active' },
    { id: '8', name: 'Đăng ký kinh doanh', category: 'Kinh doanh', description: 'Đăng ký kinh doanh tại địa phương', requirements: ['CMND/CCCD', 'Địa điểm kinh doanh', 'Hồ sơ pháp lý'], processingTime: '20 ngày làm việc', fee: 'Theo quy định', status: 'active' }
  ];

  const myApplications: MyApplication[] = [
    { id: '1', serviceName: 'Đăng ký thường trú', submittedDate: '2026-02-10', status: 'Đang xử lý', progress: 60 },
    { id: '2', serviceName: 'Cấp CCCD', submittedDate: '2026-02-05', status: 'Hoàn thành', progress: 100 },
    { id: '3', serviceName: 'Xác nhận tình trạng hôn nhân', submittedDate: '2026-02-12', status: 'Chờ duyệt', progress: 20 }
  ];

  let selectedCategory = 'all';
  let selectedService: TTHCService | null = null;
  let showApplyModal = false;

  $: filteredServices = selectedCategory === 'all' 
    ? services 
    : services.filter(s => s.category === selectedCategory);

  function openServiceDetail(service: TTHCService) {
    selectedService = service;
  }

  function applyService() {
    showApplyModal = true;
  }
</script>

<svelte:head>
  <title>TTHC - Phường/Xã Super App</title>
</svelte:head>

<div class="page-container">
  <header class="page-header">
    <div class="header-content">
      <button class="back-btn" on:click={() => goto('/government')}>
        ← Back
      </button>
      <div>
        <h1>📋 TTHC Services</h1>
        <p class="subtitle">Thủ tục hành chính - Dịch vụ công trực tuyến</p>
      </div>
    </div>
  </header>

  <div class="content">
    <section class="my-apps-section">
      <h2>📂 My Applications</h2>
      <div class="applications-list">
        {#each myApplications as app}
          <div class="application-card">
            <div class="app-info">
              <h3>{app.serviceName}</h3>
              <p>Submitted: {app.submittedDate}</p>
            </div>
            <div class="app-status">
              <span class="status-badge {app.status === 'Hoàn thành' ? 'completed' : app.status === 'Đang xử lý' ? 'processing' : 'pending'}">
                {app.status}
              </span>
              <div class="progress-bar">
                <div class="progress" style="width: {app.progress}%"></div>
              </div>
            </div>
          </div>
        {/each}
      </div>
    </section>

    <section class="services-section">
      <div class="section-header">
        <h2>🏛️ Available Services</h2>
        <select bind:value={selectedCategory} class="category-select">
          <option value="all">All Categories</option>
          <option value="Hộ khẩu">Hộ khẩu</option>
          <option value="CCCD">CCCD</option>
          <option value="Hộ tịch">Hộ tịch</option>
          <option value="Xác nhận">Xác nhận</option>
          <option value="Kinh doanh">Kinh doanh</option>
        </select>
      </div>

      <div class="services-grid">
        {#each filteredServices as service}
          <div class="service-card" on:click={() => openServiceDetail(service)}>
            <div class="service-header">
              <span class="service-category">{service.category}</span>
              <span class="service-status {service.status}">{service.status}</span>
            </div>
            <h3>{service.name}</h3>
            <p class="service-desc">{service.description}</p>
            <div class="service-meta">
              <span>⏱️ {service.processingTime}</span>
              <span>💰 {service.fee}</span>
            </div>
            <button class="btn-apply">Apply Now</button>
          </div>
        {/each}
      </div>
    </section>

    <section class="info-section">
      <h2>ℹ️ Information</h2>
      <div class="info-cards">
        <div class="info-card">
          <div class="info-icon">📞</div>
          <h3>Hotline</h3>
          <p>1900 xxxx</p>
        </div>
        <div class="info-card">
          <div class="info-icon">📧</div>
          <h3>Email</h3>
          <p>ttph@phuong.gov.vn</p>
        </div>
        <div class="info-card">
          <div class="info-icon">🏢</div>
          <h3>Working Hours</h3>
          <p>Mon-Fri: 8:00-17:00</p>
        </div>
      </div>
    </section>
  </div>
</div>

{#if selectedService}
  <div class="modal-overlay" on:click={() => selectedService = null}>
    <div class="modal" on:click|stopPropagation>
      <button class="modal-close" on:click={() => selectedService = null}>✕</button>
      <h2>{selectedService.name}</h2>
      <p class="modal-category">{selectedService.category}</p>
      <p class="modal-desc">{selectedService.description}</p>
      
      <div class="modal-section">
        <h4>📋 Requirements</h4>
        <ul>
          {#each selectedService.requirements as req}
            <li>{req}</li>
          {/each}
        </ul>
      </div>
      
      <div class="modal-meta">
        <div class="meta-item">
          <span class="meta-label">Processing Time</span>
          <span class="meta-value">{selectedService.processingTime}</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Fee</span>
          <span class="meta-value">{selectedService.fee}</span>
        </div>
      </div>
      
      <button class="btn-primary full" on:click={applyService}>Apply Now</button>
    </div>
  </div>
{/if}

<style>
  .page-container { min-height: 100vh; background: #f8fafc; }
  .page-header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 2rem; }
  .header-content { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 1rem; }
  .back-btn { background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }
  h1 { margin: 0; font-size: 1.75rem; }
  .subtitle { margin: 0.5rem 0 0; opacity: 0.9; }
  .content { max-width: 1200px; margin: 0 auto; padding: 2rem; }
  section { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  h2 { margin: 0 0 1rem; color: #1e293b; font-size: 1.25rem; }
  
  .applications-list { display: flex; flex-direction: column; gap: 1rem; }
  .application-card { display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8fafc; border-radius: 8px; }
  .app-info h3 { margin: 0; color: #1e293b; }
  .app-info p { margin: 0.25rem 0 0; font-size: 0.875rem; color: #64748b; }
  .app-status { text-align: right; }
  .status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
  .status-badge.completed { background: #dcfce7; color: #166534; }
  .status-badge.processing { background: #dbeafe; color: #1d4ed8; }
  .status-badge.pending { background: #fef3c7; color: #92400e; }
  .progress-bar { width: 100px; height: 6px; background: #e2e8f0; border-radius: 3px; margin-top: 0.5rem; }
  .progress { height: 100%; background: #3b82f6; border-radius: 3px; }
  
  .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
  .section-header h2 { margin: 0; }
  .category-select { padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; }
  
  .services-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
  .service-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; cursor: pointer; transition: all 0.2s; }
  .service-card:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15); }
  .service-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
  .service-category { font-size: 0.75rem; color: #64748b; }
  .service-status { font-size: 0.75rem; padding: 0.125rem 0.5rem; border-radius: 9999px; background: #dcfce7; color: #166534; }
  .service-card h3 { margin: 0.5rem 0; color: #1e293b; }
  .service-desc { font-size: 0.875rem; color: #64748b; margin: 0 0 1rem; }
  .service-meta { display: flex; gap: 1rem; font-size: 0.875rem; color: #64748b; margin-bottom: 1rem; }
  .btn-apply { width: 100%; background: #3b82f6; color: white; border: none; padding: 0.75rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
  
  .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
  .info-card { text-align: center; padding: 1.5rem; background: #f8fafc; border-radius: 8px; }
  .info-icon { font-size: 2rem; margin-bottom: 0.5rem; }
  .info-card h3 { margin: 0.5rem 0; font-size: 1rem; color: #1e293b; }
  .info-card p { margin: 0; color: #64748b; }
  
  .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
  .modal { background: white; border-radius: 16px; padding: 2rem; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; position: relative; }
  .modal-close { position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; }
  .modal h2 { margin: 0 0 0.5rem; color: #1e293b; }
  .modal-category { color: #3b82f6; font-weight: 600; margin: 0 0 1rem; }
  .modal-desc { color: #64748b; margin: 0 0 1.5rem; }
  .modal-section { margin-bottom: 1.5rem; }
  .modal-section h4 { margin: 0 0 0.5rem; color: #1e293b; }
  .modal-section ul { margin: 0; padding-left: 1.5rem; color: #64748b; }
  .modal-section li { margin-bottom: 0.25rem; }
  .modal-meta { display: flex; gap: 2rem; margin-bottom: 1.5rem; padding: 1rem; background: #f8fafc; border-radius: 8px; }
  .meta-item { display: flex; flex-direction: column; }
  .meta-label { font-size: 0.75rem; color: #64748b; }
  .meta-value { font-weight: 600; color: #1e293b; }
  .btn-primary.full { width: 100%; background: #3b82f6; color: white; border: none; padding: 1rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
</style>
