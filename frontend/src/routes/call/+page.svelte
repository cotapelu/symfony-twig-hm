<script lang="ts">
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';

  // Types
  interface Contact {
    id: string;
    name: string;
    avatar: string;
    status: 'online' | 'offline' | 'busy' | 'away';
    lastSeen?: string;
  }

  interface CallHistoryItem {
    id: string;
    contactId: string;
    contactName: string;
    contactAvatar: string;
    type: 'incoming' | 'outgoing' | 'missed';
    timestamp: string;
    duration: number;
  }

  type CallState = 'idle' | 'calling' | 'ringing' | 'connected' | 'ended';
  type ViewMode = 'contacts' | 'history' | 'dialer';

  // State
  let currentView: ViewMode = 'contacts';
  let callState: CallState = 'idle';
  let selectedContact: Contact | null = null;
  let isMuted = false;
  let isCameraOn = true;
  let isSpeakerOn = false;
  let callDuration = 0;
  let callTimer: ReturnType<typeof setInterval> | null = null;

  // Mock contacts
  const contacts: Contact[] = [
    { id: '1', name: 'Nguyen Van A', avatar: '👨', status: 'online' },
    { id: '2', name: 'Tran Thi B', avatar: '👩', status: 'online' },
    { id: '3', name: 'Le Van C', avatar: '👨‍💼', status: 'away' },
    { id: '4', name: 'Pham Thi D', avatar: '👩‍💼', status: 'busy' },
    { id: '5', name: 'Hoang Van E', avatar: '👨‍🦰', status: 'offline', lastSeen: '5 phút trước' },
    { id: '6', name: 'Nguyen Thi F', avatar: '👩‍🦰', status: 'online' },
    { id: '7', name: 'Vo Van G', avatar: '🧑', status: 'offline', lastSeen: '1 giờ trước' },
    { id: '8', name: 'Truong Thi H', avatar: '👩‍🦱', status: 'online' },
  ];

  // Mock call history
  let callHistory: CallHistoryItem[] = [
    { id: '1', contactId: '1', contactName: 'Nguyen Van A', contactAvatar: '👨', type: 'incoming', timestamp: '2024-01-15T10:30:00', duration: 320 },
    { id: '2', contactId: '2', contactName: 'Tran Thi B', contactAvatar: '👩', type: 'outgoing', timestamp: '2024-01-15T09:15:00', duration: 180 },
    { id: '3', contactId: '3', contactName: 'Le Van C', contactAvatar: '👨‍💼', type: 'missed', timestamp: '2024-01-14T22:45:00', duration: 0 },
    { id: '4', contactId: '4', contactName: 'Pham Thi D', contactAvatar: '👩‍💼', type: 'incoming', timestamp: '2024-01-14T18:20:00', duration: 540 },
    { id: '5', contactId: '5', contactName: 'Hoang Van E', contactAvatar: '👨‍🦰', type: 'outgoing', timestamp: '2024-01-14T14:00:00', duration: 60 },
    { id: '6', contactId: '6', contactName: 'Nguyen Thi F', contactAvatar: '👩‍🦰', type: 'missed', timestamp: '2024-01-13T16:30:00', duration: 0 },
  ];

  // Load from localStorage
  onMount(() => {
    if (browser) {
      const saved = localStorage.getItem('call_history');
      if (saved) {
        callHistory = JSON.parse(saved);
      }
    }
  });

  function saveHistory() {
    if (browser) {
      localStorage.setItem('call_history', JSON.stringify(callHistory));
    }
  }

  // Call functions
  function startCall(contact: Contact) {
    selectedContact = contact;
    callState = 'calling';
    
    // Simulate call connecting
    setTimeout(() => {
      callState = 'ringing';
    }, 1000);
    
    // Simulate answered
    setTimeout(() => {
      callState = 'connected';
      callDuration = 0;
      callTimer = setInterval(() => {
        callDuration++;
      }, 1000);
    }, 3000);
  }

  function endCall() {
    if (callTimer) {
      clearInterval(callTimer);
      callTimer = null;
    }
    
    // Add to history
    if (selectedContact) {
      const historyItem: CallHistoryItem = {
        id: Date.now().toString(),
        contactId: selectedContact.id,
        contactName: selectedContact.name,
        contactAvatar: selectedContact.avatar,
        type: 'outgoing',
        timestamp: new Date().toISOString(),
        duration: callDuration
      };
      callHistory = [historyItem, ...callHistory];
      saveHistory();
    }
    
    callState = 'ended';
    setTimeout(() => {
      callState = 'idle';
      selectedContact = null;
      callDuration = 0;
    }, 1500);
  }

  function acceptCall() {
    if (callTimer) {
      clearInterval(callTimer);
    }
    callState = 'connected';
    callDuration = 0;
    callTimer = setInterval(() => {
      callDuration++;
    }, 1000);
  }

  function declineCall() {
    if (callTimer) {
      clearInterval(callTimer);
    }
    callState = 'idle';
    selectedContact = null;
  }

  function toggleMute() {
    isMuted = !isMuted;
  }

  function toggleCamera() {
    isCameraOn = !isCameraOn;
  }

  function toggleSpeaker() {
    isSpeakerOn = !isSpeakerOn;
  }

  // Format functions
  function formatDuration(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  }

  function formatTime(timestamp: string): string {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Vừa xong';
    if (diffMins < 60) return `${diffMins} phút trước`;
    if (diffHours < 24) return `${diffHours} giờ trước`;
    if (diffDays < 7) return `${diffDays} ngày trước`;
    
    return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
  }

  function getStatusColor(status: string): string {
    switch (status) {
      case 'online': return '#10b981';
      case 'away': return '#f59e0b';
      case 'busy': return '#ef4444';
      default: return '#94a3b8';
    }
  }

  function getCallTypeIcon(type: string): string {
    switch (type) {
      case 'incoming': return '📲';
      case 'outgoing': return '📤';
      case 'missed': return '❌';
      default: return '📞';
    }
  }

  function getCallTypeColor(type: string): string {
    switch (type) {
      case 'incoming': return '#10b981';
      case 'outgoing': return '#3b82f6';
      case 'missed': return '#ef4444';
      default: return '#64748b';
    }
  }
