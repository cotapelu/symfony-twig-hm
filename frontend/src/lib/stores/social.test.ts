import { describe, it, expect, vi, beforeEach } from 'vitest';
import { socialStore } from './social';

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
  removeItem: vi.fn(),
  setItem: vi.fn(),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('Social Store', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    socialStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.posts).toEqual([]);
      expect(state.comments).toEqual({});
      expect(state.reactions).toEqual({});
      expect(state.filter).toBe('all');
      expect(state.loading).toBe(false);
      expect(state.loadingMore).toBe(false);
      expect(state.hasMore).toBe(true);
      expect(state.error).toBeNull();
      expect(state.currentPage).toBe(1);
    });
  });

  describe('loadPosts', () => {
    it('loads first page successfully (10 items => hasMore true)', async () => {
      const mockPosts = Array.from({ length: 10 }, (_, i) => ({
        id: `p${i+1}`,
        userId: 'u1',
        userName: 'Alice',
        text: `Post ${i+1}`,
        likes: 0,
        isLiked: false,
        reactions: [],
        shares: 0,
        privacy: 'public',
        createdAt: new Date(),
      }));
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => mockPosts });

      await socialStore.loadPosts(1);

      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.posts).toEqual(mockPosts);
      expect(state.loading).toBe(false);
      expect(state.loadingMore).toBe(false);
      expect(state.hasMore).toBe(true);
      expect(state.currentPage).toBe(1);
    });

    it('loads first page with <10 items => hasMore false', async () => {
      const mockPosts = [{ id: 'p1', text: 'Only' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => mockPosts });

      await socialStore.loadPosts(1);
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.posts).toEqual(mockPosts);
      expect(state.hasMore).toBe(false);
    });

    it('appends posts on pagination', async () => {
      const page1 = Array.from({ length: 5 }, (_, i) => ({ id: `p${i+1}`, text: `P1-${i+1}` }));
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => page1 });
      await socialStore.loadPosts(1);

      const page2 = Array.from({ length: 3 }, (_, i) => ({ id: `p${i+6}`, text: `P2-${i+1}` }));
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => page2 });
      await socialStore.loadPosts(2);

      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.posts.length).toBe(8);
      expect(state.currentPage).toBe(2);
      expect(state.hasMore).toBe(false);
    });

    it('handles fetch error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });
      await socialStore.loadPosts();
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.error).toBe('Failed to load posts');
      expect(state.loading).toBe(false);
    });
  });

  describe('setFilter', () => {
    it('updates filter and resets currentPage to 1', () => {
      // Precondition: not first page
      socialStore.update(s => ({ ...s, currentPage: 2 }));
      socialStore.setFilter('friends');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.filter).toBe('friends');
      expect(state.currentPage).toBe(1);
    });

    // Note: posts are not cleared immediately; they are replaced when loadPosts completes.
  });

  describe('likePost', () => {
    it('likes an unliked post', async () => {
      const post = { id: 'p1', likes: 0, isLiked: false };
      socialStore.update(s => ({ ...s, posts: [post] }));
      mockFetch.mockResolvedValueOnce({ ok: true });
      await socialStore.likePost('p1');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.posts[0].isLiked).toBe(true);
      expect(state.posts[0].likes).toBe(1);
    });

    it('unlikes a liked post', async () => {
      const post = { id: 'p1', likes: 1, isLiked: true };
      socialStore.update(s => ({ ...s, posts: [post] }));
      mockFetch.mockResolvedValueOnce({ ok: true });
      await socialStore.likePost('p1');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.posts[0].isLiked).toBe(false);
      expect(state.posts[0].likes).toBe(0);
    });

    it('does not change state on error', async () => {
      const post = { id: 'p1', likes: 0, isLiked: false };
      socialStore.update(s => ({ ...s, posts: [post] }));
      mockFetch.mockResolvedValueOnce({ ok: false });
      await socialStore.likePost('p1');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.posts[0].isLiked).toBe(false);
      expect(state.posts[0].likes).toBe(0);
    });
  });

  describe('commentOnPost', () => {
    it('adds comment to post', async () => {
      const post = { id: 'p1', comments: [] };
      socialStore.update(s => ({ ...s, posts: [post] }));
      const newComment = { id: 'c1', userId: 'u1', userName: 'Bob', text: 'Nice!', createdAt: new Date(), likes: 0 };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => newComment });
      await socialStore.commentOnPost('p1', 'Nice!');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.comments['p1']).toHaveLength(1);
      expect(state.comments['p1'][0].text).toBe('Nice!');
      expect(state.posts[0].comments).toContainEqual(newComment);
    });

    it('handles add comment error', async () => {
      const post = { id: 'p1', comments: [] };
      socialStore.update(s => ({ ...s, posts: [post] }));
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(socialStore.commentOnPost('p1', 'Fail')).rejects.toThrow('Failed to add comment');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.comments['p1']).toBeUndefined();
      expect(state.posts[0].comments).toHaveLength(0);
    });
  });

  describe('loadComments', () => {
    it('loads comments for a post', async () => {
      const mockComments = [
        { id: 'c1', userId: 'u1', userName: 'A', text: 'First', createdAt: new Date(), likes: 0 },
      ];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => mockComments });
      // Seed a post so that loadComments also updates post.comments
      socialStore.update(s => ({
        ...s,
        posts: [{ id: 'p1', comments: [] }]
      }));
      await socialStore.loadComments('p1');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.comments['p1']).toEqual(mockComments);
      // posts[0].comments should be updated as well
      expect(state.posts[0].comments).toEqual(mockComments);
    });

    it('handles error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await socialStore.loadComments('p1');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.comments['p1']).toBeUndefined();
    });
  });

  describe('loadReactions', () => {
    it('loads reactions for a post', async () => {
      const mockReactions = [{ emoji: '👍', userId: 'u1' }, { emoji: '❤️', userId: 'u2' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => mockReactions });
      await socialStore.loadReactions('p1');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.reactions['p1']).toEqual(mockReactions);
    });

    it('handles error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await socialStore.loadReactions('p1');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.reactions['p1']).toBeUndefined();
    });
  });

  describe('likeComment', () => {
    it('increments like on comment', async () => {
      const comment = { id: 'c1', likes: 0 };
      socialStore.update(s => ({ ...s, comments: { 'p1': [comment] } }));
      mockFetch.mockResolvedValueOnce({ ok: true });
      await socialStore.likeComment('p1', 'c1');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.comments['p1'][0].likes).toBe(1);
    });

    it('handles error', async () => {
      const comment = { id: 'c1', likes: 0 };
      socialStore.update(s => ({ ...s, comments: { 'p1': [comment] } }));
      mockFetch.mockResolvedValueOnce({ ok: false });
      await socialStore.likeComment('p1', 'c1');
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.comments['p1'][0].likes).toBe(0);
    });
  });

  describe('reset', () => {
    it('resets to initial state', () => {
      socialStore.update(s => ({
        ...s,
        posts: [{ id: 'p1' }],
        comments: { 'p1': [{ id: 'c1' }] },
        filter: 'friends',
        loading: true,
        error: 'Oops',
        currentPage: 2,
      }));
      socialStore.reset();
      let state: any;
      socialStore.subscribe((s) => { state = s; });
      expect(state.posts).toEqual([]);
      expect(state.comments).toEqual({});
      expect(state.filter).toBe('all');
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.currentPage).toBe(1);
      expect(state.hasMore).toBe(true);
    });
  });
});
