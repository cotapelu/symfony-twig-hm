export interface Campaign {
  id: string;
  userId: string;
  name: string;
  description?: string;
  type: 'email' | 'sms' | 'push' | 'social';
  status: 'draft' | 'scheduled' | 'active' | 'paused' | 'completed';
  targetAudience?: string;
  scheduledAt?: string;
  startedAt?: string;
  endedAt?: string;
  stats?: CampaignStats;
  createdAt: string;
  updatedAt: string;
}

export interface CampaignStats {
  sent: number;
  delivered: number;
  opened?: number;
  clicked?: number;
  converted?: number;
}

export interface CampaignState {
  campaigns: Campaign[];
  selectedCampaign: Campaign | null;
  loading: boolean;
  error: string | null;
}

export interface CreateCampaignRequest {
  name: string;
  description?: string;
  type: 'email' | 'sms' | 'push' | 'social';
  targetAudience?: string;
  scheduledAt?: string;
}

export interface UpdateCampaignRequest {
  name?: string;
  description?: string;
  status?: 'draft' | 'scheduled' | 'active' | 'paused' | 'completed';
  scheduledAt?: string;
}
