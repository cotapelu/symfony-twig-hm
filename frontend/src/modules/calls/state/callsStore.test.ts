import { describe, it, expect, vi, beforeEach } from 'vitest';
import { get } from 'svelte/store';

// Mock service methods
const mockCreateCall = vi.fn();
const mockJoinCall = vi.fn();
const mockLeaveCall = vi.fn();
const mockEndCall = vi.fn();
const mockGetActiveCalls = vi.fn();
const mockGetCallHistory = vi.fn();
const mockToggleMute = vi.fn();
const mockToggleVideo = vi.fn();
const mockStartScreenShare = vi.fn();
const mockStopScreenShare = vi.fn();
const mockStartRecording = vi.fn();
const mockStopRecording = vi.fn();
const mockSubscribeToIncomingCalls = vi.fn().mockReturnValue(() => {});

vi.mock('../services/callService', () => ({
  CallService: vi.fn().mockImplementation(() => ({
    createCall: mockCreateCall,
    joinCall: mockJoinCall,
    leaveCall: mockLeaveCall,
    endCall: mockEndCall,
    getActiveCalls: mockGetActiveCalls,
    getCallHistory: mockGetCallHistory,
    toggleMute: mockToggleMute,
    toggleVideo: mockToggleVideo,
    startScreenShare: mockStartScreenShare,
    stopScreenShare: mockStopScreenShare,
    startRecording: mockStartRecording,
    stopRecording: mockStopRecording,
    subscribeToIncomingCalls: mockSubscribeToIncomingCalls
  }))
}));

// Import store after mocking
const { callsStore } = await import('./callsStore');

