<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';

  let activeCategory = 'utilities';
  let showPaymentModal = false;
  let selectedBill: any = null;

  let services = {
    utilities: [
      { id: '1', name: 'Điện lực', icon: '⚡', provider: 'EVN Hà Nội', pending: 2 },
      { id: '2', name: 'Nước', icon: '💧', provider: 'Viwasupco', pending: 1 },
      { id: '3', name: 'Internet', icon: '📶', provider: 'VNPT', pending: 0 },
      { id: '4', name: 'Truyền hình', icon: '📺', provider: 'VTV', pending: 1 }
    ],
    government: [
      { id: '5', name: 'Đăng ký khám bệnh', icon: '🏥', provider: 'Bệnh viện', pending: 1 },
      { id: '6', name: 'Đặt lịch tiếp dân', icon: '📅', provider: 'UBND Phường', pending: 0 },
      { id: '7', name: 'Đăng ký kinh doanh', icon: '📋', provider: 'Chi cục Thuế', pending: 1 }
    ],
    services: [
      { id: '8', name: 'Vệ sinh', icon: '🧹', provider: 'Môi trường', pending: 0 },
      { id: '9', name: 'An ninh', icon: '🔒', provider: 'Công an', pending: 0 },
      { id: '10', name: 'Giao thông', icon: '🚦', provider: 'Sở GTVT', pending: 0 }
    ]
  };

  let recentPayments = [
    { id: '1', name: 'Tiền điện tháng 01/2026', amount: 1250000, date: '2026-02-10', status: 'PAID' },
    { id: '2', name: 'Tiền nước tháng 01/2026', amount: 350000, date: '2026-02-08', status: 'PAID' },
    { id: '3', name: 'Internet FPT', amount: 550000, date: '2026-02-05', status: 'PAID' }
  ];

  let upcomingAppointments = [
    { id: '1', title: 'Khám sức khỏe định kỳ', location: 'Bệnh viện Xanh Pôn', date: '2026-02-20', time: '08:00' },
    { id: '2', title: 'Nộp hồ sơ CCCD', location: 'Công an Quận', date: '2026-02-22', time: '14:30' }
  ];

  let quickActions = [
    { id: '1', name: 'Tra cứu CCCD', icon: '🪪', color: '#3b82f6' },
    { id: '2', name: 'Tra cứu BHXH', icon: '🏥', color: '#10b981' },
    { id: '3', name: 'Tra cứu thuế', icon: '💰', color: '#f59e0b' },
    { id: '4', name: 'Phản ánh', icon: '📝', color: '#ef4444' },
    { id: '5', name: 'Tin tức', icon: '📰', color: '#8b5cf6' },
    { id: '6', name: 'Hỗ trợ', icon: '❓', color: '#6b7280' }
  ];

  function openPayment(bill: any) {
    selectedBill = bill;
    showPaymentModal = true;
  }

  function processPayment() {
    showPaymentModal = false;
    selectedBill = null;
  }

  function formatCurrency(amount: number) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
  }
</script>

