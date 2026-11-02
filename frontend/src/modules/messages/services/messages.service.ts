import { messageStore } from '../state/messages-store';

export class MessageService {
  async sendMessage(content: string, conversationId: string): Promise<void> {
    return await messageStore.sendMessage(content, conversationId);
  }

  async loadConversations(): Promise<any[]> {
    return await messageStore.loadConversations();
  }

  async loadMessages(conversationId: string): Promise<any[]> {
    try {
      const response = await fetch(`/api/messages/conversation/${conversationId}`);
      
      if (!response.ok) {
        throw new Error('Failed to load messages');
      }

      return await response.json();
    } catch (error) {
      throw error;
    }
  }

  async markAsRead(messageId: string): Promise<void> {
    try {
      const response = await fetch(`/api/messages/${messageId}/read`, {
        method: 'POST'
      });

      if (!response.ok) {
        throw new Error('Failed to mark message as read');
      }
    } catch (error) {
      throw error;
    }
  }

  async deleteMessage(messageId: string): Promise<void> {
    try {
      const response = await fetch(`/api/messages/${messageId}`, {
        method: 'DELETE'
      });

      if (!response.ok) {
        throw new Error('Failed to delete message');
      }
    } catch (error) {
      throw error;
    }
  }
}

export const messageService = new MessageService();