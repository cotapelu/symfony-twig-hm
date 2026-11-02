import type { Experience, ExperiencesState } from '../contracts/experiencesTypes';

const API_BASE = '/api/experiences';

export class ExperiencesService {
  async getExperiences(): Promise<Experience[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch experiences');
    return response.json();
  }
}

export const experiencesService = new ExperiencesService();
export default experiencesService;
