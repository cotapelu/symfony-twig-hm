import type { PopulationRecord, PopulationState } from '../contracts/populationTypes';

const API_BASE = '/api/population';

export class PopulationService {
  async getRecords(): Promise<PopulationRecord[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch records');
    return response.json();
  }
}

export const populationService = new PopulationService();
export default populationService;
