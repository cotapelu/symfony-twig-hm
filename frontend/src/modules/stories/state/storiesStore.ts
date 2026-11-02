import { writable, derived } from 'svelte/store';
import { storiesService } from '../services/storiesService';

export interface Story {
  id: string;
  authorId: string;
  media: {
    url: string;
    type: 'image' | 'video';
    duration?: number;
  };
  timestamp: Date;
  expiresAt: Date;
  viewers: string[];
}

export interface StoriesState {
  stories: Story[];
  currentStory: Story | null;
  loading: boolean;
  error: string | null;
}

function createStoriesStore() {
  const { subscribe, set, update } = writable<StoriesState>({
    stories: [],
    currentStory: null,
    loading: false,
    error: null
  });

  return {
    subscribe,
    async loadStories() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info('[StoriesStore] Loading stories');
        const stories = await storiesService.getStories();
        update(s => ({ ...s, stories, loading: false }));
        console.info(`[StoriesStore] Loaded ${stories.length} stories`);
      } catch (e) {
        console.error('[StoriesStore] Failed to load stories:', e);
        update(s => ({ ...s, error: (e as Error).message, loading: false }));
      }
    },
    async loadStory(storyId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[StoriesStore] Loading story: ${storyId}`);
        const story = await storiesService.getStory(storyId);
        update(s => ({ ...s, currentStory: story, loading: false }));
        console.info(`[StoriesStore] Loaded story: ${storyId}`);
      } catch (e) {
        console.error('[StoriesStore] Failed to load story:', e);
        update(s => ({ ...s, error: (e as Error).message, loading: false }));
      }
    },
    async createStory(media: { url: string; type: 'image' | 'video'; duration?: number }) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info('[StoriesStore] Creating story');
        const story = await storiesService.createStory(media);
        update(s => ({ ...s, stories: [...s.stories, story], loading: false }));
        console.info('[StoriesStore] Story created');
        return story;
      } catch (e) {
        console.error('[StoriesStore] Failed to create story:', e);
        update(s => ({ ...s, error: (e as Error).message, loading: false }));
        throw e;
      }
    },
    async deleteStory(storyId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[StoriesStore] Deleting story: ${storyId}`);
        await storiesService.deleteStory(storyId);
        update(s => ({
          ...s,
          stories: s.stories.filter(story => story.id !== storyId),
          loading: false
        }));
        console.info(`[StoriesStore] Story deleted: ${storyId}`);
      } catch (e) {
        console.error('[StoriesStore] Failed to delete story:', e);
        update(s => ({ ...s, error: (e as Error).message, loading: false }));
        throw e;
      }
    },
    async viewStory(storyId: string) {
      try {
        await storiesService.viewStory(storyId);
        update(s => ({
          ...s,
          stories: s.stories.map(story =>
            story.id === storyId
              ? { ...story, viewers: story.viewers.includes('currentUser') ? story.viewers : [...story.viewers, 'currentUser'] }
              : story
          )
        }));
      } catch (e) {
        console.warn('[StoriesStore] Failed to mark story as viewed:', e);
      }
    },
    setCurrentStory(story: Story | null) {
      update(s => ({ ...s, currentStory: story }));
    },
    clearError() {
      update(s => ({ ...s, error: null }));
    },
    reset() {
      set({ stories: [], currentStory: null, loading: false, error: null });
    }
  };
}

export const storiesStore = createStoriesStore();
export const stories = derived(storiesStore, $state => $state.stories);
export const currentStory = derived(storiesStore, $state => $state.currentStory);
export const loading = derived(storiesStore, $state => $state.loading);
export const error = derived(storiesStore, $state => $state.error);