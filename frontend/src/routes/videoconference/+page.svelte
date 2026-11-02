<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import { browser } from '$app/environment';

  interface Participant {
    id: string;
    name: string;
    stream?: MediaStream;
    isMuted: boolean;
    isVideoOff: boolean;
    isScreenSharing: boolean;
    isSpeaking: boolean;
  }

  interface Room {
    id: string;
    name: string;
    createdAt: string;
    createdBy: string;
    participants: Participant[];
    isActive: boolean;
  }

  interface CallHistory {
    id: string;
    roomId: string;
    roomName: string;
    joinedAt: string;
    leftAt: string;
    duration: number;
    participantCount: number;
  }

  interface MediaState {
    isMuted: boolean;
    isVideoOff: boolean;
    isScreenSharing: boolean;
    localStream: MediaStream | null;
    screenStream: MediaStream | null;
  }

  // State
  let activeRooms: Room[] = [];
  let currentRoom: Room | null = null;
  let callHistory: CallHistory[] = [];
  let newRoomName = '';
  let userName = '';
  let showHistory = false;
  let showJoinModal = false;
  let pendingRoomId: string | null = null;
  let localVideoEl: HTMLVideoElement;
  let screenVideoEl: HTMLVideoElement;
  let errorMessage = '';
  let callStartTime: number | null = null;

  // Media state
  let mediaState: MediaState = {
    isMuted: false,
    isVideoOff: false,
    isScreenSharing: false,
    localStream: null,
    screenStream: null
  };

  // Load data from localStorage
  onMount(() => {
    loadFromStorage();
    
    // Get or set username
    const savedName = localStorage.getItem('videoconference_username');
    if (savedName) {
      userName = savedName;
    } else {
      userName = 'User_' + Math.floor(Math.random() * 1000);
      localStorage.setItem('videoconference_username', userName);
    }

    // Request media permissions on mount
    initializeMedia();
  });

  onDestroy(() => {
    if (mediaState.localStream) {
      mediaState.localStream.getTracks().forEach(track => track.stop());
    }
    if (mediaState.screenStream) {
      mediaState.screenStream.getTracks().forEach(track => track.stop());
    }
  });

  function loadFromStorage() {
    if (!browser) return;

    const savedRooms = localStorage.getItem('videoconference_rooms');
    const savedHistory = localStorage.getItem('videoconference_history');

    if (savedRooms) {
      activeRooms = JSON.parse(savedRooms);
    }

    if (savedHistory) {
      callHistory = JSON.parse(savedHistory);
    }
  }

  function saveToStorage() {
    if (!browser) return;
    localStorage.setItem('videoconference_rooms', JSON.stringify(activeRooms));
    localStorage.setItem('videoconference_history', JSON.stringify(callHistory));
  }

  async function initializeMedia() {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: true
      });
      mediaState.localStream = stream;
    } catch (err) {
      console.log('Media permissions not granted:', err);
      errorMessage = 'Camera/microphone access needed for video calls';
    }
  }

  function createRoom() {
    if (!newRoomName.trim()) return;

    const room: Room = {
      id: 'room_' + Date.now(),
      name: newRoomName.trim(),
      createdAt: new Date().toISOString(),
      createdBy: userName,
      participants: [{
        id: 'user_' + Date.now(),
        name: userName,
        stream: mediaState.localStream || undefined,
        isMuted: false,
        isVideoOff: false,
        isScreenSharing: false,
        isSpeaking: false
      }],
      isActive: true
    };

    activeRooms = [room, ...activeRooms];
    currentRoom = room;
    newRoomName = '';
    callStartTime = Date.now();
    saveToStorage();
  }

  function joinRoom(id: string) {
    const room = activeRooms.find(r => r.id === id);
    if (!room) return;

    // Add user to room
    const newParticipant: Participant = {
      id: 'user_' + Date.now(),
      name: userName,
      stream: mediaState.localStream || undefined,
      isMuted: mediaState.isMuted,
      isVideoOff: mediaState.isVideoOff,
      isScreenSharing: false,
      isSpeaking: false
    };

    room.participants = [...room.participants, newParticipant];
    currentRoom = room;
    callStartTime = Date.now();
    saveToStorage();
  }

  function leaveRoom() {
    if (!currentRoom || !callStartTime) return;

    // Record call history
    const historyEntry: CallHistory = {
      id: 'history_' + Date.now(),
      roomId: currentRoom.id,
      roomName: currentRoom.name,
      joinedAt: new Date(callStartTime).toISOString(),
      leftAt: new Date().toISOString(),
      duration: Math.floor((Date.now() - callStartTime) / 1000),
      participantCount: currentRoom.participants.length
    };

    callHistory = [historyEntry, ...callHistory];

    // Remove from active participants
    currentRoom.participants = currentRoom.participants.filter(
      p => p.name !== userName
    );

    // Clean up empty rooms
    if (currentRoom.participants.length === 0) {
      activeRooms = activeRooms.filter(r => r.id !== currentRoom?.id);
    }

    currentRoom = null;
    callStartTime = null;
    saveToStorage();
  }

  function toggleMute() {
    if (mediaState.localStream) {
      const audioTracks = mediaState.localStream.getAudioTracks();
      audioTracks.forEach(track => {
        track.enabled = !track.enabled;
      });
      mediaState.isMuted = !audioTracks[0]?.enabled ?? true;
    }
  }

  function toggleVideo() {
    if (mediaState.localStream) {
      const videoTracks = mediaState.localStream.getVideoTracks();
      videoTracks.forEach(track => {
        track.enabled = !track.enabled;
      });
      mediaState.isVideoOff = !videoTracks[0]?.enabled ?? true;
    }
  }

  async function toggleScreenShare() {
    if (mediaState.isScreenSharing) {
      // Stop screen sharing
      if (mediaState.screenStream) {
        mediaState.screenStream.getTracks().forEach(track => track.stop());
        mediaState.screenStream = null;
      }
      mediaState.isScreenSharing = false;
    } else {
      try {
        const screenStream = await navigator.mediaDevices.getDisplayMedia({
          video: true,
          audio: true
        });
        mediaState.screenStream = screenStream;
        mediaState.isScreenSharing = true;

        // Handle when user stops sharing via browser UI
        screenStream.getVideoTracks()[0].onended = () => {
          mediaState.isScreenSharing = false;
          mediaState.screenStream = null;
        };
      } catch (err) {
        console.log('Screen share cancelled');
      }
    }
  }

  function clearHistory() {
    callHistory = [];
    saveToStorage();
  }

  function deleteHistoryEntry(id: string) {
    callHistory = callHistory.filter(h => h.id !== id);
    saveToStorage();
  }

  function formatDuration(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
  }

  function formatTime(isoString: string): string {
    return new Date(isoString).toLocaleTimeString();
  }

  function formatDate(isoString: string): string {
    return new Date(isoString).toLocaleDateString();
  }

  // Reactive: update participant media state
  $: if (currentRoom) {
    const me = currentRoom.participants.find(p => p.name === userName);
    if (me) {
      me.isMuted = mediaState.isMuted;
      me.isVideoOff = mediaState.isVideoOff;
      me.isScreenSharing = mediaState.isScreenSharing;
    }
  }
