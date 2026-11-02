import { describe, it, expect, vi, beforeEach, afterEach, afterAll } from 'vitest';
import { OfflineSyncService, SyncQueueItem, SyncStatus } from './offline-sync';
import { get } from 'svelte/store';

// Mock $app/environment to always be browser
vi.mock('$app/environment', () => ({
  browser: true
}));

// --- Global State & Mocks ---

// Save original globals
const originalFetch = global.fetch;
const originalLocalStorage = global.localStorage;
const originalNavigator = global.navigator;
const originalSetInterval = global.setInterval;
const originalClearInterval = global.clearInterval;

// LocalStorage mock
const localStorageStore: Record<string, string> = {};
const localStorageMock = {
  getItem: vi.fn((key: string) => localStorageStore[key] ?? null),
  setItem: vi.fn((key: string, value: string) => { localStorageStore[key] = value; }),
  removeItem: vi.fn((key: string) => { delete localStorageStore[key]; }),
  clear: vi.fn(() => { Object.keys(localStorageStore).forEach(k => delete localStorageStore[k]); }),
};

// Navigator mock (online status)
let mockOnline = true;
const mockNavigator = {
  get onLine() { return mockOnline; },
};

// Window event listeners tracking
const windowEventListeners = new Map<string, Set<EventHandler>>();
type EventHandler = (...args: any[]) => void;

// Spy on window methods (single spy for whole file)
const addEventListenerSpy = vi.spyOn(window, 'addEventListener').mockImplementation((event: string, handler: EventHandler) => {
  if (!windowEventListeners.has(event)) windowEventListeners.set(event, new Set());
  windowEventListeners.get(event)!.add(handler);
});
const removeEventListenerSpy = vi.spyOn(window, 'removeEventListener').mockImplementation((event: string, handler: EventHandler) => {
  windowEventListeners.get(event)?.delete(handler);
});

// Other mocks (per-test instances)
let fetchMock: ReturnType<typeof vi.fn>;
let intervalCallback: (() => void) | null = null;

// --- Helpers ---

function createService(): OfflineSyncService {
  return new OfflineSyncService();
}

function simulateOnline() {
  const handlers = windowEventListeners.get('online') || new Set();
  handlers.forEach((h) => h());
}

function simulateOffline() {
  mockOnline = false;
  const handlers = windowEventListeners.get('offline') || new Set();
  handlers.forEach((h) => h());
}

// --- Lifecycle Hooks ---

beforeEach(() => {
  vi.clearAllMocks();
  windowEventListeners.clear();
  intervalCallback = null;
  // Clear localStorage store
  Object.keys(localStorageStore).forEach(k => delete localStorageStore[k]);
  localStorageMock.clear();
  // Reset online state
  mockOnline = true;
  // Setup fetch mock (fresh per test)
  fetchMock = vi.fn();
  global.fetch = fetchMock;
  // Setup global mocks
  global.localStorage = localStorageMock;
  Object.defineProperty(global, 'navigator', { value: mockNavigator, writable: true, configurable: true });
  global.setInterval = vi.fn((cb: any, delay: number) => {
    intervalCallback = cb as () => void;
    return 1 as any;
  }) as any;
  global.clearInterval = vi.fn(vi.fn()) as any;
});

afterEach(() => {
  // Restore globals to original values
  global.fetch = originalFetch;
  global.localStorage = originalLocalStorage;
  Object.defineProperty(global, 'navigator', { value: originalNavigator, writable: true, configurable: true });
  global.setInterval = originalSetInterval;
  global.clearInterval = originalClearInterval;
  // Note: window spies remain but are cleared; they will be restored in afterAll
});

afterAll(() => {
  // Restore window method spies to avoid leaking into other test files
  addEventListenerSpy.mockRestore();
  removeEventListenerSpy.mockRestore();
});

// --- Tests ---

