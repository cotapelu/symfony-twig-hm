import { describe, it, expect, vi, beforeEach } from 'vitest';
import { notificationsStore } from './notifications';
import { PHP } from '../lib/api';

vi.mock('../lib/api', () => ({
  PHP: {
    getNotifications: vi.fn(),
    markNotificationRead: vi.fn(),
    clearAllNotifications: vi.fn(),
  }
}));

describe('NotificationsStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    notificationsStore.reset();
    notificationsStore.subscribe(s => { state = s; });
  });

  const mockNotification = (id: string, readAt: string | null = null, title: string = 'N'): any => ({
    id,
    title,
    body: 'Body',
    readAt,
    createdAt: new Date().toISOString()
  });

  describe('loadNotifications', () => {
    it('should load notifications', async () => {
      const notifs = [mockNotification('n1', null, 'A'), mockNotification('n2', new Date().toISOString(), 'B')];
      (PHP.getNotifications as any).mockResolvedValue(notifs);

      const result = await notificationsStore.loadNotifications();

      expect(result).toEqual(notifs);
      expect(state.notifications).toEqual(notifs);
      expect(state.unreadCount).toBe(1);
      expect(state.loading).toBe(false);
    });

    it('should handle empty', async () => {
      (PHP.getNotifications as any).mockResolvedValue([]);
      await notificationsStore.loadNotifications();
      expect(state.notifications).toEqual([]);
      expect(state.unreadCount).toBe(0);
    });

    it('should set error on failure', async () => {
      (PHP.getNotifications as any).mockRejectedValue(new Error('API fail'));
      await expect(notificationsStore.loadNotifications()).rejects.toThrow('API fail');
      expect(state.loading).toBe(false);
      expect(state.error).toBe('API fail');
    });
  });

  describe('markAsRead', () => {
    it('should mark notification as read and decrement unreadCount', async () => {
      const notif = mockNotification('n1', null);
      state.notifications = [notif];
      state.unreadCount = 1;
      (PHP.markNotificationRead as any).mockResolvedValue(undefined);

      await notificationsStore.markAsRead('n1');

      expect(PHP.markNotificationRead).toHaveBeenCalledWith('n1');
      expect(state.notifications[0].readAt).not.toBeNull();
      expect(state.unreadCount).toBe(0);
    });

    it('should handle error', async () => {
      state.notifications = [mockNotification('n1', null)];
      state.unreadCount = 1;
      (PHP.markNotificationRead as any).mockRejectedValue(new Error('Mark failed'));
      await expect(notificationsStore.markAsRead('n1')).rejects.toThrow('Mark failed');
      expect(state.error).toBe('Mark failed');
    });
  });

  describe('markAllAsRead', () => {
    it('should mark all as read and reset unreadCount', async () => {
      const notifs = [mockNotification('n1', null), mockNotification('n2', null)];
      state.notifications = notifs;
      state.unreadCount = 2;
      // First getNotifications call returns unread list; second returns updated list with readAt set.
      const updatedNotifs = notifs.map(n => ({ ...n, readAt: new Date().toISOString() }));
      (PHP.getNotifications as any)
        .mockResolvedValueOnce(notifs) // first call to get unread
        .mockResolvedValueOnce(updatedNotifs); // after marking, get refreshed list
      (PHP.markNotificationRead as any).mockResolvedValue(undefined);

      await notificationsStore.markAllAsRead();

      expect(PHP.markNotificationRead).toHaveBeenCalledTimes(2);
      expect(state.notifications).toEqual(updatedNotifs);
      expect(state.unreadCount).toBe(0);
    });

    it('should handle error in marking', async () => {
      const notifs = [mockNotification('n1', null)];
      (PHP.getNotifications as any).mockResolvedValue(notifs);
      (PHP.markNotificationRead as any).mockRejectedValue(new Error('Fail'));

      await expect(notificationsStore.markAllAsRead()).rejects.toThrow('Fail');
      expect(state.error).toBe('Fail');
    });
  });

  describe('addNotification', () => {
    it('should add notification locally and increment unreadCount', () => {
      const notif = mockNotification('n1', null);
      notificationsStore.addNotification(notif);
      expect(state.notifications).toContain(notif);
      expect(state.unreadCount).toBe(1);
    });
  });

  describe('clearError', () => {
    it('should clear error', async () => {
      (PHP.getNotifications as any).mockRejectedValue(new Error('test'));
      try { await notificationsStore.loadNotifications(); } catch (e) {}
      expect(state.error).toBe('test');
      notificationsStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