describe('callsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    callsStore.reset(); // isolate the shared store singleton between tests
  });

  describe('initial state', () => {
    it('should have correct initial state', () => {
      const state = get(callsStore);
      expect(state.currentCall).toBeNull();
      expect(state.callHistory).toEqual([]);
      expect(state.incomingCall).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.isMuted).toBe(false);
      expect(state.isVideoOn).toBe(true);
      expect(state.isScreenSharing).toBe(false);
      expect(state.isRecording).toBe(false);
    });

    it('reset() restores the initial state after mutations', async () => {
      mockCreateCall.mockResolvedValue({ id: 'call-1' });
      await callsStore.createCall({ recipientId: 'u2', type: 'video' });
      expect(get(callsStore).currentCall).not.toBeNull();
      callsStore.reset();
      const s = get(callsStore);
      expect(s.currentCall).toBeNull();
      expect(s.isMuted).toBe(false);
      expect(s.isVideoOn).toBe(true);
      expect(s.isScreenSharing).toBe(false);
      expect(s.isRecording).toBe(false);
    });
  });

  describe('createCall', () => {
    it('should create call successfully', async () => {
      const mockCall = { id: '1', roomId: 'room1', status: 'active' };
      mockCreateCall.mockResolvedValue(mockCall);

      const result = await callsStore.createCall({ recipientId: 'user2', type: 'video' });

      expect(result).toEqual(mockCall);
      const state = get(callsStore);
      expect(state.currentCall).toEqual(mockCall);
      expect(state.loading).toBe(false);
    });

    it('should handle create call error', async () => {
      mockCreateCall.mockRejectedValue(new Error('Failed to create'));

      await expect(callsStore.createCall({ recipientId: 'user2', type: 'video' })).rejects.toThrow('Failed to create');
      
      const state = get(callsStore);
      expect(state.error).toBe('Failed to create');
    });
  });

  describe('joinCall', () => {
    it('should join call successfully', async () => {
      const mockCall = { id: '1', roomId: 'room1', status: 'active' };
      mockJoinCall.mockResolvedValue(mockCall);

      const result = await callsStore.joinCall({ callId: '1', roomId: 'room1' });

      expect(result).toEqual(mockCall);
      const state = get(callsStore);
      expect(state.currentCall).toEqual(mockCall);
    });

    it('should handle join call error', async () => {
      mockJoinCall.mockRejectedValue(new Error('Failed to join'));

      await expect(callsStore.joinCall({ callId: '1', roomId: 'room1' })).rejects.toThrow('Failed to join');
    });
  });

  describe('loadCallHistory', () => {
    it('should load call history successfully', async () => {
      const mockHistory = [{ id: '1', duration: 120 }];
      mockGetCallHistory.mockResolvedValue(mockHistory);

      const result = await callsStore.loadCallHistory();

      expect(result).toEqual(mockHistory);
      const state = get(callsStore);
      expect(state.callHistory).toEqual(mockHistory);
    });

    it('should handle load history error', async () => {
      mockGetCallHistory.mockRejectedValue(new Error('Failed'));

      const result = await callsStore.loadCallHistory();

      expect(result).toEqual([]);
      const state = get(callsStore);
      expect(state.error).toBe('Failed');
    });
  });

  describe('loadActiveCalls', () => {
    it('should load active calls successfully', async () => {
      const mockCalls = [{ id: '1', status: 'active' }];
      mockGetActiveCalls.mockResolvedValue(mockCalls);

      const result = await callsStore.loadActiveCalls();

      expect(result).toEqual(mockCalls);
    });

    it('should handle load active calls error', async () => {
      mockGetActiveCalls.mockRejectedValue(new Error('Failed'));

      const result = await callsStore.loadActiveCalls();

      expect(result).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('should clear error', () => {
      callsStore.clearError();
      expect(get(callsStore).error).toBeNull();
    });
  });

  // Regression tests for the P1 "no-op toggle" bug: these methods used to read
  // the static `initialState` instead of live store state, so mute/video/
  // screen-share/record/end never did anything.
  describe('call controls (toggle/end) — P1 no-op-toggle regression', () => {
    const setActiveCall = async () => {
      const call = { id: 'call-1', roomId: 'r1', status: 'active' };
      mockCreateCall.mockResolvedValue(call);
      await callsStore.createCall({ recipientId: 'u2', type: 'video' });
      return call;
    };

    it('toggleMute flips isMuted and calls the service with the new value', async () => {
      const call = await setActiveCall();
      await callsStore.toggleMute();
      expect(mockToggleMute).toHaveBeenCalledWith(call.id, true);
      expect(get(callsStore).isMuted).toBe(true);
      await callsStore.toggleMute();
      expect(mockToggleMute).toHaveBeenLastCalledWith(call.id, false);
      expect(get(callsStore).isMuted).toBe(false);
    });

    it('toggleMute is a no-op guard without an active call', async () => {
      await callsStore.toggleMute();
      expect(mockToggleMute).not.toHaveBeenCalled();
      expect(get(callsStore).isMuted).toBe(false);
    });

    it('toggleVideo flips isVideoOn (starts true)', async () => {
      const call = await setActiveCall();
      await callsStore.toggleVideo();
      expect(mockToggleVideo).toHaveBeenCalledWith(call.id, false);
      expect(get(callsStore).isVideoOn).toBe(false);
      await callsStore.toggleVideo();
      expect(mockToggleVideo).toHaveBeenLastCalledWith(call.id, true);
      expect(get(callsStore).isVideoOn).toBe(true);
    });

    it('toggleScreenShare starts then stops sharing', async () => {
      const call = await setActiveCall();
      await callsStore.toggleScreenShare();
      expect(mockStartScreenShare).toHaveBeenCalledWith(call.id);
      expect(get(callsStore).isScreenSharing).toBe(true);
      await callsStore.toggleScreenShare();
      expect(mockStopScreenShare).toHaveBeenCalledWith(call.id);
      expect(get(callsStore).isScreenSharing).toBe(false);
    });

    it('toggleRecording starts then stops recording', async () => {
      const call = await setActiveCall();
      await callsStore.toggleRecording();
      expect(mockStartRecording).toHaveBeenCalledWith(call.id);
      expect(get(callsStore).isRecording).toBe(true);
      await callsStore.toggleRecording();
      expect(mockStopRecording).toHaveBeenCalledWith(call.id);
      expect(get(callsStore).isRecording).toBe(false);
    });

    it('endCall ends the active call and clears currentCall', async () => {
      const call = await setActiveCall();
      await callsStore.endCall();
      expect(mockEndCall).toHaveBeenCalledWith(call.id);
      expect(get(callsStore).currentCall).toBeNull();
    });
  });
});
