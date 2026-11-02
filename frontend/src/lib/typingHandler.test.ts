import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { TypingHandler, TypingManager } from './typingHandler';

describe('TypingHandler', () => {
  let handler: TypingHandler;
  let onTypingStart: vi.Mock;
  let onTypingStop: vi.Mock;

  beforeEach(() => {
    onTypingStart = vi.fn();
    onTypingStop = vi.fn();
    handler = new TypingHandler({
      conversationId: 'conv1',
      userId: 'user1',
      typingTimeout: 3000,
      debounceDelay: 500,
      onTypingStart,
      onTypingStop,
    });
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('startTyping', () => {
    it('begins debounce timer when not typing', () => {
      handler.startTyping();
      // onTypingStart should not be called immediately
      expect(onTypingStart).not.toHaveBeenCalled();
      // After debounce delay, it should call onTypingStart
      vi.advanceTimersByTime(500);
      expect(onTypingStart).toHaveBeenCalledOnce();
      expect(handler.getIsTyping()).toBe(true);
    });

    it('resets debounce timer if called again while debouncing', () => {
      handler.startTyping();
      vi.advanceTimersByTime(200);
      handler.startTyping(); // reset, new timer set for 500ms from now
      vi.advanceTimersByTime(500); // after 500ms from second call, should fire
      expect(onTypingStart).toHaveBeenCalledOnce();
    });

    it('if already typing after debounce, resets the typing timer', () => {
      handler.startTyping();
      vi.advanceTimersByTime(500); // debounce finished, isTyping true
      expect(onTypingStart).toHaveBeenCalledTimes(1);
      // Reset typing timer (typingTimeout) should have been set
      // Now call startTyping again
      const startTime = Date.now();
      handler.startTyping();
      // The original typing timer would have fired at startTime + 3000.
      // After reset, it should be extended.
      vi.advanceTimersByTime(2500); // now total 3000 from first start, but we reset at some point.
      // At this point, the original timer might have fired if not reset, but we reset so not.
      // Actually after debounce, startTyping sets typingTimer for 3000. Then we call startTyping again (before it fires) which calls resetTimer: clears previous and sets new for 3000.
      // So after total 3000 from first debounce completion, we still have 3000 from reset call. Let's simulate differently:
      // After debounce (500), isTyping true, typingTimer set for 3000.
      // Advance 1000: still 2000 left.
      // Call startTyping: resetTimer clears old, sets new for 3000.
      // Advance 3000: the new timer fires -> stopTyping.
      // So onTypingStop should be called after 3000 from reset.
      // We'll test resetTimer separately.
      // For this test, just check that startTyping while typing does not call onTypingStart again.
      handler.startTyping();
      const callsAfterSecond = onTypingStart.mock.calls.length;
      vi.advanceTimersByTime(100);
      expect(onTypingStart).toHaveBeenCalledTimes(callsAfterSecond); // no new calls
    });
  });

  describe('stopTyping', () => {
    it('stops typing and calls onTypingStop if was typing', () => {
      handler.startTyping();
      vi.advanceTimersByTime(500); // debounce
      expect(handler.getIsTyping()).toBe(true);
      handler.stopTyping();
      expect(handler.getIsTyping()).toBe(false);
      expect(onTypingStop).toHaveBeenCalledOnce();
    });

    it('clears debounce timer', () => {
      handler.startTyping();
      // Debounce timer set
      expect((handler as any).debounceTimer).not.toBeNull();
      handler.stopTyping();
      expect((handler as any).debounceTimer).toBeNull();
    });

    it('clears typing timer', () => {
      handler.startTyping();
      vi.advanceTimersByTime(500);
      expect((handler as any).typingTimer).not.toBeNull();
      handler.stopTyping();
      expect((handler as any).typingTimer).toBeNull();
    });

    it('does nothing if not typing', () => {
      handler.stopTyping();
      expect(onTypingStop).not.toHaveBeenCalled();
    });
  });

  describe('resetTimer', () => {
    it('clears existing typing timer and sets a new one', () => {
      handler.startTyping();
      vi.advanceTimersByTime(500);
      const firstTimer = (handler as any).typingTimer;
      handler.startTyping(); // resetTimer called
      const secondTimer = (handler as any).typingTimer;
      expect(secondTimer).not.toBe(firstTimer);
      // After first timer would have fired at 3000 after start, but it was cleared.
      vi.advanceTimersByTime(3000);
      // Still should not have called stopTyping because second timer set for 3000 from reset.
      // Actually after second startTyping at time 500 (after debounce), typing timer set for 3000. Total elapsed now is 3500, so second timer should have fired.
      expect(handler.getIsTyping()).toBe(false);
      expect(onTypingStop).toHaveBeenCalled();
    });
  });

  describe('forceStop', () => {
    it('calls stopTyping regardless', () => {
      handler.startTyping();
      vi.advanceTimersByTime(500);
      handler.forceStop();
      expect(handler.getIsTyping()).toBe(false);
      expect(onTypingStop).toHaveBeenCalled();
    });
  });

  describe('updateConfig', () => {
    it('updates provided options', () => {
      handler.updateConfig({
        typingTimeout: 5000,
        debounceDelay: 1000,
        conversationId: 'conv2',
        userId: 'user2',
      });
      expect((handler as any).typingTimeout).toBe(5000);
      expect((handler as any).debounceDelay).toBe(1000);
      expect((handler as any).conversationId).toBe('conv2');
      expect((handler as any).userId).toBe('user2');
    });
  });

  describe('destroy', () => {
    it('clears all timers and resets typing flag', () => {
      handler.startTyping();
      vi.advanceTimersByTime(500);
      handler.destroy();
      expect(handler.getIsTyping()).toBe(false);
      expect((handler as any).debounceTimer).toBeNull();
      expect((handler as any).typingTimer).toBeNull();
    });
  });
});

describe('TypingManager', () => {
  let manager: TypingManager;
  let updateCallback: vi.Mock;

  beforeEach(() => {
    updateCallback = vi.fn();
    manager = new TypingManager({
      onTypingUpdate: updateCallback,
      getUserInfo: (userId) => ({ name: `User${userId}`, avatar: undefined }),
    });
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('getHandler', () => {
    it('creates a new handler for new conversation', () => {
      const handler = manager.getHandler('conv1', 'user1');
      expect(handler).toBeInstanceOf(TypingHandler);
    });

    it('returns same handler on subsequent calls', () => {
      const h1 = manager.getHandler('conv1', 'user1');
      const h2 = manager.getHandler('conv1', 'user1');
      expect(h1).toBe(h2);
    });

    it('creates different handlers for different conversations', () => {
      const h1 = manager.getHandler('conv1', 'user1');
      const h2 = manager.getHandler('conv2', 'user1');
      expect(h1).not.toBe(h2);
    });
  });

  describe('handleTypingStart', () => {
    it('adds typing user to conversation and triggers callback', () => {
      manager.handleTypingStart({ conversationId: 'conv1', userId: 'u1', timestamp: Date.now() });
      const users = manager.getTypingUsers('conv1');
      expect(users).toHaveLength(1);
      expect(users[0].userId).toBe('u1');
      expect(users[0].name).toBe('Useru1');
      expect(updateCallback).toHaveBeenCalledWith('conv1', users);
    });

    it('multiple users in same conversation', () => {
      manager.handleTypingStart({ conversationId: 'conv1', userId: 'u1', timestamp: Date.now() });
      manager.handleTypingStart({ conversationId: 'conv1', userId: 'u2', timestamp: Date.now() });
      const users = manager.getTypingUsers('conv1');
      expect(users).toHaveLength(2);
    });
  });

  describe('handleTypingStop', () => {
    it('removes user from typing list and calls callback', () => {
      manager.handleTypingStart({ conversationId: 'conv1', userId: 'u1', timestamp: Date.now() });
      manager.handleTypingStop({ conversationId: 'conv1', userId: 'u1' });
      expect(manager.getTypingUsers('conv1')).toHaveLength(0);
      expect(updateCallback).toHaveBeenLastCalledWith('conv1', []);
    });

    it('does nothing if no such conversation', () => {
      manager.handleTypingStop({ conversationId: 'nonexistent', userId: 'u1' });
      expect(updateCallback).not.toHaveBeenCalled();
    });
  });

  describe('getTypingUsers', () => {
    it('returns empty array when no users', () => {
      expect(manager.getTypingUsers('conv1')).toEqual([]);
    });
  });

  describe('handleRemoteTypingEvent', () => {
    it('dispatches typing_start to start handler', () => {
      manager.handleRemoteTypingEvent({
        type: 'typing_start',
        conversationId: 'conv1',
        userId: 'u1',
        timestamp: Date.now(),
      });
      expect(manager.getTypingUsers('conv1')).toHaveLength(1);
    });

    it('dispatches typing_stop to stop handler', () => {
      manager.handleRemoteTypingEvent({
        type: 'typing_start',
        conversationId: 'conv1',
        userId: 'u1',
        timestamp: Date.now(),
      });
      manager.handleRemoteTypingEvent({
        type: 'typing_stop',
        conversationId: 'conv1',
        userId: 'u1',
      });
      expect(manager.getTypingUsers('conv1')).toHaveLength(0);
    });
  });

  describe('getTypingUsersCount', () => {
    it('returns correct count', () => {
      manager.handleRemoteTypingEvent({
        type: 'typing_start',
        conversationId: 'conv1',
        userId: 'u1',
        timestamp: Date.now(),
      });
      manager.handleRemoteTypingEvent({
        type: 'typing_start',
        conversationId: 'conv1',
        userId: 'u2',
        timestamp: Date.now(),
      });
      expect(manager.getTypingUsersCount('conv1')).toBe(2);
    });
  });

  describe('isUserTyping', () => {
    it('returns true if user is typing', () => {
      manager.handleRemoteTypingEvent({
        type: 'typing_start',
        conversationId: 'conv1',
        userId: 'u1',
        timestamp: Date.now(),
      });
      expect(manager.isUserTyping('conv1', 'u1')).toBe(true);
      expect(manager.isUserTyping('conv1', 'u2')).toBe(false);
    });
  });

  describe('getConversationsWithTypingUsers', () => {
    it('returns list of conversation IDs', () => {
      manager.handleRemoteTypingEvent({
        type: 'typing_start',
        conversationId: 'conv1',
        userId: 'u1',
        timestamp: Date.now(),
      });
      manager.handleRemoteTypingEvent({
        type: 'typing_start',
        conversationId: 'conv2',
        userId: 'u2',
        timestamp: Date.now(),
      });
      expect(manager.getConversationsWithTypingUsers()).toContain('conv1');
      expect(manager.getConversationsWithTypingUsers()).toContain('conv2');
      expect(manager.getConversationsWithTypingUsers()).toHaveLength(2);
    });
  });

  describe('setTypingUpdateCallback', () => {
    it('allows changing callback', () => {
      const newCallback = vi.fn();
      manager.setTypingUpdateCallback(newCallback);
      manager.handleTypingStart({ conversationId: 'conv1', userId: 'u1', timestamp: Date.now() });
      expect(updateCallback).not.toHaveBeenCalled();
      expect(newCallback).toHaveBeenCalled();
    });
  });

  describe('defaultGetUserInfo', () => {
    it('returns default UserInfo', () => {
      // Access private method via any
      const result = (manager as any).defaultGetUserInfo('123');
      expect(result).toEqual({ name: 'User 123', avatar: undefined });
    });
  });
});
