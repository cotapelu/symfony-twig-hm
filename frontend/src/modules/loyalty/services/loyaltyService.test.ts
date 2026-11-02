import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { LoyaltyService } from './loyaltyService';
import { __testing__ } from './loyaltyService';

describe('LoyaltyService', () => {
  let service: LoyaltyService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new LoyaltyService();
  });

  describe('getProgram', () => {
    it('should fetch loyalty program', async () => {
      const program = { name: 'Points', pointsBalance: 1000 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(program) });

      const result = await service.getProgram();

      expect(result).toEqual(program);
      expect(global.fetch).toHaveBeenCalledWith('/api/loyalty/program');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getProgram()).rejects.toThrow('Failed to fetch loyalty program');
    });
  });

  describe('getRewards', () => {
    it('should fetch rewards', async () => {
      const rewards = [{ id: 'r1', name: 'Discount' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(rewards) });

      const result = await service.getRewards();

      expect(result).toEqual(rewards);
      expect(global.fetch).toHaveBeenCalledWith('/api/loyalty/rewards');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getRewards()).rejects.toThrow('Failed to fetch rewards');
    });
  });

  describe('getTransactions', () => {
    it('should fetch transactions', async () => {
      const transactions = [{ id: 't1', points: 100 }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(transactions) });

      const result = await service.getTransactions();

      expect(result).toEqual(transactions);
      expect(global.fetch).toHaveBeenCalledWith('/api/loyalty/transactions');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getTransactions()).rejects.toThrow('Failed to fetch transactions');
    });
  });

  describe('redeemReward', () => {
    const request = { rewardId: 'r1', pointsUsed: 500 };

    it('should redeem reward successfully', async () => {
      const result = { reward: { id: 'r1' }, transaction: { id: 't1' } };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.redeemReward(request);

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/loyalty/redeem', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"rewardId":"r1"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.redeemReward(request)).rejects.toThrow('Failed to redeem reward');
    });
  });

  describe('getAvailableRewards', () => {
    it('should fetch available rewards', async () => {
      const rewards = [{ id: 'r2', name: 'Freebie' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(rewards) });

      const result = await service.getAvailableRewards();

      expect(result).toEqual(rewards);
      expect(global.fetch).toHaveBeenCalledWith('/api/loyalty/rewards/available');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getAvailableRewards()).rejects.toThrow('Failed to fetch available rewards');
    });
  });
});
