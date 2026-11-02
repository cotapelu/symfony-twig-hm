import type { Report, ReportsState } from '../contracts/reportsTypes';

const API_BASE = '/api/reports';

export class ReportsService {
  async getReports(): Promise<Report[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch reports');
    return response.json();
  }
}

export const reportsService = new ReportsService();
export default reportsService;
