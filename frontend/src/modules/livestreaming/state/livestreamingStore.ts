import { writable, derived } from 'svelte/store';
import type { StreamState, Stream, LiveComment } from '../contracts/livestreamingTypes';
import { livestreamingService } from '../services/livestreamingService';

const initialState: StreamState = {
  streams: [],
  currentStream: null,
  comments: [],
  loading: false,
  error: null
};

function createLivestreamingStore() {
  const { subscribe, set, update } = writable<StreamState>(initialState);

  return {
    subscribe,

    async loadStreams() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const streams = await livestreamingService.getStreams();
        update(s => ({ ...s, streams, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadLiveStreams() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const streams = await livestreamingService.getLiveStreams();
        update(s => ({ ...s, streams, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadStream(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const stream = await livestreamingService.getStream(id);
        update(s => ({ ...s, currentStream: stream, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async createStream(title: string, description?: string, tags?: string[]) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const stream = await livestreamingService.createStream({ title, description, tags });
        update(s => ({ ...s, streams: [...s.streams, stream], currentStream: stream, loading: false }));
        return stream;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async startStream(streamId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const stream = await livestreamingService.startStream(streamId);
        update(s => ({ ...s, currentStream: stream, loading: false }));
        return stream;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async endStream(streamId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const stream = await livestreamingService.endStream(streamId);
        update(s => ({ ...s, currentStream: stream, loading: false }));
        return stream;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadComments(streamId: string) {
      try {
        const comments = await livestreamingService.getComments(streamId);
        update(s => ({ ...s, comments }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message }));
      }
    },

    async postComment(streamId: string, message: string) {
      try {
        const comment = await livestreamingService.postComment(streamId, message);
        update(s => ({ ...s, comments: [...s.comments, comment] }));
        return comment;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message }));
        throw error;
      }
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const livestreamingStore = createLivestreamingStore();

export const streams = derived(livestreamingStore, $s => $s.streams);
export const liveStreams = derived(livestreamingStore, $s => $s.streams.filter(s => s.status === 'live'));
export const currentStream = derived(livestreamingStore, $s => $s.currentStream);
export const comments = derived(livestreamingStore, $s => $s.comments);
export const livestreamingLoading = derived(livestreamingStore, $s => $s.loading);
export const livestreamingError = derived(livestreamingStore, $s => $s.error);
