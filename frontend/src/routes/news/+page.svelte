<script lang="ts">
  import { onMount } from 'svelte';

  let activeTab = 'news';
  let searchQuery = '';

  let news = [
    { id: 1, title: 'Thông báo nghỉ Tết Nguyên đán 2026', category: 'announcement', date: '2026-02-14', image: '🎊', summary: 'UBND Thành phố thông báo lịch nghỉ Tết Nguyên đán cho cán bộ, công chức...' },
    { id: 2, title: 'Tuyển dụng công chức năm 2026', category: 'recruitment', date: '2026-02-13', image: '📋', summary: 'Thông báo tuyển dụng công chức hành chính năm 2026...' },
    { id: 3, title: 'Chiến dịch vệ sinh môi trường', category: 'campaign', date: '2026-02-12', image: '🌿', summary: 'Phường phát động chiến dịch vệ sinh môi trường xanh, sạch, đẹp...' },
    { id: 4, title: 'Cấp CCCD lưu động tại phường', category: 'service', date: '2026-02-11', image: '🪪', summary: 'Công an Quận tổ chức cấp CCCD lưu động tại trụ sở UBND phường...' },
    { id: 5, title: 'Phòng chống dịch bệnh mùa xuân', category: 'health', date: '2026-02-10', image: '🏥', summary: 'Khuyến cáo phòng chống dịch bệnh mùa xuân-hè...' }
  ];

  let announcements = [
    { id: 1, title: 'Lịch tiếp công dân tháng 02/2026', department: 'UBND Phường', date: '2026-02-01', priority: 'normal' },
    { id: 2, title: 'Kế hoạch chi trả chế độ tháng 02', department: 'Phòng LAĐ', date: '2026-02-05', priority: 'normal' },
    { id: 3, title: 'Thông báo thu hồi tin nhắn giả mạo', department: 'ANTT', date: '2026-02-10', priority: 'urgent' }
  ];

  let events = [
    { id: 1, name: 'Hội nghị tổng kết năm 2025', location: 'Hội trường UBND', date: '2026-01-28', time: '14:00', status: 'completed' },
    { id: 2, name: 'Tiếp xúc cử tri định kỳ', location: 'Trụ sở phường', date: '2026-02-15', time: '09:00', status: 'upcoming' },
    { id: 3, name: 'Chiến dịch hiến máu tình nguyện', location: 'Trường THPT', date: '2026-02-20', time: '07:30', status: 'upcoming' }
  ];

  function getCategoryLabel(category: string) {
    switch (category) {
      case 'announcement': return 'Thông báo';
      case 'recruitment': return 'Tuyển dụng';
      case 'campaign': return 'Chiến dịch';
      case 'service': return 'Dịch vụ';
      case 'health': return 'Y tế';
      default: return category;
    }
  }

  function getCategoryColor(category: string) {
    switch (category) {
      case 'announcement': return '#3b82f6';
      case 'recruitment': return '#10b981';
      case 'campaign': return '#8b5cf6';
      case 'service': return '#f59e0b';
      case 'health': return '#ef4444';
      default: return '#6b7280';
    }
  }
</script>

<div class="news-page">
  <header class="page-header">
    <h1>📰 Tin Tức - Thông Báo</h1>
    <div class="search-box">
      <input type="text" placeholder="Tìm kiếm..." bind:value={searchQuery} />
    </div>
  </header>

  <div class="tabs">
    <button class="tab" class:active={activeTab === 'news'} on:click={() => activeTab = 'news'}>
      📰 Tin tức
    </button>
    <button class="tab" class:active={activeTab === 'announcements'} on:click={() => activeTab = 'announcements'}>
      📢 Thông báo
    </button>
    <button class="tab" class:active={activeTab === 'events'} on:click={() => activeTab = 'events'}>
      📅 Sự kiện
    </button>
  </div>

  {#if activeTab === 'news'}
    <div class="news-list">
      {#each news as item}
        <div class="news-card">
          <div class="news-image">{item.image}</div>
          <div class="news-content">
            <div class="news-category" style="background: {getCategoryColor(item.category)}">
              {getCategoryLabel(item.category)}
            </div>
            <h3>{item.title}</h3>
            <p>{item.summary}</p>
            <div class="news-date">{item.date}</div>
          </div>
        </div>
      {/each}
    </div>
  {:else if activeTab === 'announcements'}
    <div class="announcements-list">
      {#each announcements as item}
        <div class="announcement-item">
          <div class="priority-dot" class:urgent={item.priority === 'urgent'}></div>
          <div class="announcement-content">
            <h4>{item.title}</h4>
            <div class="announcement-meta">
              <span class="dept">{item.department}</span>
              <span class="date">{item.date}</span>
            </div>
          </div>
          <button class="view-btn">Xem</button>
        </div>
      {/each}
    </div>
  {:else}
    <div class="events-list">
      {#each events as event}
        <div class="event-card">
          <div class="event-date">
            <div class="day">{event.date.split('-')[2]}</div>
            <div class="month">Th{event.date.split('-')[1]}</div>
          </div>
          <div class="event-info">
            <h4>{event.name}</h4>
            <p>📍 {event.location}</p>
            <p>⏰ {event.time}</p>
          </div>
          <div class="event-status" class:completed={event.status === 'completed'}>
            {event.status === 'completed' ? 'Đã diễn ra' : 'Sắp diễn ra'}
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<style>
  .news-page {
    padding: 20px;
    max-width: 800px;
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
    margin: 0;
  }

  .search-box input {
    padding: 10px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    width: 200px;
    font-size: 14px;
  }

  .tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
  }

  .tab {
    flex: 1;
    padding: 12px;
    border: none;
    background: white;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
    color: #6b7280;
  }

  .tab.active {
    background: #3b82f6;
    color: white;
  }

  .news-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .news-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .news-image {
    width: 100px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
  }

  .news-content {
    padding: 16px;
    flex: 1;
  }

  .news-category {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    color: white;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 8px;
  }

  .news-content h3 {
    margin: 0 0 8px 0;
    font-size: 16px;
    color: #1f2937;
  }

  .news-content p {
    margin: 0 0 8px 0;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
  }

  .news-date {
    font-size: 12px;
    color: #9ca3af;
  }

  .announcements-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .announcement-item {
    background: white;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .priority-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #3b82f6;
  }

  .priority-dot.urgent {
    background: #ef4444;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }

  .announcement-content {
    flex: 1;
  }

  .announcement-content h4 {
    margin: 0 0 4px 0;
    font-size: 15px;
    color: #1f2937;
  }

  .announcement-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #9ca3af;
  }

  .view-btn {
    padding: 8px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    font-size: 13px;
  }

  .events-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .event-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    gap: 16px;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .event-date {
    background: #3b82f6;
    color: white;
    border-radius: 10px;
    padding: 10px 14px;
    text-align: center;
    min-width: 60px;
  }

  .day {
    font-size: 22px;
    font-weight: 700;
  }

  .month {
    font-size: 12px;
  }

  .event-info {
    flex: 1;
  }

  .event-info h4 {
    margin: 0 0 6px 0;
    font-size: 15px;
    color: #1f2937;
  }

  .event-info p {
    margin: 2px 0;
    font-size: 13px;
    color: #6b7280;
  }

  .event-status {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 12px;
    background: #dbeafe;
    color: #2563eb;
  }

  .event-status.completed {
    background: #f3f4f6;
    color: #6b7280;
  }
</style>