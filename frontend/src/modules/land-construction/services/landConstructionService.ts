import type { LandConstructionPermit, LandConstructionState } from '../contracts/landConstructionTypes';

const API_BASE = '/api/land-construction';

export class LandConstructionService {
  async getPermits(): Promise<LandConstructionPermit[]> {
    const response = await fetch(`${API_BASE}/permits`);
    if (!response.ok) throw new Error('Failed to fetch permits');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const landConstructionService = new LandConstructionService();
export default landConstructionService;
