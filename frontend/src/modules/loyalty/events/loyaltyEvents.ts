export interface PointsEarnedEvent {
  type: 'loyalty.points.earned';
  payload: {
    userId: string;
    points: number;
    description: string;
    transactionId: string;
  };
}

export interface PointsRedeemedEvent {
  type: 'loyalty.points.redeemed';
  payload: {
    userId: string;
    points: number;
    rewardName: string;
    transactionId: string;
  };
}

export interface TierUpgradedEvent {
  type: 'loyalty.tier.upgraded';
  payload: {
    userId: string;
    oldTier: string;
    newTier: string;
  };
}

export interface RewardClaimedEvent {
  type: 'loyalty.reward.claimed';
  payload: {
    userId: string;
    rewardId: string;
    rewardName: string;
    transactionId: string;
  };
}

export interface PointsExpiredEvent {
  type: 'loyalty.points.expired';
  payload: {
    userId: string;
    points: number;
    expirationDate: string;
  };
}

export type LoyaltyEvent =
  | PointsEarnedEvent
  | PointsRedeemedEvent
  | TierUpgradedEvent
  | RewardClaimedEvent
  | PointsExpiredEvent;

export const loyaltyEvents = {
  pointsEarned: (userId: string, points: number, description: string, transactionId: string): PointsEarnedEvent => ({
    type: 'loyalty.points.earned',
    payload: { userId, points, description, transactionId }
  }),

  pointsRedeemed: (userId: string, points: number, rewardName: string, transactionId: string): PointsRedeemedEvent => ({
    type: 'loyalty.points.redeemed',
    payload: { userId, points, rewardName, transactionId }
  }),

  tierUpgraded: (userId: string, oldTier: string, newTier: string): TierUpgradedEvent => ({
    type: 'loyalty.tier.upgraded',
    payload: { userId, oldTier, newTier }
  }),

  rewardClaimed: (userId: string, rewardId: string, rewardName: string, transactionId: string): RewardClaimedEvent => ({
    type: 'loyalty.reward.claimed',
    payload: { userId, rewardId, rewardName, transactionId }
  }),

  pointsExpired: (userId: string, points: number, expirationDate: string): PointsExpiredEvent => ({
    type: 'loyalty.points.expired',
    payload: { userId, points, expirationDate }
  })
};
