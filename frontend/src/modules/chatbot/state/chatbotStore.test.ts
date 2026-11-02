import { describe, it, expect, vi, beforeEach } from 'vitest';
import { chatbotStore } from './chatbotStore';
import { chatbotService } from '../services/chatbotService';
import type { ChatbotConversation, ChatbotState, ChatbotMessage } from '../contracts/chatbotTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = chatbotStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockConversation = (overrides: Partial<ChatbotConversation> = {}): ChatbotConversation => ({
  id: `conv-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  title: 'Test Conversation',
  messages: [],
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  ...overrides
});

const mockMessage = (overrides: Partial<ChatbotMessage> = {}): ChatbotMessage => ({
  id: `msg-${Math.random().toString(36).substr(2, 5)}`,
  role: 'user',
  content: 'Hello',
  timestamp: new Date().toISOString(),
  ...overrides
});

describe('ChatbotStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    chatbotStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.conversations).toEqual([]);
      expect(state.activeConversation).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.currentUserId).toBeNull();
    });
  });

  describe('setCurrentUser', () => {
    it('sets currentUserId', () => {
      chatbotStore.setCurrentUser('user-42');
      expect(getState().currentUserId).toBe('user-42');
    });
  });

  describe('loadConversations', () => {
    it('loads all conversations when no user set', async () => {
      const convs = [mockConversation(), mockConversation()];
      vi.spyOn(chatbotService, 'getConversations').mockResolvedValue(convs);

      await chatbotStore.loadConversations();

      expect(chatbotService.getConversations).toHaveBeenCalled();
      const state = getState();
      expect(state.conversations).toEqual(convs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads user conversations when currentUserId set', async () => {
      const convs = [mockConversation({ userId: 'user-1' })];
      vi.spyOn(chatbotService, 'getMyConversations').mockResolvedValue(convs);

      chatbotStore.setCurrentUser('user-1');
      await chatbotStore.loadConversations();

      expect(chatbotService.getMyConversations).toHaveBeenCalled();
      const state = getState();
      expect(state.conversations).toEqual(convs);
    });

    it('sets loading during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(chatbotService, 'getConversations').mockReturnValue(p as any);

      const loadPromise = chatbotStore.loadConversations();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockConversation()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to load conversations');
      vi.spyOn(chatbotService, 'getConversations').mockRejectedValue(error);

      await chatbotStore.loadConversations();

      const state = getState();
      expect(state.error).toBe('Failed to load conversations');
      expect(state.loading).toBe(false);
      expect(state.conversations).toEqual([]);
    });
  });

  describe('loadConversation', () => {
    it('loads conversation and sets activeConversation', async () => {
      const conv = mockConversation();
      vi.spyOn(chatbotService, 'getConversation').mockResolvedValue(conv);

      await chatbotStore.loadConversation('conv-1');

      expect(chatbotService.getConversation).toHaveBeenCalledWith('conv-1');
      const state = getState();
      expect(state.activeConversation).toEqual(conv);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('throws unauthorized if conversation user does not match currentUserId', async () => {
      const conv = mockConversation({ id: 'conv-1', userId: 'other-user' });
      vi.spyOn(chatbotService, 'getConversation').mockResolvedValue(conv);

      chatbotStore.setCurrentUser('user-1');

      await expect(chatbotStore.loadConversation('conv-1')).rejects.toThrow('Unauthorized: You can only access your own conversations');

      const state = getState();
      expect(state.error).toBe('Unauthorized: You can only access your own conversations');
      expect(state.activeConversation).toBeNull();
    });

    it('handles error', async () => {
      const error = new Error('Not found');
      vi.spyOn(chatbotService, 'getConversation').mockRejectedValue(error);

      await expect(chatbotStore.loadConversation('conv-1')).rejects.toThrow('Not found');

      const state = getState();
      expect(state.error).toBe('Not found');
      expect(state.activeConversation).toBeNull();
    });
  });

  describe('startConversation', () => {
    it('starts new conversation and updates state', async () => {
      const conv = mockConversation();
      vi.spyOn(chatbotService, 'startNewConversation').mockResolvedValue(conv);

      const result = await chatbotStore.startConversation();

      expect(result).toEqual(conv);
      const state = getState();
      expect(state.conversations).toContainEqual(conv);
      expect(state.activeConversation).toEqual(conv);
    });

    it('handles error', async () => {
      const error = new Error('Start failed');
      vi.spyOn(chatbotService, 'startNewConversation').mockRejectedValue(error);

      await expect(chatbotStore.startConversation()).rejects.toThrow('Start failed');

      const state = getState();
      expect(state.error).toBeNull(); // startConversation does not set error on state
    });
  });

  describe('sendMessage', () => {
    it('sends message and adds to active conversation', async () => {
      const conv = mockConversation();
      vi.spyOn(chatbotService, 'startNewConversation').mockResolvedValue(conv);
      await chatbotStore.startConversation();

      const msg = mockMessage({ id: 'msg-1', content: 'Hello' });
      vi.spyOn(chatbotService, 'sendMessage').mockResolvedValue(msg);

      const result = await chatbotStore.sendMessage(conv.id, 'Hello');

      expect(result).toEqual(msg);
      expect(chatbotService.sendMessage).toHaveBeenCalledWith(conv.id, { message: 'Hello', context: undefined });
      const state = getState();
      expect(state.activeConversation?.messages).toContainEqual(msg);
    });

    it('does not update active conversation if it does not match conversationId', async () => {
      const conv1 = mockConversation();
      vi.spyOn(chatbotService, 'startNewConversation').mockResolvedValue(conv1);
      await chatbotStore.startConversation();

      const reply = mockMessage();
      vi.spyOn(chatbotService, 'sendMessage').mockResolvedValue(reply);

      await chatbotStore.sendMessage('conv-2', 'Hello');

      const state = getState();
      expect(state.activeConversation?.id).toBe(conv1.id);
      expect(state.activeConversation?.messages).toHaveLength(0);
    });

    it('handles error', async () => {
      const error = new Error('Send failed');
      vi.spyOn(chatbotService, 'sendMessage').mockRejectedValue(error);

      await expect(chatbotStore.sendMessage('conv-1', 'Hello')).rejects.toThrow('Send failed');

      const state = getState();
      expect(state.error).toBe('Send failed');
    });
  });

  describe('deleteConversation', () => {
    it('deletes conversation and removes from list', async () => {
      const conv1 = mockConversation({ id: 'conv-1' });
      const conv2 = mockConversation({ id: 'conv-2' });
      vi.spyOn(chatbotService, 'getConversations').mockResolvedValue([conv1, conv2]);
      await chatbotStore.loadConversations();
      expect(getState().conversations).toHaveLength(2);

      vi.spyOn(chatbotService, 'deleteConversation').mockResolvedValue(undefined);
      await chatbotStore.deleteConversation('conv-1');

      expect(chatbotService.deleteConversation).toHaveBeenCalledWith('conv-1');
      const state = getState();
      expect(state.conversations).toHaveLength(1);
      expect(state.conversations[0].id).toBe('conv-2');
    });

    it('clears active conversation if it was deleted', async () => {
      const conv = mockConversation({ id: 'conv-1' });
      vi.spyOn(chatbotService, 'getConversations').mockResolvedValue([conv]);
      await chatbotStore.loadConversations();

      vi.spyOn(chatbotService, 'getConversation').mockResolvedValue(conv);
      await chatbotStore.loadConversation(conv.id);
      expect(getState().activeConversation?.id).toBe(conv.id);

      vi.spyOn(chatbotService, 'deleteConversation').mockResolvedValue(undefined);
      await chatbotStore.deleteConversation(conv.id);

      const state = getState();
      expect(state.activeConversation).toBeNull();
    });

    it('handles error', async () => {
      const conv = mockConversation({ id: 'conv-1' });
      vi.spyOn(chatbotService, 'getConversations').mockResolvedValue([conv]);
      await chatbotStore.loadConversations();

      const error = new Error('Delete failed');
      vi.spyOn(chatbotService, 'deleteConversation').mockRejectedValue(error);

      await expect(chatbotStore.deleteConversation('conv-1')).rejects.toThrow('Delete failed');

      const state = getState();
      expect(state.error).toBe('Delete failed');
      expect(state.conversations).toHaveLength(1);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      const error = new Error('test error');
      vi.spyOn(chatbotService, 'getConversations').mockRejectedValue(error);
      await chatbotStore.loadConversations();
      expect(getState().error).toBe('test error');

      chatbotStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      const conv = mockConversation();
      vi.spyOn(chatbotService, 'startNewConversation').mockResolvedValue(conv);
      await chatbotStore.startConversation();
      chatbotStore.setCurrentUser('user-1');

      expect(getState().conversations).toHaveLength(1);
      expect(getState().currentUserId).toBe('user-1');
      expect(getState().activeConversation).not.toBeNull();

      chatbotStore.reset();
      const state = getState();
      expect(state.conversations).toEqual([]);
      expect(state.activeConversation).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.currentUserId).toBeNull();
    });
  });
});
