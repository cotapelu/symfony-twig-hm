import { writable, derived } from 'svelte/store';
import type { ExperiencesState } from '../contracts/experiencesTypes';
import { experiencesService } from '../services/experiencesService';

const initialState: ExperiencesState = { experiences: [], loading: false, error: null };

function createExperiencesStore() {
  const { subscribe, set, update } = writable<ExperiencesState>(initialState);
  return {
    subscribe,
    async loadExperiences() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const experiences = await experiencesService.getExperiences();
        update(s => ({ ...s, experiences, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const experiencesStore = createExperiencesStore();
export const experiences = derived(experiencesStore, $s => $s.experiences);
export const experiencesLoading = derived(experiencesStore, $s => $s.loading);
