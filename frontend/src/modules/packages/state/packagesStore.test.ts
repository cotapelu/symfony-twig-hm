import { describe, it, expect, vi, beforeEach } from 'vitest';
import { packagesStore, packages, packagesLoading } from './packagesStore';
import { PackagesService } from '../services/packagesService';
import type { Package } from '../contracts/packagesTypes';

function getCurrentState() {
  let current: any;
  const unsub = packagesStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockPackage = (overrides: Partial<Package> = {}): Package => ({
  id: `pkg-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Package Name',
  description: 'Description',
  price: 100,
  currency: 'USD',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

describe('PackagesStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    packagesStore.reset();
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.packages).toEqual([]);
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
  });

  it('derived stores', () => {
    let p: Package[] = [], l = false;
    const unsubP = packages.subscribe(s => { p = s; });
    const unsubL = packagesLoading.subscribe(s => { l = s; });
    expect(p).toEqual([]);
    expect(l).toBe(false);
    unsubP(); unsubL();
  });

  describe('loadPackages', () => {
    it('loads packages', async () => {
      const pkgs = [mockPackage(), mockPackage()];
      vi.spyOn(PackagesService.prototype, 'getPackages').mockResolvedValue(pkgs);
      await packagesStore.loadPackages();
      const state = getCurrentState();
      expect(state.packages).toEqual(pkgs);
      expect(state.loading).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(PackagesService.prototype, 'getPackages').mockRejectedValue(new Error('Failed to load packages'));
      await packagesStore.loadPackages();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load packages');
      expect(state.packages).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(PackagesService.prototype, 'getPackages').mockRejectedValue(new Error('err'));
      await packagesStore.loadPackages();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      packagesStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(PackagesService.prototype, 'getPackages').mockResolvedValue([mockPackage()]);
      await packagesStore.loadPackages();
      let state = getCurrentState();
      expect(state.packages.length).toBeGreaterThan(0);
      packagesStore.reset();
      state = getCurrentState();
      expect(state.packages).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
