<script lang="ts">
  import { onMount } from 'svelte';

  let activeChat = 'all';
  let showNewChat = false;
  let messageText = '';
  
  let conversations = [
    { id: 1, name: 'Ban TT & HC', avatar: '🏛️', lastMessage: 'Đã tiếp nhận khiếu nại của bạn', time: '2 phút trước', unread: 2, online: true },
    { id: 2, name: 'Phòng Y tế', avatar: '🏥', lastMessage: 'Cảm ơn phản hồi', time: '1 giờ trước', unread: 0, online: true },
    { id: 3, name: 'Công an Phường', avatar: '👮', lastMessage: 'Sẽ kiểm tra và phản hồi', time: 'Hôm qua', unread: 0, online: false },
    { id: 4, name: 'Phòng LMĐT', avatar: '💼', lastMessage: 'Hồ sơ đã được duyệt', time: 'Hôm qua', unread: 1, online: true }
  ];

  let messages = [
    { id: 1, sender: 'them', text: 'Xin chào! Tôi có thể hỗ trợ gì cho bạn?', time: '10:30' },
    { id: 2, sender: 'me', text: 'Tôi muốn hỏi về thủ tục đăng ký kinh doanh', time: '10:31' },
    { id: 3, sender: 'them', text: 'Bạn cần chuẩn bị: CMND/CCCD, hộ khẩu, và địa chỉ kinh doanh', time: '10:32' },
    { id: 4, sender: 'them', text: 'Thời gian xử lý khoảng 5-7 ngày làm việc', time: '10:33' },
    { id: 5, sender: 'me', text: 'Cảm ơn! Còn phí không?', time: '10:34' },
    { id: 6, sender: 'them', text: 'Phí đăng ký là 100.000 VNĐ. Bạn có thể nộp online qua app', time: '10:35' }
  ];

  let officials = [
    { id: 1, name: 'Chủ tịch UBND', department: 'Lãnh đạo', avatar: '👨‍💼', available: true },
    { id: 2, name: 'Phó Chủ tịch', department: 'Lãnh đạo', avatar: '👩‍💼', available: true },
    { id: 3, name: 'Trưởng phòng TT&HC', department: 'HC-Tổ chức', avatar: '👨‍💼', available: true },
    { id: 4, name: 'Trưởng phòng Y tế', department: 'Y tế', avatar: '👩‍⚕️', available: false },
    { id: 5, name: 'Trưởng phòng XD', department: 'Xây dựng', avatar: '👨‍🔧', available: true }
  ];

  function sendMessage() {
    if (!messageText.trim()) return;
    messages = [...messages, {
      id: messages.length + 1,
      sender: 'me',
      text: messageText,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    }];
    messageText = '';
  }

  function startNewChat(official: any) {
    showNewChat = false;
    conversations = [{
      id: official.id,
      name: official.name,
      avatar: official.avatar,
      lastMessage: 'Bắt đầu cuộc trò chuyện',
      time: 'Bây giờ',
      unread: 0,
      online: official.available
    }, ...conversations];
  }
</script>

<div class="chat-page">
  <header class="page-header">
    <h1>💬 Nhắn tin Cán bộ</h1>
  </header>

  <div class="tabs">
    <button class="tab" class:active={activeChat === 'all'} on:click={() => activeChat = 'all'}>
      Tất cả
    </button>
    <button class="tab" class:active={activeChat === 'unread'} on:click={() => activeChat = 'unread'}>
      Chưa đọc
    </button>
    <button class="tab" class:active={activeChat === 'online'} on:click={() => activeChat = 'online'}>
      Đang online
    </button>
  </div>

  <div class="search-box">
    <input type="text" placeholder="Tìm kiếm..." />
  </div>

  <div class="conversations-list">
    {#each activeChat === 'unread' ? conversations.filter(c => c.unread > 0) : 
          activeChat === 'online' ? conversations.filter(c => c.online) : conversations as conv}
      <div class="conversation-item">
        <div class="avatar">{conv.avatar}</div>
        <div class="conv-info">
          <div class="conv-header">
            <span class="conv-name">{conv.name}</span>
            <span class="conv-time">{conv.time}</span>
          </div>
          <div class="conv-preview">
            {conv.lastMessage}
            {#if conv.unread > 0}
              <span class="unread-badge">{conv.unread}</span>
            {/if}
          </div>
        </div>
        {#if conv.online}
          <span class="online-dot"></span>
        {/if}
      </div>
    {/each}
  </div>

  <button class="new-chat-btn" on:click={() => showNewChat = true}>
    + Nhắn tin mới
  </button>

  {#if showNewChat}
    <div class="modal-overlay" on:click={() => showNewChat = false}>
      <div class="modal" on:click|stopPropagation>
        <div class="modal-header">
          <h2>Chọn cán bộ</h2>
          <button class="close-btn" on:click={() => showNewChat = false}>×</button>
        </div>
        <div class="modal-body">
          {#each officials as official}
            <div class="official-item" on:click={() => startNewChat(official)}>
              <div class="official-avatar">{official.avatar}</div>
              <div class="official-info">
                <div class="official-name">{official.name}</div>
                <div class="official-dept">{official.department}</div>
              </div>
              {#if official.available}
                <span class="available-badge">Online</span>
              {/if}
            </div>
          {/each}
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .chat-page {
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

  .tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
  }

  .tab {
    flex: 1;
    padding: 10px;
    border: none;
    background: white;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    color: #6b7280;
  }

  .tab.active {
    background: #3b82f6;
    color: white;
  }

  .search-box {
    margin-bottom: 16px;
  }

  .search-box input {
    width: 100%;
    padding: 12px 16px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    box-sizing: border-box;
  }

  .conversations-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .conversation-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: white;
    cursor: pointer;
    position: relative;
  }

  .conversation-item:active {
    background: #f9fafb;
  }

  .avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
  }

  .conv-info {
    flex: 1;
  }

  .conv-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
  }

  .conv-name {
    font-weight: 600;
    color: #1f2937;
  }

  .conv-time {
    font-size: 12px;
    color: #9ca3af;
  }

  .conv-preview {
    font-size: 14px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .unread-badge {
    background: #ef4444;
    color: white;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
  }

  .online-dot {
    width: 10px;
    height: 10px;
    background: #10b981;
    border-radius: 50%;
    position: absolute;
    bottom: 14px;
    left: 44px;
    border: 2px solid white;
  }

  .new-chat-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    border: none;
    font-size: 24px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5);
    cursor: pointer;
  }

  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: flex-end;
    z-index: 100;
  }

  .modal {
    background: white;
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-height: 80vh;
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
    padding: 16px;
  }

  .official-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 12px;
    cursor: pointer;
  }

  .official-item:active {
    background: #f9fafb;
  }

  .official-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
  }

  .official-info {
    flex: 1;
  }

  .official-name {
    font-weight: 600;
    color: #1f2937;
  }

  .official-dept {
    font-size: 13px;
    color: #6b7280;
  }

  .available-badge {
    font-size: 12px;
    padding: 4px 10px;
    background: #d1fae5;
    color: #059669;
    border-radius: 12px;
  }
</style>