<div class="services-page">
  <header class="page-header">
    <h1>💳 Dịch Vụ Công Dân</h1>
    <div class="user-info">
      <span class="avatar">A</span>
      <span class="name">Nguyễn Văn A</span>
    </div>
  </header>

  <div class="quick-actions-grid">
    {#each quickActions as action}
      <div class="quick-action" style="background: {action.color}" on:click={() => goto('/' + action.name.toLowerCase())}>
        <div class="action-icon">{action.icon}</div>
        <div class="action-name">{action.name}</div>
      </div>
    {/each}
  </div>

  {#if upcomingAppointments.length > 0}
    <section class="appointments-section">
      <h3>📅 Lịch hẹn sắp tới</h3>
      <div class="appointments-list">
        {#each upcomingAppointments as appt}
          <div class="appointment-card">
            <div class="appt-date">
              <div class="date-day">{appt.date.split('-')[2]}</div>
              <div class="date-month">Th{appt.date.split('-')[1]}</div>
            </div>
            <div class="appt-info">
              <div class="appt-title">{appt.title}</div>
              <div class="appt-location">{appt.location}</div>
              <div class="appt-time">⏰ {appt.time}</div>
            </div>
          </div>
        {/each}
      </div>
    </section>
  {/if}

  <section class="services-section">
    <div class="category-tabs">
      <button class:active={activeCategory === 'utilities'} on:click={() => activeCategory = 'utilities'}>
        ⚡ Tiện ích
      </button>
      <button class:active={activeCategory === 'government'} on:click={() => activeCategory = 'government'}>
        🏛️ Hành chính
      </button>
      <button class:active={activeCategory === 'services'} on:click={() => activeCategory = 'services'}>
        🛠️ Dịch vụ
      </button>
    </div>

    <div class="services-grid">
      {#each services[activeCategory] as service}
        <div class="service-card" on:click={() => openPayment(service)}>
          <div class="service-icon">{service.icon}</div>
          <div class="service-name">{service.name}</div>
          <div class="service-provider">{service.provider}</div>
          {#if service.pending > 0}
            <div class="pending-badge">{service.pending} chờ thanh toán</div>
          {:else}
            <div class="status-ok">✓</div>
          {/if}
        </div>
      {/each}
    </div>
  </section>

  <section class="history-section">
    <h3>Lịch sử thanh toán</h3>
    <div class="history-list">
      {#each recentPayments as payment}
        <div class="history-item">
          <div class="history-info">
            <div class="history-name">{payment.name}</div>
            <div class="history-date">{payment.date}</div>
          </div>
          <div class="history-amount">{formatCurrency(payment.amount)}</div>
          <div class="history-status paid">Đã thanh toán</div>
        </div>
      {/each}
    </div>
  </section>

  {#if showPaymentModal}
    <div class="modal-overlay" on:click={() => showPaymentModal = false}>
      <div class="modal" on:click|stopPropagation>
        <div class="modal-header">
          <h2>Thanh toán {selectedBill?.name}</h2>
          <button class="close-btn" on:click={() => showPaymentModal = false}>×</button>
        </div>
        <div class="modal-body">
          <div class="bill-info">
            <div class="info-row">
              <span>Nhà cung cấp:</span>
              <strong>{selectedBill?.provider}</strong>
            </div>
            <div class="info-row">
              <span>Mã khách hàng:</span>
              <strong>PN-0123456789</strong>
            </div>
            <div class="info-row">
              <span>Kỳ thanh toán:</span>
              <strong>02/2026</strong>
            </div>
            <div class="info-row total">
              <span>Số tiền:</span>
              <strong class="amount">1,250,000 VNĐ</strong>
            </div>
          </div>
          
          <div class="payment-methods">
            <h4>Phương thức thanh toán</h4>
            <div class="methods">
              <label class="method selected">
                <input type="radio" name="method" checked />
                <span>💳 Ví điện tử</span>
              </label>
              <label class="method">
                <input type="radio" name="method" />
                <span>🏦 Ngân hàng</span>
              </label>
              <label class="method">
                <input type="radio" name="method" />
                <span>📱 MoMo</span>
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" on:click={() => showPaymentModal = false}>Hủy</button>
          <button class="btn-primary" on:click={processPayment}>Thanh toán</button>
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .services-page {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
    background: #f5f5f5;
    min-height: 100vh;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }

  .page-header h1 {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
  }

  .user-info {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
  }

  .name {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
  }

  .quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 24px;
  }

  .quick-action {
    padding: 16px 8px;
    border-radius: 12px;
    text-align: center;
    color: white;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .quick-action:active {
    transform: scale(0.95);
  }

  .action-icon {
    font-size: 24px;
    margin-bottom: 6px;
  }

  .action-name {
    font-size: 12px;
    font-weight: 500;
  }

  .appointments-section {
    margin-bottom: 24px;
  }

  .appointments-section h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 12px 0;
  }

  .appointments-list {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 8px;
  }

  .appointment-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    gap: 12px;
    min-width: 200px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .appt-date {
    background: #3b82f6;
    color: white;
    border-radius: 8px;
    padding: 8px;
    text-align: center;
    min-width: 50px;
  }

  .date-day {
    font-size: 20px;
    font-weight: 700;
  }

  .date-month {
    font-size: 12px;
  }

  .appt-info {
    flex: 1;
  }

  .appt-title {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
  }

  .appt-location {
    font-size: 12px;
    color: #6b7280;
    margin: 4px 0;
  }

  .appt-time {
    font-size: 12px;
    color: #3b82f6;
  }

  .services-section {
    margin-bottom: 24px;
  }

  .category-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
  }

  .category-tabs button {
    flex: 1;
    padding: 10px;
    border: none;
    background: white;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
  }

  .category-tabs button.active {
    background: #3b82f6;
    color: white;
  }

  .services-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .service-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s;
  }

  .service-card:active {
    transform: scale(0.98);
  }

  .service-icon {
    font-size: 32px;
    margin-bottom: 8px;
  }

  .service-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
    margin-bottom: 4px;
  }

  .service-provider {
    font-size: 12px;
    color: #9ca3af;
  }

  .pending-badge {
    background: #fef3c7;
    color: #d97706;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 12px;
    margin-top: 8px;
  }

  .status-ok {
    color: #10b981;
    font-size: 18px;
    margin-top: 8px;
  }

  .history-section h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 12px 0;
  }

  .history-list {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .history-item {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
  }

  .history-item:last-child {
    border-bottom: none;
  }

  .history-info {
    flex: 1;
  }

  .history-name {
    font-weight: 500;
    color: #1f2937;
    font-size: 14px;
  }

  .history-date {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 2px;
  }

  .history-amount {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
    margin-right: 12px;
  }

  .history-status {
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 12px;
  }

  .history-status.paid {
    background: #d1fae5;
    color: #059669;
  }

  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: flex-end;
    z-index: 1000;
  }

  .modal {
    background: white;
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
  }

  .modal-header h2 {
    margin: 0;
    font-size: 18px;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #9ca3af;
  }

  .modal-body {
    padding: 20px;
  }

  .bill-info {
    background: #f9fafb;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
  }

  .info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
  }

  .info-row:last-child {
    border-bottom: none;
  }

  .info-row span {
    color: #6b7280;
    font-size: 14px;
  }

  .info-row strong {
    color: #1f2937;
    font-size: 14px;
  }

  .info-row.total .amount {
    color: #ef4444;
    font-size: 18px;
  }

  .payment-methods h4 {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 12px 0;
  }

  .methods {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .method {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
  }

  .method.selected {
    border-color: #3b82f6;
    background: #eff6ff;
  }

  .modal-footer {
    display: flex;
    gap: 12px;
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
  }

  .btn-secondary {
    flex: 1;
    padding: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: white;
    cursor: pointer;
    font-weight: 600;
  }

  .btn-primary {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 10px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
    font-weight: 600;
  }
</style>