import { describe, it, expect, vi, beforeEach } from 'vitest';
import { FeatureFlagClient } from './feature-flags';

describe('FeatureFlagClient', () => {
  let client: FeatureFlagClient;
  const originalFetch = global.fetch;

  beforeEach(() => {
    client = new FeatureFlagClient('/api/feature-flags');
    vi.spyOn(global, 'fetch').mockImplementation(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ enabled: true }),
      } as Response)
    );
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('isEnabled', () => {
    it('returns cached value when available', async () => {
      // Prime cache
      await client.isEnabled('flag1');
      // Reset fetch spy to ensure cache hit does not call fetch
      (global.fetch as any).mockClear();
      const result = await client.isEnabled('flag1');
      expect(result).toBe(true);
      expect(global.fetch).not.toHaveBeenCalled();
    });

    it('fetches flag when not cached and returns enabled', async () => {
      const result = await client.isEnabled('new-flag');
      expect(result).toBe(true);
      expect(global.fetch).toHaveBeenCalledWith('/api/feature-flags/new-flag');
    });

    it('returns false when response not ok', async () => {
      (global.fetch as any).mockImplementation(() =>
        Promise.resolve({
          ok: false,
          json: () => Promise.resolve({}),
        } as Response)
      );
      const result = await client.isEnabled('missing-flag');
      expect(result).toBe(false);
      expect(console.warn).toHaveBeenCalledWith('Feature flag missing-flag not found');
    });

    it('returns false on network error', async () => {
      (global.fetch as any).mockImplementation(() => Promise.reject(new Error('Network error')));
      const result = await client.isEnabled('error-flag');
      expect(result).toBe(false);
      expect(console.error).toHaveBeenCalled();
    });

    it('caches the result after successful fetch', async () => {
      await client.isEnabled('cached-flag');
      // Clear fetch calls to verify cache on next call
      (global.fetch as any).mockClear();
      const result = await client.isEnabled('cached-flag');
      expect(result).toBe(true);
      expect(global.fetch).not.toHaveBeenCalled();
    });
  });

  describe('preload', () => {
    it('preloads multiple uncached flags in parallel', async () => {
      // Set up: cache none initially
      await client.preload(['flagA', 'flagB']);
      expect(global.fetch).toHaveBeenCalledTimes(2);
      expect(global.fetch).toHaveBeenNthCalledWith(1, '/api/feature-flags/flagA');
      expect(global.fetch).toHaveBeenNthCalledWith(2, '/api/feature-flags/flagB');
    });

    it('does not fetch already cached flags', async () => {
      // Cache one flag
      await client.isEnabled('cached-flag');
      (global.fetch as any).mockClear();
      await client.preload(['cached-flag', 'new-flag']);
      expect(global.fetch).toHaveBeenCalledOnce();
      expect(global.fetch).toHaveBeenCalledWith('/api/feature-flags/new-flag');
    });

    it('handles fetch errors gracefully for some flags', async () => {
      // Simulate one flag succeeding, one failing
      (global.fetch as any).mockImplementation((url: string) => {
        if (url.includes('fail-flag')) {
          return Promise.reject(new Error('fail'));
        }
        return Promise.resolve({
          ok: true,
          json: () => Promise.resolve({ enabled: false }),
        } as Response);
      });

      await client.preload(['good-flag', 'fail-flag']);
      // Both attempted, errors caught
      expect(global.fetch).toHaveBeenCalledTimes(2);
      expect(console.warn).toHaveBeenCalled(); // warning for fail-flag
    });

    it('returns immediately if all flags are cached', async () => {
      await client.isEnabled('flag1');
      await client.isEnabled('flag2');
      (global.fetch as any).mockClear();
      await client.preload(['flag1', 'flag2']);
      expect(global.fetch).not.toHaveBeenCalled();
    });
  });

  describe('clearCache', () => {
    it('empties the cache', async () => {
      await client.isEnabled('flagX');
      client.clearCache();
      const cacheSize = (client as any).cache.size;
      expect(cacheSize).toBe(0);
    });
  });
});
