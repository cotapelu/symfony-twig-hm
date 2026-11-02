import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

global.fetch = vi.fn();

import { StoriesService, setRetryWithBackoff, resetRetryWithBackoff } from './storiesService';

// Suppress console logs
vi.spyOn(console, 'info').mockImplementation(() => {});
vi.spyOn(console, 'warn').mockImplementation(() => {});
vi.spyOn(console, 'error').mockImplementation(() => {});

describe('StoriesService', () => {
  let service: StoriesService;

  beforeEach(() => {
    // Mock the retry function to execute immediately without delays
    setRetryWithBackoff(vi.fn(async (operation: () => Promise<any>) => {
      return operation();
    }));

    vi.clearAllMocks();
    service = new StoriesService();
  });

  afterEach(() => {
    resetRetryWithBackoff();
    vi.restoreAllMocks();
  });

  describe('getStories', () => {
    it('should fetch stories list', async () => {
      const stories = [{ id: 's1', timestamp: '2024-01-01T00:00:00Z', expiresAt: '2024-01-02T00:00:00Z' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(stories) });

      const result = await service.getStories();

      expect(result).toHaveLength(1);
      expect(result[0]).toEqual({
        ...stories[0],
        timestamp: expect.any(Date),
        expiresAt: expect.any(Date)
      });
      expect(global.fetch).toHaveBeenCalledWith('/api/stories');
    });

    it('should handle paginated response', async () => {
      const paginated = { items: [{ id: 's2' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(paginated) });

      const result = await service.getStories();

      expect(result).toHaveLength(1);
      expect(result[0]).toMatchObject({ id: 's2' });
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getStories()).rejects.toThrow('Failed to fetch stories');
    });
  });

  describe('getStory', () => {
    it('should fetch single story', async () => {
      const story = { id: 's1', timestamp: '2024-01-01T00:00:00Z', expiresAt: '2024-01-02T00:00:00Z' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(story) });

      const result = await service.getStory('s1');

      expect(result).toMatchObject({ id: 's1' });
      expect(result.timestamp).toBeInstanceOf(Date);
      expect(result.expiresAt).toBeInstanceOf(Date);
      expect(global.fetch).toHaveBeenCalledWith('/api/stories/s1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getStory('s1')).rejects.toThrow('Failed to fetch story');
    });
  });

  describe('createStory', () => {
    const media = { url: 'https://example.com/image.jpg', type: 'image' as const };

    it('should create story successfully', async () => {
      const created = { id: 's2', ...media, timestamp: new Date(), expiresAt: new Date() };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(created) });

      const result = await service.createStory(media);

      expect(result).toEqual(created);
      expect(global.fetch).toHaveBeenCalledWith('/api/stories', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"url":"https://example.com/image.jpg"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createStory(media)).rejects.toThrow('Failed to create story');
    });
  });

  describe('deleteStory', () => {
    it('should delete story', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.deleteStory('s1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/stories/s1', expect.objectContaining({ method: 'DELETE' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.deleteStory('s1')).rejects.toThrow('Failed to delete story');
    });
  });

  describe('viewStory', () => {
    it('should mark story as viewed', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.viewStory('s1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/stories/s1/view', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.viewStory('s1')).rejects.toThrow('Failed to view story');
    });
  });
});