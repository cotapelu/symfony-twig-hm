import { describe, it, expect, vi, beforeEach } from 'vitest';
import { messageStore, conversations, activeMessages } from './messages-store';

const fetchMock = vi.fn();
vi.stubGlobal('fetch', fetchMock);

describe('MessageStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    fetchMock.mockReset();
    messageStore.reset();
    messageStore.subscribe((s: any) => {
      state = s;
    });
  });

  describe('initial state', () => {
    it('should have empty collections', () => {
      expect(state.messages).toEqual([]);
      expect(state.conversations).toEqual([]);
      expect(state.activeConversation).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('sendMessage', () => {
    it('adds message optimistically and calls API', async () => {
      const conversationId = 'conv1';
      fetchMock.mockResolvedValue({ ok: true });
      await messageStore.sendMessage('Hello', conversationId);

      expect(state.messages).toHaveLength(1);
      expect(state.messages[0].content).toBe('Hello');
      expect(state.messages[0].conversationId).toBe(conversationId);
      expect(fetchMock).toHaveBeenCalledWith('/api/messages/send', expect.objectContaining({
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: 'Hello', conversationId })
      }));
      expect(state.error).toBeNull();
    });

    it('sets error on API failure', async () => {
      const conversationId = 'conv1';
      fetchMock.mockResolvedValue({ ok: false });
      await messageStore.sendMessage('Hello', conversationId);

      expect(state.messages).toHaveLength(1);
      expect(state.error).toBe('Failed to send message');
    });

    it('sets error on network exception', async () => {
      fetchMock.mockRejectedValue(new Error('Network down'));
      await messageStore.sendMessage('Hello', 'conv1');

      expect(state.messages).toHaveLength(1);
      expect(state.error).toBe('Network down');
    });
  });

  describe('loadConversations', () => {
    it('sets loading true then loads conversations', async () => {
      // Defer fetch resolution to observe loading state
      let resolveFetch: (value: any) => void;
      const fetchPromise = new Promise(resolve => { resolveFetch = resolve; });
      fetchMock.mockReturnValue(fetchPromise);

      const loadPromise = messageStore.loadConversations();

      // Microtask to allow store update
      await new Promise(r => setTimeout(r, 0));
      expect(state.loading).toBe(true);
      expect(fetchMock).toHaveBeenCalledWith('/api/messages/conversations');

      // Resolve fetch
      const mockConvs = [
        {
          id: 'c1',
          participant: 'Alice',
          lastMessage: 'Hi',
          timestamp: new Date().toISOString(),
          unreadCount: 0
        }
      ];
      resolveFetch({ ok: true, json: () => Promise.resolve(mockConvs) });

      await loadPromise;
      expect(state.conversations).toHaveLength(1);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles API error', async () => {
      fetchMock.mockResolvedValue({ ok: false });
      await messageStore.loadConversations();

      expect(state.loading).toBe(false);
      expect(state.error).toBe('Failed to load conversations');
      expect(state.conversations).toEqual([]);
    });

    it('handles network error', async () => {
      fetchMock.mockRejectedValue(new Error('Offline'));
      await messageStore.loadConversations();

      expect(state.loading).toBe(false);
      expect(state.error).toBe('Offline');
    });
  });

  describe('setActiveConversation', () => {
    it('sets active conversation', () => {
      messageStore.setActiveConversation('conv1');
      expect(state.activeConversation).toBe('conv1');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      // Trigger an error by failing loadConversations
      fetchMock.mockResolvedValue({ ok: false });
      await messageStore.loadConversations();
      expect(state.error).toBe('Failed to load conversations');
      // Clear error
      messageStore.clearError();
      expect(state.error).toBeNull();
    });
  });

  describe('derived stores', () => {
    it('conversations returns current conversations', async () => {
      let convValue: any = [];
      conversations.subscribe(v => (convValue = v));

      expect(convValue).toEqual([]);

      const mockConvs = [
        {
          id: 'c1',
          participant: 'Alice',
          lastMessage: 'Hi',
          timestamp: new Date().toISOString(),
          unreadCount: 0
        }
      ];
      fetchMock.mockResolvedValue({ ok: true, json: () => Promise.resolve(mockConvs) });
      await messageStore.loadConversations();

      expect(convValue).toHaveLength(1);
      expect(convValue[0].id).toBe('c1');
    });

    it('activeMessages filters by activeConversation', async () => {
      let activeMsgs: any = [];
      activeMessages.subscribe(v => (activeMsgs = v));

      fetchMock.mockResolvedValue({ ok: true });
      await messageStore.sendMessage('Hello', 'conv1');
      await messageStore.sendMessage('Hi', 'conv2');

      // No active conversation => empty
      expect(activeMsgs).toEqual([]);

      messageStore.setActiveConversation('conv1');
      expect(activeMsgs).toHaveLength(1);
      expect(activeMsgs[0].content).toBe('Hello');

      messageStore.setActiveConversation('conv2');
      expect(activeMsgs).toHaveLength(1);
      expect(activeMsgs[0].content).toBe('Hi');
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      // Add a message
      fetchMock.mockResolvedValue({ ok: true });
      await messageStore.sendMessage('Hello', 'conv1');
      // Set active conversation
      messageStore.setActiveConversation('conv1');
      // Trigger an error via failing loadConversations
      fetchMock.mockResolvedValue({ ok: false });
      await messageStore.loadConversations();
      expect(state.error).toBe('Failed to load conversations');

      // Reset
      messageStore.reset();

      expect(state.messages).toEqual([]);
      expect(state.activeConversation).toBeNull();
      expect(state.error).toBeNull();
      expect(state.loading).toBe(false);
    });
  });
});
