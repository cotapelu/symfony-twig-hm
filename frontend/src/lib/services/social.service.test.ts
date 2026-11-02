import { describe, it, expect, vi, beforeEach } from 'vitest';
import { socialService } from './social';
import { getApiUrl } from '../config/api';

// Mock the api config
vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock localStorage
const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('SocialService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getFeed', () => {
    it('fetches feed and returns posts array', async () => {
      const mockPosts = [{ id: '1', content: 'Hello' }];
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ posts: mockPosts }),
      });

      const result = await socialService.getFeed();

      expect(getApiUrl).toHaveBeenCalledWith('social', '/api/social/feed');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        headers: expect.objectContaining({
          'Content-Type': 'application/json',
          Authorization: 'Bearer test-token',
        }),
      });
      expect(result).toEqual(mockPosts);
    });

    it('returns empty array if no posts', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await socialService.getFeed();
      expect(result).toEqual([]);
    });

    it('throws on fetch error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(socialService.getFeed()).rejects.toThrow('Failed to fetch feed');
    });
  });

  describe('createPost', () => {
    it('creates a post with content and optional images', async () => {
      const newPost = { id: 'p1', content: 'New', images: ['img1'] };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => newPost });

      const result = await socialService.createPost('New', ['img1']);

      expect(getApiUrl).toHaveBeenCalledWith('social', '/api/social/posts');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({
          'Content-Type': 'application/json',
          Authorization: 'Bearer test-token',
        }),
        body: JSON.stringify({ content: 'New', images: ['img1'] }),
      });
      expect(result).toEqual(newPost);
    });

    it('creates post without images', async () => {
      const newPost = { id: 'p1', content: 'No images' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => newPost });

      const result = await socialService.createPost('No images');

      const callBody = mockFetch.mock.calls[0][1].body;
      expect(callBody).toBe(JSON.stringify({ content: 'No images' }));
      expect(result).toEqual(newPost);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(socialService.createPost('Fail')).rejects.toThrow('Failed to create post');
    });
  });

  describe('likePost', () => {
    it('posts like to endpoint', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });

      await socialService.likePost('p1');

      expect(getApiUrl).toHaveBeenCalledWith('social', '/api/social/posts/p1/like');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
    });
  });

  describe('commentPost', () => {
    it('posts comment and returns comment', async () => {
      const comment = { id: 'c1', postId: 'p1', content: 'Nice' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => comment });

      const result = await socialService.commentPost('p1', 'Nice');

      expect(getApiUrl).toHaveBeenCalledWith('social', '/api/social/posts/p1/comments');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ content: 'Nice' }),
      });
      expect(result).toEqual(comment);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(socialService.commentPost('p1', 'Fail')).rejects.toThrow('Failed to comment');
    });
  });

  describe('getGroups', () => {
    it('fetches groups', async () => {
      const groups = [{ id: 'g1', name: 'Test Group' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ groups }) });

      const result = await socialService.getGroups();

      expect(getApiUrl).toHaveBeenCalledWith('social', '/api/groups');
      expect(result).toEqual(groups);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await socialService.getGroups();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(socialService.getGroups()).rejects.toThrow('Failed to fetch groups');
    });
  });

  describe('joinGroup', () => {
    it('posts join request', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });

      await socialService.joinGroup('g1');

      expect(getApiUrl).toHaveBeenCalledWith('social', '/api/groups/g1/join');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
    });
  });
});
