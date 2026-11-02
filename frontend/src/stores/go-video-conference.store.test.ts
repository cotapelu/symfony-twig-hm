import { describe, it, expect, vi, beforeEach } from 'vitest';
import { goVideoConferenceStore } from './go-all';
import { Go } from '../lib/api';

vi.mock('../lib/api', () => ({
  Go: {
    getConferences: vi.fn(),
    createConference: vi.fn(),
    joinConference: vi.fn(),
    createRoom: vi.fn(),
    joinRoom: vi.fn(),
    leaveRoom: vi.fn(),
    toggleScreenShare: vi.fn(),
    getRoomParticipants: vi.fn(),
  }
}));

describe('GoVideoConferenceStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    goVideoConferenceStore.reset();
    goVideoConferenceStore.subscribe(s => { state = s; });
  });

  describe('getConferences', () => {
    it('should fetch conferences', async () => {
      const conferences = [{ id: 'c1' }];
      (Go.getConferences as any).mockResolvedValue(conferences);

      const result = await goVideoConferenceStore.getConferences();

      expect(result).toEqual(conferences);
      expect(state.conferences).toEqual(conferences);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.getConferences as any).mockRejectedValue(new Error('Conf failed'));
      await expect(goVideoConferenceStore.getConferences()).rejects.toThrow('Conf failed');
      expect(state.error).toBe('Conf failed');
    });
  });

  describe('createConference', () => {
    it('should create conference and prepend to conferences', async () => {
      const conference = { id: 'c2' };
      (Go.createConference as any).mockResolvedValue(conference);
      state.conferences = [];

      const result = await goVideoConferenceStore.createConference({ title: 'Meet' });

      expect(result).toEqual(conference);
      expect(state.conferences).toContain(conference);
    });

    it('should handle error', async () => {
      (Go.createConference as any).mockRejectedValue(new Error('Create failed'));
      await expect(goVideoConferenceStore.createConference({})).rejects.toThrow('Create failed');
      expect(state.error).toBe('Create failed');
    });
  });

  describe('joinConference', () => {
    it('should join conference', async () => {
      (Go.joinConference as any).mockResolvedValue(undefined);

      const result = await goVideoConferenceStore.joinConference('c1');

      expect(Go.joinConference).toHaveBeenCalledWith('c1');
    });

    it('should handle error', async () => {
      (Go.joinConference as any).mockRejectedValue(new Error('Join failed'));
      await expect(goVideoConferenceStore.joinConference('c1')).rejects.toThrow('Join failed');
      expect(state.error).toBe('Join failed');
    });
  });

  describe('createRoom', () => {
    it('should create room and set as currentRoom', async () => {
      const room = { id: 'r2' };
      (Go.createRoom as any).mockResolvedValue(room);
      state.rooms = [];

      const result = await goVideoConferenceStore.createRoom({ topic: 'Topic' });

      expect(result).toEqual(room);
      expect(state.rooms).toContain(room);
      expect(state.currentRoom).toEqual(room);
    });

    it('should handle error', async () => {
      (Go.createRoom as any).mockRejectedValue(new Error('Create room failed'));
      await expect(goVideoConferenceStore.createRoom({})).rejects.toThrow('Create room failed');
      expect(state.error).toBe('Create room failed');
    });
  });

  describe('joinRoom', () => {
    it('should join room and set currentRoom', async () => {
      const room = { id: 'r1' };
      (Go.joinRoom as any).mockResolvedValue(room);

      const result = await goVideoConferenceStore.joinRoom('r1');

      expect(result).toEqual(room);
      expect(state.currentRoom).toEqual(room);
    });

    it('should handle error', async () => {
      (Go.joinRoom as any).mockRejectedValue(new Error('Join room failed'));
      await expect(goVideoConferenceStore.joinRoom('r1')).rejects.toThrow('Join room failed');
      expect(state.error).toBe('Join room failed');
    });
  });

  describe('leaveRoom', () => {
    it('should leave room and reset state', async () => {
      state.currentRoom = { id: 'r1' };
      state.participants = [{ id: 'u1' }];
      state.localStream = { id: 's1' };
      state.remoteStreams = { r1: {} };
      (Go.leaveRoom as any).mockResolvedValue(undefined);

      await goVideoConferenceStore.leaveRoom('r1');

      expect(Go.leaveRoom).toHaveBeenCalledWith('r1');
      expect(state.currentRoom).toBeNull();
      expect(state.participants).toEqual([]);
      expect(state.localStream).toBeNull();
      expect(state.remoteStreams).toEqual({});
    });

    it('should handle error', async () => {
      (Go.leaveRoom as any).mockRejectedValue(new Error('Leave failed'));
      await expect(goVideoConferenceStore.leaveRoom('r1')).rejects.toThrow('Leave failed');
      expect(state.error).toBe('Leave failed');
    });
  });

  describe('toggleScreenShare', () => {
    it('should enable screen sharing', async () => {
      (Go.toggleScreenShare as any).mockResolvedValue(undefined);

      await goVideoConferenceStore.toggleScreenShare('r1', true);

      expect(Go.toggleScreenShare).toHaveBeenCalledWith('r1', true);
      expect(state.screenSharingEnabled).toBe(true);
    });

    it('should disable screen sharing', async () => {
      (Go.toggleScreenShare as any).mockResolvedValue(undefined);
      state.screenSharingEnabled = true;

      await goVideoConferenceStore.toggleScreenShare('r1', false);

      expect(state.screenSharingEnabled).toBe(false);
    });

    it('should handle error', async () => {
      (Go.toggleScreenShare as any).mockRejectedValue(new Error('Toggle failed'));
      await expect(goVideoConferenceStore.toggleScreenShare('r1', true)).rejects.toThrow('Toggle failed');
      expect(state.error).toBe('Toggle failed');
    });
  });

  describe('getRoomParticipants', () => {
    it('should fetch participants', async () => {
      const participants = [{ id: 'u1' }];
      (Go.getRoomParticipants as any).mockResolvedValue(participants);

      const result = await goVideoConferenceStore.getRoomParticipants('r1');

      expect(result).toEqual(participants);
      expect(state.participants).toEqual(participants);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.getRoomParticipants as any).mockRejectedValue(new Error('Part failed'));
      await expect(goVideoConferenceStore.getRoomParticipants('r1')).rejects.toThrow('Part failed');
      expect(state.error).toBe('Part failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.conferences = [{ id: 'c1' }];
      state.rooms = [{ id: 'r1' }];
      state.currentRoom = { id: 'r1' };
      state.participants = [{ id: 'u1' }];
      state.localStream = { id: 's1' };
      state.remoteStreams = { r1: {} };
      state.screenSharingEnabled = true;
      state.error = 'err';
      goVideoConferenceStore.reset();
      expect(state.conferences).toEqual([]);
      expect(state.rooms).toEqual([]);
      expect(state.currentRoom).toBeNull();
      expect(state.participants).toEqual([]);
      expect(state.localStream).toBeNull();
      expect(state.remoteStreams).toEqual({});
      expect(state.screenSharingEnabled).toBe(false);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Go.getConferences as any).mockRejectedValue(new Error('test'));
      try { await goVideoConferenceStore.getConferences(); } catch (e) {}
      expect(state.error).toBe('test');
      goVideoConferenceStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
