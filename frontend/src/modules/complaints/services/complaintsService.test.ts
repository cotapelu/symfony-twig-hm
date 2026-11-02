import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ComplaintsService } from './complaintsService';

global.fetch = vi.fn();

describe('ComplaintsService', () => {
  let service: ComplaintsService;

  beforeEach(() => {
    service = new ComplaintsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getComplaints', () => {
    it('should fetch complaints', async () => {
      const complaints = [{ id: 'c1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(complaints));
      const result = await service.getComplaints();
      expect(result).toEqual(complaints);
      expect(fetch).toHaveBeenCalledWith('/api/complaints');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getComplaints()).rejects.toThrow('Failed to fetch complaints');
    });
  });

  describe('createComplaint', () => {
    it('should create a complaint', async () => {
      const created = { id: 'new' };
      (global.fetch as any).mockResolvedValue(mockResponse(created));
      const result = await service.createComplaint('billing', 'Issue', 'Description');
      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/complaints', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category: 'billing', subject: 'Issue', description: 'Description' })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createComplaint('x', 'y', 'z')).rejects.toThrow('Failed to create complaint');
    });
  });
});
