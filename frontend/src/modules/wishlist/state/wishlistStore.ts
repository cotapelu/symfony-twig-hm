import { writable, derived } from 'svelte/store';
import type { WishlistState } from '../contracts/wishlistTypes';
import { wishlistService } from '../services/wishlistService';

const initialState: WishlistState = { items: [], loading: false, error: null };

function createWishlistStore() {
  const { subscribe, set, update } = writable<WishlistState>(initialState);
  return {
    subscribe,
    async loadItems() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const items = await wishlistService.getItems();
        update(s => ({ ...s, items, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    async addItem(itemId: string, itemType: string) {
      const item = await wishlistService.addItem(itemId, itemType);
      update(s => ({ ...s, items: [...s.items, item] }));
      return item;
    },
    async removeItem(itemId: string) {
      await wishlistService.removeItem(itemId);
      update(s => ({ ...s, items: s.items.filter(i => i.itemId !== itemId) }));
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const wishlistStore = createWishlistStore();
export const wishlistItems = derived(wishlistStore, $s => $s.items);
export const wishlistLoading = derived(wishlistStore, $s => $s.loading);
