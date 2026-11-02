<script lang="ts">
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';

  interface Message {
    id: string;
    role: 'user' | 'bot';
    content: string;
    timestamp: string;
    status?: 'sending' | 'sent' | 'delivered';
  }

  interface Conversation {
    id: string;
    title: string;
    messages: Message[];
    createdAt: string;
    updatedAt: string;
  }

  interface QuickSuggestion {
    label: string;
    icon: string;
    prompt: string;
    category: string;
  }

  // State
  let conversations: Conversation[] = [];
  let currentConversationId: string | null = null;
  let inputMessage = '';
  let loading = false;
  let isTyping = false;
  let showSidebar = true;
  let searchQuery = '';
  let messagesContainer: HTMLElement;

  // Quick suggestions
  const quickSuggestions: QuickSuggestion[] = [
    { label: 'Check Orders', icon: '📦', prompt: 'Check my recent orders', category: 'orders' },
    { label: 'Find Restaurants', icon: '🍽️', prompt: 'Find nearby restaurants', category: 'food' },
    { label: 'Book Doctor', icon: '👨‍⚕️', prompt: 'I need to book a doctor appointment', category: 'health' },
    { label: 'Wallet Balance', icon: '💰', prompt: 'Check my wallet balance', category: 'wallet' },
    { label: 'Flight Status', icon: '✈️', prompt: 'Check flight status for my trip', category: 'travel' },
    { label: 'Hotel Booking', icon: '🏨', prompt: 'I want to book a hotel', category: 'travel' },
    { label: 'Cancel Order', icon: '❌', prompt: 'How do I cancel my order?', category: 'orders' },
    { label: 'Refund Status', icon: '↩️', prompt: 'What is the status of my refund?', category: 'orders' },
    { label: 'Payment Issues', icon: '💳', prompt: 'I have a payment problem', category: 'support' },
    { label: 'Track Delivery', icon: '🚚', prompt: 'Track my package delivery', category: 'orders' },
    { label: 'Loyalty Points', icon: '⭐', prompt: 'How many loyalty points do I have?', category: 'account' },
    { label: 'Help Center', icon: '❓', prompt: 'Show me the help center', category: 'support' }
  ];

  // Get current conversation
  $: currentConversation = conversations.find(c => c.id === currentConversationId);
  $: messages = currentConversation?.messages || [];

  // Filtered suggestions
  $: filteredSuggestions = searchQuery
    ? quickSuggestions.filter(s => 
        s.label.toLowerCase().includes(searchQuery.toLowerCase()) ||
        s.prompt.toLowerCase().includes(searchQuery.toLowerCase())
      )
    : quickSuggestions;

  onMount(() => {
    loadConversations();
    
    // Create initial conversation if none exists
    if (conversations.length === 0) {
      createNewConversation();
    }
  });

  function loadConversations() {
    if (!browser) return;

    const saved = localStorage.getItem('chatbot_conversations');
    const savedCurrentId = localStorage.getItem('chatbot_current_conversation');

    if (saved) {
      conversations = JSON.parse(saved);
    }

    if (savedCurrentId && conversations.find(c => c.id === savedCurrentId)) {
      currentConversationId = savedCurrentId;
    } else if (conversations.length > 0) {
      currentConversationId = conversations[0].id;
    }
  }

  function saveConversations() {
    if (!browser) return;
    localStorage.setItem('chatbot_conversations', JSON.stringify(conversations));
    if (currentConversationId) {
      localStorage.setItem('chatbot_current_conversation', currentConversationId);
    }
  }

  function createNewConversation() {
    const newConv: Conversation = {
      id: Date.now().toString(),
      title: 'New Conversation',
      messages: [
        {
          id: (Date.now() - 1).toString(),
          role: 'bot',
          content: "Hello! I'm your AI assistant. How can I help you today? You can ask me about orders, bookings, payments, or any general questions.",
          timestamp: new Date().toISOString(),
          status: 'delivered'
        }
      ],
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    conversations = [newConv, ...conversations];
    currentConversationId = newConv.id;
    saveConversations();
    
    // Scroll to bottom
    setTimeout(scrollToBottom, 100);
  }

  function switchConversation(id: string) {
    currentConversationId = id;
    saveConversations();
    setTimeout(scrollToBottom, 100);
  }

  function deleteConversation(id: string, e: Event) {
    e.stopPropagation();
    conversations = conversations.filter(c => c.id !== id);
    
    if (currentConversationId === id) {
      currentConversationId = conversations.length > 0 ? conversations[0].id : null;
      if (!currentConversationId) {
        createNewConversation();
      }
    }
    saveConversations();
  }

  function updateConversationTitle(firstMessage: string) {
    if (!currentConversation) return;
    
    // Generate title from first message
    const words = firstMessage.split(' ').slice(0, 4).join(' ');
    const title = words.length < firstMessage.length ? words + '...' : words;
    
    conversations = conversations.map(c => 
      c.id === currentConversationId 
        ? { ...c, title, updatedAt: new Date().toISOString() }
        : c
    );
    saveConversations();
  }

  async function sendMessage(customPrompt?: string) {
    const prompt = customPrompt || inputMessage;
    if (!prompt.trim() || !currentConversationId) return;

    // Add user message
    const userMsg: Message = {
      id: Date.now().toString(),
      role: 'user',
      content: prompt,
      timestamp: new Date().toISOString(),
      status: 'sent'
    };

    // Update conversation
    conversations = conversations.map(c => 
      c.id === currentConversationId 
        ? { 
            ...c, 
            messages: [...c.messages, userMsg],
            updatedAt: new Date().toISOString()
          }
        : c
    );

    // Update title if first user message
    const isFirstUserMessage = messages.filter(m => m.role === 'user').length === 0;
    if (isFirstUserMessage) {
      updateConversationTitle(prompt);
    }

    inputMessage = '';
    saveConversations();
    scrollToBottom();

    // Simulate bot typing
    isTyping = true;
    
    // Simulate AI response delay
    setTimeout(() => {
      const botResponse = generateBotResponse(prompt);
      
      const botMsg: Message = {
        id: (Date.now() + 1).toString(),
        role: 'bot',
        content: botResponse,
        timestamp: new Date().toISOString(),
        status: 'delivered'
      };

      conversations = conversations.map(c => 
        c.id === currentConversationId 
          ? { 
              ...c, 
              messages: [...c.messages, botMsg],
              updatedAt: new Date().toISOString()
            }
          : c
      );

      isTyping = false;
      saveConversations();
      scrollToBottom();
    }, 800 + Math.random() * 1000);
  }

  function generateBotResponse(userInput: string): string {
    const input = userInput.toLowerCase();
    
    // Order-related responses
    if (input.includes('order') || input.includes('orders')) {
      if (input.includes('check') || input.includes('status') || input.includes('track')) {
        return "I can help you check your orders! 📦 You currently have 3 active orders:\n\n1. **Order #12345** - Electronics - Status: Shipped\n2. **Order #12346** - Clothing - Status: Processing\n3. **Order #12347** - Books - Status: Delivered\n\nWould you like more details about any specific order?";
      }
      if (input.includes('cancel')) {
        return "To cancel an order, please follow these steps:\n\n1. Go to **My Orders** in your account\n2. Select the order you want to cancel\n3. Click **Cancel Order** button\n4. Confirm the cancellation\n\n⚠️ Note: Orders can only be cancelled before they are shipped. Would you like me to check if your specific order is eligible for cancellation?";
      }
      return "I can help you with your orders! You can:\n• Check order status\n• Track delivery\n• Cancel orders\n• View order history\n\nWhat would you like to do?";
    }

    // Food/restaurant related
    if (input.includes('restaurant') || input.includes('food') || input.includes('eat')) {
      return "🍽️ Here are some popular restaurants near you:\n\n1. **Saigon Kitchen** - Vietnamese - ⭐ 4.5 - 15 min\n2. **Tokyo Ramen** - Japanese - ⭐ 4.7 - 20 min\n3. **Pizza Napoli** - Italian - ⭐ 4.3 - 25 min\n4. **Burger House** - American - ⭐ 4.4 - 10 min\n\nWould you like to browse these restaurants or search for a specific cuisine?";
    }

    // Doctor/health related
    if (input.includes('doctor') || input.includes('health') || input.includes('medical') || input.includes('appointment')) {
      return "👨‍⚕️ I can help you book a doctor appointment! \n\n**Available Specialties:**\n• General Medicine\n• Cardiology\n• Dermatology\n• Pediatrics\n• Orthopedics\n\nPlease tell me:\n1. What specialty do you need?\n2. Preferred date and time\n3. Your location\n\nI'll find the best doctors available!";
    }

    // Wallet related
    if (input.includes('wallet') || input.includes('balance') || input.includes('money')) {
      return "💰 Your wallet balance is currently **₫5,000,000**\n\n**Recent Transactions:**\n• +₫500,000 - Top up (2 days ago)\n• -₫250,000 - Hotel Booking (5 days ago)\n• -₫100,000 - Transfer to John (7 days ago)\n\nWould you like to:\n• Top up your wallet\n• Transfer money\n• View full transaction history?";
    }

    // Flight/travel related
    if (input.includes('flight') || input.includes('book') || input.includes('hotel') || input.includes('travel')) {
      return "✈️ I can help you with travel bookings!\n\n**Flight Search:**\nTell me your:\n• Departure city\n• Destination\n• Travel date\n• Number of passengers\n\n**Hotel Booking:**\nI can search hotels by:\n• Location\n• Check-in/Check-out dates\n• Number of guests\n• Price range\n\nWhat would you like to book?";
    }

    // Payment related
    if (input.includes('payment') || input.includes('pay') || input.includes('refund')) {
      if (input.includes('refund')) {
        return "↩️ For refund inquiries, here's what you need to know:\n\n**Refund Status:**\n• Order #12345 - Refund processing (3-5 business days)\n\n**Refund Methods:**\n• Original payment method\n• Wallet credit (instant)\n\nIs there a specific order's refund status you'd like to check?";
      }
      return "💳 I can help you with payment-related issues!\n\n**Available Payment Methods:**\n• Credit/Debit Card\n• Bank Transfer\n• Digital Wallet\n• Cash at convenience store\n\nWhat payment issue can I help you with today?";
    }

    // Loyalty/points
    if (input.includes('loyalty') || input.includes('points') || input.includes('rewards')) {
      return "⭐ **Your Loyalty Points**\n\n• Available Points: **12,500**\n• Tier: Gold Member\n• Points Expiring: 2,000 (in 30 days)\n\n**Redeem For:**\n• Discount vouchers\n• Free bookings\n• Partner rewards\n• Exclusive deals\n\nWould you like to redeem your points?";
    }

    // Help/support
    if (input.includes('help') || input.includes('support') || input.includes('problem')) {
      return "❓ I'm here to help! Here are some common ways I can assist you:\n\n• 📦 Order tracking & management\n• 🍽️ Restaurant & food orders\n• 🏨 Hotel & flight bookings\n• 💳 Payment & wallet issues\n• 👨‍⚕️ Doctor appointments\n• 💰 Loyalty points & rewards\n\nOr type your question and I'll do my best to help!";
    }

    // Default responses
    const defaultResponses = [
      "That's an interesting question! Let me help you with that. Could you provide more details about what you're looking for?",
      "I'd be happy to help! Could you clarify what you need? You can ask about orders, bookings, payments, or general questions.",
      "I understand you're looking for help. Here are some things I can assist with:\n\n• Order tracking and management\n• Restaurant reservations\n• Hotel and flight bookings\n• Payment issues\n• Wallet inquiries\n• Doctor appointments\n\nWhat would you like to know more about?",
      "Thanks for reaching out! I'm here to help. Whether it's about your orders, bookings, payments, or general questions, I've got you covered. What can I assist you with today?",
      "Great question! Let me provide you with the best assistance. Are you looking for help with:\n\n📦 Orders & Delivery\n🍽️ Food & Restaurants\n✈️ Travel & Hotels\n💳 Payments & Wallets\n👨‍⚕️ Health & Doctor\n\nJust let me know!"
    ];

    return defaultResponses[Math.floor(Math.random() * defaultResponses.length)];
  }

  function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  }

  function scrollToBottom() {
    if (messagesContainer) {
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
  }

  function formatTime(timestamp: string): string {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    
    return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
  }

  function getCategoryColor(category: string): string {
    const colors: Record<string, string> = {
      orders: '#e74c3c',
      food: '#f39c12',
      health: '#27ae60',
      wallet: '#9b59b6',
      travel: '#3498db',
      support: '#1abc9c',
      account: '#e67e22'
    };
    return colors[category] || '#95a5a6';
  }
</script>

<svelte:head>
  <title>AI Chatbot - Hotel Booking App</title>
</svelte:head>

<div class="chatbot-page">
  <!-- Sidebar -->
  <aside class="sidebar" class:collapsed={!showSidebar}>
    <div class="sidebar-header">
      <h2>💬 Conversations</h2>
      <button class="new-chat-btn" on:click={createNewConversation} title="New Conversation">
        ➕
      </button>
    </div>

    <div class="search-box">
      <span class="search-icon">🔍</span>
      <input 
        type="text" 
        bind:value={searchQuery} 
        placeholder="Search conversations..."
      />
    </div>

    <div class="conversation-list">
      {#each conversations as conv}
        <button 
          class="conversation-item"
          class:active={conv.id === currentConversationId}
          on:click={() => switchConversation(conv.id)}
        >
          <div class="conv-info">
            <span class="conv-title">{conv.title}</span>
            <span class="conv-time">{formatTime(conv.updatedAt)}</span>
					</div>
					<div
						class="delete-conv"
						on:click|stopPropagation={(e) => deleteConversation(conv.id, e)}
						title="Delete conversation"
						role="button"
						tabindex="0"
					>
						🗑️
					</div>
				</button>
      {/each}
    </div>

    <div class="sidebar-footer">
      <button class="toggle-sidebar" on:click={() => showSidebar = !showSidebar}>
        {showSidebar ? '◀ Collapse' : '▶ Expand'}
      </button>
    </div>
  </aside>

  <!-- Main Chat Area -->
  <main class="chat-main">
    <header class="chat-header">
      <div class="header-left">
        <button class="menu-toggle" on:click={() => showSidebar = !showSidebar}>☰</button>
        <h1>🤖 AI Assistant</h1>
        <span class="status-badge">
          <span class="status-dot"></span>
          Online
        </span>
      </div>
      <div class="header-right">
        <button class="header-action" title="Clear Chat">
          🗑️
        </button>
        <button class="header-action" title="Settings">
          ⚙️
        </button>
      </div>
    </header>

    <div class="chat-container">
      <!-- Messages -->
      <div class="messages" bind:this={messagesContainer}>
        {#if messages.length === 0}
          <div class="empty-chat">
            <div class="empty-icon">💬</div>
            <h3>Start a conversation</h3>
            <p>Send a message or use a quick suggestion below</p>
          </div>
        {:else}
          {#each messages as msg}
            <div class="message" class:user={msg.role === 'user'} class:bot={msg.role === 'bot'}>
              <div class="avatar">
                {msg.role === 'user' ? '👤' : '🤖'}
              </div>
              <div class="message-content">
                <div class="content">
                  {msg.content}
                </div>
                <div class="message-meta">
                  <span class="time">{formatTime(msg.timestamp)}</span>
                  {#if msg.role === 'user' && msg.status}
                    <span class="status-icon">
                      {#if msg.status === 'sending'}...
                      {:else if msg.status === 'sent'}✓
                      {:else if msg.status === 'delivered'}✓✓
                      {/if}
                    </span>
                  {/if}
                </div>
              </div>
            </div>
          {/each}
        {/if}

        <!-- Typing Indicator -->
        {#if isTyping}
          <div class="message bot">
            <div class="avatar">🤖</div>
            <div class="message-content">
              <div class="typing">
                <span></span><span></span><span></span>
              </div>
            </div>
          </div>
        {/if}
      </div>

      <!-- Quick Suggestions -->
      <div class="suggestions-panel">
        <div class="suggestions-header">
          <span>Quick Suggestions</span>
        </div>
        <div class="suggestions-scroll">
          <div class="suggestions-grid">
            {#each filteredSuggestions as suggestion}
              <button 
                class="suggestion-chip"
                style="--category-color: {getCategoryColor(suggestion.category)}"
                on:click={() => sendMessage(suggestion.prompt)}
              >
                <span class="suggestion-icon">{suggestion.icon}</span>
                <span class="suggestion-label">{suggestion.label}</span>
              </button>
            {/each}
          </div>
        </div>
      </div>

      <!-- Input Area -->
      <div class="input-area">
        <div class="input-wrapper">
          <textarea
            bind:value={inputMessage}
            on:keydown={handleKeydown}
            placeholder="Type your message..."
            rows="1"
            disabled={loading || isTyping}
          ></textarea>
          <button 
            class="send-btn"
            on:click={() => sendMessage()}
            disabled={!inputMessage.trim() || loading || isTyping}
          >
            ➤
          </button>
        </div>
      </div>
    </div>
  </main>
</div>

<style>
  /* Layout */
  .chatbot-page {
    display: flex;
    height: calc(100vh - 60px);
    background: #f8fafc;
    overflow: hidden;
  }

  /* Sidebar */
  .sidebar {
    width: 280px;
    background: white;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    transition: width 0.3s ease;
  }

  .sidebar.collapsed {
    width: 0;
    border: none;
    overflow: hidden;
  }

  .sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .sidebar-header h2 {
    margin: 0;
    font-size: 1rem;
    color: #1e293b;
  }

  .new-chat-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    color: white;
    cursor: pointer;
    font-size: 1rem;
    transition: transform 0.2s;
  }

  .new-chat-btn:hover {
    transform: scale(1.05);
  }

  .search-box {
    padding: 0.75rem 1rem;
    position: relative;
  }

  .search-icon {
    position: absolute;
    left: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.875rem;
  }

  .search-box input {
    width: 100%;
    padding: 0.625rem 1rem 0.625rem 2.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    box-sizing: border-box;
  }

  .search-box input:focus {
    outline: none;
    border-color: #667eea;
  }

  .conversation-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
  }

  .conversation-item {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    margin-bottom: 0.25rem;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    text-align: left;
    transition: background 0.2s;
  }

  .conversation-item:hover {
    background: #f1f5f9;
  }

  .conversation-item.active {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    border-left: 3px solid #667eea;
  }

  .conv-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    overflow: hidden;
  }

  .conv-title {
    font-size: 0.875rem;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .conv-time {
    font-size: 0.75rem;
    color: #94a3b8;
  }

  .delete-conv {
    padding: 0.25rem;
    border: none;
    background: transparent;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s;
    font-size: 0.875rem;
  }

  .conversation-item:hover .delete-conv {
    opacity: 1;
  }

  .delete-conv:hover {
    opacity: 1 !important;
  }

  .sidebar-footer {
    padding: 0.75rem;
    border-top: 1px solid #e2e8f0;
  }

  .toggle-sidebar {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.75rem;
    color: #64748b;
  }

  /* Main Chat Area */
  .chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  .chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: white;
    border-bottom: 1px solid #e2e8f0;
  }

  .header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .menu-toggle {
    display: none;
    padding: 0.5rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1.25rem;
  }

  .chat-header h1 {
    margin: 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .status-badge {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.75rem;
    background: #dcfce7;
    border-radius: 9999px;
    font-size: 0.75rem;
    color: #166534;
  }

  .status-dot {
    width: 6px;
    height: 6px;
    background: #22c55e;
    border-radius: 50%;
  }

  .header-right {
    display: flex;
    gap: 0.5rem;
  }

  .header-action {
    padding: 0.5rem;
    border: none;
    background: #f1f5f9;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.2s;
  }

  .header-action:hover {
    background: #e2e8f0;
  }

  /* Chat Container */
  .chat-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .empty-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #64748b;
  }

  .empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .empty-chat h3 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .empty-chat p {
    margin: 0;
    font-size: 0.875rem;
  }

  /* Messages */
  .message {
    display: flex;
    gap: 0.75rem;
    max-width: 80%;
    animation: messageSlide 0.3s ease;
  }

  @keyframes messageSlide {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .message.user {
    align-self: flex-end;
    flex-direction: row-reverse;
  }

  .message.bot {
    align-self: flex-start;
  }

  .avatar {
    width: 40px;
    height: 40px;
    background: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
  }

  .message.user .avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }

  .message-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .content {
    padding: 0.875rem 1rem;
    border-radius: 16px;
    background: white;
    color: #1e293b;
    font-size: 0.9375rem;
    line-height: 1.5;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    white-space: pre-wrap;
  }

  .message.user .content {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .message-meta {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.6875rem;
    color: #94a3b8;
  }

  .message.user .message-meta {
    justify-content: flex-end;
  }

  .status-icon {
    color: #667eea;
  }

  /* Typing Animation */
  .typing {
    display: flex;
    gap: 4px;
    padding: 0.75rem 1rem;
  }

  .typing span {
    width: 8px;
    height: 8px;
    background: #94a3b8;
    border-radius: 50%;
    animation: bounce 1.4s infinite;
  }

  .typing span:nth-child(2) {
    animation-delay: 0.2s;
  }

  .typing span:nth-child(3) {
    animation-delay: 0.4s;
  }

  @keyframes bounce {
    0%, 60%, 100% {
      transform: translateY(0);
    }
    30% {
      transform: translateY(-10px);
    }
  }

  /* Suggestions Panel */
  .suggestions-panel {
    background: white;
    border-top: 1px solid #e2e8f0;
    padding: 0.75rem 1rem;
  }

  .suggestions-header {
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .suggestions-scroll {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .suggestions-grid {
    display: flex;
    gap: 0.5rem;
    flex-wrap: nowrap;
  }

  .suggestion-chip {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 9999px;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    font-size: 0.8125rem;
  }

  .suggestion-chip:hover {
    border-color: var(--category-color, #667eea);
    background: rgba(102, 126, 234, 0.05);
    transform: translateY(-1px);
  }

  .suggestion-icon {
    font-size: 0.875rem;
  }

  .suggestion-label {
    color: #374151;
  }

  /* Input Area */
  .input-area {
    padding: 1rem 1.5rem;
    background: white;
    border-top: 1px solid #e2e8f0;
  }

  .input-wrapper {
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
    background: #f1f5f9;
    border-radius: 12px;
    padding: 0.5rem;
  }

  textarea {
    flex: 1;
    padding: 0.625rem 0.75rem;
    border: none;
    background: transparent;
    resize: none;
    font-family: inherit;
    font-size: 0.9375rem;
    line-height: 1.4;
    max-height: 120px;
    min-height: 24px;
  }

  textarea:focus {
    outline: none;
  }

  textarea::placeholder {
    color: #94a3b8;
  }

  .send-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    color: white;
    cursor: pointer;
    font-size: 1.125rem;
    transition: transform 0.2s, opacity 0.2s;
  }

  .send-btn:hover:not(:disabled) {
    transform: scale(1.05);
  }

  .send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      z-index: 100;
      box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
    }

    .sidebar.collapsed {
      transform: translateX(-100%);
      width: 280px;
    }

    .menu-toggle {
      display: block;
    }

    .message {
      max-width: 90%;
    }

    .chat-header h1 {
      font-size: 1rem;
    }

    .status-badge span:not(.status-dot) {
      display: none;
    }
  }
</style>
