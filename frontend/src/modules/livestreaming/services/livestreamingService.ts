import type { Stream, StreamSession, LiveComment, StreamState, CreateStreamRequest } from '../contracts/livestreamingTypes';

const API_BASE = '/api/livestreaming';

export class LivestreamingService {
  async getStreams(): Promise<Stream[]> {
    const response = await fetch(`${API_BASE}/streams`);
    if (!response.ok) throw new Error('Failed to fetch streams');
    return response.json();
  }

  async getLiveStreams(): Promise<Stream[]> {
    const response = await fetch(`${API_BASE}/streams/live`);
    if (!response.ok) throw new Error('Failed to fetch live streams');
    return response.json();
  }

  async getStream(id: string): Promise<Stream> {
    const response = await fetch(`${API_BASE}/streams/${id}`);
    if (!response.ok) throw new Error('Failed to fetch stream');
    return response.json();
  }

  async createStream(request: CreateStreamRequest): Promise<Stream> {
    const response = await fetch(`${API_BASE}/streams`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create stream');
    return response.json();
  }

  async startStream(streamId: string): Promise<Stream> {
    const response = await fetch(`${API_BASE}/streams/${streamId}/start`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to start stream');
    return response.json();
  }

  async endStream(streamId: string): Promise<Stream> {
    const response = await fetch(`${API_BASE}/streams/${streamId}/end`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to end stream');
    return response.json();
  }

  async getComments(streamId: string): Promise<LiveComment[]> {
    const response = await fetch(`${API_BASE}/streams/${streamId}/comments`);
    if (!response.ok) throw new Error('Failed to fetch comments');
    return response.json();
  }

  async postComment(streamId: string, message: string): Promise<LiveComment> {
    const response = await fetch(`${API_BASE}/streams/${streamId}/comments`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message })
    });
    if (!response.ok) throw new Error('Failed to post comment');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const livestreamingService = new LivestreamingService();
export default livestreamingService;
