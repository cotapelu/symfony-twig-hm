<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';

  let showEmergencyModal = false;
  let emergencyType = '';

  let quickEmergency = [
    { id: 'police', name: 'Công an', icon: '👮', number: '113', color: '#3b82f6' },
    { id: 'fire', name: 'PCCC', icon: '🚒', number: '114', color: '#ef4444' },
    { id: 'ambulance', name: 'Cấp cứu', icon: '🚑', number: '115', color: '#10b981' },
    { id: 'traffic', name: 'Giao thông', icon: '🚦', number: '191', color: '#f59e0b' }
  ];

  let emergencyContacts = [
    { name: 'UBND Phường', phone: '0243XXX', available: '24/7' },
    { name: 'Công an Phường', phone: '0243XXX', available: '24/7' },
    { name: 'Trạm Y tế', phone: '0243XXX', available: '24/7' },
    { name: 'Điện lực', phone: '1900XXX', available: '24/7' }
  ];

  let recentEmergencies = [
    { id: 1, type: 'fire', location: 'Ngõ 123 Đường ABC', time: '2 giờ trước', status: 'resolved' },
    { id: 2, type: 'medical', location: 'Khu vực C', time: '5 giờ trước', status: 'resolved' }
  ];

  function callEmergency(number: string) {
    window.location.href = `tel:${number}`;
  }

  function openEmergency(type: string) {
    emergencyType = type;
    showEmergencyModal = true;
  }
</script>

<div class="emergency-page">
  <header class="page-header">
    <h1>🆘 Khẩn Cấp</h1>
  </header>

  <div class="emergency-banner">
    <div class="banner-icon">🚨</div>
    <h2>Gọi cấp cứu nhanh</h2>
    <p>Chạm để gọi ngay</p>
  </div>

  <div class="quick-call-grid">
    {#each quickEmergency as item}
      <div class="quick-call-btn" style="background: {item.color}" on:click={() => callEmergency(item.number)}>
        <div class="call-icon">{item.icon}</div>
        <div class="call-name">{item.name}</div>
        <div class="call-number">{item.number}</div>
      </div>
    {/each}
  </div>

  <section class="contacts-section">
    <h3>📞 Liên hệ khẩn</h3>
    <div class="contacts-list">
      {#each emergencyContacts as contact}
        <div class="contact-item">
          <div class="contact-info">
            <div class="contact-name">{contact.name}</div>
            <div class="contact-available">{contact.available}</div>
          </div>
          <button class="call-btn" on:click={() => callEmergency(contact.phone)}>
            📞 {contact.phone}
          </button>
        </div>
      {/each}
    </div>
  </section>

  <section class="report-section">
    <h3>📝 Báo sự cố</h3>
    <div class="report-types">
      <div class="report-type" on:click={() => openEmergency('fire')}>
        <div class="type-icon">🔥</div>
        <div class="type-name">Cháy nổ</div>
      </div>
      <div class="report-type" on:click={() => openEmergency('accident')}>
        <div class="type-icon">💥</div>
        <div class="type-name">Tai nạn</div>
      </div>
      <div class="report-type" on:click={() => openEmergency('crime')}>
        <div class="type-icon">⚠️</div>
        <div class="type-name">Tội phạm</div>
      </div>
      <div class="report-type" on:click={() => openEmergency('disaster')}>
        <div class="type-icon">🌪️</div>
        <div class="type-name">Thiên tai</div>
      </div>
      <div class="report-type" on:click={() => openEmergency('medical')}>
        <div class="type-icon">🏥</div>
        <div class="type-name">Y tế khẩn</div>
      </div>
      <div class="report-type" on:click={() => openEmergency('other')}>
        <div class="type-icon">📋</div>
        <div class="type-name">Khác</div>
      </div>
    </div>
  </section>

  {#if recentEmergencies.length > 0}
    <section class="history-section">
      <h3>Lịch sử báo cáo</h3>
      <div class="history-list">
        {#each recentEmergencies as item}
          <div class="history-item">
            <div class="history-icon">
              {item.type === 'fire' ? '🔥' : '🏥'}
            </div>
            <div class="history-info">
              <div class="history-location">{item.location}</div>
              <div class="history-time">{item.time}</div>
            </div>
            <div class="history-status resolved">Đã xử lý</div>
          </div>
        {/each}
      </div>
    </section>
  {/if}

  {#if showEmergencyModal}
    <div class="modal-overlay" on:click={() => showEmergencyModal = false}>
      <div class="modal" on:click|stopPropagation>
        <div class="modal-header">
          <h2>Báo sự cố {emergencyType}</h2>
          <button class="close-btn" on:click={() => showEmergencyModal = false}>×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Vị trí *</label>
            <input type="text" placeholder="Nhập địa chỉ sự cố" />
          </div>
          <div class="form-group">
            <label>Mô tả sự cố *</label>
            <textarea rows="4" placeholder="Mô tả chi tiết tình huống"></textarea>
          </div>
          <div class="form-group">
            <label>Hình ảnh</label>
            <div class="upload-btn">📷 Chụp ảnh</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" on:click={() => showEmergencyModal = false}>Hủy</button>
          <button class="btn-emergency" on:click={() => showEmergencyModal = false}>
            📞 Gọi cấp cứu & Báo cáo
          </button>
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .emergency-page {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
    background: #fef2f2;
    min-height: 100vh;
  }

  .page-header h1 {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 20px 0;
  }

  .emergency-banner {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    color: white;
    margin-bottom: 20px;
  }

  .banner-icon {
    font-size: 48px;
    margin-bottom: 12px;
  }

  .emergency-banner h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
  }

  .emergency-banner p {
    margin: 0;
    opacity: 0.9;
  }

  .quick-call-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 24px;
  }

  .quick-call-btn {
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    color: white;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .quick-call-btn:active {
    transform: scale(0.95);
  }

  .call-icon {
    font-size: 36px;
    margin-bottom: 8px;
  }

  .call-name {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 4px;
  }

  .call-number {
    font-size: 20px;
    font-weight: 700;
  }

  .contacts-section, .report-section, .history-section {
    margin-bottom: 24px;
  }

  .contacts-section h3, .report-section h3, .history-section h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 12px 0;
  }

  .contacts-list {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .contact-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
  }

  .contact-item:last-child {
    border-bottom: none;
  }

  .contact-name {
    font-weight: 600;
    color: #1f2937;
  }

  .contact-available {
    font-size: 12px;
    color: #10b981;
  }

  .call-btn {
    padding: 8px 14px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .report-types {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
  }

  .report-type {
    background: white;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .type-icon {
    font-size: 28px;
    margin-bottom: 8px;
  }

  .type-name {
    font-size: 12px;
    font-weight: 500;
    color: #374151;
  }

  .history-list {
    background: white;
    border-radius: 12px;
    overflow: hidden;
  }

  .history-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
  }

  .history-icon {
    font-size: 24px;
  }

  .history-info {
    flex: 1;
  }

  .history-location {
    font-weight: 500;
    color: #1f2937;
    font-size: 14px;
  }

  .history-time {
    font-size: 12px;
    color: #9ca3af;
  }

  .history-status {
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 12px;
  }

  .history-status.resolved {
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

  .form-group input, .form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
  }

  .upload-btn {
    padding: 12px;
    border: 2px dashed #e5e7eb;
    border-radius: 8px;
    text-align: center;
    color: #6b7280;
    cursor: pointer;
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

  .btn-emergency {
    flex: 2;
    padding: 14px;
    border: none;
    border-radius: 10px;
    background: #ef4444;
    color: white;
    cursor: pointer;
    font-weight: 600;
  }
</style>