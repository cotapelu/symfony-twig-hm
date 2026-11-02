<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';

  interface Message {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    timestamp: Date;
  }

  interface SuggestedQuestion {
    id: string;
    question: string;
    category: string;
  }

  let messages = writable<Message[]>([]);
  let inputMessage = '';
  let isLoading = false;
  let showSuggestions = true;

  const suggestedQuestions: SuggestedQuestion[] = [
    { id: '1', question: 'Làm thủ tục đăng ký thường trú?', category: 'TTHC' },
    { id: '2', question: 'Xin cấp giấy xác nhận tình trạng hôn nhân?', category: 'TTHC' },
    { id: '3', question: 'Đăng ký khai sinh cho con?', category: 'TTHC' },
    { id: '4', question: 'Làm CCCD ở đâu?', category: 'CCCD' },
    { id: '5', question: 'Giờ làm việc của UBND phường?', category: 'General' },
    { id: '6', question: 'Nộp hồ sơ xin giấy phép xây dựng?', category: 'TTHC' },
  ];

  const quickActions = [
    { id: '1', label: '📋 Tra cứu TTHC', icon: '📋', action: () => {} },
    { id: '2', label: '📅 Đặt lịch hẹn', icon: '📅', action: () => {} },
    { id: '3', label: '📄 Kiểm tra hồ sơ', icon: '📄', action: () => {} },
    { id: '4', label: '📞 Liên hệ hỗ trợ', icon: '📞', action: () => {} },
  ];

  function generateId(): string {
    return Math.random().toString(36).substring(2, 15);
  }

  async function sendMessage() {
    if (!inputMessage.trim() || isLoading) return;

    const userMessage: Message = {
      id: generateId(),
      role: 'user',
      content: inputMessage.trim(),
      timestamp: new Date(),
    };

    messages.update(m => [...m, userMessage]);
    inputMessage = '';
    isLoading = true;
    showSuggestions = false;

    // Simulate AI response
    await new Promise(resolve => setTimeout(resolve, 1500));

    const responses: Record<string, string> = {
      'thường trú': 'Để đăng ký thường trú, bạn cần chuẩn bị:\n1. Đơn đăng ký thường trú (theo mẫu)\n2. CCCD hoặc CMND\n3. Giấy chứng nhận quyền sở hữu nhà ở hoặc hợp đồng thuê nhà\n4. Sổ hộ khẩu\n\nBạn có thể nộp hồ sơ trực tiếp tại UBND phường/xã hoặc qua cổng dịch vụ công trực tuyến.',
      'cccd': 'Để làm CCCD, bạn đến Công an huyện/quận nơi bạn đăng ký thường trú. Mang theo:\n- CMND hoặc Sổ hộ khẩu\n- Ảnh thẻ 3x4\nThời gian xử lý: 7-10 ngày làm việc.',
      'khai sinh': 'Đăng ký khai sinh cần:\n1. Tờ khai đăng ký khai sinh\n2. Giấy chứng sinh (do bệnh viện cấp)\n3. CCCD/CMND của cha mẹ\n4. Sổ hộ khẩu\n\nThời hạn: 30 ngày kể từ ngày sinh.',
      'default': 'Cảm ơn bạn đã hỏi. Để được hỗ trợ tốt hơn, bạn có thể:\n- Gọi đường dây nóng: 1900.xxxx\n- Đến UBND phường/xã trong giờ hành chính\n- Truy cập cổng dịch vụ công trực tuyến\n\nBạn cần hỗ trợ gì khác?'
    };

    let response = responses.default;
    const lowerInput = userMessage.content.toLowerCase();
    
    for (const [key, value] of Object.entries(responses)) {
      if (key !== 'default' && lowerInput.includes(key)) {
        response = value;
        break;
      }
    }

    const assistantMessage: Message = {
      id: generateId(),
      role: 'assistant',
      content: response,
      timestamp: new Date(),
    };

    messages.update(m => [...m, assistantMessage]);
    isLoading = false;
  }

  function handleSuggestedQuestion(question: string) {
    inputMessage = question;
    sendMessage();
  }

  function handleKeyPress(event: KeyboardEvent) {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      sendMessage();
    }
  }

  onMount(() => {
    // Add welcome message
    const welcomeMessage: Message = {
      id: generateId(),
      role: 'assistant',
      content: 'Xin chào! 👋\n\nTôi là trợ lý ảo của UBND phường/xã. Tôi có thể giúp bạn:\n- Tra cứu thủ tục hành chính\n- Hướng dẫn làm CCCD, đăng ký thường trú\n- Giải đáp các câu hỏi về dịch vụ công\n- Đặt lịch hẹn nộp hồ sơ\n\nBạn cần hỗ trợ gì hôm nay?',
      timestamp: new Date(),
    };
    messages.set([welcomeMessage]);
  });
</script>

