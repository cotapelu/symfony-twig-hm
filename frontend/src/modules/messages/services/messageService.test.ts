import { describe, it, expect, vi, beforeEach } from 'vitest';
import { MessageService } from './messageService';
import { apiClient } from '../../../appcore/api-client';

// Mock apiClient
vi.mock('../../../appcore/api-client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}));

describe('MessageService', () => {
  let service: MessageService;

  beforeEach(() => {
    service = new MessageService();
    vi.clearAllMocks();
  });

  describe('getConversations', () => {
    it('should fetch conversations', async () => {
      const convs = [{ id: 'c1', name: 'General' }];
      (apiClient.get as any).mockResolvedValue(convs);

      const result = await service.getConversations();

      expect(result).toEqual(convs);
      expect(apiClient.get).toHaveBeenCalledWith('/api/messages/conversations');
    });

    it('should throw on error', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Fail'));

      await expect(service.getConversations()).rejects.toThrow('Fail');
    });
  });

  describe('getConversation', () => {
    it('should fetch conversation by id', async () => {
      const conv = { id: 'c1', messages: [] };
      (apiClient.get as any).mockResolvedValue(conv);

      const result = await service.getConversation('c1');

      expect(result).toEqual(conv);
      expect(apiClient.get).toHaveBeenCalledWith('/api/messages/conversations/c1');
    });
  });

  describe('sendMessage', () => {
    it('should send message to conversation', async () => {
      const msg = { id: 'm1', content: 'Hello' };
      const payload = { content: 'Hello', type: 'text' };
      (apiClient.post as any).mockResolvedValue(msg);

      const result = await service.sendMessage('c1', payload);

      expect(result).toEqual(msg);
      expect(apiClient.post).toHaveBeenCalledWith('/api/messages/conversations/c1/messages', payload);
    });
  });

  describe('createConversation', () => {
    it('should create conversation with participants', async () => {
      const conv = { id: 'c2' };
      (apiClient.post as any).mockResolvedValue(conv);

      const result = await service.createConversation(['u1', 'u2'], 'Hi');

      expect(result).toEqual(conv);
      expect(apiClient.post).toHaveBeenCalledWith('/api/messages/conversations', {
        participants: ['u1', 'u2'],
        initialMessage: 'Hi'
      });
    });
  });

  describe('markAsRead', () => {
    it('should mark message as read', async () => {
      (apiClient.put as any).mockResolvedValue(undefined);

      await service.markAsRead('c1', 'm1');

      expect(apiClient.put).toHaveBeenCalledWith('/api/messages/conversations/c1/messages/m1/read');
    });
  });

  describe('uploadFile', () => {
    it('should upload file to conversation', async () => {
      const file = new File(['content'], 'test.txt', { type: 'text/plain' });
      const attachment = { id: 'a1', name: 'test.txt' };
      (apiClient.post as any).mockResolvedValue(attachment);

      const result = await service.uploadFile('c1', file);

      expect(result).toEqual(attachment);
      // We can't easily assert FormData content, just verify endpoint called
      expect(apiClient.post).toHaveBeenCalledWith('/api/messages/conversations/c1/upload', expect.any(FormData));
    });
  });

  describe('deleteMessage', () => {
    it('should delete message', async () => {
      (apiClient.delete as any).mockResolvedValue(undefined);

      await service.deleteMessage('c1', 'm1');

      expect(apiClient.delete).toHaveBeenCalledWith('/api/messages/conversations/c1/messages/m1');
    });
  });
});