describe('OfflineSyncService', () => {
  describe('constructor', () => {
    it('initializes stores with default values', () => {
      const service = createService();
      const status = get(service.getStatus());
      expect(status).toEqual({
        isOnline: true,
        lastSyncTime: null,
        pendingCount: 0,
        isSyncing: false,
        error: null,
      });
      expect(get(service.getQueue())).toEqual([]);
    });

    it('loads existing queue and status from localStorage', () => {
      const storedQueue: SyncQueueItem[] = [
        {
          id: 'abc',
          type: 'create',
          entity: 'citizen',
          data: { name: 'Test' },
          timestamp: Date.now(),
          retries: 0,
        },
      ];
      const storedStatus: SyncStatus = {
        isOnline: false,
        lastSyncTime: 1234567890,
        pendingCount: 1,
        isSyncing: false,
        error: 'some error',
      };
      localStorageMock.setItem('offline_sync_queue', JSON.stringify(storedQueue));
      localStorageMock.setItem('offline_sync_status', JSON.stringify(storedStatus));

      const service = createService();

      const queue = get(service.getQueue());
      expect(queue).toHaveLength(1);
      expect(queue[0].id).toBe('abc');
      const status = get(service.getStatus());
      // isOnline is overridden by navigator.onLine during setupNetworkListeners, so it should be true
      expect(status.isOnline).toBe(true);
      expect(status.lastSyncTime).toBe(1234567890);
      expect(status.error).toBe('some error');
      expect(status.pendingCount).toBe(1);
    });

    it('catches JSON parse errors in loadFromStorage', () => {
      localStorageMock.setItem('offline_sync_queue', 'invalid json{');
      const service = createService();
      expect(get(service.getQueue())).toEqual([]);
    });

    it('starts sync interval on init', () => {
      createService();
      expect(global.setInterval).toHaveBeenCalledWith(expect.any(Function), 30000);
    });

    it('sets up network listeners', () => {
      createService();
      expect(window.addEventListener).toHaveBeenCalledWith('online', expect.any(Function));
      expect(window.addEventListener).toHaveBeenCalledWith('offline', expect.any(Function));
    });
  });

  describe('addToQueue', () => {
    it('adds a new item to the queue', () => {
      const service = createService();
      expect(get(service.getQueue())).toHaveLength(0);
      service.addToQueue('create', 'citizen', { name: 'John' });
      const queue = get(service.getQueue());
      expect(queue).toHaveLength(1);
      expect(queue[0].entity).toBe('citizen');
      expect(queue[0].type).toBe('create');
      expect(queue[0].data).toEqual({ name: 'John' });
      expect(queue[0].retries).toBe(0);
      expect(typeof queue[0].id).toBe('string');
    });

    it('triggers immediate sync when online and not syncing', async () => {
      const service = createService();
      fetchMock.mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue({}) });
      const processQueueSpy = vi.spyOn(service, 'processQueue' as any);
      await service.addToQueue('create', 'citizen', { name: 'Test' });
      expect(processQueueSpy).toHaveBeenCalled();
    });

    it('does not trigger immediate sync if offline', () => {
      // set offline before service creation so isOnline starts false
      mockOnline = false;
      const service = createService();
      const processQueueSpy = vi.spyOn(service, 'processQueue' as any);
      service.addToQueue('create', 'citizen', { name: 'Test' });
      expect(processQueueSpy).not.toHaveBeenCalled();
    });
  });

  describe('processQueue', () => {
    beforeEach(() => {
      mockOnline = true;
    });

    it('processes all items in the queue and removes successfully synced ones', async () => {
      const service = createService();
      const item1: SyncQueueItem = {
        id: '1',
        type: 'create',
        entity: 'citizen',
        data: { id: 1, name: 'A' },
        timestamp: Date.now(),
        retries: 0,
      };
      const item2: SyncQueueItem = {
        id: '2',
        type: 'update',
        entity: 'citizen',
        data: { id: 2, name: 'B' },
        timestamp: Date.now(),
        retries: 0,
      };
      service['syncQueue'].set([item1, item2]);
      fetchMock.mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue({}) });

      await service.processQueue();

      const queue = get(service.getQueue());
      expect(queue).toHaveLength(0);
      expect(fetchMock).toHaveBeenCalledTimes(2);
    });

    it('retries failed items', async () => {
      const service = createService();
      const item: SyncQueueItem = {
        id: '1',
        type: 'create',
        entity: 'citizen',
        data: { id: 1 },
        timestamp: Date.now(),
        retries: 0,
      };
      service['syncQueue'].set([item]);

      // First call fails, second succeeds
      fetchMock
        .mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValue({ ok: true });

      // First attempt
      await service.processQueue();
      let queue = get(service.getQueue());
      expect(queue).toHaveLength(1);
      expect(queue[0].retries).toBe(1);

      // Second attempt should succeed and clear queue
      await service.processQueue();
      queue = get(service.getQueue());
      expect(queue).toHaveLength(0);
    });

    it('removes items after 3 failed attempts', async () => {
      const service = createService();
      const item: SyncQueueItem = {
        id: '1',
        type: 'create',
        entity: 'citizen',
        data: { id: 1 },
        timestamp: Date.now(),
        retries: 0,
      };
      service['syncQueue'].set([item]);
      fetchMock.mockRejectedValue(new Error('Network error'));

      for (let i = 1; i <= 3; i++) {
        await service.processQueue();
        const queue = get(service.getQueue());
        if (i < 3) {
          expect(queue).toHaveLength(1);
          expect(queue[0].retries).toBe(i);
        } else {
          expect(queue).toHaveLength(0);
        }
      }
    });

    it('sets isSyncing status appropriately during sync', async () => {
      const service = createService();
      const item: SyncQueueItem = {
        id: '1',
        type: 'create',
        entity: 'citizen',
        data: { id: 1 },
        timestamp: Date.now(),
        retries: 0,
      };
      service['syncQueue'].set([item]);
      fetchMock.mockResolvedValue({ ok: true });

      const statusBefore = get(service.getStatus());
      expect(statusBefore.isSyncing).toBe(false);

      await service.processQueue();

      const statusAfter = get(service.getStatus());
      expect(statusAfter.isSyncing).toBe(false);
      expect(statusAfter.lastSyncTime).toBeGreaterThan(0);
    });

    it('does nothing if already syncing', async () => {
      const service = createService();
      const item: SyncQueueItem = {
        id: '1',
        type: 'create',
        entity: 'citizen',
        data: { id: 1 },
        timestamp: Date.now(),
        retries: 0,
      };
      service['syncQueue'].set([item]);
      fetchMock.mockResolvedValue({ ok: true });

      // Set isSyncing to true manually
      service['syncStatus'].set({ isOnline: true, lastSyncTime: null, pendingCount: 1, isSyncing: true, error: null });

      await service.processQueue();

      expect(fetchMock).not.toHaveBeenCalled();
    });

    it('does not process queue when offline', async () => {
      const service = createService();
      const item: SyncQueueItem = {
        id: '1',
        type: 'create',
        entity: 'citizen',
        data: { id: 1 },
        timestamp: Date.now(),
        retries: 0,
      };
      service['syncQueue'].set([item]);
      fetchMock.mockResolvedValue({ ok: true });

      // Set offline
      service['syncStatus'].update(s => ({ ...s, isOnline: false }));

      await service.processQueue();

      expect(fetchMock).not.toHaveBeenCalled();
    });
  });

  describe('syncItem', () => {
    it('creates correct endpoint and method for create', async () => {
      const service = createService();
      fetchMock.mockResolvedValue({ ok: true });

      const item: SyncQueueItem = {
        id: '123',
        type: 'create',
        entity: 'citizen',
        data: { name: 'Test' },
        timestamp: Date.now(),
        retries: 0,
      };

      await service['syncItem'](item);

      expect(fetchMock).toHaveBeenCalledWith('/api/government/citizens', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: 'Test' }),
      });
    });

    it('uses PUT for update with id in URL', async () => {
      const service = createService();
      fetchMock.mockResolvedValue({ ok: true });

      const item: SyncQueueItem = {
        id: '123',
        type: 'update',
        entity: 'citizen',
        data: { id: 42, name: 'Updated' },
        timestamp: Date.now(),
        retries: 0,
      };

      await service['syncItem'](item);

      expect(fetchMock).toHaveBeenCalledWith('/api/government/citizens/42', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: 42, name: 'Updated' }),
      });
    });

    it('uses DELETE for delete', async () => {
      const service = createService();
      fetchMock.mockResolvedValue({ ok: true });

      const item: SyncQueueItem = {
        id: '123',
        type: 'delete',
        entity: 'citizen',
        data: { id: 42 },
        timestamp: Date.now(),
        retries: 0,
      };

      await service['syncItem'](item);

      expect(fetchMock).toHaveBeenCalledWith('/api/government/citizens/42', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: undefined,
      });
    });

    it('throws on non-ok response', async () => {
      const service = createService();
      fetchMock.mockResolvedValue({ ok: false, status: 500 });

      const item: SyncQueueItem = {
        id: '123',
        type: 'create',
        entity: 'citizen',
        data: { name: 'Test' },
        timestamp: Date.now(),
        retries: 0,
      };

      await expect(service['syncItem'](item)).rejects.toThrow('Sync failed with status: 500');
    });

    it('throws for unknown entity', async () => {
      const service = createService();
      const item: SyncQueueItem = {
        id: '123',
        type: 'create',
        entity: 'unknown',
        data: { name: 'Test' },
        timestamp: Date.now(),
        retries: 0,
      };

      await expect(service['syncItem'](item)).rejects.toThrow('Unknown entity: unknown');
    });

    it('maps each entity to correct endpoint', () => {
      const service = createService();
      const entities = [
        ['citizen', '/api/government/citizens'],
        ['officer', '/api/government/officers'],
        ['document', '/api/government/documents'],
        ['tthc', '/api/government/procedures'],
        ['iot', '/api/iot/devices'],
        ['wallet', '/api/wallet/transactions'],
      ];
      fetchMock.mockResolvedValue({ ok: true });

      entities.forEach(([entity, endpoint]) => {
        const item: SyncQueueItem = {
          id: '123',
          type: 'create',
          entity,
          data: { any: 'data' },
          timestamp: Date.now(),
          retries: 0,
        };
        fetchMock.mockClear();
        service['syncItem'](item);
        expect(fetchMock).toHaveBeenCalledWith(endpoint, expect.any(Object));
      });
    });
  });

  describe('forceSyncNow', () => {
    it('calls processQueue when online', async () => {
      const service = createService();
      const item: SyncQueueItem = {
        id: '1',
        type: 'create',
        entity: 'citizen',
        data: { id: 1 },
        timestamp: Date.now(),
        retries: 0,
      };
      service['syncQueue'].set([item]);
      fetchMock.mockResolvedValue({ ok: true });

      await service.forceSyncNow();

      expect(fetchMock).toHaveBeenCalled();
    });

    it('throws when offline', async () => {
      const service = createService();
      service['syncStatus'].update(s => ({ ...s, isOnline: false }));
      await expect(service.forceSyncNow()).rejects.toThrow('Cannot sync while offline');
    });
  });

  describe('clearQueue', () => {
    it('empties the sync queue and updates storage', () => {
      const service = createService();
      // Avoid auto-sync by marking syncing
      service['syncStatus'].set(s => ({ ...s, isSyncing: true }));
      service.addToQueue('create', 'citizen', { name: 'Test' });
      expect(get(service.getQueue())).toHaveLength(1);
      service.clearQueue();
      expect(get(service.getQueue())).toHaveLength(0);
      expect(localStorageMock.setItem).toHaveBeenCalledWith('offline_sync_queue', '[]');
    });
  });

  describe('event system', () => {
    it('allows subscribing to events and receiving callbacks', async () => {
      const service = createService();
      const callback = vi.fn();
      const unsubscribe = service.on('citizen:synced', callback);

      // Simulate event emit
      service['emit']('citizen:synced', { id: '123' });

      expect(callback).toHaveBeenCalledWith({ id: '123' });

      // Unsubscribe and emit again
      unsubscribe();
      service['emit']('citizen:synced', { id: '456' });
      expect(callback).toHaveBeenCalledTimes(1);
    });

    it('supports multiple listeners for same event', () => {
      const service = createService();
      const cb1 = vi.fn();
      const cb2 = vi.fn();
      service.on('test', cb1);
      service.on('test', cb2);
      service['emit']('test', {});
      expect(cb1).toHaveBeenCalled();
      expect(cb2).toHaveBeenCalled();
    });
  });

  describe('network handlers', () => {
    it('updates isOnline status on online event', () => {
      const service = createService();
      expect(get(service.getStatus()).isOnline).toBe(true);
      simulateOnline();
      expect(get(service.getStatus()).isOnline).toBe(true);
    });

    it('updates isOnline status on offline event', () => {
      const service = createService();
      expect(get(service.getStatus()).isOnline).toBe(true);
      simulateOffline();
      expect(get(service.getStatus()).isOnline).toBe(false);
    });

    it('processQueue triggered on online event', async () => {
      const service = createService();
      const item: SyncQueueItem = {
        id: '1',
        type: 'create',
        entity: 'citizen',
        data: { name: 'Test' },
        timestamp: Date.now(),
        retries: 0,
      };
      service['syncQueue'].set([item]);
      fetchMock.mockResolvedValue({ ok: true });

      simulateOnline();

      expect(fetchMock).toHaveBeenCalled();
    });
  });

  describe('persistence', () => {
    it('saves queue and status to localStorage on changes', () => {
      const service = createService();
      // Avoid auto-sync to keep item in queue
      service['syncStatus'].set(s => ({ ...s, isSyncing: true }));
      service.addToQueue('create', 'citizen', { name: 'Test' });
      expect(localStorageMock.setItem).toHaveBeenCalledWith('offline_sync_queue', expect.any(String));
      expect(localStorageMock.setItem).toHaveBeenCalledWith('offline_sync_status', expect.any(String));
    });

    it('loads from localStorage on init', () => {
      const storedQueue: SyncQueueItem[] = [{
        id: 'xyz',
        type: 'delete',
        entity: 'document',
        data: { id: 99 },
        timestamp: Date.now(),
        retries: 0,
      }];
      localStorageMock.setItem('offline_sync_queue', JSON.stringify(storedQueue));
      const service = createService();
      expect(get(service.getQueue())).toHaveLength(1);
    });
  });

  describe('destroy', () => {
    it('clears the sync interval', () => {
      const service = createService();
      service.destroy();
      expect(global.clearInterval).toHaveBeenCalled();
    });
  });
});
