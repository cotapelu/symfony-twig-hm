// Loyalty/Rewards Service - Connects to PHP Loyalty + Rewards modules
import { getApiUrl } from '../config/api';

export interface LoyaltyProgram {
  id: string;
  name: string;
  points: number;
  tier: string;
  pointsToNextTier: number;
}

export interface Reward {
  id: string;
  name: string;
  description: string;
  pointsRequired: number;
  image: string;
}

export interface Redemption {
  id: string;
  rewardId: string;
  redeemedAt: string;
  status: 'pending' | 'completed' | 'expired';
}

class LoyaltyService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getMyProgram(): Promise<LoyaltyProgram> {
    const url = getApiUrl('loyalty', '/api/loyalty/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch loyalty program');
    return response.json();
  }

  async getAvailableRewards(): Promise<Reward[]> {
    const url = getApiUrl('loyalty', '/api/loyalty/rewards');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch rewards');
    const data = await response.json();
    return data.rewards || [];
  }

  async redeemReward(rewardId: string): Promise<Redemption> {
    const url = getApiUrl('loyalty', '/api/loyalty/redeem');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ rewardId }),
    });
    if (!response.ok) throw new Error('Failed to redeem reward');
    return response.json();
  }

  async getMyRedemptions(): Promise<Redemption[]> {
    const url = getApiUrl('loyalty', '/api/loyalty/redemptions/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch redemptions');
    const data = await response.json();
    return data.redemptions || [];
  }
}

export const loyaltyService = new LoyaltyService();
