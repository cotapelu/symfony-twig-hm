import { describe, it, expect, vi, beforeEach } from 'vitest';
import { notificationsStore } from './notificationsStore';
import { NotificationsService } from '../services/notificationsService';
import type { NotificationItem, NotificationPreferences } from '../contracts/notificationsTypes';

function getCurrentState() {
  let current: any;
  const unsub = notificationsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date().toISOString();

const mockNotification = (overrides: Partial<NotificationItem> = {}): NotificationItem => ({
  id: `ntf-${Math.random().toString(36).substr(2, 5)}`,
  type: 'system' as const,
  title: 'System update',
  message: 'Scheduled maintenance',
  read: false,
  createdAt: now,
  ...overrides
});

const mockPreferences = (overrides: Partial<NotificationPreferences> = {}): NotificationPreferences => ({
  email: true,
  push: true,
  sms: false,
  types: {
    message: true,
    call: false,
    payment: true,
    system: true,
    social: false,
    security: true
  },
  ...overrides
});

describe('NotificationsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    notificationsStore.reset();
  });

  describe('initial state', () => {
    it('has empty notifications, zero unreadCount, null preferences', () => {
      const state = getCurrentState();
      expect(state.notifications).toEqual([]);
      expect(state.unreadCount).toBe(0);
      expect(state.preferences).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('loadNotifications', () => {
    it('loads notifications and calculates unreadCount', async () => {
      const notifications = [mockNotification({ read: false }), mockNotification({ read: true })];
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockResolvedValue(notifications);
      await notificationsStore.loadNotifications();
      const state = getCurrentState();
      expect(state.notifications).toEqual(notifications);
      expect(state.unreadCount).toBe(1); // one unread
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockReturnValue(p as any);
      const loadPromise = notificationsStore.loadNotifications();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockNotification()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles empty response', async () => {
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockResolvedValue([]);
      await notificationsStore.loadNotifications();
      expect(getCurrentState().notifications).toEqual([]);
      expect(getCurrentState().unreadCount).toBe(0);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockRejectedValue(new Error('Fetch failed'));
      await notificationsStore.loadNotifications();
      const state = getCurrentState();
      expect(state.error).toBe('Fetch failed');
      expect(state.loading).toBe(false);
      expect(state.notifications).toEqual([]);
      expect(state.unreadCount).toBe(0);
    });
  });

  describe('markAsRead', () => {
    it('marks notification as read and updates unreadCount', async () => {
      const notifications = [mockNotification({ id: 'n1', read: false }), mockNotification({ id: 'n2', read: false })];
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockResolvedValue(notifications);
      await notificationsStore.loadNotifications();
      vi.spyOn(NotificationsService.prototype, 'markAsRead').mockResolvedValue(undefined);
      await notificationsStore.markAsRead('n1');
      let state = getCurrentState();
      expect(state.notifications.find(n => n.id === 'n1')?.read).toBe(true);
      expect(state.unreadCount).toBe(1); // one remains unread
    });

    it('handles error', async () => {
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockResolvedValue([mockNotification()]);
      await notificationsStore.loadNotifications();
      vi.spyOn(NotificationsService.prototype, 'markAsRead').mockRejectedValue(new Error('Mark failed'));
      try {
        await notificationsStore.markAsRead('n1');
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Mark failed');
    });
  });

  describe('markAllAsRead', () => {
    it('marks all as read and resets unreadCount', async () => {
      const notifications = [mockNotification({ read: false }), mockNotification({ read: false })];
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockResolvedValue(notifications);
      await notificationsStore.loadNotifications();
      let state = getCurrentState();
      expect(state.unreadCount).toBe(2);
      vi.spyOn(NotificationsService.prototype, 'markAllAsRead').mockResolvedValue(undefined);
      await notificationsStore.markAllAsRead();
      state = getCurrentState();
      expect(state.notifications.every(n => n.read)).toBe(true);
      expect(state.unreadCount).toBe(0);
    });

    it('handles error', async () => {
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockResolvedValue([mockNotification()]);
      await notificationsStore.loadNotifications();
      vi.spyOn(NotificationsService.prototype, 'markAllAsRead').mockRejectedValue(new Error('Bulk fail'));
      try {
        await notificationsStore.markAllAsRead();
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Bulk fail');
    });
  });

  describe('deleteNotification', () => {
    it('removes notification and updates unreadCount', async () => {
      const notifications = [mockNotification({ id: 'n1', read: false }), mockNotification({ id: 'n2', read: true })];
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockResolvedValue(notifications);
      await notificationsStore.loadNotifications();
      let state = getCurrentState();
      expect(state.unreadCount).toBe(1);
      vi.spyOn(NotificationsService.prototype, 'deleteNotification').mockResolvedValue(undefined);
      await notificationsStore.deleteNotification('n1');
      state = getCurrentState();
      expect(state.notifications.find(n => n.id === 'n1')).toBeUndefined();
      expect(state.unreadCount).toBe(0); // only n2 was read, now none unread
    });

    it('handles error', async () => {
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockResolvedValue([mockNotification()]);
      await notificationsStore.loadNotifications();
      vi.spyOn(NotificationsService.prototype, 'deleteNotification').mockRejectedValue(new Error('Delete fail'));
      try {
        await notificationsStore.deleteNotification('n1');
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Delete fail');
      expect(state.notifications.length).toBe(1);
    });
  });

  describe('loadPreferences', () => {
    it('loads preferences', async () => {
      const prefs = mockPreferences();
      vi.spyOn(NotificationsService.prototype, 'getPreferences').mockResolvedValue(prefs);
      await notificationsStore.loadPreferences();
      let state = getCurrentState();
      expect(state.preferences).toEqual(prefs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(NotificationsService.prototype, 'getPreferences').mockRejectedValue(new Error('Prefs fail'));
      await notificationsStore.loadPreferences();
      let state = getCurrentState();
      expect(state.error).toBe('Prefs fail');
      expect(state.preferences).toBeNull();
    });
  });

  describe('updatePreferences', () => {
    it('updates preferences', async () => {
      const initial = mockPreferences();
      vi.spyOn(NotificationsService.prototype, 'getPreferences').mockResolvedValue(initial);
      await notificationsStore.loadPreferences();
      const updated = mockPreferences({ email: false });
      vi.spyOn(NotificationsService.prototype, 'updatePreferences').mockResolvedValue(undefined);
      await notificationsStore.updatePreferences({ email: false });
      let state = getCurrentState();
      expect(state.preferences?.email).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(NotificationsService.prototype, 'updatePreferences').mockRejectedValue(new Error('Update fail'));
      try {
        await notificationsStore.updatePreferences({ email: false });
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Update fail');
    });
  });

  describe('clearAll', () => {
    it('clears notifications and unreadCount', async () => {
      const notifications = [mockNotification(), mockNotification()];
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockResolvedValue(notifications);
      await notificationsStore.loadNotifications();
      let state = getCurrentState();
      expect(state.notifications.length).toBe(2);
      notificationsStore.clearAll();
      state = getCurrentState();
      expect(state.notifications).toEqual([]);
      expect(state.unreadCount).toBe(0);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockRejectedValue(new Error('test'));
      try { await notificationsStore.loadNotifications(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      notificationsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('addNotification', () => {
    it('adds notification to list and increments unreadCount if unread', () => {
      const n1 = mockNotification({ id: 'n1', read: false });
      notificationsStore.addNotification(n1);
      let state = getCurrentState();
      expect(state.notifications[0]).toEqual(n1);
      expect(state.unreadCount).toBe(1);
      const n2 = mockNotification({ id: 'n2', read: true });
      notificationsStore.addNotification(n2);
      state = getCurrentState();
      expect(state.notifications.length).toBe(2);
      expect(state.unreadCount).toBe(1); // unchanged
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const notifications = [mockNotification()];
      vi.spyOn(NotificationsService.prototype, 'getNotifications').mockResolvedValue(notifications);
      await notificationsStore.loadNotifications();
      const prefs = mockPreferences();
      await notificationsStore.loadPreferences();
      let state = getCurrentState();
      expect(state.notifications.length).toBe(1);
      expect(state.preferences).toBeTruthy();
      notificationsStore.reset();
      state = getCurrentState();
      expect(state.notifications).toEqual([]);
      expect(state.unreadCount).toBe(0);
      expect(state.preferences).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
