import type { HelpArticle, HelpState } from '../contracts/helpTypes';

const API_BASE = '/api/help';

export class HelpService {
  async getArticles(): Promise<HelpArticle[]> {
    const response = await fetch(`${API_BASE}/articles`);
    if (!response.ok) throw new Error('Failed to fetch articles');
    return response.json();
  }
}

export const helpService = new HelpService();
export default helpService;
