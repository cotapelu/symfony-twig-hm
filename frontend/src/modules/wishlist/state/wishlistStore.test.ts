import { describe, it, expect, vi, beforeEach } from 'vitest';
import { wishlistStore, wishlistItems, wishlistLoading } from './wishlistStore';

// Mock service
vi.mock('../services/wishlistService', () => ({
  wishlistService: {
    getItems: vi.fn(),
    addItem: vi.fn(),
    removeItem: vi.fn()
  }
}));

import { wishlistService } from '../services/wishlistService';

describe('WishlistStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    wishlistStore.reset();
    wishlistStore.subscribe((s: any) => { state = s; });
  });

  describe('initial state', () => {
    it('has empty items, not loading, no error', () => {
      expect(state.items).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived wishlistItems reflects items', () => {
      let items: any = null;
      wishlistItems.subscribe(v => items = v);
      expect(items).toEqual([]);
    });

    it('derived wishlistLoading reflects loading', () => {
      let loading = false;
      wishlistLoading.subscribe(v => loading = v);
      expect(loading).toBe(false);
    });
  });

  describe('loadItems', () => {
    const mockItems = [
      { itemId: 'i1', itemType: 'product', addedAt: new Date() },
      { itemId: 'i2', itemType: 'service', addedAt: new Date() }
    ];

    it('loads items successfully', async () => {
      (wishlistService.getItems as any).mockResolvedValue(mockItems);

      await wishlistStore.loadItems();

      expect(wishlistService.getItems).toHaveBeenCalled();
      expect(state.items).toEqual(mockItems);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      (wishlistService.getItems as any).mockReturnValue(p as any);
      const loadPromise = wishlistStore.loadItems();
      await new Promise(process.nextTick);
      expect(state.loading).toBe(true);
      resolvePromise(mockItems);
      await loadPromise;
      expect(state.loading).toBe(false);
    });

    it('updates wishlistItems derived on success', async () => {
      (wishlistService.getItems as any).mockResolvedValue(mockItems);
      await wishlistStore.loadItems();
      let items: any[] = [];
      const unsub = wishlistItems.subscribe(v => { items = v; });
      expect(items).toEqual(mockItems);
      unsub();
    });

    it('handles empty response', async () => {
      (wishlistService.getItems as any).mockResolvedValue([]);
      await wishlistStore.loadItems();
      expect(state.items).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      (wishlistService.getItems as any).mockRejectedValue(new Error('Network error'));
      await wishlistStore.loadItems();
      expect(state.loading).toBe(false);
      expect(state.error).toBe('Network error');
      expect(state.items).toEqual([]);
    });
  });

  describe('addItem', () => {
    const newItem = { itemId: 'i3', itemType: 'product', addedAt: new Date() };

    it('adds item to list', async () => {
      (wishlistService.addItem as any).mockResolvedValue(newItem);

      const result = await wishlistStore.addItem('i3', 'product');

      expect(result).toEqual(newItem);
      expect(state.items).toContainEqual(newItem);
    });

    it('propagates service error', async () => {
      (wishlistService.addItem as any).mockRejectedValue(new Error('Add failed'));

      await expect(wishlistStore.addItem('i3', 'product')).rejects.toThrow('Add failed');
      // state unchanged
      expect(state.items).toEqual([]);
    });
  });

  describe('removeItem', () => {
    const item1 = { itemId: 'i1', itemType: 'product', addedAt: new Date() };
    const item2 = { itemId: 'i2', itemType: 'service', addedAt: new Date() };

    beforeEach(() => {
      // Prepopulate store
      wishlistStore.setState?.({
        items: [item1, item2],
        loading: false,
        error: null
      });
      // Since setState not exposed, we'll use loadItems to set.
      (wishlistService.getItems as any).mockResolvedValue([item1, item2]);
      wishlistStore.loadItems(); // This will set items.
    });

    it('removes item by itemId', async () => {
      (wishlistService.removeItem as any).mockResolvedValue(undefined);

      await wishlistStore.removeItem('i1');

      expect(wishlistService.removeItem).toHaveBeenCalledWith('i1');
      expect(state.items).toHaveLength(1);
      expect(state.items[0]).toEqual(item2);
    });

    it('handles remove error', async () => {
      (wishlistService.removeItem as any).mockRejectedValue(new Error('Remove failed'));

      await expect(wishlistStore.removeItem('i1')).rejects.toThrow('Remove failed');
      // state unchanged
      expect(state.items).toHaveLength(2);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      // Trigger an error first
      (wishlistService.getItems as any).mockRejectedValue(new Error('fail'));
      await wishlistStore.loadItems();
      expect(state.error).toBe('fail');

      wishlistStore.clearError();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      // Add some items and set error
      (wishlistService.getItems as any).mockResolvedValue([{ itemId: 'i1', itemType: 'product', addedAt: new Date() }]);
      await wishlistStore.loadItems();
      expect(state.items).not.toHaveLength(0);

      wishlistStore.reset();

      expect(state.items).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