</script>

<div class="videoconference-page">
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <h1>📹 Video Conference</h1>
        <p>Start or join video calls with your team</p>
      </div>
      <div class="header-right">
        <span class="user-badge">👤 {userName}</span>
        <button class="btn-history" on:click={() => showHistory = !showHistory}>
          {showHistory ? '🏠 Rooms' : '📋 History'}
        </button>
      </div>
    </div>
  </header>

  {#if errorMessage}
    <div class="error-banner">
      <span>⚠️ {errorMessage}</span>
      <button on:click={() => errorMessage = ''}>✕</button>
    </div>
  {/if}

  {#if currentRoom}
    <!-- Active Call View -->
    <div class="call-container">
      <div class="call-header">
        <div class="room-info">
          <h2>📞 {currentRoom.name}</h2>
          <span class="participant-count">
            {currentRoom.participants.length} participant{currentRoom.participants.length !== 1 ? 's' : ''}
          </span>
        </div>
        <button class="btn-leave" on:click={leaveRoom}>
          Leave Call
        </button>
      </div>

      <div class="video-grid">
        <!-- Main video (screen share or speaker) -->
        <div class="main-video">
          {#if mediaState.isScreenSharing && mediaState.screenStream}
            <video
              bind:this={screenVideoEl}
              srcObject={mediaState.screenStream}
              autoplay
              playsinline
            />
            <div class="video-label">🖥️ Screen Sharing</div>
          {:else if currentRoom.participants.length > 0}
            <div class="video-placeholder">
              <div class="avatar-large">
                {currentRoom.participants[0].name.charAt(0).toUpperCase()}
              </div>
              <span>{currentRoom.participants[0].name}</span>
            </div>
          {/if}
        </div>

        <!-- Participant grid -->
        <div class="participants-grid">
          {#each currentRoom.participants as participant (participant.id)}
            <div class="participant-video" class:speaking={participant.isSpeaking}>
              {#if participant.isVideoOff}
                <div class="avatar-container">
                  <div class="avatar">
                    {participant.name.charAt(0).toUpperCase()}
                  </div>
                </div>
              {:else if participant.stream}
                <video
                  srcObject={participant.stream}
                  autoplay
                  playsinline
                  muted={participant.name === userName}
                />
              {:else}
                <div class="avatar-container">
                  <div class="avatar">
                    {participant.name.charAt(0).toUpperCase()}
                  </div>
                </div>
              {/if}
              <div class="participant-info">
                <span class="name">
                  {participant.name === userName ? 'You' : participant.name}
                </span>
                <div class="badges">
                  {#if participant.isMuted}🔇{/if}
                  {#if participant.isVideoOff}📷{/if}
                  {#if participant.isScreenSharing}🖥️{/if}
                </div>
              </div>
            </div>
          {/each}
        </div>
      </div>

      <!-- Controls -->
      <div class="call-controls">
        <button 
          class="control-btn" 
          class:active={mediaState.isMuted}
          on:click={toggleMute}
          title={mediaState.isMuted ? 'Unmute' : 'Mute'}
        >
          {mediaState.isMuted ? '🔇' : '🎤'}
          <span>{mediaState.isMuted ? 'Unmute' : 'Mute'}</span>
        </button>

        <button 
          class="control-btn" 
          class:active={mediaState.isVideoOff}
          on:click={toggleVideo}
          title={mediaState.isVideoOff ? 'Turn on camera' : 'Turn off camera'}
        >
          {mediaState.isVideoOff ? '📷' : '🎥'}
          <span>{mediaState.isVideoOff ? 'Start Video' : 'Stop Video'}</span>
        </button>

        <button 
          class="control-btn" 
          class:active={mediaState.isScreenSharing}
          on:click={toggleScreenShare}
          title={mediaState.isScreenSharing ? 'Stop sharing' : 'Share screen'}
        >
          🖥️
          <span>{mediaState.isScreenSharing ? 'Stop Share' : 'Share Screen'}</span>
        </button>

        <button 
          class="control-btn btn-leave-control"
          on:click={leaveRoom}
          title="Leave call"
        >
          📞
          <span>Leave</span>
        </button>
      </div>
    </div>

  {:else if showHistory}
    <!-- Call History View -->
    <div class="history-container">
      <div class="history-header">
        <h2>📋 Call History</h2>
        {#if callHistory.length > 0}
          <button class="btn-clear" on:click={clearHistory}>Clear All</button>
        {/if}
      </div>

      {#if callHistory.length === 0}
        <div class="empty-state">
          <span class="empty-icon">📋</span>
          <h3>No call history yet</h3>
          <p>Your completed calls will appear here</p>
        </div>
      {:else}
        <div class="history-list">
          {#each callHistory as entry (entry.id)}
            <div class="history-card">
              <div class="history-info">
                <h4>{entry.roomName}</h4>
                <div class="history-meta">
                  <span>📅 {formatDate(entry.joinedAt)}</span>
                  <span>🕐 {formatTime(entry.joinedAt)}</span>
                  <span>👥 {entry.participantCount} participants</span>
                </div>
              </div>
              <div class="history-stats">
                <div class="duration">
                  <span class="label">Duration</span>
                  <span class="value">{formatDuration(entry.duration)}</span>
                </div>
                <button 
                  class="btn-rejoin"
                  on:click={() => {
                    const room = activeRooms.find(r => r.id === entry.roomId);
                    if (room) {
                      joinRoom(room.id);
                    }
                  }}
                >
                  Rejoin
                </button>
                <button 
                  class="btn-delete"
                  on:click={() => deleteHistoryEntry(entry.id)}
                >
                  🗑️
                </button>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </div>

  {:else}
    <!-- Room List View -->
    <div class="rooms-container">
      <section class="create-room">
        <div class="create-form">
          <input 
            type="text" 
            placeholder="Enter room name..." 
            bind:value={newRoomName}
            on:keydown={(e) => e.key === 'Enter' && createRoom()}
          />
          <button class="btn-create" on:click={createRoom}>
            ➕ Create Room
          </button>
        </div>
      </section>

      <section class="rooms">
        <h2>🏠 Active Rooms</h2>
        {#if activeRooms.length === 0}
          <div class="empty-state">
            <span class="empty-icon">📹</span>
            <h3>No active rooms</h3>
            <p>Create a room above to start a video call</p>
          </div>
        {:else}
          <div class="room-grid">
            {#each activeRooms as room (room.id)}
              <div class="room-card">
                <div class="room-header">
                  <div class="room-icon">📹</div>
                  <div class="room-info">
                    <h3>{room.name}</h3>
                    <p class="room-meta">
                      Created by {room.createdBy}
                    </p>
                  </div>
                </div>
                <div class="room-stats">
                  <div class="stat">
                    <span class="stat-value">{room.participants.length}</span>
                    <span class="stat-label">Participants</span>
                  </div>
                  <div class="stat">
                    <span class="stat-value">{formatTime(room.createdAt)}</span>
                    <span class="stat-label">Created</span>
                  </div>
                </div>
                <div class="room-actions">
                  <button class="btn-join" on:click={() => joinRoom(room.id)}>
                    Join Call
                  </button>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </section>

      {#if callHistory.length > 0}
        <section class="recent-calls">
          <h2>🕐 Recent Calls</h2>
          <div class="recent-list">
            {#each callHistory.slice(0, 5) as entry (entry.id)}
              <div class="recent-item">
                <span class="recent-name">{entry.roomName}</span>
                <span class="recent-duration">{formatDuration(entry.duration)}</span>
                <button 
                  class="btn-small"
                  on:click={() => {
                    const room = activeRooms.find(r => r.id === entry.roomId);
                    if (room) joinRoom(room.id);
                  }}
                >
                  Rejoin
                </button>
              </div>
            {/each}
          </div>
        </section>
      {/if}
    </div>
  {/if}
</div>

<style>
  :global(:root) {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --surface: #ffffff;
    --surface-elevated: #f8fafc;
    --border: #e2e8f0;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --radius: 12px;
    --radius-sm: 8px;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  }

  .videoconference-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
    padding: 0;
  }

  .header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 1.5rem 2rem;
    box-shadow: var(--shadow);
  }

  .header-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .header-left h1 {
    margin: 0;
    font-size: 1.75rem;
    color: var(--text-primary);
    font-weight: 700;
  }

  .header-left p {
    margin: 0.25rem 0 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .user-badge {
    padding: 0.5rem 1rem;
    background: var(--surface-elevated);
    border-radius: var(--radius);
    font-size: 0.875rem;
    color: var(--text-secondary);
  }

  .btn-history {
    padding: 0.5rem 1rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
  }

  .btn-history:hover {
    background: var(--primary-dark);
  }

  .error-banner {
    max-width: 1400px;
    margin: 1rem auto;
    padding: 1rem;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: var(--radius);
    color: var(--danger);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .error-banner button {
    background: none;
    border: none;
    color: var(--danger);
    cursor: pointer;
    font-size: 1rem;
  }

  .rooms-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
  }

  .create-room {
    margin-bottom: 2rem;
  }

  .create-form {
    display: flex;
    gap: 1rem;
    background: var(--surface);
    padding: 1.5rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
  }

  .create-form input {
    flex: 1;
    padding: 1rem 1.25rem;
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    transition: border-color 0.2s;
  }

  .create-form input:focus {
    outline: none;
    border-color: var(--primary);
  }

  .btn-create {
    padding: 1rem 2rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.2s;
    white-space: nowrap;
  }

  .btn-create:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
  }

  .rooms {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
  }

  .rooms h2 {
    margin: 0 0 1.5rem;
    color: var(--text-primary);
    font-size: 1.25rem;
  }

  .empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
  }

  .empty-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    margin: 0;
    color: var(--text-secondary);
  }

  .empty-state p {
    margin: 0.5rem 0 0;
  }

  .room-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }

  .room-card {
    background: var(--surface-elevated);
    border-radius: var(--radius);
    padding: 1.25rem;
    transition: all 0.2s;
    border: 1px solid var(--border);
  }

  .room-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
  }

  .room-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .room-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary), #818cf8);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
  }

  .room-info h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: 1.1rem;
  }

  .room-meta {
    margin: 0.25rem 0 0;
    color: var(--text-muted);
    font-size: 0.85rem;
  }

  .room-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1rem;
    padding: 0.75rem 0;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
  }

  .stat {
    display: flex;
    flex-direction: column;
  }

  .stat-value {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 1.1rem;
  }

  .stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .room-actions {
    display: flex;
    gap: 0.5rem;
  }

  .btn-join {
    flex: 1;
    padding: 0.75rem;
    background: var(--success);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
  }

  .btn-join:hover {
    background: #059669;
  }

  .recent-calls {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
  }

  .recent-calls h2 {
    margin: 0 0 1rem;
    font-size: 1.1rem;
    color: var(--text-secondary);
  }

  .recent-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .recent-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: var(--surface-elevated);
    border-radius: var(--radius-sm);
  }

  .recent-name {
    flex: 1;
    font-weight: 500;
    color: var(--text-primary);
  }

  .recent-duration {
    color: var(--text-muted);
    font-size: 0.875rem;
  }

  .btn-small {
    padding: 0.375rem 0.75rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 500;
  }

  .call-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
  }

  .call-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .room-info h2 {
    margin: 0;
    color: var(--text-primary);
  }

  .participant-count {
    color: var(--text-secondary);
    font-size: 0.9rem;
  }

  .btn-leave {
    padding: 0.75rem 1.5rem;
    background: var(--danger);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
  }

  .btn-leave:hover {
    background: #dc2626;
  }

  .video-grid {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .main-video {
    background: #0f172a;
    border-radius: var(--radius);
    aspect-ratio: 16/9;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }

  .main-video video {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .video-label {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
  }

  .video-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    color: white;
  }

  .avatar-large {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--primary), #818cf8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 600;
  }

  .participants-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
  }

  .participant-video {
    background: #1e293b;
    border-radius: var(--radius-sm);
    aspect-ratio: 4/3;
    position: relative;
    overflow: hidden;
  }

  .participant-video video {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .participant-video.speaking {
    box-shadow: 0 0 0 3px var(--success);
  }

  .avatar-container {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary), #818cf8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
  }

  .participant-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 0.5rem;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .participant-info .name {
    color: white;
    font-size: 0.8rem;
    font-weight: 500;
  }

  .badges {
    font-size: 0.75rem;
  }

  .call-controls {
    display: flex;
    justify-content: center;
    gap: 1rem;
    padding: 1.5rem;
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
  }

  .control-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    background: var(--surface-elevated);
    border: 2px solid var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s;
    min-width: 100px;
  }

  .control-btn:hover {
    background: var(--border);
  }

  .control-btn.active {
    background: #fef2f2;
    border-color: var(--danger);
    color: var(--danger);
  }

  .control-btn span {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-secondary);
  }

  .control-btn.active span {
    color: var(--danger);
  }

  .btn-leave-control {
    background: #fef2f2;
    border-color: var(--danger);
    color: var(--danger);
  }

  .btn-leave-control:hover {
    background: var(--danger);
    color: white;
  }

  .btn-leave-control span {
    color: var(--danger);
  }

  .btn-leave-control:hover span {
    color: white;
  }

  .history-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 2rem;
  }

  .history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .history-header h2 {
    margin: 0;
    color: var(--text-primary);
  }

  .btn-clear {
    padding: 0.5rem 1rem;
    background: none;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    color: var(--text-secondary);
    font-size: 0.875rem;
    transition: all 0.2s;
  }

  .btn-clear:hover {
    background: #fef2f2;
    border-color: var(--danger);
    color: var(--danger);
  }

  .history-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .history-card {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 1.25rem;
    box-shadow: var(--shadow);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .history-info h4 {
    margin: 0;
    color: var(--text-primary);
    font-size: 1.1rem;
  }

  .history-meta {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: var(--text-muted);
  }

  .history-stats {
    display: flex;
    align-items: center;
    gap: 1.5rem;
  }

  .duration {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .duration .label {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.5px;
  }

  .duration .value {
    font-weight: 600;
    color: var(--text-primary);
  }

  .btn-rejoin {
    padding: 0.5rem 1rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-weight: 500;
    font-size: 0.875rem;
  }

  .btn-rejoin:hover {
    background: var(--primary-dark);
  }

  .btn-delete {
    padding: 0.5rem;
    background: none;
    border: none;
    cursor: pointer;
    opacity: 0.5;
    transition: opacity 0.2s;
  }

  .btn-delete:hover {
    opacity: 1;
  }

  @media (max-width: 1024px) {
    .video-grid {
      grid-template-columns: 1fr;
    }

    .participants-grid {
      grid-template-columns: repeat(4, 1fr);
    }
  }

  @media (max-width: 768px) {
    .header-content {
      flex-direction: column;
      gap: 1rem;
      text-align: center;
    }

    .room-grid {
      grid-template-columns: 1fr;
    }

    .call-controls {
      flex-wrap: wrap;
    }

    .control-btn {
      min-width: 80px;
      padding: 0.75rem;
    }

    .participants-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .history-card {
      flex-direction: column;
      gap: 1rem;
      align-items: flex-start;
    }

    .history-stats {
      width: 100%;
      justify-content: space-between;
    }
  }
</style>
