import { describe, it, expect, vi, beforeEach } from 'vitest';
import { callsStore, activeCalls, currentCall } from './calls';
import { Zig } from '../lib/api';

vi.mock('../lib/api', () => ({
  Zig: {
    getActiveCalls: vi.fn(),
    initiateCall: vi.fn(),
    joinCall: vi.fn(),
    leaveCall: vi.fn(),
    endCall: vi.fn(),
    acceptCall: vi.fn(),
    rejectCall: vi.fn(),
    holdCall: vi.fn(),
    resumeCall: vi.fn(),
    muteCall: vi.fn(),
    unmuteCall: vi.fn(),
    switchCamera: vi.fn(),
    setSpeakerphone: vi.fn(),
  }
}));

describe('CallsStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    callsStore.reset();
    callsStore.subscribe(s => { state = s; });
  });

  const mockCall = (id: string, status: string = 'active'): any => ({
    callId: id,
    participantId: `p-${id}`,
    type: 'audio',
    status,
    startTime: new Date().toISOString(),
  });

  describe('loadActiveCalls', () => {
    it('should load active calls', async () => {
      const calls = [mockCall('c1'), mockCall('c2')];
      (Zig.getActiveCalls as any).mockResolvedValue(calls);

      await callsStore.loadActiveCalls();

      expect(state.activeCalls).toEqual(calls);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      (Zig.getActiveCalls as any).mockReturnValue(p as any);
      const loadPromise = callsStore.loadActiveCalls();
      await new Promise(process.nextTick);
      expect(state.loading).toBe(true);
      resolvePromise([mockCall('c1')]);
      await loadPromise;
      expect(state.loading).toBe(false);
    });

    it('updates activeCalls derived on success', async () => {
      const calls = [mockCall('c1')];
      (Zig.getActiveCalls as any).mockResolvedValue(calls);
      await callsStore.loadActiveCalls();
      let a: any[] = [];
      const unsub = activeCalls.subscribe(s => { a = s; });
      expect(a).toEqual(calls);
      unsub();
    });

    it('should handle empty', async () => {
      (Zig.getActiveCalls as any).mockResolvedValue([]);
      await callsStore.loadActiveCalls();
      expect(state.activeCalls).toEqual([]);
      expect(state.loading).toBe(false);
    });

    it('should set error on failure', async () => {
      (Zig.getActiveCalls as any).mockRejectedValue(new Error('API down'));
      await expect(callsStore.loadActiveCalls()).rejects.toThrow('API down');
      expect(state.loading).toBe(false);
      expect(state.error).toBe('API down');
    });
  });

  describe('initiateCall', () => {
    it('should initiate a call and add to active calls', async () => {
      const call = mockCall('c1');
      (Zig.initiateCall as any).mockResolvedValue(call);

      const result = await callsStore.initiateCall({ participantId: 'p1', type: 'audio' });

      expect(result).toEqual(call);
      expect(state.activeCalls).toContain(call);
      expect(state.currentCall).toEqual(call);
    });

    it('should handle initiate error', async () => {
      (Zig.initiateCall as any).mockRejectedValue(new Error('Initiate failed'));
      await expect(callsStore.initiateCall({ participantId: 'p1', type: 'audio' })).rejects.toThrow('Initiate failed');
      expect(state.error).toBe('Initiate failed');
    });
  });

  describe('joinCall', () => {
    it('should join call and set currentCall', async () => {
      const call = mockCall('c1', 'joined');
      (Zig.joinCall as any).mockResolvedValue(call);

      const result = await callsStore.joinCall('c1');

      expect(result).toEqual(call);
      expect(state.currentCall).toEqual(call);
    });

    it('should handle join error', async () => {
      (Zig.joinCall as any).mockRejectedValue(new Error('Join failed'));
      await expect(callsStore.joinCall('c1')).rejects.toThrow('Join failed');
      expect(state.error).toBe('Join failed');
    });
  });

  describe('leaveCall', () => {
    it('should leave current call and call Zig.endCall', async () => {
      const call = mockCall('c1');
      (Zig.initiateCall as any).mockResolvedValue(call);
      await callsStore.initiateCall({ participantId: 'p1', type: 'audio' });
      // Now currentCall set
      (Zig.endCall as any).mockResolvedValue(undefined);
      await callsStore.leaveCall();
      expect(Zig.endCall).toHaveBeenCalledWith('c1');
      // After leaveCall, currentCall should be null
      expect(state.currentCall).toBeNull();
    });
  });

  describe('endCall', () => {
    it('should end specified call', async () => {
      (Zig.endCall as any).mockResolvedValue(undefined);
      await callsStore.endCall('c1');
      expect(Zig.endCall).toHaveBeenCalledWith('c1');
    });
  });

  describe('endCall error handling', () => {
    it('should set error on endCall failure', async () => {
      (Zig.endCall as any).mockRejectedValue(new Error('End failed'));
      await expect(callsStore.endCall('c1')).rejects.toThrow('End failed');
      expect(state.error).toBe('End failed');
    });
  });

  describe('setLocalStream', () => {
    it('should set local stream', () => {
      const stream = {} as any;
      callsStore.setLocalStream(stream);
      expect(state.localStream).toBe(stream);
    });
  });

  describe('addRemoteStream', () => {
    it('should add remote stream', () => {
      const stream = {} as any;
      callsStore.addRemoteStream('c1', stream);
      expect(state.remoteStreams['c1']).toBe(stream);
    });
  });

  describe('removeRemoteStream', () => {
    it('should remove remote stream', () => {
      const stream = {} as any;
      callsStore.addRemoteStream('c1', stream);
      expect(state.remoteStreams['c1']).toBe(stream);
      callsStore.removeRemoteStream('c1');
      expect(state.remoteStreams['c1']).toBeUndefined();
    });
  });

  describe('addActiveCall', () => {
    it('should add active call', () => {
      const call = mockCall('c1');
      callsStore.addActiveCall(call);
      expect(state.activeCalls).toContain(call);
    });
  });

  describe('updateActiveCall', () => {
    it('should update active call', () => {
      const call1 = mockCall('c1', 'ringing');
      callsStore.addActiveCall(call1);
      // Correct signature: pass Call object with updates merged
      callsStore.updateActiveCall({ callId: 'c1', status: 'active' });
      expect(state.activeCalls[0].status).toBe('active');
    });
  });

  describe('removeActiveCall', () => {
    it('should remove active call', () => {
      const call = mockCall('c1');
      callsStore.addActiveCall(call);
      expect(state.activeCalls).toHaveLength(1);
      callsStore.removeActiveCall('c1');
      expect(state.activeCalls).toHaveLength(0);
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Zig.initiateCall as any).mockRejectedValue(new Error('test error'));
      try {
        await callsStore.initiateCall({ participantId: 'p1', type: 'audio' });
      } catch (e) {}
      expect(state.error).toBe('test error');
      callsStore.clearError();
      expect(state.error).toBeNull();
    });
  });

  describe('derived stores', () => {
    it('activeCalls should reflect store.activeCalls', async () => {
      callsStore.reset();
      const mock1 = mockCall('c1');
      const mock2 = mockCall('c2');
      callsStore.addActiveCall(mock1);
      callsStore.addActiveCall(mock2);
      let result: any[] = [];
      const unsub = activeCalls.subscribe((val) => { result = val; });
      expect(result).toEqual([mock1, mock2]);
      unsub();
    });

    it('currentCall should be null initially and set after join', async () => {
      callsStore.reset();
      let result = null;
      const unsub = currentCall.subscribe((val) => { result = val; });
      expect(result).toBeNull();
      const call = mockCall('c1');
      (Zig.joinCall as any).mockResolvedValue(call);
      await callsStore.joinCall('c1');
      expect(result).toEqual(call);
      unsub();
    });
  });

});
