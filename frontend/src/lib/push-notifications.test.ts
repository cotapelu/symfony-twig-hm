import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { get } from 'svelte/store';
import { PushNotificationService } from './push-notifications';

describe('PushNotificationService', () => {
  let service: PushNotificationService;
  let storage: Record<string, string>;
  let localStorageMock: any;
  let registerServiceWorkerSpy: ReturnType<typeof vi.fn>;
  let requestPermissionSpy: ReturnType<typeof vi.fn>;
  let notificationConstructorSpy: ReturnType<typeof vi.fn>;
  let vibrateSpy: ReturnType<typeof vi.fn>;
  let audioPlaySpy: ReturnType<typeof vi.fn>;

  const mockRegistration = {
    pushManager: {
      subscribe: vi.fn().mockResolvedValue({ endpoint: 'test' }),
    },
    showNotification: vi.fn().mockResolvedValue(undefined),
  };

  beforeEach(async () => {
    storage = {};
    localStorageMock = {
      getItem: vi.fn((key: string) => storage[key] ?? null),
      setItem: vi.fn((key: string, value: string) => { storage[key] = value; }),
      removeItem: vi.fn((key: string) => { delete storage[key]; }),
      clear: vi.fn(() => { Object.keys(storage).forEach(k => delete storage[k]); }),
    };
    Object.defineProperty(global, 'localStorage', { value: localStorageMock, writable: true });

    registerServiceWorkerSpy = vi.fn().mockResolvedValue(mockRegistration);
    requestPermissionSpy = vi.fn().mockResolvedValue('granted');
    notificationConstructorSpy = vi.fn();
    vibrateSpy = vi.fn();
    audioPlaySpy = vi.fn().mockResolvedValue(undefined);

    // Mock navigator.serviceWorker using defineProperty to avoid read-only issues
    Object.defineProperty(navigator, 'serviceWorker', {
      value: { register: registerServiceWorkerSpy } as any,
      writable: true,
    });

    // Mock Notification API
    const NotificationMock = class Notification {
      constructor(public title: string, public options?: any) {
        notificationConstructorSpy(title, options);
      }
      static permission = 'granted';
      static requestPermission = requestPermissionSpy;
    };
    Object.defineProperty(window, 'Notification', {
      value: NotificationMock,
      writable: true,
    });

    // Mock navigator.vibrate
    Object.defineProperty(navigator, 'vibrate', {
      value: vibrateSpy,
      writable: true,
    });

    // Mock Audio
    (global as any).Audio = vi.fn().mockImplementation(() => ({
      play: audioPlaySpy,
    }));

    // Create new service instance
    service = new PushNotificationService();
    // Flush microtasks to let any async init complete
    await Promise.resolve();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('initialization', () => {


    it('loads persisted notifications and preferences from storage', async () => {
      const notifs = [{ id: '1', title: 'Test', body: 'Body', timestamp: Date.now(), read: false }];
      const prefs = { enabled: false, sound: true };
      storage['push_notifications'] = JSON.stringify(notifs);
      storage['notification_prefs'] = JSON.stringify(prefs);

      // Create new instance
      const newService = new PushNotificationService();
      await Promise.resolve();

      const storedNotifs = get(newService['notifications']);
      const storedPrefs = get(newService['preferences']);

      expect(storedNotifs).toEqual(notifs);
      expect(storedPrefs).toEqual(prefs);
      newService['dispose']?.();
    });
  });

  describe('sendNotification', () => {
    it('does not send when preferences.enabled is false', () => {
      service['preferences'].set({ enabled: false, sound: true, vibration: true, urgent: true, news: true, applications: true, complaints: true, emergency: true });
      service.sendNotification({ title: 'Test', body: 'Body' });
      const notifs = get(service['notifications']);
      expect(notifs).toHaveLength(0);
      expect(notificationConstructorSpy).not.toHaveBeenCalled();
    });

    it('adds notification to store and persists', () => {
      service.sendNotification({ title: 'Hello', body: 'World' });
      const notifs = get(service['notifications']);
      expect(notifs).toHaveLength(1);
      expect(notifs[0].title).toBe('Hello');
      expect(notifs[0].read).toBe(false);
      expect(localStorageMock.setItem).toHaveBeenCalledWith('push_notifications', expect.any(String));
    });

    it('shows browser Notification if permission granted', () => {
      (window.Notification as any).permission = 'granted';
      service.sendNotification({ title: 'Title', body: 'Body' });
      expect(notificationConstructorSpy).toHaveBeenCalledWith('Title', {
        body: 'Body',
        icon: expect.any(String), // default or undefined
        badge: expect.any(String),
        tag: undefined,
        data: undefined,
        actions: undefined,
      });
    });

    it('plays sound and vibrates when preferences allow', () => {
      (window.Notification as any).permission = 'granted';
      service['preferences'].set({ enabled: true, sound: true, vibration: true, urgent: true, news: true, applications: true, complaints: true, emergency: true });
      service.sendNotification({ title: 'Test', body: 'Body' });
      expect(audioPlaySpy).toHaveBeenCalled();
      expect(vibrateSpy).toHaveBeenCalledWith([200, 100, 200]);
    });

    it('does not show notification if permission denied', () => {
      (window.Notification as any).permission = 'denied';
      service.sendNotification({ title: 'Test', body: 'Body' });
      // Notifications still stored, but no browser notification
      const notifs = get(service['notifications']);
      expect(notifs).toHaveLength(1);
      expect(notificationConstructorSpy).not.toHaveBeenCalled();
    });
  });

  describe('markAsRead', () => {
    it('marks notification as read', () => {
      service.sendNotification({ title: 'T', body: 'B' });
      const notifs = get(service['notifications']);
      const id = notifs[0].id;
      service.markAsRead(id);
      const updated = get(service['notifications']);
      expect(updated[0].read).toBe(true);
    });
  });

  describe('deleteNotification', () => {
    it('removes notification by id', () => {
      service.sendNotification({ title: 'T', body: 'B' });
      const notifs = get(service['notifications']);
      const id = notifs[0].id;
      service.deleteNotification(id);
      expect(get(service['notifications'])).toHaveLength(0);
    });
  });

  describe('clearAll', () => {
    it('clears all notifications', () => {
      service.sendNotification({ title: 'T1', body: 'B1' });
      service.sendNotification({ title: 'T2', body: 'B2' });
      expect(get(service['notifications'])).toHaveLength(2);
      service.clearAll();
      expect(get(service['notifications'])).toHaveLength(0);
    });
  });

  describe('updatePreferences', () => {
    it('updates provided preferences', () => {
      service.updatePreferences({ sound: false });
      const prefs = get(service['preferences']);
      expect(prefs.sound).toBe(false);
      // other defaults remain
      expect(prefs.enabled).toBe(true);
    });
  });

  describe('getUnreadCount', () => {
    it('counts unread notifications', () => {
      service.sendNotification({ title: 'T1', body: 'B1' });
      service.sendNotification({ title: 'T2', body: 'B2' });
      expect(service.getUnreadCount()).toBe(2);
      const notifs = get(service['notifications']);
      service.markAsRead(notifs[0].id);
      expect(service.getUnreadCount()).toBe(1);
    });
  });
});
