import { writable, derived } from 'svelte/store';

export interface Profile {
  id: string;
  name: string;
  email: string;
  avatar?: string;
  bio?: string;
  phone?: string;
  location?: string;
  website?: string;
  socialLinks?: Record<string, string>;
  createdAt: Date;
  updatedAt: Date;
}

export interface ProfileState {
  profile: Profile | null;
  loading: boolean;
  error: string | null;
  editing: boolean;
}

export const profileStore = writable<ProfileState>({
  profile: null,
  loading: false,
  error: null,
  editing: false
});

export const profile = derived(profileStore, $state => $state.profile);
export const loading = derived(profileStore, $state => $state.loading);
export const error = derived(profileStore, $state => $state.error);
export const editing = derived(profileStore, $state => $state.editing);