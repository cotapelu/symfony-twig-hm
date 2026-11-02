import { describe, it, expect, vi, beforeEach } from 'vitest';
import { directoryStore, directoryEntries, directoryLoading } from './directoryStore';
import { DirectoryService } from '../services/directoryService';
import type { DirectoryEntry } from '../contracts/directoryTypes';

function getCurrentState() {
  let current: any;
  const unsub = directoryStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockEntry = (overrides: Partial<DirectoryEntry> = {}): DirectoryEntry => ({
  id: `dir-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Business Name',
  address: '123 Main St',
  category: 'food',
  phone: '123-456-7890',
  website: 'https://example.com',
  ...overrides
});

describe('DirectoryStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    directoryStore.reset();
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.entries).toEqual([]);
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
  });

  it('derived stores', () => {
    let entries: DirectoryEntry[] = [], loading = false;
    const unsubE = directoryEntries.subscribe(s => { entries = s; });
    const unsubL = directoryLoading.subscribe(s => { loading = s; });
    expect(entries).toEqual([]);
    expect(loading).toBe(false);
    unsubE(); unsubL();
  });

  describe('loadEntries', () => {
    it('loads entries', async () => {
      const entries = [mockEntry(), mockEntry()];
      vi.spyOn(DirectoryService.prototype, 'getEntries').mockResolvedValue(entries);
      await directoryStore.loadEntries();
      const state = getCurrentState();
      expect(state.entries).toEqual(entries);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(DirectoryService.prototype, 'getEntries').mockRejectedValue(new Error('Network error'));
      await directoryStore.loadEntries();
      const state = getCurrentState();
      expect(state.error).toBe('Network error');
      expect(state.entries).toEqual([]);
      expect(state.loading).toBe(false);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(DirectoryService.prototype, 'getEntries').mockRejectedValue(new Error('error'));
      await directoryStore.loadEntries();
      let state = getCurrentState();
      expect(state.error).toBe('error');
      directoryStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(DirectoryService.prototype, 'getEntries').mockResolvedValue([mockEntry()]);
      await directoryStore.loadEntries();
      let state = getCurrentState();
      expect(state.entries.length).toBeGreaterThan(0);
      directoryStore.reset();
      state = getCurrentState();
      expect(state.entries).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
