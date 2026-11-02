import type { CitizenProfile, ProfileCitizenState } from '../contracts/profileCitizenTypes';
import { apiClient } from '../../../appcore/api-client';

const API_BASE = '/api/profile-citizen';

export class ProfileCitizenService {
  async getProfile(): Promise<CitizenProfile> {
    return apiClient.get<CitizenProfile>(API_BASE);
  }
}

export const profileCitizenService = new ProfileCitizenService();
export default profileCitizenService;
