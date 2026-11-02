import { dashboardStore } from '../state/dashboard-store';

export class DashboardService {
  async getStats(): Promise<any> {
    return await dashboardStore.loadStats();
  }

  async refreshStats(): Promise<any> {
    return await dashboardStore.loadStats();
  }
}

export const dashboardService = new DashboardService();