import { describe, it, expect, vi, beforeEach } from 'vitest';
import { messagesStore, unreadCount, conversations, selectedConversation, messages, loading, activeConversation } from './messages';
import { PHP } from '../lib/api';

vi.mock('../lib/api', () => ({
  PHP: {
    getMessages: vi.fn(),
    getConversationMessages: vi.fn(),
    sendMessage: vi.fn(),
    createThread: vi.fn(),
    markMessageRead: vi.fn(),
    addReaction: vi.fn(),
    uploadFile: vi.fn(),
  }
}));

describe('MessagesStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    messagesStore.reset();
    messagesStore.subscribe(s => { state = s; });
  });

  const mockConversation = (id: string, name: string, unreadCount: number = 0): any => ({
    id,
    name,
    lastMessage: null,
    unreadCount,
    updatedAt: new Date().toISOString()
  });

  const mockMessage = (id: string, conversationId: string, content: string): any => ({
    id,
    conversationId,
    content,
    senderId: 'u1',
    createdAt: new Date().toISOString()
  });

  describe('loadConversations', () => {
    it('should load conversations', async () => {
      const convs = [mockConversation('c1', 'General'), mockConversation('c2', 'Support')];
      (PHP.getMessages as any).mockResolvedValue({ conversations: convs });

      const result = await messagesStore.loadConversations();

      expect(result).toEqual(convs);
      expect(state.conversations).toEqual(convs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('should handle empty', async () => {
      (PHP.getMessages as any).mockResolvedValue({ conversations: [] });
      await messagesStore.loadConversations();
      expect(state.conversations).toEqual([]);
    });

    it('should set error on failure', async () => {
      (PHP.getMessages as any).mockRejectedValue(new Error('API fail'));
      await expect(messagesStore.loadConversations()).rejects.toThrow('API fail');
      expect(state.loading).toBe(false);
      expect(state.error).toBe('API fail');
    });
  });

  describe('loadMessages', () => {
    it('should load messages for conversation', async () => {
      const msgs = [mockMessage('m1', 'c1', 'Hello'), mockMessage('m2', 'c1', 'World')];
      (PHP.getConversationMessages as any).mockResolvedValue({ messages: msgs });

      const result = await messagesStore.loadMessages('c1');

      expect(result).toEqual(msgs);
      expect(state.messages['c1']).toEqual(msgs);
      expect(state.loading).toBe(false);
    });

    it('should handle empty messages', async () => {
      (PHP.getConversationMessages as any).mockResolvedValue({ messages: [] });
      await messagesStore.loadMessages('c1');
      expect(state.messages['c1']).toEqual([]);
    });

    it('should set error on failure', async () => {
      (PHP.getConversationMessages as any).mockRejectedValue(new Error('Fail'));
      await expect(messagesStore.loadMessages('c1')).rejects.toThrow('Fail');
      expect(state.error).toBe('Fail');
    });
  });

  describe('sendMessage', () => {
    it('should send message and add to store', async () => {
      const msg = mockMessage('m1', 'c1', 'Hello');
      (PHP.sendMessage as any).mockResolvedValue(msg);

      const result = await messagesStore.sendMessage('c1', 'Hello');

      expect(result).toEqual(msg);
      expect(state.messages['c1']).toContain(msg);
    });

    it('should handle send error', async () => {
      (PHP.sendMessage as any).mockRejectedValue(new Error('Send failed'));
      await expect(messagesStore.sendMessage('c1', 'Hello')).rejects.toThrow('Send failed');
      expect(state.error).toBe('Send failed');
    });
  });

  describe('createThread', () => {
    it('should create thread and return message without adding to store automatically', async () => {
      const thread = mockMessage('m2', 'c1', 'Reply');
      (PHP.createThread as any).mockResolvedValue(thread);

      const result = await messagesStore.createThread('m1', 'c1', 'Reply');

      expect(result).toEqual(thread);
      // Store does not add thread to messages automatically
      expect(state.messages['c1']).toBeUndefined();
    });

    it('should handle thread error', async () => {
      (PHP.createThread as any).mockRejectedValue(new Error('Thread failed'));
      await expect(messagesStore.createThread('m1', 'c1', 'Reply')).rejects.toThrow('Thread failed');
      expect(state.error).toBe('Thread failed');
    });
  });

  describe('markRead', () => {
    it('should mark conversation as read and update conversation', async () => {
      const conv = mockConversation('c1', 'General', 5);
      state.conversations = [conv];
      (PHP.markMessageRead as any).mockResolvedValue(undefined);

      await messagesStore.markRead('c1');

      expect(PHP.markMessageRead).toHaveBeenCalledWith('c1');
      // The store updates conversations: unreadCount set to 0
      expect(state.conversations[0].unreadCount).toBe(0);
    });

    it('should handle markRead error without throwing', async () => {
      (PHP.markMessageRead as any).mockRejectedValue(new Error('Mark failed'));
      // The method catches error and logs; does not throw or set error
      await messagesStore.markRead('c1');
      // No error should be set
      expect(state.error).toBeNull();
    });
  });

  describe('addReaction', () => {
    it('should add reaction to message', async () => {
      (PHP.addReaction as any).mockResolvedValue(undefined);
      await messagesStore.addReaction('m1', '👍');
      expect(PHP.addReaction).toHaveBeenCalledWith('m1', '👍');
    });

    it('should handle reaction error without throwing', async () => {
      (PHP.addReaction as any).mockRejectedValue(new Error('React failed'));
      await messagesStore.addReaction('m1', '👍');
      expect(state.error).toBe('React failed');
    });
  });

  describe('uploadFile', () => {
    it('should upload file to conversation', async () => {
      const file = new File(['data'], 'doc.pdf', { type: 'application/pdf' });
      const fileItem = { id: 'f1', name: 'doc.pdf' };
      (PHP.uploadFile as any).mockResolvedValue(fileItem);

      const result = await messagesStore.uploadFile(file, 'c1');

      expect(result).toEqual(fileItem);
      expect(PHP.uploadFile).toHaveBeenCalledWith(file, 'c1');
    });

    it('should handle upload error', async () => {
      const file = new File(['data'], 'doc.pdf');
      (PHP.uploadFile as any).mockRejectedValue(new Error('Upload failed'));
      await expect(messagesStore.uploadFile(file, 'c1')).rejects.toThrow('Upload failed');
      expect(state.error).toBe('Upload failed');
    });
  });

  describe('setActiveConversation', () => {
    it('should set active conversation id', () => {
      messagesStore.setActiveConversation('c1');
      expect(state.activeConversationId).toBe('c1');
    });
    it('should clear active conversation when null', () => {
      messagesStore.setActiveConversation('c1');
      messagesStore.setActiveConversation(null);
      expect(state.activeConversationId).toBeNull();
    });
  });

  describe('addMessage', () => {
    it('should add message to conversation', () => {
      const msg = mockMessage('m1', 'c1', 'Hi');
      messagesStore.addMessage(msg);
      expect(state.messages['c1']).toContain(msg);
    });
  });

  describe('updateConversation', () => {
    it('should update conversation in list', () => {
      const conv = mockConversation('c1', 'General');
      state.conversations = [conv];
      messagesStore.updateConversation({ id: 'c1', name: 'Updated' });
      expect(state.conversations[0].name).toBe('Updated');
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (PHP.sendMessage as any).mockRejectedValue(new Error('test'));
      try {
        await messagesStore.sendMessage('c1', 'Hi');
      } catch (e) {}
      expect(state.error).toBe('test');
      messagesStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});

describe('unreadCount derived', () => {
  it('sums unread counts from conversations', async () => {
    messagesStore.reset();
    const mockConv = (id: string, unread: number) => ({
      id,
      name: 'Conv',
      lastMessage: null,
      unreadCount: unread,
      updatedAt: new Date().toISOString(),
    });
    (PHP.getMessages as any).mockResolvedValue({ conversations: [mockConv('c1', 3), mockConv('c2', 2)] });
    await messagesStore.loadConversations();

    let count = 0;
    const unsub = unreadCount.subscribe((val: number) => { count = val; });
    expect(count).toBe(5);
    unsub();
  });

  it('updates when conversation unread changes', async () => {
    messagesStore.reset();
    (PHP.getMessages as any).mockResolvedValue({
      conversations: [{
        id: 'c1',
        name: 'Conv',
        lastMessage: null,
        unreadCount: 1,
        updatedAt: new Date().toISOString(),
      }],
    });
    await messagesStore.loadConversations();

    let count = 0;
    const unsub = unreadCount.subscribe((val: number) => { count = val; });
    expect(count).toBe(1);

    // Mark conversation as read; markRead updates conversation unreadCount to 0
    (PHP.markMessageRead as any).mockResolvedValue(undefined);
    await messagesStore.markRead('c1');

    expect(count).toBe(0);
    unsub();
  });

  describe('derived stores', () => {
    it('conversations should reflect store.conversations', async () => {
      messagesStore.reset();
      const convs = [{ id: 'c1', name: 'General', unreadCount: 0, updatedAt: new Date().toISOString() }];
      (PHP.getMessages as any).mockResolvedValue({ conversations: convs });
      await messagesStore.loadConversations();

      let result: any[] = [];
      const unsub = conversations.subscribe((val) => { result = val; });
      expect(result).toEqual(convs);
      unsub();
    });

    it('selectedConversation should reflect activeConversationId', async () => {
      messagesStore.reset();
      const convs = [{ id: 'c1', name: 'General', unreadCount: 0, updatedAt: new Date().toISOString() }];
      (PHP.getMessages as any).mockResolvedValue({ conversations: convs });
      await messagesStore.loadConversations();
      messagesStore.setActiveConversation('c1');

      let result: any = null;
      const unsub = selectedConversation.subscribe((val) => { result = val; });
      expect(result).toEqual(convs[0]);
      unsub();
    });

    it('messages derived should reflect state.messages for conversation', async () => {
      messagesStore.reset();
      const msgs = [{ id: 'm1', conversationId: 'c1', content: 'Hello' }];
      (PHP.getConversationMessages as any).mockResolvedValue({ messages: msgs });
      await messagesStore.loadMessages('c1');

      let result: any = {};
      const unsub = messages.subscribe((val) => { result = val; });
      expect(result.c1).toEqual(msgs);
      unsub();
    });

    it('activeConversation derived should return current conversation messages', async () => {
      messagesStore.reset();
      const msgs = [{ id: 'm1', conversationId: 'c1', content: 'Hi' }];
      (PHP.getConversationMessages as any).mockResolvedValue({ messages: msgs });
      const convs = [{ id: 'c1', name: 'Conv', unreadCount: 0, updatedAt: new Date().toISOString() }];
      (PHP.getMessages as any).mockResolvedValue({ conversations: convs });
      await messagesStore.loadConversations();
      await messagesStore.loadMessages('c1');
      messagesStore.setActiveConversation('c1');

      let result: any[] = [];
      const unsub = activeConversation.subscribe((val) => { result = val; });
      expect(result).toEqual(msgs);
      unsub();
    });
  });
});

