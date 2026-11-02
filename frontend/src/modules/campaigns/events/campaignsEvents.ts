export interface CampaignCreatedEvent {
  type: 'campaign.created';
  payload: {
    campaignId: string;
    userId: string;
    name: string;
    type: string;
  };
}

export interface CampaignStartedEvent {
  type: 'campaign.started';
  payload: {
    campaignId: string;
    scheduledAt?: string;
  };
}

export interface CampaignPausedEvent {
  type: 'campaign.paused';
  payload: {
    campaignId: string;
  };
}

export interface CampaignCompletedEvent {
  type: 'campaign.completed';
  payload: {
    campaignId: string;
    stats: {
      sent: number;
      delivered: number;
      opened?: number;
      clicked?: number;
    };
  };
}

export interface CampaignDeletedEvent {
  type: 'campaign.deleted';
  payload: {
    campaignId: string;
  };
}

export type CampaignsEvent =
  | CampaignCreatedEvent
  | CampaignStartedEvent
  | CampaignPausedEvent
  | CampaignCompletedEvent
  | CampaignDeletedEvent;

export const campaignsEvents = {
  campaignCreated: (campaignId: string, userId: string, name: string, type: string): CampaignCreatedEvent => ({
    type: 'campaign.created',
    payload: { campaignId, userId, name, type }
  }),

  campaignStarted: (campaignId: string, scheduledAt?: string): CampaignStartedEvent => ({
    type: 'campaign.started',
    payload: { campaignId, scheduledAt }
  }),

  campaignPaused: (campaignId: string): CampaignPausedEvent => ({
    type: 'campaign.paused',
    payload: { campaignId }
  }),

  campaignCompleted: (campaignId: string, stats: { sent: number; delivered: number; opened?: number; clicked?: number }): CampaignCompletedEvent => ({
    type: 'campaign.completed',
    payload: { campaignId, stats }
  }),

  campaignDeleted: (campaignId: string): CampaignDeletedEvent => ({
    type: 'campaign.deleted',
    payload: { campaignId }
  })
};
