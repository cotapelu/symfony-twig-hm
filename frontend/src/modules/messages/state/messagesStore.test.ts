import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  messagesStore,
  conversations,
  activeConversation,
  activeMessages,
  loading,
  error,
  loadConversations,
  loadMessages,
  sendMessage,
  setActiveConversation,
  markAsRead,
  clearError
} from './messagesStore';
import type { Message, Conversation } from '../contracts/messagesTypes';

function getCurrentState() {
  let current: any;
  const unsub = messagesStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockConversation = (overrides: Partial<Conversation> = {}): Conversation => ({
  id: `conv-${Math.random().toString(36).substr(2, 5)}`,
  participants: ['user1', 'user2'],
  lastMessage: undefined,
  unreadCount: 0,
  isActive: true,
  createdAt: now,
  updatedAt: now,
  ...overrides
});

const mockMessage = (overrides: Partial<Message> = {}): Message => ({
  id: `msg-${Math.random().toString(36).substr(2, 5)}`,
  senderId: 'user1',
  receiverId: 'user2',
  content: 'Hello',
  timestamp: now,
  read: false,
  type: 'text',
  ...overrides
});

describe('MessagesStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Reset store to initial
    messagesStore.set({
      conversations: [],
      activeConversation: null,
      messages: {},
      loading: false,
      error: null,
      typing: {}
    });
  });

  describe('initial state', () => {
    it('has empty arrays and defaults', () => {
      const state = getCurrentState();
      expect(state.conversations).toEqual([]);
      expect(state.activeConversation).toBeNull();
      expect(state.messages).toEqual({});
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.typing).toEqual({});
    });

    it('derived stores', () => {
      let convs: Conversation[] = [], active: string | null = [], msgs: Message[] = [], load = false, err: string | null = null;
      const unsubC = conversations.subscribe(s => { convs = s; });
      const unsubA = activeConversation.subscribe(s => { active = s; });
      const unsubM = activeMessages.subscribe(s => { msgs = s; });
      const unsubL = loading.subscribe(s => { load = s; });
      const unsubE = error.subscribe(s => { err = s; });
      expect(convs).toEqual([]);
      expect(active).toBeNull();
      expect(msgs).toEqual([]);
      expect(load).toBe(false);
      expect(err).toBeNull();
      unsubC(); unsubA(); unsubM(); unsubL(); unsubE();
    });
  });

  describe('loadConversations', () => {
    it('loads conversations', async () => {
      const convs = [mockConversation(), mockConversation()];
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(convs)
      }) as any;
      await loadConversations();
      const state = getCurrentState();
      expect(state.conversations).toEqual(convs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: false
      }) as any;
      await loadConversations();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load conversations');
      expect(state.conversations).toEqual([]);
    });
  });

  describe('loadMessages', () => {
    it('loads messages for conversation and sets activeConversation', async () => {
      const convId = 'conv-1';
      const msgs = [mockMessage(), mockMessage()];
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(msgs)
      }) as any;
      await loadMessages(convId);
      const state = getCurrentState();
      expect(state.activeConversation).toBe(convId);
      expect(state.messages[convId]).toEqual(msgs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: false
      }) as any;
      await loadMessages('conv-1');
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load messages');
      expect(state.activeConversation).toBeNull();
    });
  });

  describe('sendMessage', () => {
    it('adds message to conversation', async () => {
      const convId = 'conv-1';
      // Preload conversation and active
      global.fetch = vi.fn()
        .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve([]) }) // loadMessages conversations fetch (but loadMessages doesn't call that)
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve(mockMessage({ id: 'm1' }))
        }) as any;
      // First, loadMessages to set activeConversation
      await loadMessages(convId);
      // Now sendMessage
      const sent = await sendMessage(convId, 'Hi');
      expect(sent).toEqual(expect.objectContaining({ id: 'm1' }));
      let state = getCurrentState();
      expect(state.messages[convId]).toHaveLength(1);
    });

    it('propagates errors', async () => {
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: false
      }) as any;
      try {
        await sendMessage('conv-1', 'Hi');
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Failed to send message');
    });
  });

  describe('setActiveConversation', () => {
    it('sets activeConversation', () => {
      setActiveConversation('conv-1');
      let state = getCurrentState();
      expect(state.activeConversation).toBe('conv-1');
      setActiveConversation(null);
      state = getCurrentState();
      expect(state.activeConversation).toBeNull();
    });
  });

  describe('markAsRead', () => {
    it('marks message as read in conversation', () => {
      const convId = 'conv-1';
      const msgs: Message[] = [
        mockMessage({ id: 'm1', read: false }),
        mockMessage({ id: 'm2', read: true })
      ];
      // Set initial state directly
      messagesStore.update(state => ({
        ...state,
        messages: { ...state.messages, [convId]: msgs }
      }));
      markAsRead(convId, 'm1');
      let state = getCurrentState();
      expect(state.messages[convId][0].read).toBe(true);
      expect(state.messages[convId][1].read).toBe(true); // unchanged
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: false
      }) as any;
      await loadConversations();
      let state = getCurrentState();
      expect(state.error).toBe('Failed to load conversations');
      clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });
});
