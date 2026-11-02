import { writable, derived } from 'svelte/store';
import type { NewsArticle, NewsComment, NewsState } from '../contracts/newsTypes';
import { newsService } from '../services/newsService';

const initialState: NewsState = {
  articles: [],
  selectedArticle: null,
  comments: [],
  loading: false,
  error: null
};

function createNewsStore() {
  const { subscribe, set, update } = writable<NewsState>(initialState);

  return {
    subscribe,

    async loadArticles() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const articles = await newsService.getArticles();
        update(s => ({ ...s, articles, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadArticle(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const selectedArticle = await newsService.getArticle(id);
        update(s => ({ ...s, selectedArticle, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async createArticle(request: { title: string; content: string; summary?: string; category: string; tags?: string[]; status?: 'draft' | 'published' }) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const article = await newsService.createArticle(request);
        update(s => ({ ...s, articles: [article, ...s.articles], loading: false }));
        return article;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async updateArticle(id: string, request: { title?: string; content?: string; summary?: string; category?: string; tags?: string[]; status?: 'draft' | 'published' | 'archived' }) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const article = await newsService.updateArticle(id, request);
        update(s => ({
          ...s,
          articles: s.articles.map(a => a.id === id ? article : a),
          selectedArticle: s.selectedArticle?.id === id ? article : s.selectedArticle,
          loading: false
        }));
        return article;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async deleteArticle(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        await newsService.deleteArticle(id);
        update(s => ({
          ...s,
          articles: s.articles.filter(a => a.id !== id),
          selectedArticle: s.selectedArticle?.id === id ? null : s.selectedArticle,
          loading: false
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadComments(articleId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const comments = await newsService.getComments(articleId);
        update(s => ({ ...s, comments, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async addComment(articleId: string, content: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const comment = await newsService.addComment(articleId, content);
        update(s => ({ ...s, comments: [...s.comments, comment], loading: false }));
        return comment;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    selectArticle(article: NewsArticle | null) {
      update(s => ({ ...s, selectedArticle: article }));
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const newsStore = createNewsStore();

export const articles = derived(newsStore, $s => $s.articles);
export const selectedArticle = derived(newsStore, $s => $s.selectedArticle);
export const comments = derived(newsStore, $s => $s.comments);
export const newsLoading = derived(newsStore, $s => $s.loading);
export const newsError = derived(newsStore, $s => $s.error);
export const publishedArticles = derived(newsStore, $s => $s.articles.filter(a => a.status === 'published'));
