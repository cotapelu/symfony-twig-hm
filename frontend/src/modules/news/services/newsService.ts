import type { NewsArticle, NewsComment, NewsState, CreateArticleRequest, UpdateArticleRequest } from '../contracts/newsTypes';

const API_BASE = '/api/news';

export class NewsService {
  async getArticles(): Promise<NewsArticle[]> {
    const response = await fetch(`${API_BASE}/articles`);
    if (!response.ok) throw new Error('Failed to fetch articles');
    return response.json();
  }

  async getArticle(id: string): Promise<NewsArticle> {
    const response = await fetch(`${API_BASE}/articles/${id}`);
    if (!response.ok) throw new Error('Failed to fetch article');
    return response.json();
  }

  async getArticlesByCategory(category: string): Promise<NewsArticle[]> {
    const response = await fetch(`${API_BASE}/articles?category=${encodeURIComponent(category)}`);
    if (!response.ok) throw new Error('Failed to fetch articles by category');
    return response.json();
  }

  async createArticle(request: CreateArticleRequest): Promise<NewsArticle> {
    const response = await fetch(`${API_BASE}/articles`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create article');
    return response.json();
  }

  async updateArticle(id: string, request: UpdateArticleRequest): Promise<NewsArticle> {
    const response = await fetch(`${API_BASE}/articles/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to update article');
    return response.json();
  }

  async deleteArticle(id: string): Promise<void> {
    const response = await fetch(`${API_BASE}/articles/${id}`, {
      method: 'DELETE'
    });
    if (!response.ok) throw new Error('Failed to delete article');
  }

  async getComments(articleId: string): Promise<NewsComment[]> {
    const response = await fetch(`${API_BASE}/articles/${articleId}/comments`);
    if (!response.ok) throw new Error('Failed to fetch comments');
    return response.json();
  }

  async addComment(articleId: string, content: string): Promise<NewsComment> {
    const response = await fetch(`${API_BASE}/articles/${articleId}/comments`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ content })
    });
    if (!response.ok) throw new Error('Failed to add comment');
    return response.json();
  }
}

export const newsService = new NewsService();
export default newsService;
