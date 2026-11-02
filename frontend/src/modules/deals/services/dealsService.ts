import type { Deal, DealsState } from '../contracts/dealsTypes';

const API_BASE = '/api/deals';

export class DealsService {
  async getDeals(): Promise<Deal[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch deals');
    return response.json();
  }
}

export const dealsService = new DealsService();
export default dealsService;
