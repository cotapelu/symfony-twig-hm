import { writable, derived } from 'svelte/store';
import type { VolunteerState } from '../contracts/volunteerTypes';
import { volunteerService } from '../services/volunteerService';

const initialState: VolunteerState = {
  opportunities: [],
  loading: false,
  error: null,
  pagination: { page: 1, limit: 20, total: 0, totalPages: 0 }
};

function createVolunteerStore() {
  const { subscribe, set, update } = writable<VolunteerState>(initialState);
  return {
    subscribe,
    async loadOpportunities(page: number = 1, limit: number = 20) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[VolunteerStore] Loading opportunities: page=${page}, limit=${limit}`);
        const response = await volunteerService.getOpportunities(page, limit);
        update(s => ({
          ...s,
          opportunities: response.items,
          loading: false,
          pagination: {
            page: response.page,
            limit: response.limit,
            total: response.total,
            totalPages: response.totalPages
          }
        }));
        console.info(`[VolunteerStore] Loaded ${response.items.length} opportunities, total=${response.total}`);
      } catch (error) {
        console.error('[VolunteerStore] Failed to load opportunities:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async apply(opportunityId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[VolunteerStore] Applying to opportunity: ${opportunityId}`);
        await volunteerService.apply(opportunityId);
        update(s => ({ ...s, loading: false }));
        console.info(`[VolunteerStore] Applied to opportunity: ${opportunityId}`);
      } catch (error) {
        console.error('[VolunteerStore] Failed to apply:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const volunteerStore = createVolunteerStore();
export const volunteerOpportunities = derived(volunteerStore, $s => $s.opportunities);
export const volunteerLoading = derived(volunteerStore, $s => $s.loading);
export const volunteerPagination = derived(volunteerStore, $s => $s.pagination);
