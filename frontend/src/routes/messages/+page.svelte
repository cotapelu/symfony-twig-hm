<script lang="ts">
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';

  interface Message {
    id: string;
    conversationId: string;
    senderId: string;
    senderName: string;
    senderAvatar: string;
    content: string;
    timestamp: string;
    status: 'sending' | 'sent' | 'delivered' | 'read';
    isOwn: boolean;
  }

  interface Conversation {
    id: string;
    participantIds: string[];
    participantNames: string[];
    participantAvatars: string[];
    lastMessage: string;
    lastMessageTime: string;
    unreadCount: number;
    isOnline: boolean;
    createdAt: string;
    updatedAt: string;
  }

  interface User {
    id: string;
    name: string;
    avatar: string;
    isOnline: boolean;
    lastSeen?: string;
  }

  // Current user (mock)
  const currentUser: User = {
    id: 'user_1',
    name: 'You',
    avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=user1',
    isOnline: true
  };

  // Mock contacts
  const mockContacts: User[] = [
    { id: 'user_2', name: 'Sarah Chen', avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=sarah', isOnline: true },
    { id: 'user_3', name: 'Mike Johnson', avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=mike', isOnline: false, lastSeen: '5 min ago' },
    { id: 'user_4', name: 'Emma Wilson', avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=emma', isOnline: true },
    { id: 'user_5', name: 'David Kim', avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=david', isOnline: false, lastSeen: '1 hour ago' },
    { id: 'user_6', name: 'Lisa Park', avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=lisa', isOnline: true },
    { id: 'user_7', name: 'Tom Brown', avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=tom', isOnline: false, lastSeen: '2 hours ago' },
    { id: 'user_8', name: 'Amy Garcia', avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=amy', isOnline: true },
    { id: 'user_9', name: 'James Lee', avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=james', isOnline: false, lastSeen: '1 day ago' }
  ];

  // State
  let conversations: Conversation[] = [];
  let messages: Message[] = [];
  let selectedConversationId: string | null = null;
  let inputMessage = '';
  let searchQuery = '';
  let showNewChatModal = false;
  let messagesContainer: HTMLElement;
  let isMobileView = false;
  let showConversationList = true;

  // Reactive derived values
  $: selectedConversation = conversations.find(c => c.id === selectedConversationId);
  $: conversationMessages = messages.filter(m => m.conversationId === selectedConversationId);
  $: filteredConversations = searchQuery
    ? conversations.filter(c => 
        c.participantNames.some(name => name.toLowerCase().includes(searchQuery.toLowerCase()))
      )
    : conversations;

  $: onlineContacts = mockContacts.filter(c => c.isOnline);
  $: offlineContacts = mockContacts.filter(c => !c.isOnline);

  $: if (selectedConversationId) {
    markConversationAsRead(selectedConversationId);
  }

  onMount(() => {
    loadData();
    checkMobileView();
    window.addEventListener('resize', checkMobileView);
    return () => window.removeEventListener('resize', checkMobileView);
  });

  function checkMobileView() {
    isMobileView = window.innerWidth < 768;
    if (!isMobileView) {
      showConversationList = true;
    }
  }

  function loadData() {
    if (!browser) return;

    const savedConversations = localStorage.getItem('messages_conversations');
    const savedMessages = localStorage.getItem('messages_data');
    const savedSelectedId = localStorage.getItem('messages_selected_conversation');

    if (savedConversations) {
      conversations = JSON.parse(savedConversations);
    } else {
      // Create sample conversations
      conversations = [
        {
          id: 'conv_1',
          participantIds: ['user_2'],
          participantNames: ['Sarah Chen'],
          participantAvatars: ['https://api.dicebear.com/7.x/avataaars/svg?seed=sarah'],
          lastMessage: 'Sure, I\'ll send you the files later today!',
          lastMessageTime: new Date(Date.now() - 1000 * 60 * 5).toISOString(),
          unreadCount: 2,
          isOnline: true,
          createdAt: new Date(Date.now() - 1000 * 60 * 60 * 24 * 7).toISOString(),
          updatedAt: new Date(Date.now() - 1000 * 60 * 5).toISOString()
        },
        {
          id: 'conv_2',
          participantIds: ['user_3'],
          participantNames: ['Mike Johnson'],
          participantAvatars: ['https://api.dicebear.com/7.x/avataaars/svg?seed=mike'],
          lastMessage: 'The meeting has been rescheduled to 3 PM',
          lastMessageTime: new Date(Date.now() - 1000 * 60 * 30).toISOString(),
          unreadCount: 0,
          isOnline: false,
          createdAt: new Date(Date.now() - 1000 * 60 * 60 * 24 * 3).toISOString(),
          updatedAt: new Date(Date.now() - 1000 * 60 * 30).toISOString()
        },
        {
          id: 'conv_3',
          participantIds: ['user_4'],
          participantNames: ['Emma Wilson'],
          participantAvatars: ['https://api.dicebear.com/7.x/avataaars/svg?seed=emma'],
          lastMessage: 'Thanks for your help with the project!',
          lastMessageTime: new Date(Date.now() - 1000 * 60 * 60 * 2).toISOString(),
          unreadCount: 1,
          isOnline: true,
          createdAt: new Date(Date.now() - 1000 * 60 * 60 * 24 * 14).toISOString(),
          updatedAt: new Date(Date.now() - 1000 * 60 * 60 * 2).toISOString()
        },
        {
          id: 'conv_4',
          participantIds: ['user_5'],
          participantNames: ['David Kim'],
          participantAvatars: ['https://api.dicebear.com/7.x/avataaars/svg?seed=david'],
          lastMessage: 'Let me know when you\'re free to chat',
          lastMessageTime: new Date(Date.now() - 1000 * 60 * 60 * 5).toISOString(),
          unreadCount: 0,
          isOnline: false,
          createdAt: new Date(Date.now() - 1000 * 60 * 60 * 24 * 2).toISOString(),
          updatedAt: new Date(Date.now() - 1000 * 60 * 60 * 5).toISOString()
        }
      ];
      saveConversations();
    }

    if (savedMessages) {
      messages = JSON.parse(savedMessages);
    } else {
      // Sample messages
      messages = [
        // Conversation 1 - Sarah Chen
        {
          id: 'msg_1',
          conversationId: 'conv_1',
          senderId: 'user_2',
          senderName: 'Sarah Chen',
          senderAvatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=sarah',
          content: 'Hey! How are you doing?',
          timestamp: new Date(Date.now() - 1000 * 60 * 60).toISOString(),
          status: 'delivered',
          isOwn: false
        },
        {
          id: 'msg_2',
          conversationId: 'conv_1',
          senderId: 'user_1',
          senderName: 'You',
          senderAvatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=user1',
          content: 'I\'m doing great, thanks! How about you?',
          timestamp: new Date(Date.now() - 1000 * 60 * 45).toISOString(),
          status: 'read',
          isOwn: true
        },
        {
          id: 'msg_3',
          conversationId: 'conv_1',
          senderId: 'user_2',
          senderName: 'Sarah Chen',
          senderAvatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=sarah',
          content: 'Pretty good! I wanted to ask - do you have those files I requested?',
          timestamp: new Date(Date.now() - 1000 * 60 * 30).toISOString(),
          status: 'delivered',
          isOwn: false
        },
        {
          id: 'msg_4',
          conversationId: 'conv_1',
          senderId: 'user_1',
          senderName: 'You',
          senderAvatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=user1',
          content: 'Yes, I have them! I\'ll send them over right now.',
          timestamp: new Date(Date.now() - 1000 * 60 * 15).toISOString(),
          status: 'read',
          isOwn: true
        },
        {
          id: 'msg_5',
          conversationId: 'conv_1',
          senderId: 'user_2',
          senderName: 'Sarah Chen',
          senderAvatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=sarah',
          content: 'Sure, I\'ll send you the files later today!',
          timestamp: new Date(Date.now() - 1000 * 60 * 5).toISOString(),
          status: 'delivered',
          isOwn: false
        },
        // Conversation 2 - Mike Johnson
        {
          id: 'msg_6',
          conversationId: 'conv_2',
          senderId: 'user_3',
          senderName: 'Mike Johnson',
          senderAvatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=mike',
          content: 'Hi, did you see the meeting invite?',
          timestamp: new Date(Date.now() - 1000 * 60 * 60).toISOString(),
          status: 'delivered',
          isOwn: false
        },
        {
          id: 'msg_7',
          conversationId: 'conv_2',
          senderId: 'user_1',
          senderName: 'You',
          senderAvatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=user1',
          content: 'Yes, I saw it. I\'ll be there.',
          timestamp: new Date(Date.now() - 1000 * 60 * 45).toISOString(),
          status: 'read',
          isOwn: true
        },
        {
          id: 'msg_8',
          conversationId: 'conv_2',
          senderId: 'user_3',
          senderName: 'Mike Johnson',
          senderAvatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=mike',
          content: 'The meeting has been rescheduled to 3 PM',
          timestamp: new Date(Date.now() - 1000 * 60 * 30).toISOString(),
          status: 'delivered',
          isOwn: false
        }
      ];
      saveMessages();
    }

    if (savedSelectedId && conversations.find(c => c.id === savedSelectedId)) {
      selectedConversationId = savedSelectedId;
    } else if (conversations.length > 0) {
      selectedConversationId = conversations[0].id;
    }
  }

  function saveConversations() {
    if (!browser) return;
    localStorage.setItem('messages_conversations', JSON.stringify(conversations));
  }

  function saveMessages() {
    if (!browser) return;
    localStorage.setItem('messages_data', JSON.stringify(messages));
  }

  function saveSelectedConversation() {
    if (!browser) return;
    if (selectedConversationId) {
      localStorage.setItem('messages_selected_conversation', selectedConversationId);
    }
  }

  function selectConversation(id: string) {
    selectedConversationId = id;
    saveSelectedConversation();
    if (isMobileView) {
      showConversationList = false;
    }
    setTimeout(scrollToBottom, 100);
  }

  function markConversationAsRead(conversationId: string) {
    conversations = conversations.map(c => 
      c.id === conversationId ? { ...c, unreadCount: 0 } : c
    );
    saveConversations();
  }

  function formatTime(timestamp: string): string {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffMins < 1) return 'Now';
    if (diffMins < 60) return `${diffMins}m`;
    if (diffHours < 24) return `${diffHours}h`;
    if (diffDays < 7) return `${diffDays}d`;
    
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function formatMessageTime(timestamp: string): string {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
  }

  function sendMessage() {
    if (!inputMessage.trim() || !selectedConversationId) return;

    const newMessage: Message = {
      id: `msg_${Date.now()}`,
      conversationId: selectedConversationId,
      senderId: currentUser.id,
      senderName: currentUser.name,
      senderAvatar: currentUser.avatar,
      content: inputMessage.trim(),
      timestamp: new Date().toISOString(),
      status: 'sending',
      isOwn: true
    };

    messages = [...messages, newMessage];
    
    // Update conversation
    conversations = conversations.map(c => 
      c.id === selectedConversationId 
        ? { 
            ...c, 
            lastMessage: inputMessage.trim(), 
            lastMessageTime: newMessage.timestamp,
            updatedAt: newMessage.timestamp
          } 
        : c
    );

    const messageText = inputMessage.trim();
    inputMessage = '';
    
    saveMessages();
    saveConversations();
    setTimeout(scrollToBottom, 100);

    // Simulate sent status
    setTimeout(() => {
      messages = messages.map(m => 
        m.id === newMessage.id ? { ...m, status: 'sent' } : m
      );
      saveMessages();
    }, 500);

    // Simulate delivered status
    setTimeout(() => {
      messages = messages.map(m => 
        m.id === newMessage.id ? { ...m, status: 'delivered' } : m
      );
      saveMessages();
    }, 1500);

    // Simulate reply
    setTimeout(() => {
      const conversation = selectedConversation;
      if (conversation) {
        const replyMessage: Message = {
          id: `msg_${Date.now()}`,
          conversationId: selectedConversationId!,
          senderId: conversation.participantIds[0],
          senderName: conversation.participantNames[0],
          senderAvatar: conversation.participantAvatars[0],
          content: getAutoReply(messageText),
          timestamp: new Date().toISOString(),
          status: 'delivered',
          isOwn: false
        };
        messages = [...messages, replyMessage];
        
        // Update conversation
        conversations = conversations.map(c => 
          c.id === selectedConversationId 
            ? { 
                ...c, 
                lastMessage: replyMessage.content, 
                lastMessageTime: replyMessage.timestamp,
                updatedAt: replyMessage.timestamp,
                unreadCount: c.id === selectedConversationId ? 0 : c.unreadCount + 1
              } 
            : c
        );
        
        saveMessages();
        saveConversations();
        setTimeout(scrollToBottom, 100);
      }
    }, 2000);
  }

  function getAutoReply(originalMessage: string): string {
    const lowerMsg = originalMessage.toLowerCase();
    if (lowerMsg.includes('file') || lowerMsg.includes('document')) {
      return 'Got it! I\'ll review and get back to you soon.';
    }
    if (lowerMsg.includes('meeting') || lowerMsg.includes('schedule')) {
      return 'Sounds good! I\'ll put it on my calendar.';
    }
    if (lowerMsg.includes('thanks') || lowerMsg.includes('thank')) {
      return 'You\'re welcome! Let me know if you need anything else.';
    }
    if (lowerMsg.includes('hello') || lowerMsg.includes('hi')) {
      return 'Hi there! How can I help you today?';
    }
    
    const replies = [
      'Thanks for letting me know!',
      'Sounds good!',
      'I\'ll check and get back to you.',
      'Perfect, thanks!',
      'Got it!',
      'Will do!'
    ];
    return replies[Math.floor(Math.random() * replies.length)];
  }

  function handleKeyPress(e: KeyboardEvent) {
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

  function startNewConversation(contact: User) {
    // Check if conversation already exists
    const existingConv = conversations.find(c => 
      c.participantIds.includes(contact.id)
    );

    if (existingConv) {
      selectConversation(existingConv.id);
    } else {
      const newConv: Conversation = {
        id: `conv_${Date.now()}`,
        participantIds: [contact.id],
        participantNames: [contact.name],
        participantAvatars: [contact.avatar],
        lastMessage: '',
        lastMessageTime: new Date().toISOString(),
        unreadCount: 0,
        isOnline: contact.isOnline,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      };
      conversations = [newConv, ...conversations];
      selectConversation(newConv.id);
      saveConversations();
    }
    showNewChatModal = false;
  }

  function goBackToList() {
    showConversationList = true;
    selectedConversationId = null;
  }
</script>

<div class="messages-page">
  <!-- Sidebar / Conversation List -->
  <aside class="sidebar" class:hidden={!showConversationList && isMobileView}>
    <div class="sidebar-header">
      <h1>Messages</h1>
      <button class="new-chat-btn" on:click={() => showNewChatModal = true}>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          <line x1="12" y1="8" x2="12" y2="14"></line>
          <line x1="9" y1="11" x2="15" y2="11"></line>
        </svg>
      </button>
    </div>

    <div class="search-box">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <input 
        type="text" 
        placeholder="Search conversations..." 
        bind:value={searchQuery}
      />
    </div>

    <div class="conversation-list">
      {#each filteredConversations as conversation (conversation.id)}
        <button 
          class="conversation-item"
          class:active={selectedConversationId === conversation.id}
          on:click={() => selectConversation(conversation.id)}
        >
          <div class="avatar-container">
            <img src={conversation.participantAvatars[0]} alt={conversation.participantNames[0]} class="avatar" />
            <span class="online-indicator" class:online={conversation.isOnline}></span>
          </div>
          <div class="conversation-info">
            <div class="conversation-header">
              <span class="name">{conversation.participantNames[0]}</span>
              <span class="time">{formatTime(conversation.lastMessageTime)}</span>
            </div>
            <div class="conversation-preview">
              <span class="last-message">{conversation.lastMessage || 'No messages yet'}</span>
              {#if conversation.unreadCount > 0}
                <span class="unread-badge">{conversation.unreadCount}</span>
              {/if}
            </div>
          </div>
        </button>
      {/each}
    </div>
  </aside>

  <!-- Chat Area -->
  <main class="chat-area" class:hidden={showConversationList && isMobileView}>
    {#if selectedConversation}
      <!-- Chat Header -->
      <header class="chat-header">
        {#if isMobileView}
          <button class="back-btn" on:click={goBackToList}>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="19" y1="12" x2="5" y2="12"></line>
              <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
          </button>
        {/if}
        <img 
          src={selectedConversation.participantAvatars[0]} 
          alt={selectedConversation.participantNames[0]} 
          class="chat-avatar"
        />
        <div class="chat-user-info">
          <span class="chat-user-name">{selectedConversation.participantNames[0]}</span>
          <span class="chat-user-status" class:online={selectedConversation.isOnline}>
            {selectedConversation.isOnline ? 'Online' : 'Offline'}
          </span>
        </div>
        <div class="chat-actions">
          <button class="action-btn" title="Voice call">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
          </button>
          <button class="action-btn" title="Video call">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="23 7 16 12 23 17 23 7"></polygon>
              <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
            </svg>
          </button>
          <button class="action-btn" title="More options">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="1"></circle>
              <circle cx="19" cy="12" r="1"></circle>
              <circle cx="5" cy="12" r="1"></circle>
            </svg>
          </button>
        </div>
      </header>

      <!-- Messages -->
      <div class="messages-container" bind:this={messagesContainer}>
        {#each conversationMessages as message (message.id)}
          <div class="message" class:own={message.isOwn}>
            {#if !message.isOwn}
              <img src={message.senderAvatar} alt={message.senderName} class="message-avatar" />
            {/if}
            <div class="message-content">
              <div class="message-bubble">
                {message.content}
              </div>
              <div class="message-meta">
                <span class="message-time">{formatMessageTime(message.timestamp)}</span>
                {#if message.isOwn}
                  <span class="message-status">
                    {#if message.status === 'sending'}
                      <svg class="status-icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                      </svg>
                    {:else if message.status === 'sent'}
                      <svg class="status-icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                    {:else if message.status === 'delivered'}
                      <svg class="status-icon double" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="18 6 9 17 4 12"></polyline>
                        <polyline points="22 6 13 17"></polyline>
                      </svg>
                    {:else if message.status === 'read'}
                      <svg class="status-icon double read" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="18 6 9 17 4 12"></polyline>
                        <polyline points="22 6 13 17"></polyline>
                      </svg>
                    {/if}
                  </span>
                {/if}
              </div>
            </div>
          </div>
        {/each}
      </div>

      <!-- Input Area -->
      <div class="input-area">
        <button class="attach-btn" title="Attach file">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
          </svg>
        </button>
        <input 
          type="text" 
          placeholder="Type a message..." 
          bind:value={inputMessage}
          on:keypress={handleKeyPress}
        />
        <button class="emoji-btn" title="Add emoji">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
            <line x1="9" y1="9" x2="9.01" y2="9"></line>
            <line x1="15" y1="9" x2="15.01" y2="9"></line>
          </svg>
        </button>
        <button 
          class="send-btn" 
          on:click={sendMessage}
          disabled={!inputMessage.trim()}
          title="Send message"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="22" y1="2" x2="11" y2="13"></line>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
        </button>
      </div>
    {:else}
      <div class="no-conversation">
        <div class="no-conversation-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
        </div>
        <h2>Select a conversation</h2>
        <p>Choose a conversation from the list to start messaging</p>
      </div>
    {/if}
  </main>

  <!-- New Chat Modal -->
  {#if showNewChatModal}
    <div class="modal-overlay" on:click={() => showNewChatModal = false}>
      <div class="modal" on:click|stopPropagation>
        <div class="modal-header">
          <h2>New Message</h2>
          <button class="close-btn" on:click={() => showNewChatModal = false}>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="contacts-section">
            <h3>Online</h3>
            <div class="contact-list">
              {#each onlineContacts as contact (contact.id)}
                <button class="contact-item" on:click={() => startNewConversation(contact)}>
                  <div class="avatar-container">
                    <img src={contact.avatar} alt={contact.name} class="avatar" />
                    <span class="online-indicator online"></span>
                  </div>
                  <span class="contact-name">{contact.name}</span>
                </button>
              {/each}
            </div>
          </div>
          <div class="contacts-section">
            <h3>Offline</h3>
            <div class="contact-list">
              {#each offlineContacts as contact (contact.id)}
                <button class="contact-item" on:click={() => startNewConversation(contact)}>
                  <div class="avatar-container">
                    <img src={contact.avatar} alt={contact.name} class="avatar" />
                    <span class="online-indicator"></span>
                  </div>
                  <span class="contact-name">{contact.name}</span>
                  <span class="last-seen">{contact.lastSeen}</span>
                </button>
              {/each}
            </div>
          </div>
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .messages-page {
    display: flex;
    height: calc(100vh - 60px);
    background: #fff;
    overflow: hidden;
  }

  /* Sidebar */
  .sidebar {
    width: 340px;
    border-right: 1px solid #e8e8e8;
    display: flex;
    flex-direction: column;
    background: #fafafa;
  }

  .sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid #e8e8e8;
    background: #fff;
  }

  .sidebar-header h1 {
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
  }

  .new-chat-btn {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    border: none;
    background: #4f46e5;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }

  .new-chat-btn:hover {
    background: #4338ca;
    transform: scale(1.05);
  }

  .search-box {
    padding: 12px 16px;
    background: #fff;
  }

  .search-box input {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1px solid #e8e8e8;
    border-radius: 10px;
    font-size: 14px;
    background: #f5f5f5;
    transition: all 0.2s ease;
  }

  .search-box input:focus {
    outline: none;
    border-color: #4f46e5;
    background: #fff;
  }

  .search-box {
    position: relative;
  }

  .search-box svg {
    position: absolute;
    left: 28px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
  }

  /* Conversation List */
  .conversation-list {
    flex: 1;
    overflow-y: auto;
  }

  .conversation-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    width: 100%;
    border: none;
    background: transparent;
    cursor: pointer;
    text-align: left;
    transition: background 0.15s ease;
    border-bottom: 1px solid #f0f0f0;
  }

  .conversation-item:hover {
    background: #f0f0f0;
  }

  .conversation-item.active {
    background: #eef2ff;
  }

  .avatar-container {
    position: relative;
    flex-shrink: 0;
  }

  .avatar {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    object-fit: cover;
  }

  .online-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #ccc;
    border: 2px solid #fafafa;
  }

  .online-indicator.online {
    background: #22c55e;
  }

  .conversation-info {
    flex: 1;
    min-width: 0;
  }

  .conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
  }

  .name {
    font-weight: 600;
    font-size: 14px;
    color: #1a1a1a;
  }

  .time {
    font-size: 12px;
    color: #999;
  }

  .conversation-preview {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .last-message {
    font-size: 13px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
  }

  .unread-badge {
    background: #4f46e5;
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
  }

  /* Chat Area */
  .chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #fff;
  }

  .chat-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #e8e8e8;
    background: #fff;
  }

  .back-btn {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    color: #666;
    border-radius: 8px;
    transition: background 0.15s ease;
  }

  .back-btn:hover {
    background: #f0f0f0;
  }

  .chat-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    object-fit: cover;
  }

  .chat-user-info {
    flex: 1;
  }

  .chat-user-name {
    font-weight: 600;
    font-size: 15px;
    color: #1a1a1a;
    display: block;
  }

  .chat-user-status {
    font-size: 12px;
    color: #999;
  }

  .chat-user-status.online {
    color: #22c55e;
  }

  .chat-actions {
    display: flex;
    gap: 8px;
  }

  .action-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    border: none;
    background: #f5f5f5;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
  }

  .action-btn:hover {
    background: #e8e8e8;
    color: #1a1a1a;
  }

  /* Messages */
  .messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: #f8f9fa;
  }

  .message {
    display: flex;
    gap: 10px;
    max-width: 70%;
  }

  .message.own {
    align-self: flex-end;
    flex-direction: row-reverse;
  }

  .message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
  }

  .message-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .message.own .message-content {
    align-items: flex-end;
  }

  .message-bubble {
    padding: 12px 16px;
    border-radius: 18px;
    font-size: 14px;
    line-height: 1.5;
    background: #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  }

  .message.own .message-bubble {
    background: #4f46e5;
    color: white;
    border-bottom-right-radius: 4px;
  }

  .message:not(.own) .message-bubble {
    border-bottom-left-radius: 4px;
  }

  .message-meta {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 0 4px;
  }

  .message-time {
    font-size: 11px;
    color: #999;
  }

  .message-status {
    display: flex;
    align-items: center;
  }

  .status-icon {
    color: #999;
  }

  .status-icon.double {
    position: relative;
    left: -4px;
  }

  .status-icon.read {
    color: #4f46e5;
  }

  /* Input Area */
  .input-area {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 20px;
    border-top: 1px solid #e8e8e8;
    background: #fff;
  }

  .input-area input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #e8e8e8;
    border-radius: 24px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.15s ease;
  }

  .input-area input:focus {
    border-color: #4f46e5;
  }

  .attach-btn, .emoji-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: #f5f5f5;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
  }

  .attach-btn:hover, .emoji-btn:hover {
    background: #e8e8e8;
    color: #1a1a1a;
  }

  .send-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: #4f46e5;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }

  .send-btn:hover:not(:disabled) {
    background: #4338ca;
    transform: scale(1.05);
  }

  .send-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
  }

  /* No Conversation State */
  .no-conversation {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #999;
    background: #fafafa;
  }

  .no-conversation-icon {
    margin-bottom: 20px;
    opacity: 0.3;
  }

  .no-conversation h2 {
    font-size: 20px;
    font-weight: 600;
    color: #666;
    margin: 0 0 8px;
  }

  .no-conversation p {
    font-size: 14px;
    margin: 0;
  }

  /* Modal */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    animation: fadeIn 0.2s ease;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  .modal {
    background: #fff;
    border-radius: 16px;
    width: 90%;
    max-width: 400px;
    max-height: 70vh;
    overflow: hidden;
    animation: slideUp 0.2s ease;
  }

  @keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }

  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid #e8e8e8;
  }

  .modal-header h2 {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    color: #1a1a1a;
  }

  .close-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #f5f5f5;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
  }

  .close-btn:hover {
    background: #e8e8e8;
    color: #1a1a1a;
  }

  .modal-body {
    padding: 16px;
    overflow-y: auto;
    max-height: calc(70vh - 70px);
  }

  .contacts-section {
    margin-bottom: 20px;
  }

  .contacts-section h3 {
    font-size: 12px;
    font-weight: 600;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 12px;
  }

  .contact-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border: none;
    background: transparent;
    border-radius: 10px;
    cursor: pointer;
    text-align: left;
    width: 100%;
    transition: background 0.15s ease;
  }

  .contact-item:hover {
    background: #f5f5f5;
  }

  .contact-name {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    color: #1a1a1a;
  }

  .last-seen {
    font-size: 12px;
    color: #999;
  }

  /* Mobile Responsive */
  @media (max-width: 768px) {
    .sidebar {
      width: 100%;
      position: absolute;
      inset: 0;
    }

    .sidebar.hidden {
      display: none;
    }

    .chat-area {
      width: 100%;
      position: absolute;
      inset: 0;
    }

    .chat-area.hidden {
      display: none;
    }

    .message {
      max-width: 85%;
    }
  }
</style>
