<script lang="ts">
  import { onMount } from 'svelte';
  import { queueStore, myWaitingTickets, myCompletedTickets } from '../state/queueStore';
  import { queueService } from '../services/queueService';
  import type { QueueTicket } from '../contracts/queueTypes';
  
  onMount(async () => {
    const phone = localStorage.getItem('citizenPhone');
    if (phone) {
      await loadMyTickets(phone);
    }
  });
  
  async function loadMyTickets(phone: string) {
    try {
      await queueService.loadTicketsByPhone(phone);
    } catch (error) {
      console.error('Failed to load tickets:', error);
    }
  }
  
  async function cancelTicket(ticket: QueueTicket) {
    if (!confirm(`Bạn có chắc muốn hủy số ${ticket.ticketNumber}?`)) {
      return;
    }
    
    try {
      await queueService.cancelTicket(ticket.id, 'Khách hàng yêu cầu');
      alert('Đã hủy số thành công!');
    } catch (error) {
      alert('Lỗi: ' + (error instanceof Error ? error.message : 'Unknown'));
    }
  }
  
  function getStatusBadgeClass(status: string): string {
    switch (status) {
      case 'waiting': return 'waiting';
      case 'called': return 'called';
      case 'in_progress': return 'in-progress';
      case 'completed': return 'completed';
      case 'cancelled': return 'cancelled';
      default: return '';
    }
  }
  
  function getStatusText(status: string): string {
    switch (status) {
      case 'waiting': return 'Đang chờ';
      case 'called': return 'Được gọi';
      case 'in_progress': return 'Đang xử lý';
      case 'completed': return 'Hoàn thành';
      case 'cancelled': return 'Đã hủy';
      case 'skipped': return 'Bỏ qua';
      default: return status;
    }
  }
</script>

