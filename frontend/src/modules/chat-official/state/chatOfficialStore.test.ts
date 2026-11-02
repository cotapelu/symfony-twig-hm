import { describe, it, expect, vi, beforeEach } from 'vitest';
import { chatOfficialStore, officialChats, activeOfficialChat, chatOfficialLoading } from './chatOfficialStore';
import { chatOfficialService } from '../services/chatOfficialService';
import type { OfficialChat, ChatMessage } from '../contracts/chatOfficialTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = chatOfficialStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockChat = (overrides: Partial<OfficialChat> = {}): OfficialChat => ({
  id: `chat-${Math.random().toString(36).substr(2, 5)}`,
  departmentId: 'dept-1',
  departmentName: 'General Support',
  participantIds: ['user1', 'agent1'],
  lastMessage: null,
  unreadCount: 0,
  isActive: true,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  ...overrides
});

const mockMessage = (overrides: Partial<ChatMessage> = {}): ChatMessage => ({
  id: `msg-${Math.random().toString(36).substr(2, 5)}`,
  chatId: 'chat-1',
  senderId: 'user1',
  content: 'Hello',
  attachments: [],
  timestamp: new Date().toISOString(),
  ...overrides
});

describe('ChatOfficialStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    chatOfficialStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.chats).toEqual([]);
      expect(state.activeChat).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let chats: OfficialChat[] = [], active: OfficialChat | null = null, load = false;
      const unsubC = officialChats.subscribe(s => { chats = s; });
      const unsubA = activeOfficialChat.subscribe(s => { active = s; });
      const unsubL = chatOfficialLoading.subscribe(s => { load = s; });
      expect(chats).toEqual([]);
      expect(active).toBeNull();
      expect(load).toBe(false);
      unsubC(); unsubA(); unsubL();
    });
  });

  describe('loadChats', () => {
    it('loads chats successfully', async () => {
      const chats = [mockChat(), mockChat()];
      vi.spyOn(chatOfficialService, 'getChats').mockResolvedValue(chats);

      await chatOfficialStore.loadChats();

      expect(chatOfficialService.getChats).toHaveBeenCalledTimes(1);
      const state = getState();
      expect(state.chats).toEqual(chats);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading during fetch', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(chatOfficialService, 'getChats').mockReturnValue(p as any);

      const loadPromise = chatOfficialStore.loadChats();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockChat()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch chats');
      vi.spyOn(chatOfficialService, 'getChats').mockRejectedValue(error);

      await chatOfficialStore.loadChats();

      const state = getState();
      expect(state.error).toBe('Failed to fetch chats');
      expect(state.loading).toBe(false);
      expect(state.chats).toEqual([]);
    });
  });

  describe('loadChat', () => {
    it('loads specific chat and sets activeChat', async () => {
      const chat = mockChat({ id: 'chat-1' });
      vi.spyOn(chatOfficialService, 'getChat').mockResolvedValue(chat);

      await chatOfficialStore.loadChat('chat-1');

      expect(chatOfficialService.getChat).toHaveBeenCalledWith('chat-1');
      const state = getState();
      expect(state.activeChat).toEqual(chat);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading during fetch', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(chatOfficialService, 'getChat').mockReturnValue(p as any);

      const loadPromise = chatOfficialStore.loadChat('chat-1');

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(mockChat());
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Chat not found');
      vi.spyOn(chatOfficialService, 'getChat').mockRejectedValue(error);

      await chatOfficialStore.loadChat('chat-1');

      const state = getState();
      expect(state.error).toBe('Chat not found');
      expect(state.loading).toBe(false);
      expect(state.activeChat).toBeNull();
    });
  });

  describe('createChat', () => {
    it('creates chat and adds to chats list', async () => {
      const newChat = mockChat({ id: 'new-chat' });
      vi.spyOn(chatOfficialService, 'createChat').mockResolvedValue(newChat);

      const result = await chatOfficialStore.createChat('dept-1');

      expect(result).toEqual(newChat);
      expect(chatOfficialService.createChat).toHaveBeenCalledWith('dept-1');
      const state = getState();
      expect(state.chats).toContainEqual(newChat);
    });

    it('throws on error without setting error state', async () => {
      const error = new Error('Create failed');
      vi.spyOn(chatOfficialService, 'createChat').mockRejectedValue(error);

      await expect(chatOfficialStore.createChat('dept-1')).rejects.toThrow('Create failed');

      const state = getState();
      // createChat does not catch errors, so error state remains unchanged
      expect(state.error).toBeNull();
      expect(state.chats).toEqual([]);
    });
  });

  describe('sendMessage', () => {
    it('sends message and appends to activeChat messages if active', async () => {
      const chat = mockChat({ id: 'chat-1', messages: [] });
      const message = mockMessage({ chatId: 'chat-1' });

      // Pre-set activeChat
      vi.spyOn(chatOfficialService, 'getChat').mockResolvedValue(chat);
      await chatOfficialStore.loadChat('chat-1');
      expect(getState().activeChat).toEqual(chat);

      vi.spyOn(chatOfficialService, 'sendMessage').mockResolvedValue(message);
      const result = await chatOfficialStore.sendMessage('chat-1', 'Hello');

      expect(result).toEqual(message);
      expect(chatOfficialService.sendMessage).toHaveBeenCalledWith({ chatId: 'chat-1', content: 'Hello', attachments: undefined });

      const state = getState();
      expect(state.activeChat?.messages).toContainEqual(message);
    });

    it('does not modify activeChat if different', async () => {
      const chat = mockChat({ id: 'chat-1', messages: [] });
      const message = mockMessage({ chatId: 'chat-2' });

      vi.spyOn(chatOfficialService, 'getChat').mockResolvedValue(chat);
      await chatOfficialStore.loadChat('chat-1');

      vi.spyOn(chatOfficialService, 'sendMessage').mockResolvedValue(message);
      await chatOfficialStore.sendMessage('chat-2', 'Hello');

      const state = getState();
      expect(state.activeChat?.messages).toEqual([]);
    });

    it('throws on error without setting error state', async () => {
      const error = new Error('Send failed');
      vi.spyOn(chatOfficialService, 'sendMessage').mockRejectedValue(error);

      await expect(chatOfficialStore.sendMessage('chat-1', 'Hello')).rejects.toThrow('Send failed');

      const state = getState();
      // sendMessage does not catch errors, so error state remains unchanged
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(chatOfficialService, 'getChats').mockRejectedValue(new Error('test error'));
      await chatOfficialStore.loadChats().catch(() => {});

      expect(getState().error).toBe('test error');

      chatOfficialStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Load some chats
      const chats = [mockChat(), mockChat()];
      vi.spyOn(chatOfficialService, 'getChats').mockResolvedValue(chats);
      await chatOfficialStore.loadChats();
      expect(getState().chats).toHaveLength(2);

      // Set activeChat
      const chat = mockChat({ id: 'chat-1' });
      vi.spyOn(chatOfficialService, 'getChat').mockResolvedValue(chat);
      await chatOfficialStore.loadChat('chat-1');
      expect(getState().activeChat).not.toBeNull();

      // Reset
      chatOfficialStore.reset();

      const state = getState();
      expect(state.chats).toEqual([]);
      expect(state.activeChat).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
