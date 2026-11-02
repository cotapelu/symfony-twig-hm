import type { Campaign, CampaignStats, CampaignState, CreateCampaignRequest, UpdateCampaignRequest } from '../contracts/campaignsTypes';

const API_BASE = '/api/campaigns';

export class CampaignsService {
  async getCampaigns(): Promise<Campaign[]> {
    const response = await fetch(`${API_BASE}/campaigns`);
    if (!response.ok) throw new Error('Failed to fetch campaigns');
    return response.json();
  }

  async getCampaign(id: string): Promise<Campaign> {
    const response = await fetch(`${API_BASE}/campaigns/${id}`);
    if (!response.ok) throw new Error('Failed to fetch campaign');
    return response.json();
  }

  async createCampaign(request: CreateCampaignRequest): Promise<Campaign> {
    const response = await fetch(`${API_BASE}/campaigns`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create campaign');
    return response.json();
  }

  async updateCampaign(id: string, request: UpdateCampaignRequest): Promise<Campaign> {
    const response = await fetch(`${API_BASE}/campaigns/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to update campaign');
    return response.json();
  }

  async deleteCampaign(id: string): Promise<void> {
    const response = await fetch(`${API_BASE}/campaigns/${id}`, {
      method: 'DELETE'
    });
    if (!response.ok) throw new Error('Failed to delete campaign');
  }

  async getCampaignStats(id: string): Promise<CampaignStats> {
    const response = await fetch(`${API_BASE}/campaigns/${id}/stats`);
    if (!response.ok) throw new Error('Failed to fetch campaign stats');
    return response.json();
  }

  async startCampaign(id: string): Promise<Campaign> {
    const response = await fetch(`${API_BASE}/campaigns/${id}/start`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to start campaign');
    return response.json();
  }

  async pauseCampaign(id: string): Promise<Campaign> {
    const response = await fetch(`${API_BASE}/campaigns/${id}/pause`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to pause campaign');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const campaignsService = new CampaignsService();
export default campaignsService;
