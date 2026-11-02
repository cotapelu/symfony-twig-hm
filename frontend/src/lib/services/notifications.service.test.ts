import { describe, it, expect, vi, beforeEach } from 'vitest';
import { notificationsService } from './notifications';
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

describe('NotificationsService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getNotifications', () => {
    it('fetches notifications list', async () => {
      const notifications = [{ id: 'n1', type: 'info', title: 'Hi', message: 'Hello', read: false, createdAt: '2024-01-01' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ notifications }) });

      const result = await notificationsService.getNotifications();

      expect(getApiUrl).toHaveBeenCalledWith('notifications', '/api/notifications');
      expect(result).toEqual(notifications);
    });

    it('returns empty array if no notifications', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await notificationsService.getNotifications();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(notificationsService.getNotifications()).rejects.toThrow('Failed to fetch notifications');
    });
  });

  describe('markAsRead', () => {
    it('marks a notification as read', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });

      await notificationsService.markAsRead('n1');

      expect(getApiUrl).toHaveBeenCalledWith('notifications', '/api/notifications/n1/read');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
    });
  });

  describe('markAllAsRead', () => {
    it('marks all notifications as read', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });

      await notificationsService.markAllAsRead();

      expect(getApiUrl).toHaveBeenCalledWith('notifications', '/api/notifications/read-all');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
    });
  });

  describe('deleteNotification', () => {
    it('deletes a notification', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });

      await notificationsService.deleteNotification('n1');

      expect(getApiUrl).toHaveBeenCalledWith('notifications', '/api/notifications/n1');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'DELETE',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
    });
  });

  describe('getUnreadCount', () => {
    it('fetches unread count', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ count: 5 }) });

      const count = await notificationsService.getUnreadCount();

      expect(getApiUrl).toHaveBeenCalledWith('notifications', '/api/notifications/unread-count');
      expect(count).toBe(5);
    });

    it('returns 0 if no count', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const count = await notificationsService.getUnreadCount();
      expect(count).toBe(0);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(notificationsService.getUnreadCount()).rejects.toThrow('Failed to get count');
    });
  });
});
