import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { CampaignsService } from './campaignsService';
import { __testing__ } from './campaignsService';

describe('CampaignsService', () => {
  let service: CampaignsService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new CampaignsService();
  });

  describe('getCampaigns', () => {
    it('should fetch campaigns', async () => {
      const campaigns = [{ id: 'c1', name: 'Summer Sale' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(campaigns) });

      const result = await service.getCampaigns();

      expect(result).toEqual(campaigns);
      expect(global.fetch).toHaveBeenCalledWith('/api/campaigns/campaigns');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getCampaigns()).rejects.toThrow('Failed to fetch campaigns');
    });
  });

  describe('getCampaign', () => {
    it('should fetch campaign by id', async () => {
      const campaign = { id: 'c1', name: 'Campaign' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(campaign) });

      const result = await service.getCampaign('c1');

      expect(result).toEqual(campaign);
      expect(global.fetch).toHaveBeenCalledWith('/api/campaigns/campaigns/c1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getCampaign('c1')).rejects.toThrow('Failed to fetch campaign');
    });
  });

  describe('createCampaign', () => {
    const request = { name: 'New Campaign', budget: 1000, startDate: '2024-01-01' };

    it('should create campaign successfully', async () => {
      const campaign = { id: 'c1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(campaign) });

      const result = await service.createCampaign(request);

      expect(result).toEqual(campaign);
      expect(global.fetch).toHaveBeenCalledWith('/api/campaigns/campaigns', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"name":"New Campaign"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createCampaign(request)).rejects.toThrow('Failed to create campaign');
    });
  });

  describe('updateCampaign', () => {
    const updates = { budget: 2000 };
    const updated = { id: 'c1', name: 'Campaign', ...updates };

    it('should update campaign successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(updated) });

      const result = await service.updateCampaign('c1', updates);

      expect(result).toEqual(updated);
      expect(global.fetch).toHaveBeenCalledWith('/api/campaigns/campaigns/c1', expect.objectContaining({
        method: 'PUT',
        body: expect.stringContaining('"budget":2000')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.updateCampaign('c1', updates)).rejects.toThrow('Failed to update campaign');
    });
  });

  describe('deleteCampaign', () => {
    it('should delete campaign successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.deleteCampaign('c1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/campaigns/campaigns/c1', expect.objectContaining({ method: 'DELETE' }));
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.deleteCampaign('c1')).rejects.toThrow('Failed to delete campaign');
    });
  });

  describe('getCampaignStats', () => {
    it('should fetch campaign stats', async () => {
      const stats = { campaignId: 'c1', impressions: 1000, clicks: 50 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(stats) });

      const result = await service.getCampaignStats('c1');

      expect(result).toEqual(stats);
      expect(global.fetch).toHaveBeenCalledWith('/api/campaigns/campaigns/c1/stats');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getCampaignStats('c1')).rejects.toThrow('Failed to fetch campaign stats');
    });
  });

  describe('startCampaign', () => {
    it('should start campaign', async () => {
      const campaign = { id: 'c1', status: 'active' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(campaign) });

      const result = await service.startCampaign('c1');

      expect(result).toEqual(campaign);
      expect(global.fetch).toHaveBeenCalledWith('/api/campaigns/campaigns/c1/start', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.startCampaign('c1')).rejects.toThrow('Failed to start campaign');
    });
  });

  describe('pauseCampaign', () => {
    it('should pause campaign', async () => {
      const campaign = { id: 'c1', status: 'paused' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(campaign) });

      const result = await service.pauseCampaign('c1');

      expect(result).toEqual(campaign);
      expect(global.fetch).toHaveBeenCalledWith('/api/campaigns/campaigns/c1/pause', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.pauseCampaign('c1')).rejects.toThrow('Failed to pause campaign');
    });
  });
});
