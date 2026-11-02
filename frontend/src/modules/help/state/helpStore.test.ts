import { describe, it, expect, vi, beforeEach } from 'vitest';
import { helpStore, helpArticles, helpLoading } from './helpStore';
import { HelpService } from '../services/helpService';
import type { HelpArticle } from '../contracts/helpTypes';

function getCurrentState() {
  let current: any;
  const unsub = helpStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockArticle = (overrides: Partial<HelpArticle> = {}): HelpArticle => ({
  id: `help-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Help Title',
  content: 'Content',
  category: 'general',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

describe('HelpStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    helpStore.reset();
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.articles).toEqual([]);
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
  });

  it('derived stores', () => {
    let a: HelpArticle[] = [], l = false;
    const unsubA = helpArticles.subscribe(s => { a = s; });
    const unsubL = helpLoading.subscribe(s => { l = s; });
    expect(a).toEqual([]);
    expect(l).toBe(false);
    unsubA(); unsubL();
  });

  describe('loadArticles', () => {
    it('loads articles', async () => {
      const arts = [mockArticle(), mockArticle()];
      vi.spyOn(HelpService.prototype, 'getArticles').mockResolvedValue(arts);
      await helpStore.loadArticles();
      const state = getCurrentState();
      expect(state.articles).toEqual(arts);
      expect(state.loading).toBe(false);
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(HelpService.prototype, 'getArticles').mockReturnValue(p as any);
      const loadPromise = helpStore.loadArticles();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockArticle()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates helpArticles derived on success', async () => {
      const arts = [mockArticle()];
      vi.spyOn(HelpService.prototype, 'getArticles').mockResolvedValue(arts);
      await helpStore.loadArticles();
      let a: HelpArticle[] = [];
      const unsub = helpArticles.subscribe(s => { a = s; });
      expect(a).toEqual(arts);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(HelpService.prototype, 'getArticles').mockResolvedValue([]);
      await helpStore.loadArticles();
      expect(getCurrentState().articles).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(HelpService.prototype, 'getArticles').mockRejectedValue(new Error('Failed to load articles'));
      await helpStore.loadArticles();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load articles');
      expect(state.loading).toBe(false);
      expect(state.articles).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(HelpService.prototype, 'getArticles').mockRejectedValue(new Error('err'));
      await helpStore.loadArticles();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      helpStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(HelpService.prototype, 'getArticles').mockResolvedValue([mockArticle()]);
      await helpStore.loadArticles();
      let state = getCurrentState();
      expect(state.articles.length).toBeGreaterThan(0);
      helpStore.reset();
      state = getCurrentState();
      expect(state.articles).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
