import type { MyApplication, MyApplicationsState } from '../contracts/myApplicationsTypes';

const API_BASE = '/api/my-applications';

export class MyApplicationsService {
  async getApplications(): Promise<MyApplication[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch applications');
    return response.json();
  }
}

export const myApplicationsService = new MyApplicationsService();
export default myApplicationsService;
