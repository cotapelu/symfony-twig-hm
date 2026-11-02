import type { ElderlyService, ElderlyState } from '../contracts/elderlyTypes';

const API_BASE = '/api/elderly';

export class ElderlyServiceModule {
  async getServices(): Promise<ElderlyService[]> {
    const response = await fetch(`${API_BASE}/services`);
    if (!response.ok) throw new Error('Failed to fetch services');
    return response.json();
  }
}

export const elderlyServiceModule = new ElderlyServiceModule();
export default elderlyServiceModule;
