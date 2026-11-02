import { writable, derived } from 'svelte/store';
import type { VoicemailState } from '../contracts/voicemailTypes';
import { voicemailService } from '../services/voicemailService';

const initialState: VoicemailState = {
  voicemails: [],
  currentVoicemail: null,
  loading: false,
  error: null,
  pagination: { page: 1, limit: 20, total: 0, totalPages: 0 }
};

function createVoicemailStore() {
  const { subscribe, set, update } = writable<VoicemailState>(initialState);

  return {
    subscribe,
    async loadVoicemails(page: number = 1, limit: number = 20) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[VoicemailStore] Loading voicemails: page=${page}, limit=${limit}`);
        const response = await voicemailService.getVoicemails(page, limit);
        update(s => ({
          ...s,
          voicemails: response.items,
          loading: false,
          pagination: {
            page: response.page,
            limit: response.limit,
            total: response.total,
            totalPages: response.totalPages
          }
        }));
        console.info(`[VoicemailStore] Loaded ${response.items.length} voicemails, total=${response.total}`);
      } catch (error) {
        console.error('[VoicemailStore] Failed to load voicemails:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async loadVoicemail(voicemailId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[VoicemailStore] Loading voicemail: ${voicemailId}`);
        const voicemail = await voicemailService.getVoicemail(voicemailId);
        update(s => ({ ...s, currentVoicemail: voicemail, loading: false }));
        console.info(`[VoicemailStore] Loaded voicemail: ${voicemailId}`);
      } catch (error) {
        console.error('[VoicemailStore] Failed to load voicemail:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async markAsListened(voicemailId: string) {
      try {
        await voicemailService.markAsListened(voicemailId);
        update(s => ({
          ...s,
          voicemails: s.voicemails.map(v =>
            v.id === voicemailId ? { ...v, listened: true } : v
          )
        }));
        console.info(`[VoicemailStore] Voicemail marked as listened: ${voicemailId}`);
      } catch (error) {
        console.error('[VoicemailStore] Failed to mark as listened:', error);
        update(s => ({ ...s, error: (error as Error).message }));
      }
    },
    async deleteVoicemail(voicemailId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[VoicemailStore] Deleting voicemail: ${voicemailId}`);
        await voicemailService.deleteVoicemail(voicemailId);
        update(s => ({
          ...s,
          voicemails: s.voicemails.filter(v => v.id !== voicemailId),
          loading: false
        }));
        console.info(`[VoicemailStore] Voicemail deleted: ${voicemailId}`);
      } catch (error) {
        console.error('[VoicemailStore] Failed to delete voicemail:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    clearError() {
      update(s => ({ ...s, error: null }));
    },
    reset() {
      set(initialState);
    }
  };
}

export const voicemailStore = createVoicemailStore();
export const voicemails = derived(voicemailStore, $s => $s.voicemails);
export const voicemailLoading = derived(voicemailStore, $s => $s.loading);
export const voicemailPagination = derived(voicemailStore, $s => $s.pagination);