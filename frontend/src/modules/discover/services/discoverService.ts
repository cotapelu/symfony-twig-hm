import type { DiscoverItem, DiscoverState } from '../contracts/discoverTypes';

const API_BASE = '/api/discover';

export class DiscoverService {
  async getItems(): Promise<DiscoverItem[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch items');
    return response.json();
  }
}

export const discoverService = new DiscoverService();
export default discoverService;
