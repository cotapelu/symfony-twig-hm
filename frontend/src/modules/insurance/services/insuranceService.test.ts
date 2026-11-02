import { describe, it, expect, vi, beforeEach } from 'vitest';
import { InsuranceService } from './insuranceService';

global.fetch = vi.fn();

describe('InsuranceService', () => {
  let service: InsuranceService;

  beforeEach(() => {
    service = new InsuranceService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getPolicies', () => {
    it('should fetch policies', async () => {
      const policies = [{ id: 'p1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(policies));
      const result = await service.getPolicies();
      expect(result).toEqual(policies);
      expect(fetch).toHaveBeenCalledWith('/api/insurance/policies');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getPolicies()).rejects.toThrow('Failed to fetch policies');
    });
  });

  describe('getMyPolicies', () => {
    it('should fetch my policies', async () => {
      const policies = [{ id: 'p2' }];
      (global.fetch as any).mockResolvedValue(mockResponse(policies));
      const result = await service.getMyPolicies();
      expect(result).toEqual(policies);
      expect(fetch).toHaveBeenCalledWith('/api/insurance/my-policies');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getMyPolicies()).rejects.toThrow('Failed to fetch my policies');
    });
  });

  describe('getPolicy', () => {
    it('should fetch policy by id', async () => {
      const policy = { id: 'p1' };
      (global.fetch as any).mockResolvedValue(mockResponse(policy));
      const result = await service.getPolicy('p1');
      expect(result).toEqual(policy);
      expect(fetch).toHaveBeenCalledWith('/api/insurance/policies/p1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getPolicy('p1')).rejects.toThrow('Failed to fetch policy');
    });
  });

  describe('createPolicy', () => {
    it('should create a policy', async () => {
      const created = { id: 'new' };
      (global.fetch as any).mockResolvedValue(mockResponse(created));
      const request = { type: 'health', coverage: 10000 };
      const result = await service.createPolicy(request);
      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/insurance/policies', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createPolicy({} as any)).rejects.toThrow('Failed to create policy');
    });
  });

  describe('updatePolicy', () => {
    it('should update a policy', async () => {
      const updated = { id: 'p1', coverage: 20000 };
      (global.fetch as any).mockResolvedValue(mockResponse(updated));
      const result = await service.updatePolicy('p1', { coverage: 20000 });
      expect(result).toEqual(updated);
      expect(fetch).toHaveBeenCalledWith('/api/insurance/policies/p1', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ coverage: 20000 })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.updatePolicy('p1', {})).rejects.toThrow('Failed to update policy');
    });
  });

  describe('getClaims', () => {
    it('should fetch claims', async () => {
      const claims = [{ id: 'c1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(claims));
      const result = await service.getClaims();
      expect(result).toEqual(claims);
      expect(fetch).toHaveBeenCalledWith('/api/insurance/claims');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getClaims()).rejects.toThrow('Failed to fetch claims');
    });
  });

  describe('getMyClaims', () => {
    it('should fetch my claims', async () => {
      const claims = [{ id: 'c2' }];
      (global.fetch as any).mockResolvedValue(mockResponse(claims));
      const result = await service.getMyClaims();
      expect(result).toEqual(claims);
      expect(fetch).toHaveBeenCalledWith('/api/insurance/my-claims');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getMyClaims()).rejects.toThrow('Failed to fetch my claims');
    });
  });

  describe('submitClaim', () => {
    it('should submit a claim', async () => {
      const submitted = { id: 'new' };
      (global.fetch as any).mockResolvedValue(mockResponse(submitted));
      const request = { policyId: 'p1', description: 'Accident' };
      const result = await service.submitClaim(request);
      expect(result).toEqual(submitted);
      expect(fetch).toHaveBeenCalledWith('/api/insurance/claims', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.submitClaim({} as any)).rejects.toThrow('Failed to submit claim');
    });
  });
});
