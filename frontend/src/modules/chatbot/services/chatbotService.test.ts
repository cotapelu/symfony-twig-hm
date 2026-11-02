import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ChatbotService } from './chatbotService';

global.fetch = vi.fn();

describe('ChatbotService', () => {
  let service: ChatbotService;

  beforeEach(() => {
    service = new ChatbotService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getConversations', () => {
    it('should fetch all conversations', async () => {
      const convos = [{ id: 'c1', title: 'Help' }];
      (global.fetch as any).mockResolvedValue(mockResponse(convos));
      const result = await service.getConversations();
      expect(result).toEqual(convos);
      expect(fetch).toHaveBeenCalledWith('/api/chatbot/conversations');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getConversations()).rejects.toThrow('Failed to fetch conversations');
    });
  });

  describe('getMyConversations', () => {
    it('should fetch user conversations', async () => {
      const convos = [{ id: 'c2' }];
      (global.fetch as any).mockResolvedValue(mockResponse(convos));
      const result = await service.getMyConversations();
      expect(result).toEqual(convos);
      expect(fetch).toHaveBeenCalledWith('/api/chatbot/my-conversations');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getMyConversations()).rejects.toThrow('Failed to fetch my conversations');
    });
  });

  describe('getConversation', () => {
    it('should fetch conversation by id', async () => {
      const convo = { id: 'c1' };
      (global.fetch as any).mockResolvedValue(mockResponse(convo));
      const result = await service.getConversation('c1');
      expect(result).toEqual(convo);
      expect(fetch).toHaveBeenCalledWith('/api/chatbot/conversations/c1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getConversation('c1')).rejects.toThrow('Failed to fetch conversation');
    });
  });

  describe('sendMessage', () => {
    it('should send message to conversation', async () => {
      const msg = { id: 'm1', text: 'Hi' };
      (global.fetch as any).mockResolvedValue(mockResponse(msg));
      const query = { text: 'Hello' };
      const result = await service.sendMessage('c1', query);
      expect(result).toEqual(msg);
      expect(fetch).toHaveBeenCalledWith('/api/chatbot/conversations/c1/messages', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(query)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.sendMessage('c1', { text: 'Hi' })).rejects.toThrow('Failed to send message');
    });
  });

  describe('startNewConversation', () => {
    it('should start a new conversation', async () => {
      const convo = { id: 'new' };
      (global.fetch as any).mockResolvedValue(mockResponse(convo));
      const result = await service.startNewConversation();
      expect(result).toEqual(convo);
      expect(fetch).toHaveBeenCalledWith('/api/chatbot/conversations', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.startNewConversation()).rejects.toThrow('Failed to start conversation');
    });
  });

  describe('deleteConversation', () => {
    it('should delete conversation', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.deleteConversation('c1');
      expect(fetch).toHaveBeenCalledWith('/api/chatbot/conversations/c1', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.deleteConversation('c1')).rejects.toThrow('Failed to delete conversation');
    });
  });
});
