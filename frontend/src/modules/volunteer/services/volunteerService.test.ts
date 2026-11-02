import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

global.fetch = vi.fn();

import { VolunteerService, setRetryWithBackoff, resetRetryWithBackoff } from './volunteerService';

// Suppress console logs
vi.spyOn(console, 'info').mockImplementation(() => {});
vi.spyOn(console, 'warn').mockImplementation(() => {});
vi.spyOn(console, 'error').mockImplementation(() => {});

describe('VolunteerService', () => {
  let service: VolunteerService;

  beforeEach(() => {
    // Mock the retry function to execute immediately without delays
    setRetryWithBackoff(vi.fn(async (operation: () => Promise<any>) => {
      return operation();
    }));

    vi.clearAllMocks();
    service = new VolunteerService();
  });

  afterEach(() => {
    resetRetryWithBackoff();
    vi.restoreAllMocks();
  });

  describe('getOpportunities', () => {
    it('should fetch opportunities with default pagination', async () => {
      const result = { items: [{ id: 'o1', title: 'Cleanup' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getOpportunities();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/volunteer/opportunities?page=1&limit=20');
    });

    it('should fetch with custom pagination', async () => {
      const result = { items: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getOpportunities(2, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/volunteer/opportunities?page=2&limit=50');
    });

    it('should throw on invalid pagination', async () => {
      await expect(service.getOpportunities(0, 10)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getOpportunities(1, 0)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getOpportunities(1, 150)).rejects.toThrow('Invalid pagination parameters');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getOpportunities()).rejects.toThrow('Failed to fetch opportunities');
    });
  });

  describe('getOpportunity', () => {
    it('should fetch opportunity by id', async () => {
      const opp = { id: 'o1', title: 'Cleanup' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(opp) });

      const result = await service.getOpportunity('o1');

      expect(result).toEqual(opp);
      expect(global.fetch).toHaveBeenCalledWith('/api/volunteer/opportunities/o1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getOpportunity('o1')).rejects.toThrow('Failed to fetch opportunity');
    });
  });

  describe('apply', () => {
    it('should apply to opportunity', async () => {
      const res = { success: true };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(res) });

      const result = await service.apply('o1');

      expect(result).toEqual(res);
      expect(global.fetch).toHaveBeenCalledWith('/api/volunteer/opportunities/o1/apply', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.apply('o1')).rejects.toThrow('Failed to apply');
    });
  });
});