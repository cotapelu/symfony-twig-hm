import { apiClient } from '../../../appcore/api-client';

export class MessageService {
  private baseUrl = '/api/messages';

  async getConversations() {
    return apiClient.get(`${this.baseUrl}/conversations`);
  }

  async getConversation(conversationId: string) {
    return apiClient.get(`${this.baseUrl}/conversations/${conversationId}`);
  }

  async sendMessage(conversationId: string, message: { content: string; type: string }) {
    return apiClient.post(`${this.baseUrl}/conversations/${conversationId}/messages`, message);
  }

  async createConversation(participants: string[], initialMessage?: string) {
    return apiClient.post(`${this.baseUrl}/conversations`, { participants, initialMessage });
  }

  async markAsRead(conversationId: string, messageId: string) {
    return apiClient.put(`${this.baseUrl}/conversations/${conversationId}/messages/${messageId}/read`);
  }

  async uploadFile(conversationId: string, file: File) {
    const formData = new FormData();
    formData.append('file', file);
    return apiClient.post(`${this.baseUrl}/conversations/${conversationId}/upload`, formData);
  }

  async deleteMessage(conversationId: string, messageId: string) {
    return apiClient.delete(`${this.baseUrl}/conversations/${conversationId}/messages/${messageId}`);
  }
}