import type { EmergencyAlert, EmergencyState } from '../contracts/emergencyTypes';

const API_BASE = '/api/emergency';

export class EmergencyService {
  async getAlerts(): Promise<EmergencyAlert[]> {
    const response = await fetch(`${API_BASE}/alerts`);
    if (!response.ok) throw new Error('Failed to fetch alerts');
    return response.json();
  }
}

export const emergencyService = new EmergencyService();
export default emergencyService;
