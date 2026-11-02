import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ProfileCitizenService } from './profileCitizenService';
import { apiClient } from '../../../appcore/api-client';

vi.mock('../../../appcore/api-client', () => ({
  apiClient: { get: vi.fn() }
}));

describe('ProfileCitizenService', () => {
  let service: ProfileCitizenService;

  beforeEach(() => {
    service = new ProfileCitizenService();
    vi.clearAllMocks();
  });

  describe('getProfile', () => {
    it('should fetch citizen profile', async () => {
      const profile = { id: 'p1', name: 'Nguyen Van A' };
      (apiClient.get as any).mockResolvedValue(profile);

      const result = await service.getProfile();

      expect(result).toEqual(profile);
      expect(apiClient.get).toHaveBeenCalledWith('/api/profile-citizen');
    });

    it('should propagate errors from apiClient', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('API error'));

      await expect(service.getProfile()).rejects.toThrow('API error');
    });
  });
});
