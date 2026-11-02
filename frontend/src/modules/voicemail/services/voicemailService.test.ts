import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

global.fetch = vi.fn();

import { VoicemailService, setRetryWithBackoff, resetRetryWithBackoff } from './voicemailService';

describe('VoicemailService', () => {
  let service: VoicemailService;

  beforeEach(() => {
    // Mock the retry function to execute immediately without delays
    setRetryWithBackoff(vi.fn(async (operation: () => Promise<any>) => {
      return operation();
    }));

    vi.clearAllMocks();
    service = new VoicemailService();
  });

  afterEach(() => {
    resetRetryWithBackoff();
    vi.restoreAllMocks();
  });

  describe('getVoicemails', () => {
    it('should fetch voicemails with default pagination', async () => {
      const result = { items: [{ id: 'v1', sender: 'John' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getVoicemails();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/voicemail?page=1&limit=20');
    });

    it('should fetch with custom pagination', async () => {
      const result = { items: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getVoicemails(2, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/voicemail?page=2&limit=50');
    });

    it('should throw on invalid page params', async () => {
      await expect(service.getVoicemails(0, 10)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getVoicemails(1, 0)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getVoicemails(1, 150)).rejects.toThrow('Invalid pagination parameters');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getVoicemails()).rejects.toThrow('Failed to fetch voicemails');
    });
  });

  describe('getVoicemail', () => {
    it('should fetch voicemail by id', async () => {
      const voicemail = { id: 'v1', sender: 'John' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(voicemail) });

      const result = await service.getVoicemail('v1');

      expect(result).toEqual(voicemail);
      expect(global.fetch).toHaveBeenCalledWith('/api/voicemail/v1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getVoicemail('v1')).rejects.toThrow('Failed to fetch voicemail');
    });
  });

  describe('markAsListened', () => {
    it('should mark voicemail as listened', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.markAsListened('v1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/voicemail/v1/listen', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.markAsListened('v1')).rejects.toThrow('Failed to mark voicemail as listened');
    });
  });

  describe('deleteVoicemail', () => {
    it('should delete voicemail', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.deleteVoicemail('v1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/voicemail/v1', expect.objectContaining({ method: 'DELETE' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.deleteVoicemail('v1')).rejects.toThrow('Failed to delete voicemail');
    });
  });
});