import { describe, it, expect, vi, beforeEach } from 'vitest';
import { webrtcStore, callState } from './webrtc';

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('WebRTC Store', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    webrtcStore.reset();
  });

  describe('initial state', () => {
    it('has correct callState defaults', () => {
      let state: any;
      callState.subscribe((s) => { state = s; });
      expect(state.isInCall).toBe(false);
      expect(state.roomId).toBeNull();
      expect(state.participants).toEqual([]);
      expect(state.localStream).toBeNull();
      expect(state.remoteStreams).toBeInstanceOf(Map);
      expect(state.isMuted).toBe(false);
      expect(state.isVideoOff).toBe(false);
      expect(state.isScreenSharing).toBe(false);
    });

    it('has empty peerConnections Map', () => {
      expect(webrtcStore.peerConnections.size).toBe(0);
    });
  });

  describe('joinRoom', () => {
    it('posts to correct endpoint with userId', async () => {
      const mockResponse = { roomId: 'room1', participants: ['u1', 'u2'] };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => mockResponse });

      const result = await webrtcStore.joinRoom('room1', 'user1');

      expect(mockFetch).toHaveBeenCalledWith('/api/webrtc/room/room1/join', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: 'Bearer test-token',
        },
        body: JSON.stringify({ userId: 'user1' }),
      });
      expect(result).toEqual(mockResponse);
    });

    it('propagates fetch errors', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));
      await expect(webrtcStore.joinRoom('room1', 'user1')).rejects.toThrow('Network error');
    });
  });

  describe('sendOffer', () => {
    it('posts offer to room', async () => {
      const offer = { type: 'offer', sdp: 'fake-sdp' };
      const mockResponse = { answer: { type: 'answer', sdp: 'answer-sdp' } };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => mockResponse });

      const result = await webrtcStore.sendOffer('room1', offer);

      expect(mockFetch).toHaveBeenCalledWith('/api/webrtc/room/room1/offer', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: 'Bearer test-token',
        },
        body: JSON.stringify({ offer }),
      });
      expect(result).toEqual(mockResponse);
    });
  });

  describe('sendAnswer', () => {
    it('posts answer to room', async () => {
      const answer = { type: 'answer', sdp: 'answer-sdp' };
      const mockResponse = { success: true };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => mockResponse });

      await webrtcStore.sendAnswer('room1', answer);

      expect(mockFetch).toHaveBeenCalledWith('/api/webrtc/room/room1/answer', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: 'Bearer test-token',
        },
        body: JSON.stringify({ answer }),
      });
    });
  });

  describe('sendCandidate', () => {
    it('posts ICE candidate', async () => {
      const candidate = { candidate: 'fake-candidate', sdpMid: '0', sdpMLineIndex: 0 };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });

      await webrtcStore.sendCandidate('room1', candidate);

      expect(mockFetch).toHaveBeenCalledWith('/api/webrtc/room/room1/candidate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: 'Bearer test-token',
        },
        body: JSON.stringify({ candidate }),
      });
    });
  });

  describe('getRoomData', () => {
    it('fetches room data', async () => {
      const mockData = { roomId: 'room1', users: ['u1'] };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => mockData });

      const data = await webrtcStore.getRoomData('room1');

      expect(mockFetch).toHaveBeenCalledWith('/api/webrtc/room/room1', {
        headers: { Authorization: 'Bearer test-token' },
      });
      expect(data).toEqual(mockData);
    });
  });

  describe('leaveRoom', () => {
    it('posts leave request', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });

      await webrtcStore.leaveRoom('room1');

      expect(mockFetch).toHaveBeenCalledWith('/api/webrtc/room/room1/leave', {
        method: 'POST',
        headers: { Authorization: 'Bearer test-token' },
      });
    });
  });

  describe('reset', () => {
    it('resets callState and clears peerConnections', () => {
      // Modify state
      callState.set({
        isInCall: true,
        roomId: 'room1',
        participants: ['u1'],
        localStream: {},
        remoteStreams: new Map(),
        isMuted: true,
        isVideoOff: false,
        isScreenSharing: false,
      });
      webrtcStore.peerConnections.set('conn1', {} as any);

      webrtcStore.reset();

      let state: any;
      callState.subscribe((s) => { state = s; });
      expect(state.isInCall).toBe(false);
      expect(state.roomId).toBeNull();
      expect(state.participants).toEqual([]);
      expect(state.localStream).toBeNull();
      expect(state.isMuted).toBe(false);
      expect(webrtcStore.peerConnections.size).toBe(0);
    });
  });
});
