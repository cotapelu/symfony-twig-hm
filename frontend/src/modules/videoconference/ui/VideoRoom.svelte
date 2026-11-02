<script>
  let roomId = '';
  let participants = [
    { id: 1, name: 'Bạn', isHost: true, video: true, audio: true },
    { id: 2, name: 'User 2', isHost: false, video: true, audio: false }
  ];
  let isScreenSharing = false;
  let chatMessages = [];
  let newMessage = '';
  
  function toggleVideo() {
    participants[0].video = !participants[0].video;
  }
  
  function toggleAudio() {
    participants[0].audio = !participants[0].audio;
  }
  
  function sendMessage() {
    if (newMessage.trim()) {
      chatMessages = [...chatMessages, { user: 'Bạn', text: newMessage }];
      newMessage = '';
    }
  }
</script>

<div class="video-room">
  <div class="video-grid">
    {#each participants as p}
      <div class="video-tile">
        {#if p.video}
          <div class="video-placeholder">📹</div>
        {:else}
          <div class="video-off">Camera tắt</div>
        {/if}
        <span class="participant-name">{p.name} {p.isHost ? '(Host)' : ''}</span>
        <div class="indicators">
          {#if !p.audio}🔇{/if}
        </div>
      </div>
    {/each}
  </div>
  
  <div class="controls-bar">
    <button on:click={toggleAudio}>
      {participants[0].audio ? '🎤' : '🔇'}
    </button>
    <button on:click={toggleVideo}>
      {participants[0].video ? '📹' : '📷'}
    </button>
    <button on:click={() => isScreenSharing = !isScreenSharing}>
      {isScreenSharing ? '📺' : '🖥️'}
    </button>
    <button class="btn-leave">📞 Rời phòng</button>
  </div>
  
  <div class="chat-panel">
    <div class="messages">
      {#each chatMessages as msg}
        <div class="message">
          <strong>{msg.user}:</strong> {msg.text}
        </div>
      {/each}
    </div>
    <input bind:value={newMessage} on:keypress={(e) => e.key === 'Enter' && sendMessage()} placeholder="Nhập tin nhắn..." />
  </div>
</div>

<style>
  .video-room { display: grid; grid-template-columns: 1fr 300px; grid-template-rows: 1fr auto; height: 100vh; }
  .video-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; padding: 16px; }
  .video-tile { background: #1a1a1a; border-radius: 12px; position: relative; display: flex; align-items: center; justify-content: center; min-height: 200px; }
  .video-placeholder { font-size: 64px; }
  .video-off { color: white; font-size: 18px; }
  .participant-name { position: absolute; bottom: 8px; left: 8px; color: white; background: rgba(0,0,0,0.6); padding: 4px 8px; border-radius: 4px; }
  .indicators { position: absolute; top: 8px; right: 8px; }
  .controls-bar { grid-column: 1 / -1; display: flex; justify-content: center; gap: 16px; padding: 16px; background: #2a2a2a; }
  .controls-bar button { width: 48px; height: 48px; border-radius: 50%; border: none; background: #444; color: white; font-size: 20px; cursor: pointer; }
  .btn-leave { width: auto !important; padding: 0 24px !important; border-radius: 24px !important; background: #ef4444 !important; }
  .chat-panel { background: white; border-left: 1px solid #e5e7eb; display: flex; flex-direction: column; }
  .messages { flex: 1; overflow-y: auto; padding: 16px; }
  input { padding: 12px; border: none; border-top: 1px solid #e5e7eb; }
  .message { margin-bottom: 8px; }
</style>
