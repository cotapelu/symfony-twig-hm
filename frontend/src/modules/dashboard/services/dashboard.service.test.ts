import { describe, it, expect, vi, beforeEach } from 'vitest';
import { DashboardService } from './dashboard.service';

// Mock the dashboard store
vi.mock('../state/dashboard-store', () => ({
  dashboardStore: {
    loadStats: vi.fn()
  }
}));

import { dashboardStore } from '../state/dashboard-store';

describe('DashboardService', () => {
  let service: DashboardService;

  beforeEach(() => {
    service = new DashboardService();
    vi.clearAllMocks();
  });

  describe('getStats', () => {
    it('should return stats from store', async () => {
      const mockStats = { messages: 5, groups: 2, files: 10, calls: 3 };
      (dashboardStore.loadStats as any).mockResolvedValue(mockStats);

      const result = await service.getStats();

      expect(result).toEqual(mockStats);
      expect(dashboardStore.loadStats).toHaveBeenCalledTimes(1);
    });

    it('should propagate errors', async () => {
      (dashboardStore.loadStats as any).mockRejectedValue(new Error('Network error'));

      await expect(service.getStats()).rejects.toThrow('Network error');
    });
  });

  describe('refreshStats', () => {
    it('should return stats from store', async () => {
      const mockStats = { messages: 8, groups: 4, files: 12, calls: 6 };
      (dashboardStore.loadStats as any).mockResolvedValue(mockStats);

      const result = await service.refreshStats();

      expect(result).toEqual(mockStats);
      expect(dashboardStore.loadStats).toHaveBeenCalledTimes(1);
    });

    it('should propagate errors', async () => {
      (dashboardStore.loadStats as any).mockRejectedValue(new Error('API down'));

      await expect(service.refreshStats()).rejects.toThrow('API down');
    });
  });
});
