import { describe, it, expect, beforeEach } from 'vitest';
import { profileStore, profile, loading, error, editing } from './profileStore';
import type { Profile, ProfileState } from '../contracts/profileTypes';

function getCurrentState() {
  let current: any;
  const unsub = profileStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockProfile = (overrides: Partial<Profile> = {}): Profile => ({
  id: 'user-1',
  name: 'John Doe',
  email: 'john@example.com',
  avatar: '',
  bio: '',
  phone: '',
  location: '',
  website: '',
  socialLinks: {},
  createdAt: new Date(),
  updatedAt: new Date(),
  ...overrides
});

describe('ProfileStore', () => {
  beforeEach(() => {
    // Reset to initial
    profileStore.set({
      profile: null,
      loading: false,
      error: null,
      editing: false
    });
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.profile).toBeNull();
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
    expect(state.editing).toBe(false);
  });

  describe('derived stores', () => {
    it('profile derived', () => {
      let p: Profile | null = null;
      const unsub = profile.subscribe(s => { p = s; });
      expect(p).toBeNull();
      // Use a single profile instance
      const testProfile = mockProfile();
      profileStore.set({ ...getCurrentState(), profile: testProfile });
      // Re-subscribe to get updated value
      unsub();
      const unsub2 = profile.subscribe(s => { p = s; });
      expect(p).toBe(testProfile);
      unsub2();
    });

    it('loading derived', () => {
      let l = false;
      const unsub = loading.subscribe(s => { l = s; });
      expect(l).toBe(false);
      profileStore.set({ ...getCurrentState(), loading: true });
      expect(l).toBe(true);
      unsub();
    });

    it('error derived', () => {
      let e: string | null = null;
      const unsub = error.subscribe(s => { e = s; });
      expect(e).toBeNull();
      profileStore.set({ ...getCurrentState(), error: 'test error' });
      expect(e).toBe('test error');
      unsub();
    });

    it('editing derived', () => {
      let e = false;
      const unsub = editing.subscribe(s => { e = s; });
      expect(e).toBe(false);
      profileStore.set({ ...getCurrentState(), editing: true });
      expect(e).toBe(true);
      unsub();
    });
  });

  // Since there are no methods to test, we just cover state changes.
});
