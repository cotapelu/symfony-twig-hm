import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  storiesStore,
  stories,
  currentStory,
  loading,
  error
} from './storiesStore';
import { storiesService } from '../services/storiesService';
import type { Story } from '../contracts/storiesTypes';

function getCurrentState() {
  let current: any;
  const unsub = storiesStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();
const expires = new Date(Date.now() + 86400000);

const mockStory = (overrides: Partial<Story> = {}): Story => ({
  id: `story-${Math.random().toString(36).substr(2, 5)}`,
  authorId: 'user-1',
  media: {
    url: `https://example.com/${Date.now()}.jpg`,
    type: 'image' as const
  },
  timestamp: now,
  expiresAt: expires,
  viewers: ['user-2'],
  ...overrides
});

const mockMedia = (type: 'image' | 'video' = 'image') => ({
  url: `https://example.com/${Date.now()}.jpg`,
  type,
  duration: type === 'video' ? 15 : undefined
});

describe('StoriesStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    storiesStore.reset();
  });

  describe('initial state', () => {
    it('has empty stories and null currentStory', () => {
      const state = getCurrentState();
      expect(state.stories).toEqual([]);
      expect(state.currentStory).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stories matches state.stories', () => {
      let storiesList: Story[] = [];
      const unsub = stories.subscribe(s => { storiesList = s; });
      expect(storiesList).toEqual([]);
      unsub();
    });

    it('derived currentStory matches state.currentStory', () => {
      let curr: Story | null = null;
      const unsub = currentStory.subscribe(s => { curr = s; });
      expect(curr).toBeNull();
      unsub();
    });

    it('derived loading and error', () => {
      let load = false, err: string | null = null;
      const unsubL = loading.subscribe(s => { load = s; });
      const unsubE = error.subscribe(s => { err = s; });
      expect(load).toBe(false);
      expect(err).toBeNull();
      unsubL(); unsubE();
    });
  });

  describe('loadStories', () => {
    it('loads stories and updates state', async () => {
      const storyList = [mockStory(), mockStory()];
      vi.spyOn(storiesService, 'getStories').mockResolvedValue(storyList);
      await storiesStore.loadStories();
      const state = getCurrentState();
      expect(state.stories).toEqual(storyList);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(storiesService, 'getStories').mockReturnValue(p as any);
      const loadPromise = storiesStore.loadStories();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockStory()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates stories derived on success', async () => {
      const storyList = [mockStory()];
      vi.spyOn(storiesService, 'getStories').mockResolvedValue(storyList);
      await storiesStore.loadStories();
      let s: Story[] = [];
      const unsub = stories.subscribe(val => { s = val; });
      expect(s).toEqual(storyList);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(storiesService, 'getStories').mockResolvedValue([]);
      await storiesStore.loadStories();
      expect(getCurrentState().stories).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(storiesService, 'getStories').mockRejectedValue(new Error('Network'));
      await storiesStore.loadStories();
      const state = getCurrentState();
      expect(state.error).toBe('Network');
      expect(state.loading).toBe(false);
      expect(state.stories).toEqual([]);
    });
  });

  describe('loadStory', () => {
    it('loads story and sets as currentStory', async () => {
      const story = mockStory({ id: 's1' });
      vi.spyOn(storiesService, 'getStory').mockResolvedValue(story);
      await storiesStore.loadStory('s1');
      const state = getCurrentState();
      expect(state.currentStory).toEqual(story);
    });

    it('handles error', async () => {
      vi.spyOn(storiesService, 'getStory').mockRejectedValue(new Error('Not found'));
      try {
        await storiesStore.loadStory('missing');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Not found');
    });
  });

  describe('createStory', () => {
    it('creates story and adds to list', async () => {
      const newStory = mockStory({ id: 'new-1' });
      vi.spyOn(storiesService, 'createStory').mockResolvedValue(newStory);
      const media = mockMedia();
      const result = await storiesStore.createStory(media);
      expect(result).toEqual(newStory);
      let state = getCurrentState();
      expect(state.stories[0]).toEqual(newStory);
    });

    it('propagates errors', async () => {
      vi.spyOn(storiesService, 'createStory').mockRejectedValue(new Error('Validation'));
      try {
        await storiesStore.createStory(mockMedia());
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Validation');
    });
  });

  describe('deleteStory', () => {
    it('removes story from list', async () => {
      const story = mockStory({ id: 's1' });
      vi.spyOn(storiesService, 'getStories').mockResolvedValue([story]);
      await storiesStore.loadStories();
      let state = getCurrentState();
      expect(state.stories.some(s => s.id === 's1')).toBe(true);
      vi.spyOn(storiesService, 'deleteStory').mockResolvedValue(undefined);
      await storiesStore.deleteStory('s1');
      state = getCurrentState();
      expect(state.stories.find(s => s.id === 's1')).toBeUndefined();
    });

    it('handles error', async () => {
      vi.spyOn(storiesService, 'getStories').mockResolvedValue([mockStory()]);
      await storiesStore.loadStories();
      vi.spyOn(storiesService, 'deleteStory').mockRejectedValue(new Error('Delete failed'));
      try {
        await storiesStore.deleteStory('s1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Delete failed');
    });
  });

  describe('viewStory', () => {
    it('adds current user to viewers', async () => {
      const story = mockStory({ id: 's1', viewers: ['user-2'] });
      vi.spyOn(storiesService, 'getStories').mockResolvedValue([story]);
      await storiesStore.loadStories();
      vi.spyOn(storiesService, 'viewStory').mockResolvedValue(undefined);
      await storiesStore.viewStory('s1');
      const state = getCurrentState();
      const updated = state.stories.find(s => s.id === 's1');
      expect(updated?.viewers).toContain('currentUser');
    });

    it('does not duplicate viewer', async () => {
      const story = mockStory({ id: 's1', viewers: ['currentUser'] });
      vi.spyOn(storiesService, 'getStories').mockResolvedValue([story]);
      await storiesStore.loadStories();
      vi.spyOn(storiesService, 'viewStory').mockResolvedValue(undefined);
      await storiesStore.viewStory('s1');
      const state = getCurrentState();
      const updated = state.stories.find(s => s.id === 's1');
      expect(updated?.viewers.filter(v => v === 'currentUser').length).toBe(1);
    });

    it('handles service error without throwing', async () => {
      const story = mockStory();
      vi.spyOn(storiesService, 'getStories').mockResolvedValue([story]);
      await storiesStore.loadStories();
      vi.spyOn(storiesService, 'viewStory').mockRejectedValue(new Error('Fail'));
      await storiesStore.viewStory('s1'); // should not throw
      // state should remain unchanged (viewers not added)
      const state = getCurrentState();
      expect(state.stories[0].viewers).not.toContain('currentUser');
    });
  });

  describe('setCurrentStory', () => {
    it('sets currentStory', async () => {
      const story = mockStory();
      storiesStore.setCurrentStory(story);
      let state = getCurrentState();
      expect(state.currentStory).toEqual(story);
      storiesStore.setCurrentStory(null);
      state = getCurrentState();
      expect(state.currentStory).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(storiesService, 'getStories').mockRejectedValue(new Error('test'));
      try { await storiesStore.loadStories(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      storiesStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const storyList = [mockStory()];
      vi.spyOn(storiesService, 'getStories').mockResolvedValue(storyList);
      await storiesStore.loadStories();
      let state = getCurrentState();
      expect(state.stories.length).toBeGreaterThan(0);
      storiesStore.reset();
      state = getCurrentState();
      expect(state.stories).toEqual([]);
      expect(state.currentStory).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
