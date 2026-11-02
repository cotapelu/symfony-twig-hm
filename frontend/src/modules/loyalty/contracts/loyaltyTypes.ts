export interface LoyaltyProgram {
  id: string;
  userId: string;
  name: string;
  points: number;
  tier: 'bronze' | 'silver' | 'gold' | 'platinum';
  totalEarned: number;
  totalRedeemed: number;
  createdAt: string;
  updatedAt: string;
}

export interface LoyaltyReward {
  id: string;
  name: string;
  description?: string;
  pointsRequired: number;
  category: string;
  isAvailable: boolean;
  image?: string;
}

export interface LoyaltyTransaction {
  id: string;
  userId: string;
  type: 'earn' | 'redeem';
  points: number;
  description: string;
  createdAt: string;
}

export interface LoyaltyState {
  program: LoyaltyProgram | null;
  rewards: LoyaltyReward[];
  transactions: LoyaltyTransaction[];
  loading: boolean;
  error: string | null;
}

export interface RedeemRewardRequest {
  rewardId: string;
  quantity?: number;
}
