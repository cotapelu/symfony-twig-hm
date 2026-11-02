import { describe, it, expect, vi, beforeEach } from 'vitest';
import { insuranceService } from './insurance';
import { getApiUrl } from '../config/api';

vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('InsuranceService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getMyPolicies', () => {
    it('fetches user policies', async () => {
      const policies = [{ id: 'pol1', type: 'health', provider: 'ABC', coverage: 10000, premium: 100, startDate: '2024-01-01', endDate: '2025-01-01', status: 'active' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ policies }) });

      const result = await insuranceService.getMyPolicies();

      expect(getApiUrl).toHaveBeenCalledWith('insurance', '/api/insurance/policies/my');
      expect(result).toEqual(policies);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await insuranceService.getMyPolicies();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(insuranceService.getMyPolicies()).rejects.toThrow('Failed to fetch policies');
    });
  });

  describe('submitClaim', () => {
    it('submits a claim', async () => {
      const claim = { id: 'c1', policyId: 'pol1', amount: 500, status: 'pending', submittedAt: '2024-01-01' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => claim });

      const result = await insuranceService.submitClaim('pol1', 500);

      expect(getApiUrl).toHaveBeenCalledWith('insurance', '/api/insurance/claims');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ policyId: 'pol1', amount: 500 }),
      });
      expect(result).toEqual(claim);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(insuranceService.submitClaim('pol1', 100)).rejects.toThrow('Failed to submit claim');
    });
  });

  describe('getMyClaims', () => {
    it('fetches user claims', async () => {
      const claims = [{ id: 'c1', policyId: 'pol1', amount: 200, status: 'approved', submittedAt: '2024-01-01' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ claims }) });

      const result = await insuranceService.getMyClaims();

      expect(getApiUrl).toHaveBeenCalledWith('insurance', '/api/insurance/claims/my');
      expect(result).toEqual(claims);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await insuranceService.getMyClaims();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(insuranceService.getMyClaims()).rejects.toThrow('Failed to fetch claims');
    });
  });
});
