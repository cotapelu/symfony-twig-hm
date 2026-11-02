import type { OfficialChat, ChatMessage, SendMessageRequest, ChatOfficialState } from '../contracts/chatOfficialTypes';

const API_BASE = '/api/chat-official';

export class ChatOfficialService {
  async getChats(): Promise<OfficialChat[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch chats');
    return response.json();
  }

  async getChat(id: string): Promise<OfficialChat> {
    const response = await fetch(`${API_BASE}/${id}`);
    if (!response.ok) throw new Error('Failed to fetch chat');
    return response.json();
  }

  async createChat(departmentId: string): Promise<OfficialChat> {
    const response = await fetch(`${API_BASE}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ departmentId })
    });
    if (!response.ok) throw new Error('Failed to create chat');
    return response.json();
  }

  async sendMessage(request: SendMessageRequest): Promise<ChatMessage> {
    const response = await fetch(`${API_BASE}/messages`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to send message');
    return response.json();
  }

  async closeChat(id: string): Promise<OfficialChat> {
    const response = await fetch(`${API_BASE}/${id}/close`, { method: 'PUT' });
    if (!response.ok) throw new Error('Failed to close chat');
    return response.json();
  }
}

export const chatOfficialService = new ChatOfficialService();
export default chatOfficialService;
