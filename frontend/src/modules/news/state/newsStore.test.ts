import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  newsStore,
  articles,
  selectedArticle,
  comments,
  newsLoading,
  newsError,
  publishedArticles
} from './newsStore';
import { NewsService } from '../services/newsService';
import type { NewsArticle, NewsComment } from '../contracts/newsTypes';

function getCurrentState() {
  let current: any;
  const unsub = newsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockArticle = (overrides: Partial<NewsArticle> = {}): NewsArticle => ({
  id: `art-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Article Title',
  content: 'Content',
  summary: 'Summary',
  category: 'general',
  tags: ['tag1'],
  status: 'published',
  authorId: 'user-1',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

const mockComment = (overrides: Partial<NewsComment> = {}): NewsComment => ({
  id: `cmt-${Math.random().toString(36).substr(2, 5)}`,
  articleId: 'art-1',
  userId: 'user-2',
  content: 'Comment',
  createdAt: now,
  ...overrides
});

describe('NewsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    newsStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.articles).toEqual([]);
      expect(state.selectedArticle).toBeNull();
      expect(state.comments).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let a: NewsArticle[] = [], sa: NewsArticle | null = [], c: NewsComment[] = [], l = false, e: string | null = null;
      const unsubA = articles.subscribe(s => { a = s; });
      const unsubSA = selectedArticle.subscribe(s => { sa = s; });
      const unsubC = comments.subscribe(s => { c = s; });
      const unsubL = newsLoading.subscribe(s => { l = s; });
      const unsubE = newsError.subscribe(s => { e = s; });
      expect(a).toEqual([]);
      expect(sa).toBeNull();
      expect(c).toEqual([]);
      expect(l).toBe(false);
      expect(e).toBeNull();
      unsubA(); unsubSA(); unsubC(); unsubL(); unsubE();
    });
  });

  describe('loadArticles', () => {
    it('loads articles', async () => {
      const arts = [mockArticle(), mockArticle()];
      vi.spyOn(NewsService.prototype, 'getArticles').mockResolvedValue(arts);
      await newsStore.loadArticles();
      const state = getCurrentState();
      expect(state.articles).toEqual(arts);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(NewsService.prototype, 'getArticles').mockReturnValue(p as any);
      const loadPromise = newsStore.loadArticles();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockArticle()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates articles derived on success', async () => {
      const arts = [mockArticle()];
      vi.spyOn(NewsService.prototype, 'getArticles').mockResolvedValue(arts);
      await newsStore.loadArticles();
      let a: NewsArticle[] = [];
      const unsub = articles.subscribe(s => { a = s; });
      expect(a).toEqual(arts);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(NewsService.prototype, 'getArticles').mockResolvedValue([]);
      await newsStore.loadArticles();
      expect(getCurrentState().articles).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(NewsService.prototype, 'getArticles').mockRejectedValue(new Error('Load failed'));
      await newsStore.loadArticles();
      const state = getCurrentState();
      expect(state.error).toBe('Load failed');
      expect(state.loading).toBe(false);
      expect(state.articles).toEqual([]);
    });
  });

  describe('loadArticle', () => {
    it('loads single article and sets selectedArticle', async () => {
      const art = mockArticle({ id: 'a1' });
      vi.spyOn(NewsService.prototype, 'getArticle').mockResolvedValue(art);
      await newsStore.loadArticle('a1');
      const state = getCurrentState();
      expect(state.selectedArticle).toEqual(art);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(NewsService.prototype, 'getArticle').mockReturnValue(p as any);
      const loadPromise = newsStore.loadArticle('a1');
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise(mockArticle({ id: 'a1' }));
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates selectedArticle derived on success', async () => {
      const art = mockArticle({ id: 'a1' });
      vi.spyOn(NewsService.prototype, 'getArticle').mockResolvedValue(art);
      await newsStore.loadArticle('a1');
      let sa: NewsArticle | null = null;
      const unsub = selectedArticle.subscribe(s => { sa = s; });
      expect(sa).toEqual(art);
      unsub();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(NewsService.prototype, 'getArticle').mockRejectedValue(new Error('Not found'));
      try {
        await newsStore.loadArticle('a1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
      expect(state.selectedArticle).toBeNull();
    });
  });

  describe('createArticle', () => {
    it('creates article and prepends to articles', async () => {
      const newArt = mockArticle({ id: 'a1', title: 'New' });
      vi.spyOn(NewsService.prototype, 'createArticle').mockResolvedValue(newArt);
      const result = await newsStore.createArticle({ title: 'New', content: 'C', category: 'gen' });
      expect(result).toEqual(newArt);
      const state = getCurrentState();
      expect(state.articles[0]).toEqual(newArt);
    });

    it('propagates error', async () => {
      vi.spyOn(NewsService.prototype, 'createArticle').mockRejectedValue(new Error('Create error'));
      try {
        await newsStore.createArticle({ title: 'New', content: 'C', category: 'gen' });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Create error');
    });
  });

  describe('updateArticle', () => {
    it('updates article in articles list', async () => {
      const original = mockArticle({ id: 'a1', title: 'Original' });
      const updated = mockArticle({ id: 'a1', title: 'Updated' });
      // Load original article into store
      vi.spyOn(NewsService.prototype, 'getArticles').mockResolvedValue([original]);
      await newsStore.loadArticles();
      // Perform update
      vi.spyOn(NewsService.prototype, 'updateArticle').mockResolvedValue(updated);
      const result = await newsStore.updateArticle('a1', { title: 'Updated' });
      expect(result).toEqual(updated);
      const state = getCurrentState();
      expect(state.articles).toHaveLength(1);
      expect(state.articles[0].title).toBe('Updated');
    });

    it('updates selectedArticle when it matches', async () => {
      const original = mockArticle({ id: 'a1', title: 'Original' });
      const updated = mockArticle({ id: 'a1', title: 'Updated' });
      // Load article
      vi.spyOn(NewsService.prototype, 'getArticles').mockResolvedValue([original]);
      await newsStore.loadArticles();
      // Select the article
      newsStore.selectArticle(original);
      let state = getCurrentState();
      expect(state.selectedArticle?.id).toBe('a1');
      // Update article
      vi.spyOn(NewsService.prototype, 'updateArticle').mockResolvedValue(updated);
      await newsStore.updateArticle('a1', { title: 'Updated' });
      state = getCurrentState();
      expect(state.selectedArticle?.title).toBe('Updated');
    });

    it('handles error', async () => {
      vi.spyOn(NewsService.prototype, 'updateArticle').mockRejectedValue(new Error('Update failed'));
      try {
        await newsStore.updateArticle('a1', { title: 'New' });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Update failed');
    });
  });

  describe('deleteArticle', () => {
    it('removes article from articles and clears selectedArticle if needed', async () => {
      const art = mockArticle({ id: 'a1' });
      // Load article
      vi.spyOn(NewsService.prototype, 'getArticles').mockResolvedValue([art]);
      await newsStore.loadArticles();
      // Select it
      newsStore.selectArticle(art);
      // Delete
      vi.spyOn(NewsService.prototype, 'deleteArticle').mockResolvedValue(undefined);
      await newsStore.deleteArticle('a1');
      const state = getCurrentState();
      expect(state.articles).toEqual([]);
      expect(state.selectedArticle).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(NewsService.prototype, 'deleteArticle').mockRejectedValue(new Error('Delete failed'));
      try {
        await newsStore.deleteArticle('a1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Delete failed');
    });
  });

  describe('loadComments', () => {
    it('loads comments', async () => {
      const cmts = [mockComment(), mockComment()];
      vi.spyOn(NewsService.prototype, 'getComments').mockResolvedValue(cmts);
      await newsStore.loadComments('a1');
      const state = getCurrentState();
      expect(state.comments).toEqual(cmts);
    });

    it('handles error', async () => {
      vi.spyOn(NewsService.prototype, 'getComments').mockRejectedValue(new Error('Comments failed'));
      await newsStore.loadComments('a1');
      const state = getCurrentState();
      expect(state.error).toBe('Comments failed');
    });
  });

  describe('addComment', () => {
    it('adds comment to comments', async () => {
      const cmt = mockComment({ id: 'c1' });
      vi.spyOn(NewsService.prototype, 'addComment').mockResolvedValue(cmt);
      const result = await newsStore.addComment('a1', 'Nice!');
      expect(result).toEqual(cmt);
      const state = getCurrentState();
      expect(state.comments).toContainEqual(cmt);
    });

    it('handles error', async () => {
      vi.spyOn(NewsService.prototype, 'addComment').mockRejectedValue(new Error('Add failed'));
      try {
        await newsStore.addComment('a1', 'Nice!');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Add failed');
    });
  });

  describe('selectArticle', () => {
    it('selects article', () => {
      const art = mockArticle();
      newsStore.selectArticle(art);
      let state = getCurrentState();
      expect(state.selectedArticle).toEqual(art);
      newsStore.selectArticle(null);
      state = getCurrentState();
      expect(state.selectedArticle).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(NewsService.prototype, 'getArticles').mockRejectedValue(new Error('err'));
      await newsStore.loadArticles();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      newsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(NewsService.prototype, 'getArticles').mockResolvedValue([mockArticle()]);
      await newsStore.loadArticles();
      let state = getCurrentState();
      expect(state.articles.length).toBeGreaterThan(0);
      newsStore.reset();
      state = getCurrentState();
      expect(state.articles).toEqual([]);
      expect(state.selectedArticle).toBeNull();
      expect(state.comments).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('publishedArticles derived', () => {
    it('filters only published articles', async () => {
      const published = mockArticle({ id: 'p1', status: 'published' });
      const draft = mockArticle({ id: 'd1', status: 'draft' });
      vi.spyOn(NewsService.prototype, 'getArticles').mockResolvedValue([published, draft]);
      await newsStore.loadArticles();
      let pub: NewsArticle[] = [];
      const unsub = publishedArticles.subscribe(s => { pub = s; });
      expect(pub).toEqual([published]);
      unsub();
    });
  });
});
