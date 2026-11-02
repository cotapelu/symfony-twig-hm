import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  socialStore,
  posts,
  loading,
  error,
  newPostContent,
  selectedImages,
  loadPosts,
  createPost,
  likePost,
  unlikePost,
  addComment,
  setNewPostContent,
  setSelectedImages,
  clearError
} from './socialStore';
import type { Post, Comment } from '../contracts/socialTypes';

function getCurrentState() {
  let current: any;
  const unsub = socialStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockPost = (overrides: Partial<Post> = {}): Post => ({
  id: `post-${Math.random().toString(36).substr(2, 5)}`,
  authorId: 'user-1',
  content: 'Post content',
  images: [],
  likes: ['user-2'],
  comments: [],
  shares: 0,
  timestamp: now,
  privacy: 'public',
  ...overrides
});

const mockComment = (overrides: Partial<Comment> = {}): Comment => ({
  id: `comment-${Math.random().toString(36).substr(2, 5)}`,
  postId: 'post-1',
  authorId: 'user-2',
  content: 'Comment content',
  likes: [],
  timestamp: now,
  ...overrides
});

describe('SocialStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Reset store to initial state
    socialStore.set({
      posts: [],
      loading: false,
      error: null,
      newPostContent: '',
      selectedImages: []
    });
    global.fetch = vi.fn(); // will be configured per test
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.posts).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.newPostContent).toBe('');
      expect(state.selectedImages).toEqual([]);
    });

    it('derived stores', () => {
      let p: Post[] = [], l = false, e: string | null = '', npc = '', si: string[] = [];
      const unsubP = posts.subscribe(s => { p = s; });
      const unsubL = loading.subscribe(s => { l = s; });
      const unsubE = error.subscribe(s => { e = s; });
      const unsubNPC = newPostContent.subscribe(s => { npc = s; });
      const unsubSI = selectedImages.subscribe(s => { si = s; });
      expect(p).toEqual([]);
      expect(l).toBe(false);
      expect(e).toBeNull();
      expect(npc).toBe('');
      expect(si).toEqual([]);
      unsubP(); unsubL(); unsubE(); unsubNPC(); unsubSI();
    });
  });

  describe('loadPosts', () => {
    it('loads posts', async () => {
      const postsData = [mockPost(), mockPost()];
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(postsData)
      }) as any;
      await loadPosts();
      const state = getCurrentState();
      expect(state.posts).toEqual(postsData);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      global.fetch = vi.fn().mockReturnValueOnce({
        ok: true,
        json: () => p
      }) as any;
      const loadPromise = loadPosts();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockPost()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates posts derived on success', async () => {
      const postsData = [mockPost()];
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(postsData)
      }) as any;
      await loadPosts();
      let p: Post[] = [];
      const unsub = posts.subscribe(s => { p = s; });
      expect(p).toEqual(postsData);
      unsub();
    });

    it('handles empty response', async () => {
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve([])
      }) as any;
      await loadPosts();
      expect(getCurrentState().posts).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: false
      }) as any;
      await loadPosts();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load posts');
      expect(state.loading).toBe(false);
      expect(state.posts).toEqual([]);
    });
  });

  describe('createPost', () => {
    it('creates post and resets newPostContent and selectedImages', async () => {
      const newPost = mockPost({ id: 'new1', content: 'Hello' });
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(newPost)
      }) as any;
      // Set initial content and images
      setNewPostContent('Hello');
      setSelectedImages(['img1', 'img2']);
      // Create post
      const result = await createPost('Hello', ['img1', 'img2']);
      expect(result).toEqual(newPost);
      const state = getCurrentState();
      expect(state.posts[0]).toEqual(newPost);
      expect(state.newPostContent).toBe('');
      expect(state.selectedImages).toEqual([]);
    });

    it('propagates error', async () => {
      setNewPostContent('Hello');
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: false
      }) as any;
      try {
        await createPost('Hello', []);
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Failed to create post');
      // newPostContent and selectedImages should not be cleared on error
      expect(state.newPostContent).toBe('Hello'); // unchanged
    });
  });

  describe('likePost', () => {
    it('adds like for current user', async () => {
      const post = mockPost({ id: 'p1', likes: ['user-2'] });
      socialStore.update(state => ({ ...state, posts: [post] }));
      global.fetch = vi.fn().mockResolvedValueOnce({ ok: true }) as any;
      await likePost('p1');
      const state = getCurrentState();
      expect(state.posts[0].likes).toContain('current-user');
      expect(state.posts[0].likes).toHaveLength(2);
    });

    it('handles error', async () => {
      const post = mockPost({ id: 'p1', likes: [] });
      socialStore.update(state => ({ ...state, posts: [post] }));
      global.fetch = vi.fn().mockResolvedValueOnce({ ok: false }) as any;
      await likePost('p1');
      const state = getCurrentState();
      expect(state.error).toBe('Failed to like post');
    });
  });

  describe('unlikePost', () => {
    it('removes like for current user', async () => {
      const post = mockPost({ id: 'p1', likes: ['user-2', 'current-user'] });
      socialStore.update(state => ({ ...state, posts: [post] }));
      global.fetch = vi.fn().mockResolvedValueOnce({ ok: true }) as any;
      await unlikePost('p1');
      const state = getCurrentState();
      expect(state.posts[0].likes).not.toContain('current-user');
      expect(state.posts[0].likes).toHaveLength(1);
    });

    it('handles error', async () => {
      const post = mockPost({ id: 'p1', likes: [] });
      socialStore.update(state => ({ ...state, posts: [post] }));
      global.fetch = vi.fn().mockResolvedValueOnce({ ok: false }) as any;
      await unlikePost('p1');
      const state = getCurrentState();
      expect(state.error).toBe('Failed to unlike post');
    });
  });

  describe('addComment', () => {
    it('adds comment to post', async () => {
      const comment = mockComment({ id: 'c1', postId: 'p1', content: 'Nice' });
      const post = mockPost({ id: 'p1', comments: [] });
      socialStore.update(state => ({ ...state, posts: [post] }));
      global.fetch = vi.fn().mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(comment)
      }) as any;
      await addComment('p1', 'Nice');
      let state = getCurrentState();
      expect(state.posts[0].comments).toContainEqual(comment);
    });

    it('handles error', async () => {
      const post = mockPost({ id: 'p1', comments: [] });
      socialStore.update(state => ({ ...state, posts: [post] }));
      global.fetch = vi.fn().mockResolvedValueOnce({ ok: false }) as any;
      try {
        await addComment('p1', 'Nice');
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Failed to add comment');
    });
  });

  describe('setNewPostContent', () => {
    it('sets newPostContent', () => {
      setNewPostContent('Hello world');
      let state = getCurrentState();
      expect(state.newPostContent).toBe('Hello world');
    });
  });

  describe('setSelectedImages', () => {
    it('sets selectedImages', () => {
      setSelectedImages(['img1', 'img2']);
      let state = getCurrentState();
      expect(state.selectedImages).toEqual(['img1', 'img2']);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      global.fetch = vi.fn().mockResolvedValueOnce({ ok: false }) as any;
      await loadPosts();
      let state = getCurrentState();
      expect(state.error).toBe('Failed to load posts');
      clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });
});
