import { describe, it, expect, vi, beforeEach } from 'vitest';
import { cultureSportsStore, cultureSportsEvents, cultureSportsLoading } from './cultureSportsStore';
import { cultureSportsService } from '../services/cultureSportsService';
import type { CulturalEvent } from '../contracts/cultureSportsTypes';

function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockEvent = (overrides: Partial<CulturalEvent> = {}): CulturalEvent => ({
  id: `cs-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test Event',
  description: 'A test cultural event',
  date: new Date().toISOString(),
  location: 'Test Venue',
  category: 'music',
  ...overrides
});

describe('CultureSportsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    cultureSportsStore.reset();
  });

  describe('initial state', () => {
    it('has empty events and defaults', () => {
      const state = getCurrentState(cultureSportsStore);
      expect(state.events).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived cultureSportsEvents matches state.events', () => {
      expect(getCurrentState(cultureSportsEvents)).toEqual([]);
    });

    it('derived cultureSportsLoading matches state.loading', () => {
      expect(getCurrentState(cultureSportsLoading)).toBe(false);
    });
  });

  describe('loadEvents', () => {
    it('loads events and updates state', async () => {
      const events = [mockEvent(), mockEvent()];
      vi.spyOn(cultureSportsService, 'getEvents').mockResolvedValue(events);
      await cultureSportsStore.loadEvents();
      const state = getCurrentState(cultureSportsStore);
      expect(state.events).toEqual(events);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(cultureSportsService, 'getEvents').mockRejectedValue(new Error('Fetch failed'));
      await cultureSportsStore.loadEvents();
      const state = getCurrentState(cultureSportsStore);
      expect(state.error).toBe('Fetch failed');
      expect(state.events).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(cultureSportsService, 'getEvents').mockRejectedValue(new Error('test'));
      try { await cultureSportsStore.loadEvents(); } catch {}
      let state = getCurrentState(cultureSportsStore);
      expect(state.error).toBe('test');
      cultureSportsStore.clearError();
      state = getCurrentState(cultureSportsStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const events = [mockEvent()];
      vi.spyOn(cultureSportsService, 'getEvents').mockResolvedValue(events);
      await cultureSportsStore.loadEvents();
      let state = getCurrentState(cultureSportsStore);
      expect(state.events.length).toBeGreaterThan(0);
      cultureSportsStore.reset();
      state = getCurrentState(cultureSportsStore);
      expect(state.events).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
