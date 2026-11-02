import { describe, it, expect, vi, beforeEach } from 'vitest';
import { campaignsStore, campaigns, selectedCampaign } from './campaignsStore';
import { campaignsService } from '../services/campaignsService';
import type { Campaign } from '../contracts/campaignsTypes';

// Helper to get current store state
function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factory
const mockCampaign = (overrides: Partial<Campaign> = {}): Campaign => ({
  id: `camp-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test Campaign',
  description: 'A test campaign',
  type: 'email' as const,
  status: 'draft' as const,
  targetAudience: 'all',
  scheduledAt: null,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  ...overrides
});

describe('CampaignsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    campaignsStore.reset();
  });

  describe('initial state', () => {
    it('has empty campaigns and null selectedCampaign', () => {
      const state = getCurrentState(campaignsStore);
      expect(state.campaigns).toEqual([]);
      expect(state.selectedCampaign).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('loadCampaigns', () => {
    it('loads campaigns successfully', async () => {
      const campaigns = [mockCampaign(), mockCampaign()];
      vi.spyOn(campaignsService, 'getCampaigns').mockResolvedValue(campaigns);

      await campaignsStore.loadCampaigns();

      const state = getCurrentState(campaignsStore);
      expect(state.campaigns).toEqual(campaigns);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(campaignsService, 'getCampaigns').mockReturnValue(p as any);
      const loadPromise = campaignsStore.loadCampaigns();
      await new Promise(process.nextTick);
      expect(getCurrentState(campaignsStore).loading).toBe(true);
      resolvePromise([mockCampaign()]);
      await loadPromise;
      expect(getCurrentState(campaignsStore).loading).toBe(false);
    });

    it('handles empty response', async () => {
      vi.spyOn(campaignsService, 'getCampaigns').mockResolvedValue([]);
      await campaignsStore.loadCampaigns();
      const state = getCurrentState(campaignsStore);
      expect(state.campaigns).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(campaignsService, 'getCampaigns').mockRejectedValue(new Error('Campaigns API error'));

      await campaignsStore.loadCampaigns();

      const state = getCurrentState(campaignsStore);
      expect(state.error).toBe('Campaigns API error');
      expect(state.campaigns).toEqual([]);
      expect(state.loading).toBe(false);
    });

    it('updates campaigns derived on success', async () => {
      const campaignsList = [mockCampaign()];
      vi.spyOn(campaignsService, 'getCampaigns').mockResolvedValue(campaignsList);
      await campaignsStore.loadCampaigns();
      let c: Campaign[] = [];
      const unsub = campaigns.subscribe(s => { c = s; });
      expect(c).toEqual(campaignsList);
      unsub();
    });
  });

  describe('loadCampaign', () => {
    it('loads a campaign and sets selectedCampaign', async () => {
      const campaign = mockCampaign({ id: 'c1' });
      vi.spyOn(campaignsService, 'getCampaign').mockResolvedValue(campaign);

      await campaignsStore.loadCampaign('c1');

      const state = getCurrentState(campaignsStore);
      expect(state.selectedCampaign).toEqual(campaign);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(campaignsService, 'getCampaign').mockReturnValue(p as any);
      const loadPromise = campaignsStore.loadCampaign('c1');
      await new Promise(process.nextTick);
      expect(getCurrentState(campaignsStore).loading).toBe(true);
      resolvePromise(mockCampaign({ id: 'c1' }));
      await loadPromise;
      expect(getCurrentState(campaignsStore).loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(campaignsService, 'getCampaign').mockRejectedValue(new Error('Not found'));
      try {
        await campaignsStore.loadCampaign('c1');
      } catch {}
      const state = getCurrentState(campaignsStore);
      expect(state.error).toBe('Not found');
      expect(state.selectedCampaign).toBeNull();
      expect(state.loading).toBe(false);
    });

    it('updates selectedCampaign derived on success', async () => {
      const campaign = mockCampaign({ id: 'c1' });
      vi.spyOn(campaignsService, 'getCampaign').mockResolvedValue(campaign);
      await campaignsStore.loadCampaign('c1');
      let sel: Campaign | null = null;
      const unsub = selectedCampaign.subscribe(s => { sel = s; });
      expect(sel).toEqual(campaign);
      unsub();
    });
  });

  describe('createCampaign', () => {
    it('creates a campaign and adds to list', async () => {
      const newCampaign = mockCampaign({ id: 'new' });
      vi.spyOn(campaignsService, 'createCampaign').mockResolvedValue(newCampaign);

      const request = { name: 'New Camp', type: 'email' };
      const result = await campaignsStore.createCampaign(request);

      expect(result).toEqual(newCampaign);
      const state = getCurrentState(campaignsStore);
      expect(state.campaigns).toContainEqual(newCampaign);
    });

    it('handles error and throws', async () => {
      vi.spyOn(campaignsService, 'createCampaign').mockRejectedValue(new Error('Create failed'));

      await expect(campaignsStore.createCampaign({ name: 'X', type: 'sms' })).rejects.toThrow('Create failed');

      const state = getCurrentState(campaignsStore);
      expect(state.error).toBe('Create failed');
    });
  });

  describe('updateCampaign', () => {
    it('updates campaign in list and selectedCampaign if applicable', async () => {
      const existing = mockCampaign({ id: 'c1', name: 'Old' });
      // Preload list and selected
      vi.spyOn(campaignsService, 'getCampaigns').mockResolvedValue([existing]);
      await campaignsStore.loadCampaigns();
      campaignsStore.selectCampaign?.(existing); // store doesn't have direct select? It sets selectedCampaign in loadCampaign or we can set via? Actually store has no public method to set selectedCampaign except loadCampaign. We'll load via loadCampaign.
      await campaignsStore.loadCampaign('c1');
      let state = getCurrentState(campaignsStore);
      expect(state.selectedCampaign?.id).toBe('c1');

      const updated = mockCampaign({ id: 'c1', name: 'New' });
      vi.spyOn(campaignsService, 'updateCampaign').mockResolvedValue(updated);

      await campaignsStore.updateCampaign('c1', { name: 'New' });

      state = getCurrentState(campaignsStore);
      expect(state.campaigns[0].name).toBe('New');
      expect(state.selectedCampaign?.name).toBe('New');
    });

    it('handles error', async () => {
      vi.spyOn(campaignsService, 'updateCampaign').mockRejectedValue(new Error('Update error'));

      await expect(campaignsStore.updateCampaign('c1', { status: 'active' })).rejects.toThrow('Update error');

      const state = getCurrentState(campaignsStore);
      expect(state.error).toBe('Update error');
    });
  });

  describe('deleteCampaign', () => {
    it('removes campaign from list', async () => {
      const c1 = mockCampaign({ id: 'c1' });
      const c2 = mockCampaign({ id: 'c2' });
      vi.spyOn(campaignsService, 'getCampaigns').mockResolvedValue([c1, c2]);
      await campaignsStore.loadCampaigns();
      let state = getCurrentState(campaignsStore);
      expect(state.campaigns).toHaveLength(2);

      vi.spyOn(campaignsService, 'deleteCampaign').mockResolvedValue(undefined);
      await campaignsStore.deleteCampaign('c1');

      state = getCurrentState(campaignsStore);
      expect(state.campaigns).toHaveLength(1);
      expect(state.campaigns[0].id).toBe('c2');
    });

    it('handles error', async () => {
      vi.spyOn(campaignsService, 'deleteCampaign').mockRejectedValue(new Error('Delete failed'));

      await expect(campaignsStore.deleteCampaign('c1')).rejects.toThrow('Delete failed');

      const state = getCurrentState(campaignsStore);
      expect(state.error).toBe('Delete failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(campaignsService, 'getCampaigns').mockRejectedValue(new Error('test'));
      try {
        await campaignsStore.loadCampaigns();
      } catch (e) {}
      let state = getCurrentState(campaignsStore);
      expect(state.error).toBe('test');

      campaignsStore.clearError();
      state = getCurrentState(campaignsStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      // Load some data
      const campaigns = [mockCampaign()];
      vi.spyOn(campaignsService, 'getCampaigns').mockResolvedValue(campaigns);
      await campaignsStore.loadCampaigns();
      let state = getCurrentState(campaignsStore);
      expect(state.campaigns).not.toEqual([]);

      // Set selectedCampaign via loadCampaign
      const camp = mockCampaign({ id: 'c1' });
      vi.spyOn(campaignsService, 'getCampaign').mockResolvedValue(camp);
      await campaignsStore.loadCampaign('c1');
      state = getCurrentState(campaignsStore);
      expect(state.selectedCampaign).not.toBeNull();

      campaignsStore.reset();
      state = getCurrentState(campaignsStore);
      expect(state.campaigns).toEqual([]);
      expect(state.selectedCampaign).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