<div class="chatbot-container">
  <!-- Header -->
  <header class="chat-header">
    <div class="bot-info">
      <div class="bot-avatar">🤖</div>
      <div class="bot-details">
        <h3>Trợ lý ảo Phường/Xã</h3>
        <span class="status">
          <span class="status-dot"></span>
          Hoạt động 24/7
        </span>
      </div>
    </div>
    <div class="header-actions">
      <button class="icon-btn" title="Cài đặt">⚙️</button>
      <button class="icon-btn" title="Lịch sử">📜</button>
    </div>
  </header>

  <!-- Quick Actions -->
  <div class="quick-actions">
    {#each quickActions as action}
      <button class="quick-action-btn" on:click={action.action}>
        <span class="action-icon">{action.icon}</span>
        <span class="action-label">{action.label}</span>
      </button>
    {/each}
  </div>

  <!-- Messages Area -->
  <div class="messages-area">
    {#each $messages as message (message.id)}
      <div class="message {message.role}">
        {#if message.role === 'assistant'}
          <div class="message-avatar">🤖</div>
        {/if}
        <div class="message-content">
          <div class="message-text">{message.content}</div>
          <div class="message-time">
            {message.timestamp.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}
          </div>
        </div>
        {#if message.role === 'user'}
          <div class="message-avatar">👤</div>
        {/if}
      </div>
    {/each}

    {#if isLoading}
      <div class="message assistant">
        <div class="message-avatar">🤖</div>
        <div class="message-content">
          <div class="typing-indicator">
            <span></span><span></span><span></span>
          </div>
        </div>
      </div>
    {/if}
  </div>

  <!-- Suggested Questions -->
  {#if showSuggestions}
    <div class="suggestions">
      <p class="suggestions-title">Câu hỏi phổ biến:</p>
      <div class="suggestions-list">
        {#each suggestedQuestions as sq}
          <button 
            class="suggestion-chip"
            on:click={() => handleSuggestedQuestion(sq.question)}
          >
            {sq.question}
          </button>
        {/each}
      </div>
    </div>
  {/if}

  <!-- Input Area -->
  <div class="input-area">
    <div class="input-wrapper">
      <textarea
        bind:value={inputMessage}
        placeholder="Nhập câu hỏi của bạn..."
        rows="1"
        on:keypress={handleKeyPress}
        disabled={isLoading}
      ></textarea>
      <button 
        class="send-btn" 
        on:click={sendMessage}
        disabled={!inputMessage.trim() || isLoading}
      >
        ➤
      </button>
    </div>
  </div>
</div>

<style>
  .chatbot-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #f8fafc;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  }

  .chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .bot-info {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .bot-avatar {
    width: 48px;
    height: 48px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
  }

  .bot-details h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
  }

  .status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    opacity: 0.9;
  }

  .status-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }

  .header-actions {
    display: flex;
    gap: 8px;
  }

  .icon-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.2s;
  }

  .icon-btn:hover {
    background: rgba(255, 255, 255, 0.3);
  }

  .quick-actions {
    display: flex;
    gap: 8px;
    padding: 12px 16px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    overflow-x: auto;
  }

  .quick-action-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: #f1f5f9;
    border: none;
    border-radius: 20px;
    font-size: 0.8rem;
    color: #475569;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
  }

  .quick-action-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
  }

  .messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .message {
    display: flex;
    gap: 12px;
    max-width: 85%;
  }

  .message.user {
    align-self: flex-end;
    flex-direction: row-reverse;
  }

  .message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
  }

  .message.assistant .message-avatar {
    background: #e0e7ff;
  }

  .message.user .message-avatar {
    background: #dbeafe;
  }

  .message-content {
    padding: 12px 16px;
    border-radius: 16px;
    max-width: calc(100% - 44px);
  }

  .message.assistant .message-content {
    background: white;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .message.user .message-content {
    background: #667eea;
    color: white;
    border-bottom-right-radius: 4px;
  }

  .message-text {
    white-space: pre-wrap;
    line-height: 1.5;
    font-size: 0.9375rem;
  }

  .message.user .message-text {
    color: white;
  }

  .message-time {
    font-size: 0.7rem;
    margin-top: 4px;
    opacity: 0.7;
  }

  .message.user .message-time {
    text-align: right;
  }

  .typing-indicator {
    display: flex;
    gap: 4px;
    padding: 8px;
  }

  .typing-indicator span {
    width: 8px;
    height: 8px;
    background: #94a3b8;
    border-radius: 50%;
    animation: typing 1.4s infinite;
  }

  .typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
  }

  .typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
  }

  @keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-8px); }
  }

  .suggestions {
    padding: 12px 16px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
  }

  .suggestions-title {
    margin: 0 0 8px;
    font-size: 0.8rem;
    color: #64748b;
  }

  .suggestions-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .suggestion-chip {
    padding: 6px 12px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    font-size: 0.8rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
  }

  .suggestion-chip:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .input-area {
    padding: 16px;
    background: white;
    border-top: 1px solid #e2e8f0;
  }

  .input-wrapper {
    display: flex;
    gap: 12px;
    align-items: flex-end;
  }

  .input-wrapper textarea {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 24px;
    font-size: 0.9375rem;
    resize: none;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
  }

  .input-wrapper textarea:focus {
    border-color: #667eea;
  }

  .send-btn {
    width: 48px;
    height: 48px;
    background: #667eea;
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 1.25rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .send-btn:hover:not(:disabled) {
    background: #5a67d8;
    transform: scale(1.05);
  }

  .send-btn:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
  }
</style>
