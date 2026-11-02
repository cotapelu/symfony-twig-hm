<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';

  interface ChatMessage {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    timestamp: Date;
  }

  interface SmartReply {
    text: string;
    action?: () => void;
  }

  // Props
  export let position: 'bottom-right' | 'bottom-left' = 'bottom-right';
  export let initialMessage = 'Xin chào! Tôi là trợ lý AI. Tôi có thể giúp gì cho bạn?';
  export let placeholder = 'Nhập tin nhắn...';
  export let apiEndpoint = '/api/ai/chat';

  let isOpen = false;
  let messages: ChatMessage[] = [];
  let inputMessage = '';
  let loading = false;
  let chatContainer: HTMLDivElement;
  let smartReplies: SmartReply[] = [];

  const unreadCount = writable(0);

  onMount(() => {
    // Add initial message
    addMessage('assistant', initialMessage);
    
    // Load smart replies
    loadSmartReplies();
  });

  function toggleChat() {
    isOpen = !isOpen;
    if (isOpen) {
      setTimeout(() => scrollToBottom(), 100);
    }
  }

  function addMessage(role: 'user' | 'assistant', content: string) {
    const message: ChatMessage = {
      id: Date.now().toString(),
      role,
      content,
      timestamp: new Date()
    };
    messages = [...messages, message];
    
    if (role === 'assistant') {
      unreadCount.update(n => n + 1);
    }
    
    setTimeout(() => scrollToBottom(), 100);
  }

  async function sendMessage() {
    if (!inputMessage.trim() || loading) return;

    const userMessage = inputMessage.trim();
    addMessage('user', userMessage);
    inputMessage = '';
    smartReplies = [];

    loading = true;

    try {
      const response = await fetch(apiEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message: userMessage,
          context: messages.slice(-10)
        })
      });

      if (!response.ok) throw new Error('Failed to get response');

      const data = await response.json();
      addMessage('assistant', data.response || 'Xin lỗi, tôi không hiểu. Bạn có thể nói rõ hơn không?');
      
      // Load new smart replies
      if (data.smartReplies) {
        smartReplies = data.smartReplies.map((text: string) => ({ text }));
      }
    } catch (error) {
      addMessage('assistant', 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại sau.');
    } finally {
      loading = false;
    }
  }

  async function loadSmartReplies() {
    try {
      const response = await fetch('/api/ai/smart-replies', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          conversation_context: messages.slice(-5),
          last_message: messages[messages.length - 1]?.content || ''
        })
      });

      if (response.ok) {
        const data = await response.json();
        smartReplies = data.slice(0, 3).map((text: string) => ({ 
          text,
          action: () => {
            inputMessage = text;
            sendMessage();
          }
        }));
      }
    } catch (error) {
      console.error('Failed to load smart replies:', error);
    }
  }

  function handleKeyPress(event: KeyboardEvent) {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      sendMessage();
    }
  }

  function scrollToBottom() {
    if (chatContainer) {
      chatContainer.scrollTop = chatContainer.scrollHeight;
    }
  }

  function formatTime(date: Date): string {
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
  }

  function useSmartReply(reply: SmartReply) {
    if (reply.action) {
      reply.action();
    } else {
      inputMessage = reply.text;
      sendMessage();
    }
  }
</script>

