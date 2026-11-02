// Messages Service - Connects to PHP Message module
import { getApiUrl } from '../config/api';

export interface Message {
  id: string;
  conversationId: string;
  senderId: string;
  content: string;
  type: string;
  createdAt: string;
  threadId?: string;
  attachments?: Attachment[];
}

export interface Attachment {
  name: string;
  size: number;
  type: string;
  url?: string;
}

export interface Conversation {
  id: string;
  title: string;
  type: 'direct' | 'group';
  participants: string[];
  lastMessage?: Message;
  unreadCount: number;
  createdAt: string;
  updatedAt: string;
}

export interface SendMessageRequest {
  conversationId: string;
  content: string;
  threadId?: string;
  files?: Attachment[];
}

class MessagesService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  // Conversations
  async getConversations(): Promise<Conversation[]> {
    const url = getApiUrl('conversation', '/api/conversations');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch conversations');
    const data = await response.json();
    return data.conversations || [];
  }

  async getConversation(id: string): Promise<Conversation> {
    const url = getApiUrl('conversation', `/api/conversations/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch conversation');
    return response.json();
  }

  async createConversation(participantIds: string[], title?: string): Promise<Conversation> {
    const url = getApiUrl('conversation', '/api/conversations');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ participantIds, title }),
    });
    if (!response.ok) throw new Error('Failed to create conversation');
    return response.json();
  }

  // Messages
  async getMessages(conversationId: string, page = 1, limit = 50): Promise<Message[]> {
    const url = getApiUrl('message', `/api/messages/${conversationId}?page=${page}&limit=${limit}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch messages');
    const data = await response.json();
    return data.messages || [];
  }

  async sendMessage(request: SendMessageRequest): Promise<Message> {
    const url = getApiUrl('message', '/api/messages');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(request),
    });
    if (!response.ok) throw new Error('Failed to send message');
    return response.json();
  }

  async deleteMessage(messageId: string): Promise<void> {
    const url = getApiUrl('message', `/api/messages/${messageId}`);
    const response = await fetch(url, {
      method: 'DELETE',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to delete message');
  }

  async markAsRead(conversationId: string): Promise<void> {
    const url = getApiUrl('message', `/api/conversations/${conversationId}/read`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to mark as read');
  }

  // Search
  async searchMessages(query: string): Promise<Message[]> {
    const url = getApiUrl('message', `/api/messages/search?q=${encodeURIComponent(query)}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to search messages');
    const data = await response.json();
    return data.messages || [];
  }
}

export const messagesService = new MessagesService();
