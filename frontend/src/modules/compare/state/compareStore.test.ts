import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  compareStore,
  compareItems,
  compareHistory,
  compareLoading
} from './compareStore';
import { compareService } from '../services/compareService';
import type { CompareItem, CompareList } from '../contracts/compareTypes';

function getCurrentState() {
  let current: any;
  const unsub = compareStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date().toISOString();

const mockItem = (overrides: Partial<CompareItem> = {}): CompareItem => ({
  id: `item-${Math.random().toString(36).substr(2, 5)}`,
  type: 'product' as const,
  name: 'Widget',
  image: 'img.jpg',
  price: 9.99,
  rating: 4.5,
  features: ['Feature A', 'Feature B'],
  ...overrides
});

const mockHistory = (overrides: Partial<CompareList> = {}): CompareList => ({
  id: `list-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  items: [mockItem()],
  createdAt: now,
  ...overrides
});

describe('CompareStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    compareStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and defaults', () => {
      const state = getCurrentState();
      expect(state.currentCompare).toEqual([]);
      expect(state.history).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived compareItems and compareHistory', () => {
      let items: CompareItem[] = [], hist: CompareList[] = [];
      const unsubI = compareItems.subscribe(s => { items = s; });
      const unsubH = compareHistory.subscribe(s => { hist = s; });
      expect(items).toEqual([]);
      expect(hist).toEqual([]);
      unsubI(); unsubH();
    });

    it('derived compareLoading', () => {
      let load = false;
      const unsub = compareLoading.subscribe(s => { load = s; });
      expect(load).toBe(false);
      unsub();
    });
  });

  describe('loadHistory', () => {
    it('loads history', async () => {
      const hist = [mockHistory(), mockHistory()];
      vi.spyOn(compareService, 'getHistory').mockResolvedValue(hist);
      await compareStore.loadHistory();
      const state = getCurrentState();
      expect(state.history).toEqual(hist);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(compareService, 'getHistory').mockRejectedValue(new Error('Fail'));
      await compareStore.loadHistory();
      const state = getCurrentState();
      expect(state.error).toBe('Fail');
      expect(state.history).toEqual([]);
    });
  });

  describe('addItem', () => {
    it('adds item and sets currentCompare to returned array', async () => {
      const item = mockItem({ id: 'i1' });
      vi.spyOn(compareService, 'addItem').mockResolvedValue([item]);
      await compareStore.addItem(item);
      let state = getCurrentState();
      expect(state.currentCompare).toEqual([item]);
    });

    it('supports multiple items (service returns full list)', async () => {
      const item1 = mockItem({ id: 'i1' });
      const item2 = mockItem({ id: 'i2' });
      vi.spyOn(compareService, 'addItem').mockResolvedValue([item1, item2]);
      await compareStore.addItem(item1);
      let state = getCurrentState();
      expect(state.currentCompare).toEqual([item1, item2]);
    });

    it('handles error', async () => {
      vi.spyOn(compareService, 'addItem').mockRejectedValue(new Error('Add failed'));
      try {
        await compareStore.addItem(mockItem());
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Add failed');
      expect(state.currentCompare).toEqual([]);
    });
  });

  describe('removeItem', () => {
    it('removes item and updates currentCompare', async () => {
      const item1 = mockItem({ id: 'i1' });
      const item2 = mockItem({ id: 'i2' });
      // Set currentCompare with two items
      vi.spyOn(compareService, 'addItem').mockResolvedValue([item1, item2]);
      await compareStore.addItem(item1);
      // Mock removeItem to return array without item1
      vi.spyOn(compareService, 'removeItem').mockResolvedValue([item2]);
      await compareStore.removeItem('i1');
      let state = getCurrentState();
      expect(state.currentCompare).toEqual([item2]);
    });

    it('handles remove error', async () => {
      const item = mockItem();
      vi.spyOn(compareService, 'addItem').mockResolvedValue([item]);
      await compareStore.addItem(item);
      vi.spyOn(compareService, 'removeItem').mockRejectedValue(new Error('Remove failed'));
      try {
        await compareStore.removeItem('i1');
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Remove failed');
      // currentCompare unchanged
      expect(state.currentCompare).toEqual([item]);
    });
  });

  describe('clearCompare', () => {
    it('clears currentCompare', async () => {
      const item = mockItem();
      vi.spyOn(compareService, 'addItem').mockResolvedValue([item]);
      await compareStore.addItem(item);
      let state = getCurrentState();
      expect(state.currentCompare).toEqual([item]);
      compareStore.clearCompare();
      state = getCurrentState();
      expect(state.currentCompare).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(compareService, 'getHistory').mockRejectedValue(new Error('test'));
      try { await compareStore.loadHistory(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      compareStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const hist = [mockHistory()];
      vi.spyOn(compareService, 'getHistory').mockResolvedValue(hist);
      await compareStore.loadHistory();
      vi.spyOn(compareService, 'addItem').mockResolvedValue([mockItem()]);
      await compareStore.addItem(mockItem());
      let state = getCurrentState();
      expect(state.history.length).toBeGreaterThan(0);
      expect(state.currentCompare.length).toBeGreaterThan(0);
      compareStore.reset();
      state = getCurrentState();
      expect(state.history).toEqual([]);
      expect(state.currentCompare).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
