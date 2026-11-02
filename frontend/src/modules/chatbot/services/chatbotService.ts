import type { ChatbotConversation, ChatbotMessage, ChatbotQuery } from '../contracts/chatbotTypes';

const API_BASE = '/api/chatbot';

export class ChatbotService {
  async getConversations(): Promise<ChatbotConversation[]> {
    const response = await fetch(`${API_BASE}/conversations`);
    if (!response.ok) throw new Error('Failed to fetch conversations');
    return response.json();
  }

  async getMyConversations(): Promise<ChatbotConversation[]> {
    const response = await fetch(`${API_BASE}/my-conversations`);
    if (!response.ok) throw new Error('Failed to fetch my conversations');
    return response.json();
  }

  async getConversation(id: string): Promise<ChatbotConversation> {
    const response = await fetch(`${API_BASE}/conversations/${id}`);
    if (!response.ok) throw new Error('Failed to fetch conversation');
    return response.json();
  }

  async sendMessage(conversationId: string, query: ChatbotQuery): Promise<ChatbotMessage> {
    const response = await fetch(`${API_BASE}/conversations/${conversationId}/messages`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(query)
    });
    if (!response.ok) throw new Error('Failed to send message');
    return response.json();
  }

  async startNewConversation(): Promise<ChatbotConversation> {
    const response = await fetch(`${API_BASE}/conversations`, { method: 'POST' });
    if (!response.ok) throw new Error('Failed to start conversation');
    return response.json();
  }

  async deleteConversation(id: string): Promise<void> {
    const response = await fetch(`${API_BASE}/conversations/${id}`, { method: 'DELETE' });
    if (!response.ok) throw new Error('Failed to delete conversation');
  }
}

export const chatbotService = new ChatbotService();
export default chatbotService;
