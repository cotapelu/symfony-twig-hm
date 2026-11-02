import { describe, it, expect, vi, beforeEach } from 'vitest';
import { MessageService } from './messages.service';
import { messageStore } from '../state/messages-store';

// Mock messageStore
vi.mock('../state/messages-store', () => ({
  messageStore: {
    sendMessage: vi.fn(),
    loadConversations: vi.fn()
  }
}));

// Mock fetch
global.fetch = vi.fn();

describe('MessageService', () => {
  let service: MessageService;

  beforeEach(() => {
    service = new MessageService();
    vi.clearAllMocks();
  });

  describe('sendMessage', () => {
    it('should send via messageStore', async () => {
      (messageStore.sendMessage as any).mockResolvedValue(undefined);
      await service.sendMessage('Hello', 'conv1');
      expect(messageStore.sendMessage).toHaveBeenCalledWith('Hello', 'conv1');
    });

    it('should propagate errors', async () => {
      (messageStore.sendMessage as any).mockRejectedValue(new Error('Failed'));
      await expect(service.sendMessage('Hi', 'conv1')).rejects.toThrow('Failed');
    });
  });

  describe('loadConversations', () => {
    it('should load via messageStore', async () => {
      const convos = [{ id: 'c1' }];
      (messageStore.loadConversations as any).mockResolvedValue(convos);
      const result = await service.loadConversations();
      expect(result).toEqual(convos);
    });

    it('should propagate errors', async () => {
      (messageStore.loadConversations as any).mockRejectedValue(new Error('Error'));
      await expect(service.loadConversations()).rejects.toThrow('Error');
    });
  });

  describe('loadMessages', () => {
    it('should fetch messages for conversation', async () => {
      const messages = [{ id: 'm1', content: 'Hi' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: async () => messages });
      const result = await service.loadMessages('conv1');
      expect(result).toEqual(messages);
      expect(global.fetch).toHaveBeenCalledWith('/api/messages/conversation/conv1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.loadMessages('conv1')).rejects.toThrow('Failed to load messages');
    });
  });

  describe('markAsRead', () => {
    it('should mark message as read', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.markAsRead('m1');
      expect(global.fetch).toHaveBeenCalledWith('/api/messages/m1/read', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.markAsRead('m1')).rejects.toThrow('Failed to mark message as read');
    });
  });

  describe('deleteMessage', () => {
    it('should delete message', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.deleteMessage('m1');
      expect(global.fetch).toHaveBeenCalledWith('/api/messages/m1', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.deleteMessage('m1')).rejects.toThrow('Failed to delete message');
    });
  });
});