<div class="my-tickets">
  <div class="header">
    <h1>Số của tôi</h1>
    <p class="subtitle">Theo dõi trạng thái số của bạn</p>
  </div>
  
  {#if $queueStore.loading}
    <div class="loading">
      <div class="spinner"></div>
      <p>Đang tải...</p>
    </div>
  {:else if $queueStore.error}
    <div class="error">
      <p>{$queueStore.error}</p>
      <button on:click={() => {
        const phone = localStorage.getItem('citizenPhone');
        if (phone) loadMyTickets(phone);
      }}>Thử lại</button>
    </div>
  {:else if $myWaitingTickets.length === 0 && $myCompletedTickets.length === 0}
    <div class="empty-state">
      <p>Bạn chưa có số nào</p>
      <p class="hint">Vui lòng lấy số từ trang chủ để theo dõi</p>
    </div>
  {:else}
    {#if $myWaitingTickets.length > 0}
      <section class="tickets-section">
        <h2>Số đang chờ ({$myWaitingTickets.length})</h2>
        <div class="tickets-list">
          {#each $myWaitingTickets as ticket}
            <div class="ticket-card {getStatusBadgeClass(ticket.status)}">
              <div class="ticket-header">
                <div class="ticket-number">
                  <span class="number-label">Số:</span>
                  <span class="number-value">{ticket.ticketNumber}</span>
                </div>
                <span class="status-badge {getStatusBadgeClass(ticket.status)}">
                  {getStatusText(ticket.status)}
                </span>
              </div>
              
              <div class="ticket-info">
                <div class="info-row">
                  <span class="label">Hàng đợi:</span>
                  <span class="value">{ticket.queueName}</span>
                </div>
                <div class="info-row">
                  <span class="label">Dịch vụ:</span>
                  <span class="value">{ticket.serviceType}</span>
                </div>
                <div class="info-row">
                  <span class="label">Tên:</span>
                  <span class="value">{ticket.citizenName}</span>
                </div>
                <div class="info-row">
                  <span class="label">Thời gian lấy:</span>
                  <span class="value">{new Date(ticket.createdAt).toLocaleString('vi-VN')}</span>
                </div>
              </div>
              
              {#if ticket.counterNumber}
                <div class="ticket-alert called">
                  <span class="icon">🔔</span>
                  <div>
                    <strong>Được gọi đến quầy {ticket.counterNumber}!</strong>
                    <span>Vui lòng đến ngay</span>
                  </div>
                </div>
              {/if}
              
              {#if ticket.estimatedWaitTime && ticket.status === 'waiting'}
                <div class="ticket-estimate">
                  <span class="icon">⏱️</span>
                  <span>Thời gian dự kiến chờ: ~{ticket.estimatedWaitTime} phút</span>
                </div>
              {/if}
              
              {#if ticket.status === 'waiting'}
                <button class="cancel-btn" on:click={() => cancelTicket(ticket)}>
                  Hủy số
                </button>
              {/if}
            </div>
          {/each}
        </div>
      </section>
    {/if}
    
    {#if $myCompletedTickets.length > 0}
      <section class="tickets-section">
        <h2>Số đã hoàn thành ({$myCompletedTickets.length})</h2>
        <div class="tickets-list">
          {#each $myCompletedTickets.slice(0, 10) as ticket}
            <div class="ticket-card completed">
              <div class="ticket-header">
                <div class="ticket-number">
                  <span class="number-label">Số:</span>
                  <span class="number-value">{ticket.ticketNumber}</span>
                </div>
                <span class="status-badge completed">
                  {getStatusText(ticket.status)}
                </span>
              </div>
              
              <div class="ticket-info">
                <div class="info-row">
                  <span class="label">Hàng đợi:</span>
                  <span class="value">{ticket.queueName}</span>
                </div>
                <div class="info-row">
                  <span class="label">Dịch vụ:</span>
                  <span class="value">{ticket.serviceType}</span>
                </div>
                {#if ticket.serviceDuration}
                  <div class="info-row">
                    <span class="label">Thời gian xử lý:</span>
                    <span class="value">{ticket.serviceDuration} phút</span>
                  </div>
                {/if}
                <div class="info-row">
                  <span class="label">Thời gian hoàn thành:</span>
                  <span class="value">{ticket.completedAt ? new Date(ticket.completedAt).toLocaleString('vi-VN') : '-'}</span>
                </div>
              </div>
            </div>
          {/each}
        </div>
      </section>
    {/if}
  {/if}
</div>

<style>
  .my-tickets {
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
  }
  
  .header {
    text-align: center;
    margin-bottom: 30px;
  }
  
  .header h1 {
    font-size: 28px;
    color: #2c3e50;
    margin: 0 0 8px 0;
  }
  
  .subtitle {
    font-size: 16px;
    color: #7f8c8d;
    margin: 0;
  }
  
  .tickets-section {
    margin-bottom: 40px;
  }
  
  .tickets-section h2 {
    font-size: 20px;
    color: #2c3e50;
    margin-bottom: 16px;
  }
  
  .tickets-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  
  .ticket-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #6c757d;
  }
  
  .ticket-card.waiting {
    border-left-color: #ffc107;
  }
  
  .ticket-card.called {
    border-left-color: #17a2b8;
    animation: pulse 2s infinite;
  }
  
  .ticket-card.in-progress {
    border-left-color: #007bff;
  }
  
  .ticket-card.completed {
    border-left-color: #28a745;
  }
  
  .ticket-card.cancelled {
    border-left-color: #dc3545;
    opacity: 0.7;
  }
  
  @keyframes pulse {
    0%, 100% { box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3); }
    50% { box-shadow: 0 4px 16px rgba(23, 162, 184, 0.6); }
  }
  
  .ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
  }
  
  .ticket-number {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .number-label {
    font-size: 14px;
    color: #6c757d;
  }
  
  .number-value {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
  }
  
  .status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
  }
  
  .status-badge.waiting {
    background: #fff3cd;
    color: #856404;
  }
  
  .status-badge.called {
    background: #d1ecf1;
    color: #0c5460;
  }
  
  .status-badge.in-progress {
    background: #cce5ff;
    color: #004085;
  }
  
  .status-badge.completed {
    background: #d4edda;
    color: #155724;
  }
  
  .status-badge.cancelled {
    background: #f8d7da;
    color: #721c24;
  }
  
  .ticket-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
  }
  
  .info-row {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
  }
  
  .label {
    color: #6c757d;
    min-width: 120px;
  }
  
  .value {
    color: #2c3e50;
    font-weight: 500;
  }
  
  .ticket-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #d1ecf1;
    border-radius: 8px;
    margin-bottom: 12px;
  }
  
  .ticket-alert.called {
    background: #fff3cd;
  }
  
  .ticket-alert .icon {
    font-size: 24px;
  }
  
  .ticket-alert span {
    display: block;
    font-size: 14px;
  }
  
  .ticket-estimate {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 14px;
    color: #495057;
    margin-bottom: 12px;
  }
  
  .cancel-btn {
    padding: 10px 20px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
  }
  
  .cancel-btn:hover {
    background: #c82333;
  }
  
  .loading, .error, .empty-state {
    text-align: center;
    padding: 60px 20px;
  }
  
  .spinner {
    width: 40px;
    height: 40px;
    margin: 0 auto 16px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }
  
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  
  .hint {
    font-size: 14px;
    color: #6c757d;
    margin-top: 8px;
  }
</style>
