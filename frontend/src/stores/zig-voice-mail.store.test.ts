import { describe, it, expect, vi, beforeEach } from 'vitest';
import { zigVoiceMailStore } from './zig-stores';
import { Zig } from '../lib/api';

vi.mock('../lib/api', () => ({
  Zig: {
    recordVoiceMail: vi.fn(),
    transcribeVoiceMail: vi.fn(),
    getVoiceMails: vi.fn(),
    deleteVoiceMail: vi.fn(),
  }
}));

describe('ZigVoiceMailStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    zigVoiceMailStore.reset();
    zigVoiceMailStore.subscribe(s => { state = s; });
  });

  const mockVoiceMail = (id: string, isNew: boolean = true, duration: number = 30): any => ({
    id,
    isNew,
    duration,
    createdAt: new Date().toISOString()
  });

  describe('recordVoiceMail', () => {
    it('should record voice mail and add to store', async () => {
      const vm = mockVoiceMail('v1');
      (Zig.recordVoiceMail as any).mockResolvedValue(vm);
      const audioFile = new File(['data'], 'audio.wav', { type: 'audio/wav' });

      const result = await zigVoiceMailStore.recordVoiceMail(audioFile);

      expect(result).toEqual(vm);
      expect(state.voiceMails).toContain(vm);
      expect(state.loading).toBe(false);
    });

    it('should handle record error', async () => {
      (Zig.recordVoiceMail as any).mockRejectedValue(new Error('Record failed'));
      const audioFile = new File(['data'], 'audio.wav');

      await expect(zigVoiceMailStore.recordVoiceMail(audioFile)).rejects.toThrow('Record failed');
      expect(state.error).toBe('Record failed');
      expect(state.loading).toBe(false);
    });
  });

  describe('transcribeVoiceMail', () => {
    it('should transcribe and store transcription', async () => {
      const transcription = { text: 'Hello world' };
      (Zig.transcribeVoiceMail as any).mockResolvedValue(transcription);
      state.voiceMails = [mockVoiceMail('v1')];

      const result = await zigVoiceMailStore.transcribeVoiceMail('v1');

      expect(result).toEqual(transcription);
      expect(state.transcriptions['v1']).toEqual(transcription);
    });

    it('should handle transcribe error', async () => {
      (Zig.transcribeVoiceMail as any).mockRejectedValue(new Error('Transcribe failed'));
      await expect(zigVoiceMailStore.transcribeVoiceMail('v1')).rejects.toThrow('Transcribe failed');
      expect(state.error).toBe('Transcribe failed');
    });
  });

  describe('getVoiceMails', () => {
    it('should fetch voice mails and compute stats', async () => {
      const vms = [mockVoiceMail('v1', true, 30), mockVoiceMail('v2', false, 60)];
      (Zig.getVoiceMails as any).mockResolvedValue(vms);

      const result = await zigVoiceMailStore.getVoiceMails('user1');

      expect(result).toEqual(vms);
      expect(state.voiceMails).toEqual(vms);
      expect(state.listenedCount).toBe(1);
      expect(state.totalDuration).toBe(90);
      expect(state.loading).toBe(false);
    });

    it('should handle empty list', async () => {
      (Zig.getVoiceMails as any).mockResolvedValue([]);
      await zigVoiceMailStore.getVoiceMails('user1');
      expect(state.voiceMails).toEqual([]);
      expect(state.listenedCount).toBe(0);
      expect(state.totalDuration).toBe(0);
    });

    it('should set error on failure', async () => {
      (Zig.getVoiceMails as any).mockRejectedValue(new Error('Fetch failed'));
      await expect(zigVoiceMailStore.getVoiceMails('user1')).rejects.toThrow('Fetch failed');
      expect(state.error).toBe('Fetch failed');
    });
  });

  describe('deleteVoiceMail', () => {
    it('should delete voice mail', async () => {
      state.voiceMails = [mockVoiceMail('v1')];
      (Zig.deleteVoiceMail as any).mockResolvedValue(undefined);

      await zigVoiceMailStore.deleteVoiceMail('v1');

      expect(Zig.deleteVoiceMail).toHaveBeenCalledWith('v1');
      expect(state.voiceMails).toHaveLength(0);
    });

    it('should handle delete error', async () => {
      state.voiceMails = [mockVoiceMail('v1')];
      (Zig.deleteVoiceMail as any).mockRejectedValue(new Error('Delete failed'));
      await expect(zigVoiceMailStore.deleteVoiceMail('v1')).rejects.toThrow('Delete failed');
      expect(state.voiceMails).toHaveLength(1);
      expect(state.error).toBe('Delete failed');
    });
  });

  describe('setRecording', () => {
    it('should set recording flag', () => {
      zigVoiceMailStore.setRecording(true);
      expect(state.recording).toBe(true);
      zigVoiceMailStore.setRecording(false);
      expect(state.recording).toBe(false);
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Zig.recordVoiceMail as any).mockRejectedValue(new Error('test error'));
      try { await zigVoiceMailStore.recordVoiceMail(new File(['a'], 'a.wav')); } catch (e) {}
      expect(state.error).toBe('test error');
      zigVoiceMailStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