<div class="ai-chatbot {position}">
  <!-- Chat Button -->
  <button 
    class="chat-button"
    on:click={toggleChat}
    aria-label="Trợ lý AI"
  >
    {#if isOpen}
      <span class="icon">✕</span>
    {:else}
      <span class="icon">🤖</span>
      {#if $unreadCount > 0}
        <span class="badge">{$unreadCount}</span>
      {/if}
    {/if}
  </button>

  <!-- Chat Window -->
  {#if isOpen}
    <div class="chat-window">
      <!-- Header -->
      <div class="chat-header">
        <div class="header-info">
          <span class="avatar">🤖</span>
          <div class="header-text">
            <h3>Trợ lý AI</h3>
            <span class="status">
              <span class="status-dot"></span>
              Trực tuyến
            </span>
          </div>
        </div>
        <button class="btn-close" on:click={toggleChat}>✕</button>
      </div>

      <!-- Messages -->
      <div class="chat-messages" bind:this={chatContainer}>
        {#each messages as message (message.id)}
          <div class="message {message.role}">
            <div class="message-avatar">
              {message.role === 'assistant' ? '🤖' : '👤'}
            </div>
            <div class="message-content">
              <div class="message-bubble">
                {message.content}
              </div>
              <span class="message-time">{formatTime(message.timestamp)}</span>
            </div>
          </div>
        {/each}

        {#if loading}
          <div class="message assistant loading">
            <div class="message-avatar">🤖</div>
            <div class="message-content">
              <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          </div>
        {/if}
      </div>

      <!-- Smart Replies -->
      {#if smartReplies.length > 0}
        <div class="smart-replies">
          {#each smartReplies as reply}
            <button 
              class="smart-reply-btn"
              on:click={() => useSmartReply(reply)}
            >
              {reply.text}
            </button>
          {/each}
        </div>
      {/if}

      <!-- Input -->
      <div class="chat-input">
        <textarea
          bind:value={inputMessage}
          placeholder={placeholder}
          rows="1"
          on:keypress={handleKeyPress}
          on:input={(e) => {
            const target = e.target as HTMLTextAreaElement;
            target.style.height = 'auto';
            target.style.height = Math.min(target.scrollHeight, 100) + 'px';
          }}
        ></textarea>
        <button 
          class="btn-send"
          on:click={sendMessage}
          disabled={!inputMessage.trim() || loading}
          aria-label="Gửi"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="22" y1="2" x2="11" y2="13"></line>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
        </button>
      </div>
    </div>
  {/if}
</div>

<style>
  .ai-chatbot {
    position: fixed;
    z-index: 1000;
  }

  .ai-chatbot.bottom-right {
    bottom: 1.5rem;
    right: 1.5rem;
  }

  .ai-chatbot.bottom-left {
    bottom: 1.5rem;
    left: 1.5rem;
  }

  .chat-button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-size: 1.75rem;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
  }

  .chat-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
  }

  .icon {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    min-width: 20px;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
  }

  .chat-window {
    position: absolute;
    bottom: calc(100% + 1rem);
    right: 0;
    width: 380px;
    height: 500px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease;
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .header-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .avatar {
    font-size: 2rem;
  }

  .header-text h3 {
    margin: 0;
    font-size: 1rem;
  }

  .status {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    opacity: 0.9;
  }

  .status-dot {
    width: 8px;
    height: 8px;
    background: #4ade80;
    border-radius: 50%;
    animation: blink 2s infinite;
  }

  @keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }

  .btn-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.25rem;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s;
  }

  .btn-close:hover {
    opacity: 1;
  }

  .chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .message {
    display: flex;
    gap: 0.75rem;
    max-width: 85%;
  }

  .message.user {
    align-self: flex-end;
    flex-direction: row-reverse;
  }

  .message-avatar {
    font-size: 1.5rem;
    flex-shrink: 0;
  }

  .message-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .message-bubble {
    padding: 0.75rem 1rem;
    border-radius: 18px;
    font-size: 0.9375rem;
    line-height: 1.5;
  }

  .message.assistant .message-bubble {
    background: #f3f4f6;
    color: #1f2937;
    border-bottom-left-radius: 4px;
  }

  .message.user .message-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom-right-radius: 4px;
  }

  .message-time {
    font-size: 0.75rem;
    color: #9ca3af;
  }

  .typing-indicator {
    display: flex;
    gap: 4px;
    padding: 0.75rem 1rem;
    background: #f3f4f6;
    border-radius: 18px;
    border-bottom-left-radius: 4px;
  }

  .typing-indicator span {
    width: 8px;
    height: 8px;
    background: #9ca3af;
    border-radius: 50%;
    animation: bounce 1.4s infinite ease-in-out;
  }

  .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
  .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

  @keyframes bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
  }

  .smart-replies {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-top: 1px solid #e5e7eb;
  }

  .smart-reply-btn {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border: none;
    border-radius: 16px;
    font-size: 0.875rem;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.2s;
  }

  .smart-reply-btn:hover {
    background: #e5e7eb;
    color: #1f2937;
  }

  .chat-input {
    display: flex;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
    background: white;
  }

  .chat-input textarea {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 24px;
    font-size: 0.9375rem;
    resize: none;
    outline: none;
    transition: border-color 0.2s;
  }

  .chat-input textarea:focus {
    border-color: #667eea;
  }

  .btn-send {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }

  .btn-send:hover:not(:disabled) {
    transform: scale(1.05);
  }

  .btn-send:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
</style>
