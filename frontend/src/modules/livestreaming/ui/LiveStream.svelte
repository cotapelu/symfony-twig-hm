<script>
  let viewerCount = 156;
  let messages = [{user: 'User1', message: 'Hello!'}];
  let newMessage = '';
  let isLive = true;
  
  function sendMessage() {
    if (!newMessage.trim()) return;
    messages = [...messages, { user: 'You', message: newMessage }];
    newMessage = '';
  }
</script>

<div class="live-stream">
  <div class="video-section">
    <div class="live-badge">LIVE</div>
    <div class="viewer-count">👁 {viewerCount} ngườí xem</div>
  </div>
  <div class="chat-section">
    <div class="chat-header">💬 Chat</div>
    <div class="messages">
      {#each messages as msg}
        <div><b>{msg.user}:</b> {msg.message}</div>
      {/each}
    </div>
    <input bind:value={newMessage} on:keypress={(e) => e.key === 'Enter' && sendMessage()} placeholder="Nhập tin nhắn..." />
    <button on:click={sendMessage}>Gửi</button>
  </div>
</div>
