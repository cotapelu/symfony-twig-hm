import { describe, it, expect, vi, beforeEach } from 'vitest';
import { discoverStore, discoverItems, discoverLoading } from './discoverStore';
import { discoverService } from '../services/discoverService';
import type { DiscoverItem } from '../contracts/discoverTypes';

function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockItem = (overrides: Partial<DiscoverItem> = {}): DiscoverItem => ({
  id: `item-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Test Item',
  description: 'A test item',
  imageUrl: 'https://example.com/image.jpg',
  ...overrides
});

describe('DiscoverStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    discoverStore.reset();
  });

  describe('initial state', () => {
    it('has empty items and defaults', () => {
      const state = getCurrentState(discoverStore);
      expect(state.items).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived discoverItems matches state.items', () => {
      expect(getCurrentState(discoverItems)).toEqual([]);
    });

    it('derived discoverLoading matches state.loading', () => {
      expect(getCurrentState(discoverLoading)).toBe(false);
    });
  });

  describe('loadItems', () => {
    it('loads items and updates state', async () => {
      const items = [mockItem(), mockItem()];
      vi.spyOn(discoverService, 'getItems').mockResolvedValue(items);
      await discoverStore.loadItems();
      const state = getCurrentState(discoverStore);
      expect(state.items).toEqual(items);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(discoverService, 'getItems').mockReturnValue(p as any);
      const loadPromise = discoverStore.loadItems();
      await new Promise(process.nextTick);
      expect(getCurrentState(discoverLoading)).toBe(true);
      resolvePromise([mockItem()]);
      await loadPromise;
      expect(getCurrentState(discoverLoading)).toBe(false);
    });

    it('updates discoverItems derived on success', async () => {
      const items = [mockItem()];
      vi.spyOn(discoverService, 'getItems').mockResolvedValue(items);
      await discoverStore.loadItems();
      expect(getCurrentState(discoverItems)).toEqual(items);
    });

    it('handles empty response', async () => {
      vi.spyOn(discoverService, 'getItems').mockResolvedValue([]);
      await discoverStore.loadItems();
      expect(getCurrentState(discoverItems)).toEqual([]);
      expect(getCurrentState(discoverLoading)).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(discoverService, 'getItems').mockRejectedValue(new Error('Fetch failed'));
      await discoverStore.loadItems();
      const state = getCurrentState(discoverStore);
      expect(state.error).toBe('Fetch failed');
      expect(state.loading).toBe(false);
      expect(state.items).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(discoverService, 'getItems').mockRejectedValue(new Error('test'));
      try { await discoverStore.loadItems(); } catch {}
      let state = getCurrentState(discoverStore);
      expect(state.error).toBe('test');
      discoverStore.clearError();
      state = getCurrentState(discoverStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const items = [mockItem()];
      vi.spyOn(discoverService, 'getItems').mockResolvedValue(items);
      await discoverStore.loadItems();
      let state = getCurrentState(discoverStore);
      expect(state.items.length).toBeGreaterThan(0);
      discoverStore.reset();
      state = getCurrentState(discoverStore);
      expect(state.items).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
