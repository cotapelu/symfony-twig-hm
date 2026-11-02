import { writable, derived } from 'svelte/store';
import type { Campaign, CampaignState } from '../contracts/campaignsTypes';
import { campaignsService } from '../services/campaignsService';

const initialState: CampaignState = {
  campaigns: [],
  selectedCampaign: null,
  loading: false,
  error: null
};

function createCampaignsStore() {
  const { subscribe, set, update } = writable<CampaignState>(initialState);

  return {
    subscribe,

    async loadCampaigns() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const campaigns = await campaignsService.getCampaigns();
        update(s => ({ ...s, campaigns, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadCampaign(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const selectedCampaign = await campaignsService.getCampaign(id);
        update(s => ({ ...s, selectedCampaign, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async createCampaign(request: { name: string; description?: string; type: 'email' | 'sms' | 'push' | 'social'; targetAudience?: string; scheduledAt?: string }) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const campaign = await campaignsService.createCampaign(request);
        update(s => ({ ...s, campaigns: [...s.campaigns, campaign], loading: false }));
        return campaign;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async updateCampaign(id: string, request: { name?: string; description?: string; status?: 'draft' | 'scheduled' | 'active' | 'paused' | 'completed'; scheduledAt?: string }) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const campaign = await campaignsService.updateCampaign(id, request);
        update(s => ({
          ...s,
          campaigns: s.campaigns.map(c => c.id === id ? campaign : c),
          selectedCampaign: s.selectedCampaign?.id === id ? campaign : s.selectedCampaign,
          loading: false
        }));
        return campaign;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async deleteCampaign(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        await campaignsService.deleteCampaign(id);
        update(s => ({
          ...s,
          campaigns: s.campaigns.filter(c => c.id !== id),
          loading: false
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async startCampaign(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const campaign = await campaignsService.startCampaign(id);
        update(s => ({
          ...s,
          campaigns: s.campaigns.map(c => c.id === id ? campaign : c),
          loading: false
        }));
        return campaign;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async pauseCampaign(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const campaign = await campaignsService.pauseCampaign(id);
        update(s => ({
          ...s,
          campaigns: s.campaigns.map(c => c.id === id ? campaign : c),
          loading: false
        }));
        return campaign;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    selectCampaign(campaign: Campaign | null) {
      update(s => ({ ...s, selectedCampaign: campaign }));
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const campaignsStore = createCampaignsStore();

export const campaigns = derived(campaignsStore, $s => $s.campaigns);
export const selectedCampaign = derived(campaignsStore, $s => $s.selectedCampaign);
export const campaignsLoading = derived(campaignsStore, $s => $s.loading);
export const campaignsError = derived(campaignsStore, $s => $s.error);
export const activeCampaigns = derived(campaignsStore, $s => $s.campaigns.filter(c => c.status === 'active'));
