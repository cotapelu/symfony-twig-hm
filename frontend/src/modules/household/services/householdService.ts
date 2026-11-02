import type { Household, HouseholdState } from '../contracts/householdTypes';

const API_BASE = '/api/household';

export class HouseholdService {
  async getHouseholds(): Promise<Household[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch households');
    return response.json();
  }
}

export const householdService = new HouseholdService();
export default householdService;
