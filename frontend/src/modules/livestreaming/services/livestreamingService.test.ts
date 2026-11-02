import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { LivestreamingService } from './livestreamingService';
import { __testing__ } from './livestreamingService';

describe('LivestreamingService', () => {
  let service: LivestreamingService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new LivestreamingService();
  });

  describe('getStreams', () => {
    it('should fetch all streams', async () => {
      const streams = [{ id: 's1', title: 'Live Stream' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(streams) });

      const result = await service.getStreams();

      expect(result).toEqual(streams);
      expect(global.fetch).toHaveBeenCalledWith('/api/livestreaming/streams');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getStreams()).rejects.toThrow('Failed to fetch streams');
    });
  });

  describe('getLiveStreams', () => {
    it('should fetch live streams', async () => {
      const streams = [{ id: 's1', isLive: true }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(streams) });

      const result = await service.getLiveStreams();

      expect(result).toEqual(streams);
      expect(global.fetch).toHaveBeenCalledWith('/api/livestreaming/streams/live');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getLiveStreams()).rejects.toThrow('Failed to fetch live streams');
    });
  });

  describe('getStream', () => {
    it('should fetch stream by id', async () => {
      const stream = { id: 's1', title: 'Stream' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(stream) });

      const result = await service.getStream('s1');

      expect(result).toEqual(stream);
      expect(global.fetch).toHaveBeenCalledWith('/api/livestreaming/streams/s1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getStream('s1')).rejects.toThrow('Failed to fetch stream');
    });
  });

  describe('createStream', () => {
    const request = { title: 'New Stream', description: 'Description', isPrivate: false };

    it('should create stream successfully', async () => {
      const stream = { id: 's1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(stream) });

      const result = await service.createStream(request);

      expect(result).toEqual(stream);
      expect(global.fetch).toHaveBeenCalledWith('/api/livestreaming/streams', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"title":"New Stream"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createStream(request)).rejects.toThrow('Failed to create stream');
    });
  });

  describe('startStream', () => {
    it('should start stream', async () => {
      const stream = { id: 's1', status: 'live' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(stream) });

      const result = await service.startStream('s1');

      expect(result).toEqual(stream);
      expect(global.fetch).toHaveBeenCalledWith('/api/livestreaming/streams/s1/start', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.startStream('s1')).rejects.toThrow('Failed to start stream');
    });
  });

  describe('endStream', () => {
    it('should end stream', async () => {
      const stream = { id: 's1', status: 'ended' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(stream) });

      const result = await service.endStream('s1');

      expect(result).toEqual(stream);
      expect(global.fetch).toHaveBeenCalledWith('/api/livestreaming/streams/s1/end', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.endStream('s1')).rejects.toThrow('Failed to end stream');
    });
  });

  describe('getComments', () => {
    it('should fetch comments for stream', async () => {
      const comments = [{ id: 'c1', message: 'Hello' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(comments) });

      const result = await service.getComments('s1');

      expect(result).toEqual(comments);
      expect(global.fetch).toHaveBeenCalledWith('/api/livestreaming/streams/s1/comments');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getComments('s1')).rejects.toThrow('Failed to fetch comments');
    });
  });

  describe('postComment', () => {
    it('should post comment to stream', async () => {
      const comment = { id: 'c1', message: 'Hello', userId: 'u1' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(comment) });

      const result = await service.postComment('s1', 'Hello');

      expect(result).toEqual(comment);
      expect(global.fetch).toHaveBeenCalledWith('/api/livestreaming/streams/s1/comments', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"message":"Hello"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.postComment('s1', 'Hello')).rejects.toThrow('Failed to post comment');
    });
  });
});
