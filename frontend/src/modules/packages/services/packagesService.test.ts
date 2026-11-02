import { describe, it, expect, vi, beforeEach } from 'vitest';
import { PackagesService } from './packagesService';

global.fetch = vi.fn();

describe('PackagesService', () => {
  let service: PackagesService;

  beforeEach(() => {
    service = new PackagesService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  it('should fetch packages', async () => {
    const packages = [{ id: 'p1', name: 'Package A' }];
    (global.fetch as any).mockResolvedValue(mockResponse(packages));
    const result = await service.getPackages();
    expect(result).toEqual(packages);
    expect(fetch).toHaveBeenCalledWith('/api/packages');
  });

  it('should throw on failure', async () => {
    (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
    await expect(service.getPackages()).rejects.toThrow('Failed to fetch packages');
  });
});
