import type { CultureSportsEvent, CultureSportsState } from '../contracts/cultureSportsTypes';

const API_BASE = '/api/culture-sports';

export class CultureSportsService {
  async getEvents(): Promise<CultureSportsEvent[]> {
    const response = await fetch(`${API_BASE}/events`);
    if (!response.ok) throw new Error('Failed to fetch events');
    return response.json();
  }
}

export const cultureSportsService = new CultureSportsService();
export default cultureSportsService;
