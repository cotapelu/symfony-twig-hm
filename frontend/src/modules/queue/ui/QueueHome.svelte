<script lang="ts">
  import { onMount } from 'svelte';
  import { queueStore, openQueues, totalWaitingCount } from '../state/queueStore';
  import { queueService } from '../services/queueService';
  import type { Queue } from '../contracts/queueTypes';
  
  onMount(async () => {
    await loadQueues();
  });
  
  async function loadQueues() {
    try {
      await queueService.loadOpenQueues();
    } catch (error) {
      console.error('Failed to load queues:', error);
    }
  }
  
  async function joinQueue(queue: Queue) {
    const serviceType = prompt('Chọn loại dịch vụ:', queue.serviceTypes[0]);
    if (!serviceType || !queue.serviceTypes.includes(serviceType)) {
      alert('Loại dịch vụ không hợp lệ');
      return;
    }
    
    const citizenName = prompt('Nhập tên công dân:');
    if (!citizenName) {
      alert('Vui lòng nhập tên');
      return;
    }
    
    const citizenPhone = prompt('Nhập số điện thoại (tùy chọn):') || undefined;
    
    try {
      await queueService.joinQueue({
        queueId: queue.id,
        serviceType,
        citizenName,
        citizenPhone,
        priority: 0
      });
      alert('Đã lấy số thành công!');
      await loadQueues();
    } catch (error) {
      alert('Lỗi: ' + (error instanceof Error ? error.message : 'Unknown'));
    }
  }
</script>

<div class="queue-home">
  <div class="header">
    <h1>Hệ thống Xếp hàng số</h1>
    <p class="subtitle">Chọn hàng đợi để lấy số</p>
  </div>
  
  {#if $queueStore.loading}
    <div class="loading">
      <div class="spinner"></div>
      <p>Đang tải...</p>
    </div>
  {:else if $queueStore.error}
    <div class="error">
      <p>{$queueStore.error}</p>
      <button on:click={loadQueues}>Thử lại</button>
    </div>
  {:else}
    <div class="stats-bar">
      <div class="stat-item">
        <span class="stat-label">Hàng đợi đang mở:</span>
        <span class="stat-value">{$openQueues.length}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Tổng số đang chờ:</span>
        <span class="stat-value">{totalWaitingCount}</span>
      </div>
    </div>
    
    {#if $openQueues.length === 0}
      <div class="empty-state">
        <p>Không có hàng đợi nào đang mở</p>
        <button on:click={loadQueues}>Cập nhật</button>
      </div>
    {:else}
      <div class="queues-grid">
        {#each $openQueues as queue}
          <div class="queue-card">
            <div class="queue-header">
              <h3>{queue.name}</h3>
              <span class="status-badge open">ĐANG MỞ</span>
            </div>
            
            {#if queue.departmentName}
              <p class="department">{queue.departmentName}</p>
            {/if}
            
            <div class="queue-stats">
              <div class="stat">
                <span class="icon">⏱️</span>
                <span>{queue.waitingTicketCount} đang chờ</span>
              </div>
              {#if queue.averageWaitTime}
                <div class="stat">
                  <span class="icon">⏰</span>
                  <span>~{queue.averageWaitTime} phút</span>
                </div>
              {/if}
            </div>
            
            <div class="services">
              <p class="services-label">Dịch vụ:</p>
              <div class="service-tags">
                {#each queue.serviceTypes as type}
                  <span class="service-tag">{type}</span>
                {/each}
              </div>
            </div>
            
            <div class="operating-hours">
              <span class="icon">🕐</span>
              <span>Giờ làm việc: {queue.operatingHours ? 'Đang mở' : '24/7'}</span>
            </div>
            
            <button class="join-btn" on:click={() => joinQueue(queue)}>
              LẤY SỐ
            </button>
          </div>
        {/each}
      </div>
    {/if}
  {/if}
</div>

<style>
  .queue-home {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .header {
    text-align: center;
    margin-bottom: 30px;
  }
  
  .header h1 {
    font-size: 32px;
    color: #2c3e50;
    margin: 0 0 8px 0;
  }
  
  .subtitle {
    font-size: 16px;
    color: #7f8c8d;
    margin: 0;
  }
  
  .stats-bar {
    display: flex;
    gap: 20px;
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
  }
  
  .stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .stat-label {
    color: #6c757d;
    font-size: 14px;
  }
  
  .stat-value {
    font-weight: 600;
    color: #2c3e50;
    font-size: 18px;
  }
  
  .queues-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
  }
  
  .queue-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
  }
  
  .queue-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  }
  
  .queue-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
  }
  
  .queue-header h3 {
    font-size: 20px;
    color: #2c3e50;
    margin: 0;
  }
  
  .status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
  }
  
  .status-badge.open {
    background: #d4edda;
    color: #155724;
  }
  
  .department {
    color: #6c757d;
    font-size: 14px;
    margin: 0 0 16px 0;
  }
  
  .queue-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
  }
  
  .stat {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #495057;
  }
  
  .icon {
    font-size: 16px;
  }
  
  .services {
    margin-bottom: 16px;
  }
  
  .services-label {
    font-size: 12px;
    color: #6c757d;
    margin: 0 0 8px 0;
  }
  
  .service-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  
  .service-tag {
    background: #e9ecef;
    color: #495057;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
  }
  
  .operating-hours {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #6c757d;
    margin-bottom: 20px;
  }
  
  .join-btn {
    width: 100%;
    padding: 14px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
  }
  
  .join-btn:hover {
    background: #0056b3;
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
  
  .error button, .empty-state button {
    margin-top: 16px;
    padding: 10px 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
  }
</style>
