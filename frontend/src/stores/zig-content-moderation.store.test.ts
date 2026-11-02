import { describe, it, expect, vi, beforeEach } from 'vitest';

// Mock the API module before importing stores
vi.mock('../lib/api', () => ({
  Zig: {
    moderateText: vi.fn(),
    moderateImage: vi.fn(),
    moderateVideo: vi.fn(),
  }
}));

import { zigContentModerationStore } from './zig-stores';
import { Zig } from '../lib/api';

describe('ZigContentModerationStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    zigContentModerationStore.reset();
    zigContentModerationStore.subscribe(s => { state = s; });
  });

  const mockResult = (type: string, safe: boolean): any => ({
    type,
    safe,
    confidence: 0.9,
    moderatedAt: new Date().toISOString()
  });

  describe('moderateText', () => {
    it('should moderate text and store result', async () => {
      const result = mockResult('text', true);
      (Zig.moderateText as any).mockResolvedValue(result);

      const res = await zigContentModerationStore.moderateText('hello');

      expect(res).toEqual(result);
      expect(Object.keys(state.moderationResults).length).toBeGreaterThan(0);
    });

    it('should handle error', async () => {
      (Zig.moderateText as any).mockRejectedValue(new Error('Moderate failed'));
      await expect(zigContentModerationStore.moderateText('bad')).rejects.toThrow('Moderate failed');
      expect(state.error).toBe('Moderate failed');
    });
  });

  describe('moderateImage', () => {
    it('should moderate image and store result', async () => {
      const result = mockResult('image', false);
      (Zig.moderateImage as any).mockResolvedValue(result);
      const file = new File(['img'], 'pic.jpg', { type: 'image/jpeg' });

      const res = await zigContentModerationStore.moderateImage(file);

      expect(res).toEqual(result);
      expect(Object.keys(state.moderationResults).length).toBeGreaterThan(0);
    });

    it('should handle error', async () => {
      (Zig.moderateImage as any).mockRejectedValue(new Error('Image fail'));
      const file = new File(['img'], 'pic.jpg');
      await expect(zigContentModerationStore.moderateImage(file)).rejects.toThrow('Image fail');
      expect(state.error).toBe('Image fail');
    });
  });

  describe('moderateVideo', () => {
    it('should moderate video and store result', async () => {
      const result = mockResult('video', true);
      (Zig.moderateVideo as any).mockResolvedValue(result);
      const file = new File(['vid'], 'clip.mp4', { type: 'video/mp4' });

      const res = await zigContentModerationStore.moderateVideo(file);

      expect(res).toEqual(result);
      expect(Object.keys(state.moderationResults).length).toBeGreaterThan(0);
    });

    it('should handle error', async () => {
      (Zig.moderateVideo as any).mockRejectedValue(new Error('Video fail'));
      const file = new File(['vid'], 'clip.mp4');
      await expect(zigContentModerationStore.moderateVideo(file)).rejects.toThrow('Video fail');
      expect(state.error).toBe('Video fail');
    });
  });

  describe('reset', () => {
    it('should clear state', () => {
      state.moderationResults = { a: 1 };
      state.error = 'error';
      zigContentModerationStore.reset();
      expect(state.moderationResults).toEqual({});
      expect(state.error).toBeNull();
      expect(state.loading).toBe(false);
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Zig.moderateText as any).mockRejectedValue(new Error('test'));
      try {
        await zigContentModerationStore.moderateText('bad');
      } catch (e) {}
      expect(state.error).toBe('test');
      zigContentModerationStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
