import { writable, derived } from 'svelte/store';
import type { ProfileCitizenState } from '../contracts/profileCitizenTypes';
import { profileCitizenService } from '../services/profileCitizenService';

const initialState: ProfileCitizenState = { profile: null, loading: false, error: null };

function createProfileCitizenStore() {
  const { subscribe, set, update } = writable<ProfileCitizenState>(initialState);
  return {
    subscribe,
    async loadProfile() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const profile = await profileCitizenService.getProfile();
        update(s => ({ ...s, profile, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const profileCitizenStore = createProfileCitizenStore();
export const citizenProfile = derived(profileCitizenStore, $s => $s.profile);
export const profileCitizenLoading = derived(profileCitizenStore, $s => $s.loading);
