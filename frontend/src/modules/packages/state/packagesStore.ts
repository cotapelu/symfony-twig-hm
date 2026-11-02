import { writable, derived } from 'svelte/store';
import type { PackagesState } from '../contracts/packagesTypes';
import { packagesService } from '../services/packagesService';

const initialState: PackagesState = { packages: [], loading: false, error: null };

function createPackagesStore() {
  const { subscribe, set, update } = writable<PackagesState>(initialState);
  return {
    subscribe,
    async loadPackages() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const packages = await packagesService.getPackages();
        update(s => ({ ...s, packages, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const packagesStore = createPackagesStore();
export const packages = derived(packagesStore, $s => $s.packages);
export const packagesLoading = derived(packagesStore, $s => $s.loading);
