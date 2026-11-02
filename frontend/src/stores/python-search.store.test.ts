import { describe, it, expect, vi, beforeEach } from 'vitest';
import { pythonSearchStore } from './python-all';
import { Python } from '../lib/api';

vi.mock('../lib/api', () => ({
  Python: {
    search: vi.fn(),
    getSuggestions: vi.fn(),
  }
}));

describe('PythonSearchStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    pythonSearchStore.reset();
    pythonSearchStore.subscribe(s => { state = s; });
  });

  describe('search', () => {
    it('should perform search and store results', async () => {
      const results = [{ id: 'r1' }];
      (Python.search as any).mockResolvedValue(results);

      const result = await pythonSearchStore.search('query');

      expect(result).toEqual(results);
      expect(state.searchResults).toEqual(results);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.search as any).mockRejectedValue(new Error('Search failed'));
      await expect(pythonSearchStore.search('q')).rejects.toThrow('Search failed');
      expect(state.error).toBe('Search failed');
    });
  });

  describe('getSuggestions', () => {
    it('should fetch suggestions', async () => {
      const suggestions = ['sugg1', 'sugg2'];
      (Python.getSuggestions as any).mockResolvedValue(suggestions);

      const result = await pythonSearchStore.getSuggestions('partial');

      expect(result).toEqual(suggestions);
      expect(state.suggestions).toEqual(suggestions);
    });

    it('should handle error', async () => {
      (Python.getSuggestions as any).mockRejectedValue(new Error('Suggestion failed'));
      await expect(pythonSearchStore.getSuggestions('p')).rejects.toThrow('Suggestion failed');
      expect(state.error).toBe('Suggestion failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.searchResults = [{ id: 'r' }];
      state.suggestions = ['s'];
      state.error = 'err';
      pythonSearchStore.reset();
      expect(state.searchResults).toEqual([]);
      expect(state.suggestions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Python.search as any).mockRejectedValue(new Error('test'));
      try { await pythonSearchStore.search('q'); } catch (e) {}
      expect(state.error).toBe('test');
      pythonSearchStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
