import { writable, derived } from 'svelte/store';

export interface Message {
  id: string;
  senderId: string;
  receiverId: string;
  content: string;
  timestamp: Date;
  read: boolean;
  type: 'text' | 'image' | 'file' | 'voice';
  metadata?: {
    fileName?: string;
    fileSize?: number;
    duration?: number;
  };
}

export interface Conversation {
  id: string;
  participants: string[];
  lastMessage?: Message;
  unreadCount: number;
  isActive: boolean;
  createdAt: Date;
  updatedAt: Date;
}

export interface MessagesState {
  conversations: Conversation[];
  activeConversation: string | null;
  messages: Record<string, Message[]>;
  loading: boolean;
  error: string | null;
  typing: Record<string, boolean>;
}

export const messagesStore = writable<MessagesState>({
  conversations: [],
  activeConversation: null,
  messages: {},
  loading: false,
  error: null,
  typing: {}
});

export const conversations = derived(messagesStore, $state => $state.conversations);
export const activeConversation = derived(messagesStore, $state => $state.activeConversation);
export const activeMessages = derived(
  [messagesStore, activeConversation],
  ([$state, $activeId]) => $activeId ? $state.messages[$activeId] || [] : []
);
export const loading = derived(messagesStore, $state => $state.loading);
export const error = derived(messagesStore, $state => $state.error);

export async function loadConversations() {
  messagesStore.update(state => ({ ...state, loading: true, error: null }));
  
  try {
    const response = await fetch('/api/messages/conversations');
    if (!response.ok) throw new Error('Failed to load conversations');
    
    const data = await response.json();
    messagesStore.update(state => ({
      ...state,
      conversations: data,
      loading: false
    }));
  } catch (err) {
    messagesStore.update(state => ({
      ...state,
      loading: false,
      error: (err as Error).message
    }));
  }
}

export async function loadMessages(conversationId: string) {
  messagesStore.update(state => ({ ...state, loading: true, error: null }));
  
  try {
    const response = await fetch(`/api/messages/conversations/${conversationId}`);
    if (!response.ok) throw new Error('Failed to load messages');
    
    const data = await response.json();
    messagesStore.update(state => ({
      ...state,
      activeConversation: conversationId,
      messages: { ...state.messages, [conversationId]: data },
      loading: false
    }));
  } catch (err) {
    messagesStore.update(state => ({
      ...state,
      loading: false,
      error: (err as Error).message
    }));
  }
}

export async function sendMessage(conversationId: string, content: string, type: 'text' | 'image' | 'file' | 'voice' = 'text') {
  try {
    const response = await fetch(`/api/messages/conversations/${conversationId}/messages`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ content, type })
    });
    
    if (!response.ok) throw new Error('Failed to send message');
    
    const message = await response.json();
    messagesStore.update(state => ({
      ...state,
      messages: {
        ...state.messages,
        [conversationId]: [...(state.messages[conversationId] || []), message]
      }
    }));
    
    return message;
  } catch (err) {
    messagesStore.update(state => ({
      ...state,
      error: (err as Error).message
    }));
    throw err;
  }
}

export function setActiveConversation(conversationId: string | null) {
  messagesStore.update(state => ({
    ...state,
    activeConversation: conversationId
  }));
}

export function markAsRead(conversationId: string, messageId: string) {
  messagesStore.update(state => ({
    ...state,
    messages: {
      ...state.messages,
      [conversationId]: state.messages[conversationId].map(msg =>
        msg.id === messageId ? { ...msg, read: true } : msg
      )
    }
  }));
}

export function clearError() {
  messagesStore.update(state => ({ ...state, error: null }));
}