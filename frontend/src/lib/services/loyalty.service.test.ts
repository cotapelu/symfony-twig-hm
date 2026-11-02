import { describe, it, expect, vi, beforeEach } from 'vitest';
import { loyaltyService } from './loyalty';
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

describe('LoyaltyService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getMyProgram', () => {
    it('fetches user loyalty program', async () => {
      const program = { id: 'lp1', name: 'VIP', points: 1000, tier: 'Gold', pointsToNextTier: 500 };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => program });

      const result = await loyaltyService.getMyProgram();

      expect(getApiUrl).toHaveBeenCalledWith('loyalty', '/api/loyalty/my');
      expect(result).toEqual(program);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(loyaltyService.getMyProgram()).rejects.toThrow('Failed to fetch loyalty program');
    });
  });

  describe('getAvailableRewards', () => {
    it('fetches available rewards', async () => {
      const rewards = [{ id: 'r1', name: 'Discount', pointsRequired: 500, description: '', image: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ rewards }) });

      const result = await loyaltyService.getAvailableRewards();

      expect(getApiUrl).toHaveBeenCalledWith('loyalty', '/api/loyalty/rewards');
      expect(result).toEqual(rewards);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await loyaltyService.getAvailableRewards();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(loyaltyService.getAvailableRewards()).rejects.toThrow('Failed to fetch rewards');
    });
  });

  describe('redeemReward', () => {
    it('redeems a reward and returns redemption', async () => {
      const redemption = { id: 'red1', rewardId: 'r1', redeemedAt: '2024-01-01', status: 'pending' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => redemption });

      const result = await loyaltyService.redeemReward('r1');

      expect(getApiUrl).toHaveBeenCalledWith('loyalty', '/api/loyalty/redeem');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ rewardId: 'r1' }),
      });
      expect(result).toEqual(redemption);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(loyaltyService.redeemReward('r1')).rejects.toThrow('Failed to redeem reward');
    });
  });

  describe('getMyRedemptions', () => {
    it('fetches user redemptions', async () => {
      const redemptions = [{ id: 'red1', rewardId: 'r1', redeemedAt: '2024-01-01', status: 'completed' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ redemptions }) });

      const result = await loyaltyService.getMyRedemptions();

      expect(getApiUrl).toHaveBeenCalledWith('loyalty', '/api/loyalty/redemptions/my');
      expect(result).toEqual(redemptions);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await loyaltyService.getMyRedemptions();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(loyaltyService.getMyRedemptions()).rejects.toThrow('Failed to fetch redemptions');
    });
  });
});
