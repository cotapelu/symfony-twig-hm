import { describe, it, expect, vi, beforeEach } from 'vitest';
import { get } from 'svelte/store';
import { storiesStore } from './stories';

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock localStorage for auth token
const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
  userId: 'user123',
  removeItem: vi.fn(),
  setItem: vi.fn(),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('Stories Store', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    storiesStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.stories).toEqual([]);
      expect(state.userStories).toEqual([]);
      expect(state.publicStories).toEqual([]);
      expect(state.storyViewers).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('loadStories', () => {
    it('loads stories successfully', async () => {
      const mockStories = [
        { id: 's1', userId: 'u1', userName: 'Alice', content: 'Hello', reactions: [] },
      ];
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockStories,
      });

      await storiesStore.loadStories();

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.stories).toEqual(mockStories);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles fetch error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });

      await storiesStore.loadStories();

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.error).toBe('Failed to load stories');
      expect(state.loading).toBe(false);
    });

    it('sets loading true when called', async () => {
      const pendingPromise = new Promise(() => {});
      mockFetch.mockReturnValueOnce(pendingPromise);

      storiesStore.loadStories();

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.loading).toBe(true);
    });
  });

  describe('loadUserStories', () => {
    it('loads user stories successfully', async () => {
      const mockStories = [{ id: 's2', userId: 'u1', content: 'My story' }];
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockStories,
      });

      await storiesStore.loadUserStories();

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.userStories).toEqual(mockStories);
      expect(state.loading).toBe(false);
    });

    it('handles error for user stories', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await storiesStore.loadUserStories();
      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.error).toBe('Failed to load user stories');
    });
  });

  describe('loadPublicStories', () => {
    it('loads public stories successfully', async () => {
      const mockStories = [{ id: 's3', content: 'Public' }];
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockStories,
      });

      await storiesStore.loadPublicStories();

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.publicStories).toEqual(mockStories);
      expect(state.loading).toBe(false);
    });
  });

  describe('createStory', () => {
    it('creates a new story and adds to userStories', async () => {
      const newStory = { id: 's4', content: 'New' };
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => newStory,
      });

      await storiesStore.createStory({ files: [] });

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.userStories).toHaveLength(1);
      expect(state.userStories[0]).toEqual(newStory);
      expect(state.loading).toBe(false);
    });

    it('handles create error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(storiesStore.createStory({ files: [] })).rejects.toThrow();
      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.error).toBe('Failed to create story');
      expect(state.loading).toBe(false);
    });
  });

  describe('addReaction', () => {
    it('adds reaction to a story', async () => {
      const existingStory = { id: 's5', reactions: [] };
      storiesStore.set({ stories: [existingStory] });

      mockFetch.mockResolvedValueOnce({ ok: true });
      await storiesStore.addReaction({ storyId: 's5', emoji: '👍' });

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.stories[0].reactions).toHaveLength(1);
      expect(state.stories[0].reactions[0].emoji).toBe('👍');
    });

    it('handles error without changing state', async () => {
      const existingStory = { id: 's5', reactions: [] };
      storiesStore.set({ stories: [existingStory] });

      mockFetch.mockResolvedValueOnce({ ok: false });
      await storiesStore.addReaction({ storyId: 's5', emoji: '🔥' });

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.stories[0].reactions).toHaveLength(0);
    });
  });

  describe('removeReaction', () => {
    it('removes reaction from a story', async () => {
      const existingStory = { id: 's5', reactions: [{ emoji: '👍', userId: 'test-token' }] };
      storiesStore.set({ stories: [existingStory] });

      mockFetch.mockResolvedValueOnce({ ok: true });
      await storiesStore.removeReaction({ storyId: 's5' });

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.stories[0].reactions).toHaveLength(0);
    });
  });

  describe('viewStory', () => {
    it('posts view event without throwing', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });
      // Should not throw even if fetch fails
      await storiesStore.viewStory('s1');
      expect(mockFetch).toHaveBeenCalledWith('/api/stories/s1/view', expect.objectContaining({
        method: 'POST',
      }));
    });

    it('logs error but does not throw on failure', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      mockFetch.mockResolvedValueOnce({ ok: false });
      await storiesStore.viewStory('s1');
      expect(consoleSpy).toHaveBeenCalled();
      consoleSpy.mockRestore();
    });
  });

  describe('deleteStory', () => {
    it('removes story from userStories on success', async () => {
      storiesStore.set({ userStories: [{ id: 's1' }, { id: 's2' }] });

      mockFetch.mockResolvedValueOnce({ ok: true });
      await storiesStore.deleteStory('s1');

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.userStories).toHaveLength(1);
      expect(state.userStories[0].id).toBe('s2');
    });

    it('handles delete error', async () => {
      storiesStore.set({ userStories: [{ id: 's1' }] });
      mockFetch.mockResolvedValueOnce({ ok: false });

      await expect(storiesStore.deleteStory('s1')).rejects.toThrow('Failed to delete story');

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.userStories).toHaveLength(1); // still there
    });
  });

  describe('reset', () => {
    it('resets store to initial state', () => {
      storiesStore.set({
        stories: [{ id: 's1' }],
        userStories: [{ id: 's2' }],
        loading: true,
        error: 'Oops',
      });
      storiesStore.reset();

      let state: any;
      storiesStore.subscribe((value) => { state = value; });
      expect(state.stories).toEqual([]);
      expect(state.userStories).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
