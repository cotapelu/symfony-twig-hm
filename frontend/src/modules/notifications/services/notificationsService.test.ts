import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { NotificationsService } from './notificationsService';

// Mock fetch
global.fetch = vi.fn();

// Mock WebSocket
let wsInstance: any;
class MockWebSocket {
  onmessage: ((event: { data: string }) => void) | null = null;
  onerror: ((event: any) => void) | null = null;
  close: any;
  constructor(url: string) {
    wsInstance = this;
    this.onmessage = null;
    this.onerror = null;
    this.close = vi.fn();
  }
}
(global as any).WebSocket = MockWebSocket;

describe('NotificationsService', () => {
  let service: NotificationsService;

  beforeEach(() => {
    service = new NotificationsService();
    vi.clearAllMocks();
    wsInstance = null;
  });

  const mockNotifications = [{ id: 'n1', title: 'Test notify', message: 'Hi' }];
  const mockPreferences = { email: true, push: false };

  const okResponse = (data: any) => ({
    ok: true,
    json: vi.fn().mockResolvedValue(data)
  });

  const errorResponse = () => ({
    ok: false,
    json: vi.fn().mockResolvedValue({})
  });

  describe('getNotifications', () => {
    it('should fetch notifications successfully', async () => {
      (global.fetch as any).mockResolvedValue(okResponse(mockNotifications));

      const result = await service.getNotifications();

      expect(result).toEqual(mockNotifications);
      expect(fetch).toHaveBeenCalledWith('/api/notifications');
    });

    it('should throw on fetch failure', async () => {
      (global.fetch as any).mockResolvedValue(errorResponse());

      await expect(service.getNotifications()).rejects.toThrow('Failed to fetch notifications');
    });
  });

  describe('markAsRead', () => {
    it('should mark notification as read', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });

      await service.markAsRead('n1');

      expect(fetch).toHaveBeenCalledWith('/api/notifications/n1/read', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });

      await expect(service.markAsRead('n1')).rejects.toThrow('Failed to mark notification as read');
    });
  });

  describe('markAllAsRead', () => {
    it('should mark all as read', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });

      await service.markAllAsRead();

      expect(fetch).toHaveBeenCalledWith('/api/notifications/read-all', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });

      await expect(service.markAllAsRead()).rejects.toThrow('Failed to mark all notifications as read');
    });
  });

  describe('deleteNotification', () => {
    it('should delete notification', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });

      await service.deleteNotification('n1');

      expect(fetch).toHaveBeenCalledWith('/api/notifications/n1', { method: 'DELETE' });
    });

    it('should throw on delete failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });

      await expect(service.deleteNotification('n1')).rejects.toThrow('Failed to delete notification');
    });
  });

  describe('getPreferences', () => {
    it('should fetch preferences', async () => {
      (global.fetch as any).mockResolvedValue(okResponse(mockPreferences));

      const result = await service.getPreferences();

      expect(result).toEqual(mockPreferences);
      expect(fetch).toHaveBeenCalledWith('/api/notifications/preferences');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(errorResponse());

      await expect(service.getPreferences()).rejects.toThrow('Failed to fetch preferences');
    });
  });

  describe('updatePreferences', () => {
    it('should update preferences successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });

      const prefs = { email: false };
      await service.updatePreferences(prefs);

      expect(fetch).toHaveBeenCalledWith('/api/notifications/preferences', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(prefs)
      });
    });

    it('should throw on update failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });

      await expect(service.updatePreferences({})).rejects.toThrow('Failed to update preferences');
    });
  });

  describe('createNotification', () => {
    it('should create notification and return item', async () => {
      const created = { id: 'new', title: 'New' };
      (global.fetch as any).mockResolvedValue(okResponse(created));

      const result = await service.createNotification({ title: 'New', message: 'Hello' });

      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/notifications', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title: 'New', message: 'Hello' })
      });
    });

    it('should throw on create failure', async () => {
      (global.fetch as any).mockResolvedValue(errorResponse());

      await expect(service.createNotification({ title: 'X', message: 'Y' })).rejects.toThrow('Failed to create notification');
    });
  });

  describe('subscribeToNotifications', () => {
    it('should subscribe to WebSocket and invoke callback on message', async () => {
      // Mock WebSocket constructor to capture onmessage
      const mockWs = {
        onmessage: null as ((event: { data: string }) => void) | null,
        onerror: null as ((event: any) => void) | null,
        close: vi.fn()
      };
      (global as any).WebSocket = vi.fn().mockImplementation(() => mockWs);

      const callback = vi.fn();
      const unsubscribe = service.subscribeToNotifications(callback);

      // Simulate incoming message
      const notification = { id: 'n2', title: 'WS notify' };
      mockWs.onmessage({ data: JSON.stringify(notification) });

      expect(callback).toHaveBeenCalledWith(notification);

      // Unsubscribe
      unsubscribe();
      expect(mockWs.close).toHaveBeenCalled();
    });

    it('should handle WebSocket errors', () => {
      const spy = vi.spyOn(console, 'error').mockImplementation(() => {});
      const mockWs = {
        onmessage: null,
        onerror: (cb: any) => cb(new Error('ws error')),
        close: vi.fn()
      };
      (global as any).WebSocket = vi.fn().mockImplementation(() => mockWs);

      const callback = vi.fn();
      service.subscribeToNotifications(callback);

      // trigger onerror by calling the stored onerror
      // (In our mock, the service sets ws.onerror directly)
      // To simulate, call mockWs.onerror
      mockWs.onerror(new Error('ws error'));
      // Since service logs error, we can spy on console.error to verify it was called
      expect(spy).toHaveBeenCalled();

      spy.mockRestore();
    });
  });
});
