import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ReportsService } from './reportsService';

global.fetch = vi.fn();

describe('ReportsService', () => {
  let service: ReportsService;

  beforeEach(() => {
    service = new ReportsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  it('should fetch reports', async () => {
    const reports = [{ id: 'r1', title: 'Report 1' }];
    (global.fetch as any).mockResolvedValue(mockResponse(reports));
    const result = await service.getReports();
    expect(result).toEqual(reports);
    expect(fetch).toHaveBeenCalledWith('/api/reports');
  });

  it('should throw on failure', async () => {
    (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
    await expect(service.getReports()).rejects.toThrow('Failed to fetch reports');
  });
});
