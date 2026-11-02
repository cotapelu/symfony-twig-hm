import type { Story } from '../state/storiesStore';

const API_BASE = '/api/stories';

const DEFAULT_RETRY_COUNT = 3;
const DEFAULT_RETRY_DELAY = 1000;

// Configurable retry function for testing
let retryWithBackoffFn: typeof retryWithBackoff = retryWithBackoff;

export function setRetryWithBackoff(fn: typeof retryWithBackoff) {
  retryWithBackoffFn = fn;
}

export function resetRetryWithBackoff() {
  retryWithBackoffFn = retryWithBackoff;
}

async function retryWithBackoff<T>(
  operation: () => Promise<T>,
  maxRetries: number = DEFAULT_RETRY_COUNT,
  delay: number = DEFAULT_RETRY_DELAY
): Promise<T> {
  let lastError: Error | null = null;
  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      return await operation();
    } catch (error) {
      lastError = error as Error;
      console.warn(`[Stories] Attempt ${attempt + 1} failed:`, error);
      if (attempt < maxRetries - 1) {
        await new Promise(resolve => setTimeout(resolve, delay * Math.pow(2, attempt)));
      }
    }
  }
  throw lastError;
}

function parseStoryDates(story: Record<string, unknown>): Story {
  return {
    ...story,
    timestamp: new Date(story.timestamp as string),
    expiresAt: new Date(story.expiresAt as string)
  } as Story;
}

export class StoriesService {
  async getStories(): Promise<Story[]> {
    console.info('[Stories] Fetching stories');
    return retryWithBackoffFn(async () => {
      const response = await fetch(API_BASE);
      if (!response.ok) {
        throw new Error(`Failed to fetch stories: ${response.status}`);
      }
      const data = await response.json();
      const stories = (Array.isArray(data) ? data : data.items || []).map(parseStoryDates);
      console.info(`[Stories] Fetched ${stories.length} stories`);
      return stories;
    });
  }

  async getStory(storyId: string): Promise<Story> {
    console.info(`[Stories] Fetching story: ${storyId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/${storyId}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch story: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[Stories] Fetched story: ${storyId}`);
      return parseStoryDates(data);
    });
  }

  async createStory(media: { url: string; type: 'image' | 'video'; duration?: number }): Promise<Story> {
    console.info('[Stories] Creating story');
    return retryWithBackoffFn(async () => {
      const response = await fetch(API_BASE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(media)
      });
      if (!response.ok) {
        throw new Error(`Failed to create story: ${response.status}`);
      }
      const data = await response.json();
      console.info('[Stories] Story created');
      return parseStoryDates(data);
    });
  }

  async deleteStory(storyId: string): Promise<void> {
    console.info(`[Stories] Deleting story: ${storyId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/${storyId}`, {
        method: 'DELETE'
      });
      if (!response.ok) {
        throw new Error(`Failed to delete story: ${response.status}`);
      }
      console.info(`[Stories] Story deleted: ${storyId}`);
    });
  }

  async viewStory(storyId: string): Promise<void> {
    console.info(`[Stories] Marking story as viewed: ${storyId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/${storyId}/view`, {
        method: 'POST'
      });
      if (!response.ok) {
        throw new Error(`Failed to view story: ${response.status}`);
      }
      console.info(`[Stories] Story marked as viewed: ${storyId}`);
    });
  }
}

export const storiesService = new StoriesService();
export default storiesService;

// Exported for testing only
export const __testing__ = {
  clearCache: () => {},
  setRetryWithBackoff: (fn: typeof retryWithBackoff) => { retryWithBackoffFn = fn; },
  resetRetryWithBackoff: () => { retryWithBackoffFn = retryWithBackoff; }
};