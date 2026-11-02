import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  livestreamingStore,
  streams,
  liveStreams,
  currentStream,
  comments,
  livestreamingLoading,
  livestreamingError
} from './livestreamingStore';
import { livestreamingService } from '../services/livestreamingService';
import type { Stream, LiveComment, CreateStreamRequest } from '../contracts/livestreamingTypes';

function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date().toISOString();

const mockStream = (overrides: Partial<Stream> = {}): Stream => ({
  id: `stream-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  title: 'Test Stream',
  description: 'A test stream',
  status: 'live' as const,
  viewerCount: 0,
  totalViewers: 0,
  startedAt: now,
  tags: ['test'],
  ...overrides
});

const mockComment = (overrides: Partial<LiveComment> = {}): LiveComment => ({
  id: `comment-${Math.random().toString(36).substr(2, 5)}`,
  streamId: 'stream-1',
  userId: 'user-2',
  username: 'viewer',
  message: 'Hello!',
  timestamp: now,
  ...overrides
});

describe('LivestreamingStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    livestreamingStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and defaults', () => {
      const state = getCurrentState(livestreamingStore);
      expect(state.streams).toEqual([]);
      expect(state.currentStream).toBeNull();
      expect(state.comments).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived streams matches state.streams', () => expect(getCurrentState(streams)).toEqual([]));
    it('derived liveStreams filters by status', () => expect(getCurrentState(liveStreams)).toEqual([]));
    it('derived currentStream matches state.currentStream', () => expect(getCurrentState(currentStream)).toBeNull());
    it('derived comments matches state.comments', () => expect(getCurrentState(comments)).toEqual([]));
    it('derived livestreamingLoading matches state.loading', () => expect(getCurrentState(livestreamingLoading)).toBe(false));
    it('derived livestreamingError matches state.error', () => expect(getCurrentState(livestreamingError)).toBeNull());
  });

  describe('loadStreams', () => {
    it('loads all streams', async () => {
      const strms = [mockStream(), mockStream()];
      vi.spyOn(livestreamingService, 'getStreams').mockResolvedValue(strms);
      await livestreamingStore.loadStreams();
      const state = getCurrentState(livestreamingStore);
      expect(state.streams).toEqual(strms);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(livestreamingService, 'getStreams').mockRejectedValue(new Error('Failed'));
      await livestreamingStore.loadStreams();
      const state = getCurrentState(livestreamingStore);
      expect(state.error).toBe('Failed');
      expect(state.streams).toEqual([]);
    });
  });

  describe('loadLiveStreams', () => {
    it('loads live streams only', async () => {
      const live = mockStream({ status: 'live' as const });
      const ended = mockStream({ status: 'ended' as const });
      vi.spyOn(livestreamingService, 'getLiveStreams').mockResolvedValue([live, ended]);
      await livestreamingStore.loadLiveStreams();
      const state = getCurrentState(livestreamingStore);
      expect(state.streams).toEqual([live, ended]); // loads into streams
      const liveList = getCurrentState(liveStreams);
      expect(liveList).toContainEqual(live);
      expect(liveList.length).toBe(1);
    });

    it('handles error', async () => {
      vi.spyOn(livestreamingService, 'getLiveStreams').mockRejectedValue(new Error('Error'));
      await livestreamingStore.loadLiveStreams();
      const state = getCurrentState(livestreamingStore);
      expect(state.error).toBe('Error');
    });
  });

  describe('loadStream', () => {
    it('loads a stream and sets as currentStream', async () => {
      const strm = mockStream();
      vi.spyOn(livestreamingService, 'getStream').mockResolvedValue(strm);
      await livestreamingStore.loadStream('stream-1');
      const state = getCurrentState(livestreamingStore);
      expect(state.currentStream).toEqual(strm);
    });

    it('handles error', async () => {
      vi.spyOn(livestreamingService, 'getStream').mockRejectedValue(new Error('Not found'));
      await livestreamingStore.loadStream('missing');
      const state = getCurrentState(livestreamingStore);
      expect(state.error).toBe('Not found');
    });
  });

  describe('createStream', () => {
    it('creates stream and adds to streams and currentStream', async () => {
      const strm = mockStream();
      vi.spyOn(livestreamingService, 'createStream').mockResolvedValue(strm);
      const request: CreateStreamRequest = { title: 'New Stream', description: '...', tags: ['tag'] };
      const result = await livestreamingStore.createStream(request);
      expect(result).toEqual(strm);
      let state = getCurrentState(livestreamingStore);
      expect(state.streams).toContainEqual(strm);
      expect(state.currentStream).toEqual(strm);
    });

    it('propagates errors', async () => {
      vi.spyOn(livestreamingService, 'createStream').mockRejectedValue(new Error('Validation'));
      try {
        await livestreamingStore.createStream({ title: '' });
      } catch {}
      const state = getCurrentState(livestreamingStore);
      expect(state.error).toBe('Validation');
    });
  });

  describe('startStream', () => {
    it('starts stream and updates currentStream', async () => {
      const started = mockStream({ status: 'live' as const, startedAt: now });
      vi.spyOn(livestreamingService, 'startStream').mockResolvedValue(started);
      const result = await livestreamingStore.startStream('stream-1');
      expect(result).toEqual(started);
      const state = getCurrentState(livestreamingStore);
      expect(state.currentStream).toEqual(started);
    });

    it('handles error', async () => {
      vi.spyOn(livestreamingService, 'startStream').mockRejectedValue(new Error('Cannot start'));
      try {
        await livestreamingStore.startStream('stream-1');
      } catch {}
      const state = getCurrentState(livestreamingStore);
      expect(state.error).toBe('Cannot start');
    });
  });

  describe('endStream', () => {
    it('ends stream and updates currentStream', async () => {
      const ended = mockStream({ status: 'ended' as const, endedAt: now });
      vi.spyOn(livestreamingService, 'endStream').mockResolvedValue(ended);
      const result = await livestreamingStore.endStream('stream-1');
      expect(result).toEqual(ended);
      const state = getCurrentState(livestreamingStore);
      expect(state.currentStream).toEqual(ended);
    });

    it('handles error', async () => {
      vi.spyOn(livestreamingService, 'endStream').mockRejectedValue(new Error('Cannot end'));
      try {
        await livestreamingStore.endStream('stream-1');
      } catch {}
      const state = getCurrentState(livestreamingStore);
      expect(state.error).toBe('Cannot end');
    });
  });

  describe('loadComments', () => {
    it('loads comments for stream', async () => {
      const cmts = [mockComment(), mockComment()];
      vi.spyOn(livestreamingService, 'getComments').mockResolvedValue(cmts);
      await livestreamingStore.loadComments('stream-1');
      const state = getCurrentState(livestreamingStore);
      expect(state.comments).toEqual(cmts);
    });

    it('handles error', async () => {
      vi.spyOn(livestreamingService, 'getComments').mockRejectedValue(new Error('Fail'));
      await livestreamingStore.loadComments('stream-1');
      const state = getCurrentState(livestreamingStore);
      expect(state.error).toBe('Fail');
    });
  });

  describe('postComment', () => {
    it('adds comment to list', async () => {
      const cmt = mockComment();
      vi.spyOn(livestreamingService, 'postComment').mockResolvedValue(cmt);
      await livestreamingStore.postComment('stream-1', 'Great!');
      const state = getCurrentState(livestreamingStore);
      expect(state.comments).toContainEqual(cmt);
    });

    it('handles error', async () => {
      vi.spyOn(livestreamingService, 'postComment').mockRejectedValue(new Error('Failed'));
      try {
        await livestreamingStore.postComment('stream-1', 'spam');
      } catch {}
      const state = getCurrentState(livestreamingStore);
      expect(state.error).toBe('Failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(livestreamingService, 'getStreams').mockRejectedValue(new Error('test'));
      try { await livestreamingStore.loadStreams(); } catch {}
      let state = getCurrentState(livestreamingStore);
      expect(state.error).toBe('test');
      livestreamingStore.clearError();
      state = getCurrentState(livestreamingStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const strms = [mockStream()];
      vi.spyOn(livestreamingService, 'getStreams').mockResolvedValue(strms);
      await livestreamingStore.loadStreams();
      let state = getCurrentState(livestreamingStore);
      expect(state.streams.length).toBeGreaterThan(0);
      livestreamingStore.reset();
      state = getCurrentState(livestreamingStore);
      expect(state.streams).toEqual([]);
      expect(state.currentStream).toBeNull();
      expect(state.comments).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
