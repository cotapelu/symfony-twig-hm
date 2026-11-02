import { describe, it, expect, vi, beforeEach } from 'vitest';
import { messagesService } from './messages';
import { getApiUrl } from '../config/api';

vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('MessagesService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getConversations', () => {
    it('fetches conversations list', async () => {
      const conversations = [{ id: 'c1', type: 'direct', participants: ['u1'], unreadCount: 0, createdAt: '', updatedAt: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ conversations }) });

      const result = await messagesService.getConversations();

      expect(getApiUrl).toHaveBeenCalledWith('conversation', '/api/conversations');
      expect(result).toEqual(conversations);
    });

    it('returns empty array if no conversations', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await messagesService.getConversations();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(messagesService.getConversations()).rejects.toThrow('Failed to fetch conversations');
    });
  });

  describe('getConversation', () => {
    it('fetches a single conversation', async () => {
      const conversation = { id: 'c1', type: 'direct', participants: [], unreadCount: 0, createdAt: '', updatedAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => conversation });

      const result = await messagesService.getConversation('c1');

      expect(getApiUrl).toHaveBeenCalledWith('conversation', '/api/conversations/c1');
      expect(result).toEqual(conversation);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(messagesService.getConversation('c1')).rejects.toThrow('Failed to fetch conversation');
    });
  });

  describe('createConversation', () => {
    it('creates a group conversation with title', async () => {
      const newConv = { id: 'c2', type: 'group', participants: ['u1', 'u2'], title: 'Group', unreadCount: 0, createdAt: '', updatedAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => newConv });

      const result = await messagesService.createConversation(['u1', 'u2'], 'Group');

      expect(getApiUrl).toHaveBeenCalledWith('conversation', '/api/conversations');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ participantIds: ['u1', 'u2'], title: 'Group' }),
      });
      expect(result).toEqual(newConv);
    });

    it('creates direct conversation without title', async () => {
      const newConv = { id: 'c3', type: 'direct', participants: ['u1'], unreadCount: 0, createdAt: '', updatedAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => newConv });

      const result = await messagesService.createConversation(['u1']);

      expect(mockFetch.mock.calls[0][1].body).toBe(JSON.stringify({ participantIds: ['u1'] }));
      expect(result).toEqual(newConv);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(messagesService.createConversation(['u1'])).rejects.toThrow('Failed to create conversation');
    });
  });

  describe('getMessages', () => {
    it('fetches messages for a conversation', async () => {
      const messages = [{ id: 'm1', conversationId: 'c1', senderId: 'u1', content: 'Hi', type: 'text', createdAt: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ messages }) });

      const result = await messagesService.getMessages('c1');

      expect(getApiUrl).toHaveBeenCalledWith('message', '/api/messages/c1?page=1&limit=50');
      expect(result).toEqual(messages);
    });

    it('supports pagination', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await messagesService.getMessages('c1', 2, 20);
      const calledUrl = getApiUrl.mock.calls[0][1];
      expect(calledUrl).toBe('/api/messages/c1?page=2&limit=20');
    });

    it('returns empty array if no messages', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await messagesService.getMessages('c1');
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(messagesService.getMessages('c1')).rejects.toThrow('Failed to fetch messages');
    });
  });

  describe('sendMessage', () => {
    it('sends a text message', async () => {
      const message = { id: 'm1', conversationId: 'c1', senderId: 'u1', content: 'Hello', type: 'text', createdAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => message });

      const result = await messagesService.sendMessage({ conversationId: 'c1', content: 'Hello' });

      expect(getApiUrl).toHaveBeenCalledWith('message', '/api/messages');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ conversationId: 'c1', content: 'Hello' }),
      });
      expect(result).toEqual(message);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(messagesService.sendMessage({ conversationId: 'c1', content: 'Test' })).rejects.toThrow('Failed to send message');
    });
  });

  describe('deleteMessage', () => {
    it('deletes a message', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });

      await messagesService.deleteMessage('m1');

      expect(getApiUrl).toHaveBeenCalledWith('message', '/api/messages/m1');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'DELETE',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(messagesService.deleteMessage('m1')).rejects.toThrow('Failed to delete message');
    });
  });

  describe('markAsRead', () => {
    it('marks conversation as read', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });

      await messagesService.markAsRead('c1');

      expect(getApiUrl).toHaveBeenCalledWith('message', '/api/conversations/c1/read');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(messagesService.markAsRead('c1')).rejects.toThrow('Failed to mark as read');
    });
  });

  describe('searchMessages', () => {
    it('searches messages with query', async () => {
      const messages = [{ id: 'm1', content: 'Hello' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ messages }) });

      const result = await messagesService.searchMessages('hello');

      expect(getApiUrl).toHaveBeenCalledWith('message', '/api/messages/search?q=hello');
      expect(result).toEqual(messages);
    });

    it('encodes query', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await messagesService.searchMessages('a b');
      const urlParam = getApiUrl.mock.calls[0][1];
      expect(urlParam).toContain('q=a%20b');
    });

    it('returns empty array if no matches', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await messagesService.searchMessages('xyz');
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(messagesService.searchMessages('test')).rejects.toThrow('Failed to search messages');
    });
  });
});
