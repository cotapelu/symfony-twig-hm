import type { Complaint, ComplaintsState } from '../contracts/complaintsTypes';

const API_BASE = '/api/complaints';

export class ComplaintsService {
  async getComplaints(): Promise<Complaint[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch complaints');
    return response.json();
  }

  async createComplaint(category: string, subject: string, description: string): Promise<Complaint> {
    const response = await fetch(`${API_BASE}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ category, subject, description })
    });
    if (!response.ok) throw new Error('Failed to create complaint');
    return response.json();
  }
}

export const complaintsService = new ComplaintsService();
export default complaintsService;
