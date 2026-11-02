import type { EducationGovService, EducationGovState } from '../contracts/educationGovTypes';

const API_BASE = '/api/education-gov';

export class EducationGovServiceModule {
  async getServices(): Promise<EducationGovService[]> {
    const response = await fetch(`${API_BASE}/services`);
    if (!response.ok) throw new Error('Failed to fetch services');
    return response.json();
  }
}

export const educationGovServiceModule = new EducationGovServiceModule();
export default educationGovServiceModule;
