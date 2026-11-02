import { writable, derived } from 'svelte/store';
import type { HelpState } from '../contracts/helpTypes';
import { helpService } from '../services/helpService';

const initialState: HelpState = { articles: [], loading: false, error: null };

function createHelpStore() {
  const { subscribe, set, update } = writable<HelpState>(initialState);
  return {
    subscribe,
    async loadArticles() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const articles = await helpService.getArticles();
        update(s => ({ ...s, articles, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const helpStore = createHelpStore();
export const helpArticles = derived(helpStore, $s => $s.articles);
export const helpLoading = derived(helpStore, $s => $s.loading);
