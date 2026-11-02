import type { EnvironmentReport, EnvironmentState } from '../contracts/environmentTypes';

const API_BASE = '/api/environment';

export class EnvironmentService {
  async getReports(): Promise<EnvironmentReport[]> {
    const response = await fetch(`${API_BASE}/reports`);
    if (!response.ok) throw new Error('Failed to fetch reports');
    return response.json();
  }
}

export const environmentService = new EnvironmentService();
export default environmentService;
