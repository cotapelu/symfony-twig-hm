import type { LoyaltyProgram, LoyaltyReward, LoyaltyTransaction, LoyaltyState, RedeemRewardRequest } from '../contracts/loyaltyTypes';

const API_BASE = '/api/loyalty';

export class LoyaltyService {
  async getProgram(): Promise<LoyaltyProgram> {
    const response = await fetch(`${API_BASE}/program`);
    if (!response.ok) throw new Error('Failed to fetch loyalty program');
    return response.json();
  }

  async getRewards(): Promise<LoyaltyReward[]> {
    const response = await fetch(`${API_BASE}/rewards`);
    if (!response.ok) throw new Error('Failed to fetch rewards');
    return response.json();
  }

  async getTransactions(): Promise<LoyaltyTransaction[]> {
    const response = await fetch(`${API_BASE}/transactions`);
    if (!response.ok) throw new Error('Failed to fetch transactions');
    return response.json();
  }

  async redeemReward(request: RedeemRewardRequest): Promise<{ reward: LoyaltyReward; transaction: LoyaltyTransaction }> {
    const response = await fetch(`${API_BASE}/redeem`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to redeem reward');
    return response.json();
  }

  async getAvailableRewards(): Promise<LoyaltyReward[]> {
    const response = await fetch(`${API_BASE}/rewards/available`);
    if (!response.ok) throw new Error('Failed to fetch available rewards');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const loyaltyService = new LoyaltyService();
export default loyaltyService;
