import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createMDMProvider } from './mdm-provider';

describe('MDMProvider', () => {
  let fetchMock: ReturnType<typeof vi.fn>;
  let localStorageMock: any;
  let storage: Record<string, string>;
  let config: any;

  function createProvider(overrides = {}): any {
    // Ensure token exists for auth
    if (!storage['iam_auth_token']) {
      storage['iam_auth_token'] = 'dummy-token';
    }
    return createMDMProvider({ ...config, ...overrides });
  }

  beforeEach(() => {
    storage = {};
    localStorageMock = {
      getItem: vi.fn((key: string) => storage[key] ?? null),
      setItem: vi.fn((key: string, value: string) => { storage[key] = value; }),
      removeItem: vi.fn((key: string) => { delete storage[key]; }),
      clear: vi.fn(() => { Object.keys(storage).forEach(k => delete storage[k]); }),
    };
    Object.defineProperty(global, 'localStorage', { value: localStorageMock, writable: true });

    fetchMock = vi.fn();
    global.fetch = fetchMock;

    config = {
      apiUrl: 'https://api.example.com',
      cacheEnabled: true,
      cacheExpiry: 60000,
      syncInterval: 300000,
      enableRealTime: false,
      retryAttempts: 3,
      retryDelay: 0,
    };

    // Default auth token
    storage['iam_auth_token'] = 'dummy-token';

    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
  });

  describe('constructor', () => {
    it('initializes without errors', () => {
      const provider = createProvider();
      expect(provider).toBeDefined();
    });
  });

  describe('getReferenceData', () => {
    it('returns cached dataset when valid and not forcing refresh', async () => {
      const datasetName = 'test';
      const dataset = { name: datasetName, version: '1', items: [] };
      const cacheEntry = { data: dataset, expiresAt: new Date(Date.now() + 60000).toISOString() };
      storage['mdm_cache'] = JSON.stringify({ [`refdata:${datasetName}`]: cacheEntry });
      const provider = createProvider();
      const result = await provider.getReferenceData(datasetName);
      expect(result.name).toBe(datasetName);
      expect(fetchMock).not.toHaveBeenCalled();
    });

    it('fetches when cache miss', async () => {
      const datasetName = 'newset';
      const now = new Date();
      const responseData = { name: datasetName, version: '1', items: [], lastSyncAt: now, expiresAt: undefined };
      fetchMock.mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValueOnce(responseData),
      } as Response);

      const provider = createProvider();
      const result = await provider.getReferenceData(datasetName);
      // The provider returns a dataset with lastSyncAt as Date and expiresAt undefined
      expect(result).toEqual(expect.objectContaining({ name: datasetName, version: '1', items: [], lastSyncAt: now, expiresAt: undefined }));
      expect(fetchMock).toHaveBeenCalledWith(
        `${config.apiUrl}/mdm/reference-data/${datasetName}`,
        expect.objectContaining({
          headers: expect.objectContaining({
            Authorization: 'Bearer dummy-token',
          }),
        })
      );
    });

    it('forceRefresh bypasses cache and fetches new data', async () => {
      const datasetName = 'cachedset';
      const oldData = { name: datasetName, version: '1', items: [] };
      const now = new Date();
      const newData = { name: datasetName, version: '2', items: [{ id: '2' }], lastSyncAt: now, expiresAt: undefined };
      const cachedEntry = {
        data: oldData,
        expiresAt: new Date(Date.now() + 60000).toISOString(),
      };
      storage['mdm_cache'] = JSON.stringify({ [`refdata:${datasetName}`]: cachedEntry });

      fetchMock.mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValueOnce(newData),
      } as Response);

      const provider = createProvider();
      const result = await provider.getReferenceData(datasetName, true);
      // The provider returns dataset with lastSyncAt as Date and expiresAt undefined
      expect(result).toEqual(expect.objectContaining({ name: datasetName, version: '2', items: [{ id: '2' }], lastSyncAt: now, expiresAt: undefined }));
      expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    it('throws when fetch fails', async () => {
      const datasetName = 'fail';
      fetchMock.mockRejectedValue(new Error('Network down'));

      const provider = createProvider();
      await expect(provider.getReferenceData(datasetName)).rejects.toThrow('Network down');
      // With retryAttempts=3, fetch called 3 times
      expect(fetchMock).toHaveBeenCalledTimes(3);
    });
  });

  describe('clearCache', () => {
    it('clears in-memory cache and localStorage', async () => {
      const datasetName = 'toclear';
      const responseData = { name: datasetName, version: '1', items: [] };
      fetchMock.mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValueOnce(responseData),
      } as Response);

      const provider = createProvider();
      // load into cache
      await provider.getReferenceData(datasetName);

      // verify cache saved to storage
      expect(localStorageMock.setItem).toHaveBeenCalledWith(expect.stringContaining('mdm_cache'), expect.any(String));

      // clear
      provider.clearCache();

      expect(localStorageMock.removeItem).toHaveBeenCalledWith('mdm_cache');

      // after clear, fetching should hit network again
      fetchMock.mockClear();
      fetchMock.mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValueOnce(responseData),
      } as Response);
      await provider.getReferenceData(datasetName);
      expect(fetchMock).toHaveBeenCalledTimes(1);
    });
  });

  describe('event handling', () => {
    it('adds and removes event listeners', () => {
      const provider = createProvider();
      const listener = vi.fn();
      provider.addEventListener('data_updated', listener);
      provider.emitEvent('data_updated', { foo: 'bar' });
      expect(listener).toHaveBeenCalledWith({ foo: 'bar' });
      provider.removeEventListener('data_updated', listener);
      provider.emitEvent('data_updated', { foo: 'bar' });
      expect(listener).toHaveBeenCalledTimes(1);
    });
  });

  describe('autoSync', () => {
    it('startAutoSync sets interval and fetches', async () => {
      const provider = createProvider({ enableRealTime: true });
      vi.useFakeTimers();

      // Preload a dataset so there is something to sync
      const datasetName = 'sync-test';
      const now = new Date().toISOString();
      fetchMock.mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValueOnce({ name: datasetName, version: '1', items: [], lastSyncAt: now }),
      } as Response);
      await provider.getReferenceData(datasetName);
      expect(fetchMock).toHaveBeenCalledTimes(1);
      fetchMock.mockClear();

      provider.startAutoSync();
      expect(provider.syncInterval).not.toBeNull();
      // Fast-forward to trigger sync
      vi.advanceTimersByTime(config.syncInterval * 1000);
      // Should have fetched reference data (at least once after interval)
      expect(fetchMock).toHaveBeenCalled();
    });

    it('stopAutoSync clears interval', () => {
      const provider = createProvider({ enableRealTime: true });
      provider.startAutoSync();
      provider.stopAutoSync();
      // syncInterval is set back to 0
      expect(provider.syncInterval).toBe(0);
    });
  });
});