</script>

<svelte:head>
  <title>Call - Hotel Booking App</title>
</svelte:head>

<div class="call-page">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <h2>📞 Gọi điện</h2>
    </div>

    <nav class="nav-tabs">
      <button 
        class="nav-tab" 
        class:active={currentView === 'contacts'}
        on:click={() => currentView = 'contacts'}
      >
        <span class="tab-icon">👥</span>
        <span class="tab-label">Danh bạ</span>
      </button>
      <button 
        class="nav-tab" 
        class:active={currentView === 'history'}
        on:click={() => currentView = 'history'}
      >
        <span class="tab-icon">📜</span>
        <span class="tab-label">Lịch sử</span>
      </button>
    </nav>

    <div class="sidebar-content">
      {#if currentView === 'contacts'}
        <div class="contact-list">
          {#each contacts as contact}
            <button class="contact-item" on:click={() => startCall(contact)}>
              <div class="avatar-wrapper">
                <span class="avatar">{contact.avatar}</span>
                <span class="status-dot" style="background: {getStatusColor(contact.status)}"></span>
              </div>
<div class="contact-info">
							<span class="contact-name">{contact.name}</span>
							<span class="contact-status">
								{#if contact.status === 'online'}
									Đang hoạt động
								{:else if contact.lastSeen}
									{contact.lastSeen}
								{:else}
									{contact.status === 'away' ? 'Vắng mặt' : 'Ngoại tuyến'}
								{/if}
							</span>
						</div>
						<div class="call-btn" title="Gọi video" on:click|stopPropagation={() => startCall(contact)}>📹</div>
					</button>
          {/each}
        </div>
      {:else if currentView === 'history'}
        <div class="history-list">
          {#each callHistory as item}
            <div class="history-item">
              <div class="avatar">{item.contactAvatar}</div>
              <div class="history-info">
                <span class="history-name">{item.contactName}</span>
                <div class="history-meta">
                  <span class="call-type" style="color: {getCallTypeColor(item.type)}">
                    {getCallTypeIcon(item.type)} 
                    {item.type === 'incoming' ? 'Đến' : item.type === 'outgoing' ? 'Đi' : 'Nhỡ'}
                  </span>
                  <span class="history-time">{formatTime(item.timestamp)}</span>
                </div>
              </div>
              <div class="history-duration">
                {#if item.duration > 0}
                  {formatDuration(item.duration)}
                {:else}
                  --
                {/if}
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </div>
  </aside>

  <!-- Main Area -->
  <main class="main-area">
    <!-- Call Screens -->
    {#if callState === 'idle'}
      <div class="idle-screen">
        <div class="idle-content">
          <div class="idle-icon">📹</div>
          <h2>Gọi video</h2>
          <p>Chọn một liên hệ để bắt đầu cuộc gọi</p>
        </div>
      </div>

    {:else if callState === 'calling'}
      <div class="calling-screen">
        <div class="caller-info">
          <div class="caller-avatar">
            {selectedContact?.avatar}
          </div>
          <h2>{selectedContact?.name}</h2>
          <p class="calling-status">Đang gọi...</p>
          <div class="calling-animation">
            <span class="pulse"></span>
            <span class="pulse"></span>
            <span class="pulse"></span>
          </div>
        </div>
        <div class="call-actions">
          <button class="action-btn end-call" on:click={endCall}>
            <span>📵</span>
            <span>Kết thúc</span>
          </button>
        </div>
      </div>

    {:else if callState === 'ringing'}
      <div class="ringing-screen">
        <div class="caller-info">
          <div class="caller-avatar ringing">
            {selectedContact?.avatar}
          </div>
          <h2>{selectedContact?.name}</h2>
          <p class="ringing-status">Đang đổ chuông...</p>
        </div>
        <div class="call-actions">
          <button class="action-btn decline" on:click={declineCall}>
            <span>📵</span>
            <span>Từ chối</span>
          </button>
          <button class="action-btn accept" on:click={acceptCall}>
            <span>📞</span>
            <span>Trả lời</span>
          </button>
        </div>
      </div>

    {:else if callState === 'connected'}
      <div class="connected-screen">
        <div class="video-area">
          <div class="remote-video">
            <div class="video-placeholder">
              <span class="placeholder-avatar">{selectedContact?.avatar}</span>
              <span class="placeholder-name">{selectedContact?.name}</span>
            </div>
          </div>
          <div class="local-video" class:off={!isCameraOn}>
            {#if isCameraOn}
              <div class="video-placeholder small">
                <span>📷</span>
              </div>
            {:else}
              <div class="camera-off">
                <span>📷</span>
              </div>
            {/if}
          </div>
        </div>
        
        <div class="call-info">
          <span class="call-timer">{formatDuration(callDuration)}</span>
          <span class="call-name">{selectedContact?.name}</span>
        </div>

        <div class="call-controls">
          <button 
            class="control-btn" 
            class:active={isMuted}
            on:click={toggleMute}
            title={isMuted ? 'Bật tiếng' : 'Tắt tiếng'}
          >
            <span>{isMuted ? '🔇' : '🎤'}</span>
          </button>
          <button 
            class="control-btn" 
            class:active={!isCameraOn}
            on:click={toggleCamera}
            title={isCameraOn ? 'Tắt camera' : 'Bật camera'}
          >
            <span>{isCameraOn ? '📹' : '📷'}</span>
          </button>
          <button 
            class="control-btn" 
            class:active={isSpeakerOn}
            on:click={toggleSpeaker}
            title={isSpeakerOn ? 'Tắt loa' : 'Bật loa'}
          >
            <span>{isSpeakerOn ? '🔊' : '🔈'}</span>
          </button>
          <button class="control-btn end" on:click={endCall} title="Kết thúc">
            <span>📵</span>
          </button>
        </div>
      </div>

    {:else if callState === 'ended'}
      <div class="ended-screen">
        <div class="ended-content">
          <div class="ended-icon">✓</div>
          <h2>Cuộc gọi đã kết thúc</h2>
          <p>Thời gian gọi: {formatDuration(callDuration)}</p>
        </div>
      </div>
    {/if}
  </main>
</div>

<style>
  .call-page {
    display: flex;
    height: calc(100vh - 60px);
    background: #f8fafc;
  }

  /* Sidebar */
  .sidebar {
    width: 320px;
    background: white;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
  }

  .sidebar-header {
    padding: 1.25rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .sidebar-header h2 {
    margin: 0;
    font-size: 1.125rem;
    color: #1e293b;
  }

  .nav-tabs {
    display: flex;
    padding: 0.75rem;
    gap: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .nav-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    color: #64748b;
    font-size: 0.875rem;
  }

  .nav-tab:hover {
    background: #f1f5f9;
  }

  .nav-tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .tab-icon {
    font-size: 1rem;
  }

  .sidebar-content {
    flex: 1;
    overflow-y: auto;
  }

  /* Contact List */
  .contact-list {
    padding: 0.5rem;
  }

  .contact-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border: none;
    background: transparent;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
  }

  .contact-item:hover {
    background: #f1f5f9;
  }

  .avatar-wrapper {
    position: relative;
  }

  .avatar {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 50%;
    font-size: 1.5rem;
  }

  .status-dot {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
  }

  .contact-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    min-width: 0;
  }

  .contact-name {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #1e293b;
  }

  .contact-status {
    font-size: 0.75rem;
    color: #64748b;
  }

  .call-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    transition: transform 0.2s;
  }

  .call-btn:hover {
    transform: scale(1.1);
  }

  /* History List */
  .history-list {
    padding: 0.5rem;
  }

  .history-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 10px;
    transition: background 0.2s;
  }

  .history-item:hover {
    background: #f1f5f9;
  }

  .history-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    min-width: 0;
  }

  .history-name {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #1e293b;
  }

  .history-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
  }

  .call-type {
    font-weight: 500;
  }

  .history-time {
    color: #94a3b8;
  }

  .history-duration {
    font-size: 0.875rem;
    color: #64748b;
    font-variant-numeric: tabular-nums;
  }

  /* Main Area */
  .main-area {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* Idle Screen */
  .idle-screen {
    text-align: center;
    padding: 2rem;
  }

  .idle-icon {
    font-size: 5rem;
    margin-bottom: 1rem;
    opacity: 0.3;
  }

  .idle-content h2 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
    font-size: 1.5rem;
  }

  .idle-content p {
    margin: 0;
    color: #64748b;
  }

  /* Calling Screen */
  .calling-screen,
  .ringing-screen {
    text-align: center;
    padding: 2rem;
  }

  .caller-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }

  .caller-avatar {
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    font-size: 4rem;
    animation: pulse-glow 2s infinite;
  }

  .caller-avatar.ringing {
    animation: ring 1s infinite;
  }

  @keyframes pulse-glow {
    0%, 100% {
      box-shadow: 0 0 20px rgba(102, 126, 234, 0.4);
    }
    50% {
      box-shadow: 0 0 40px rgba(102, 126, 234, 0.6);
    }
  }

  @keyframes ring {
    0%, 100% { transform: rotate(-5deg); }
    50% { transform: rotate(5deg); }
  }

  .caller-info h2 {
    margin: 0;
    font-size: 1.75rem;
    color: #1e293b;
  }

  .calling-status,
  .ringing-status {
    margin: 0;
    color: #64748b;
    font-size: 1rem;
  }

  .calling-animation {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 1rem;
  }

  .pulse {
    width: 12px;
    height: 12px;
    background: #667eea;
    border-radius: 50%;
    animation: pulse 1.4s infinite ease-in-out;
  }

  .pulse:nth-child(2) {
    animation-delay: 0.2s;
  }

  .pulse:nth-child(3) {
    animation-delay: 0.4s;
  }

  @keyframes pulse {
    0%, 80%, 100% {
      transform: scale(0.6);
      opacity: 0.5;
    }
    40% {
      transform: scale(1);
      opacity: 1;
    }
  }

  .call-actions {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    margin-top: 3rem;
  }

  .action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border: none;
    border-radius: 16px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.2s;
  }

  .action-btn span:first-child {
    font-size: 2rem;
  }

  .action-btn.end-call,
  .action-btn.decline {
    background: #fee2e2;
    color: #dc2626;
  }

  .action-btn.decline:hover {
    background: #fecaca;
  }

  .action-btn.accept {
    background: #dcfce7;
    color: #16a34a;
  }

  .action-btn.accept:hover {
    background: #bbf7d0;
  }

  /* Connected Screen */
  .connected-screen {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #0f172a;
  }

  .video-area {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .remote-video {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  }

  .video-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }

  .placeholder-avatar {
    font-size: 6rem;
  }

  .placeholder-name {
    color: #94a3b8;
    font-size: 1.25rem;
  }

  .local-video {
    position: absolute;
    bottom: 1.5rem;
    right: 1.5rem;
    width: 180px;
    height: 135px;
    background: #1e293b;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #334155;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .local-video.off {
    background: #334155;
  }

  .video-placeholder.small span {
    font-size: 2rem;
  }

  .camera-off {
    color: #64748b;
    font-size: 2rem;
  }

  .call-info {
    position: absolute;
    top: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
  }

  .call-timer {
    font-size: 1.25rem;
    color: white;
    font-variant-numeric: tabular-nums;
  }

  .call-name {
    font-size: 1rem;
    color: #94a3b8;
  }

  .call-controls {
    display: flex;
    justify-content: center;
    gap: 1rem;
    padding: 1.5rem;
    background: rgba(0, 0, 0, 0.5);
  }

  .control-btn {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 50%;
    background: #334155;
    cursor: pointer;
    font-size: 1.5rem;
    transition: all 0.2s;
  }

  .control-btn:hover {
    background: #475569;
    transform: scale(1.1);
  }

  .control-btn.active {
    background: #ef4444;
  }

  .control-btn.end {
    background: #dc2626;
  }

  .control-btn.end:hover {
    background: #b91c1c;
  }

  /* Ended Screen */
  .ended-screen {
    text-align: center;
    padding: 2rem;
  }

  .ended-icon {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #dcfce7;
    color: #16a34a;
    border-radius: 50%;
    font-size: 2.5rem;
    margin: 0 auto 1.5rem;
  }

  .ended-content h2 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
    font-size: 1.5rem;
  }

  .ended-content p {
    margin: 0;
    color: #64748b;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      z-index: 100;
      transform: translateX(-100%);
      transition: transform 0.3s;
    }

    .sidebar.open {
      transform: translateX(0);
    }

    .local-video {
      width: 100px;
      height: 75px;
      bottom: 1rem;
      right: 1rem;
    }

    .control-btn {
      width: 48px;
      height: 48px;
      font-size: 1.25rem;
    }
  }
</style>
