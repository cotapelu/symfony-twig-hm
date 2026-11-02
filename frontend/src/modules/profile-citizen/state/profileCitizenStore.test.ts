import { describe, it, expect, vi, beforeEach } from 'vitest';
import { profileCitizenStore, citizenProfile, profileCitizenLoading } from './profileCitizenStore';
import { ProfileCitizenService } from '../services/profileCitizenService';
import type { CitizenProfile } from '../contracts/profileCitizenTypes';

function getCurrentState() {
  let current: any;
  const unsub = profileCitizenStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockProfile = (overrides: Partial<CitizenProfile> = {}): CitizenProfile => ({
  id: `user-${Math.random().toString(36).substr(2, 5)}`,
  name: 'John Doe',
  email: 'john@example.com',
  phone: '123-456-7890',
  address: '123 Main St',
  dateOfBirth: new Date(now.getFullYear() - 30, now.getMonth(), now.getDate()),
  createdAt: now,
  updatedAt: now,
  ...overrides
});

describe('ProfileCitizenStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    profileCitizenStore.reset();
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.profile).toBeNull();
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
  });

  it('derived stores', () => {
    let p: CitizenProfile | null = null, l = false;
    const unsubP = citizenProfile.subscribe(s => { p = s; });
    const unsubL = profileCitizenLoading.subscribe(s => { l = s; });
    expect(p).toBeNull();
    expect(l).toBe(false);
    unsubP(); unsubL();
  });

  describe('loadProfile', () => {
    it('loads profile', async () => {
      const profile = mockProfile();
      vi.spyOn(ProfileCitizenService.prototype, 'getProfile').mockResolvedValue(profile);
      await profileCitizenStore.loadProfile();
      const state = getCurrentState();
      expect(state.profile).toEqual(profile);
      expect(state.loading).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(ProfileCitizenService.prototype, 'getProfile').mockRejectedValue(new Error('Failed to load profile'));
      await profileCitizenStore.loadProfile();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load profile');
      expect(state.profile).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(ProfileCitizenService.prototype, 'getProfile').mockRejectedValue(new Error('err'));
      await profileCitizenStore.loadProfile();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      profileCitizenStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(ProfileCitizenService.prototype, 'getProfile').mockResolvedValue(mockProfile());
      await profileCitizenStore.loadProfile();
      let state = getCurrentState();
      expect(state.profile).not.toBeNull();
 profileCitizenStore.reset();
      state = getCurrentState();
      expect(state.profile).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
