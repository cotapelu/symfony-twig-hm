import { describe, it, expect, vi, beforeEach } from 'vitest';
import { CallService } from './callService';

// Mock fetch
global.fetch = vi.fn();

// Mock WebSocket
class MockWebSocket {
  static instances: MockWebSocket[] = [];
  url: string;
  onmessage: ((event: any) => void) | null = null;
  onerror: ((error: any) => void) | null = null;

  constructor(url: string) {
    this.url = url;
    MockWebSocket.instances.push(this);
  }

  close() {}
}

let mockEnumerateDevices: any;

describe('CallService', () => {
  let service: CallService;

  beforeEach(() => {
    service = new CallService();
    vi.clearAllMocks();
    MockWebSocket.instances = [];

    // Reset mediaDevices mock (only needed for getMediaDevices)
    mockEnumerateDevices = vi.fn().mockResolvedValue([
      { deviceId: 'dev1', kind: 'audioinput', label: 'Mic' },
      { deviceId: 'dev2', kind: 'videoinput', label: 'Cam' }
    ]);
    // @ts-ignore
    global.navigator = { mediaDevices: { enumerateDevices: mockEnumerateDevices } };
    // @ts-ignore
    global.WebSocket = MockWebSocket;
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('createCall', () => {
    it('should create a call successfully', async () => {
      const call = { id: 'c1' };
      (global.fetch as any).mockResolvedValue(mockResponse(call));
      const request = { participants: ['u1'] };
      const result = await service.createCall(request as any);
      expect(result).toEqual(call);
      expect(fetch).toHaveBeenCalledWith('/api/calls', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createCall({ participants: [] } as any)).rejects.toThrow('Failed to create call');
    });
  });

  describe('joinCall', () => {
    it('should join a call successfully', async () => {
      const call = { id: 'c1' };
      (global.fetch as any).mockResolvedValue(mockResponse(call));
      const request = { callId: 'c1', userId: 'u1' };
      const result = await service.joinCall(request as any);
      expect(result).toEqual(call);
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/join', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ callId: 'c1', userId: 'u1' })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.joinCall({ callId: 'c1', userId: 'u1' } as any)).rejects.toThrow('Failed to join call');
    });
  });

  describe('leaveCall', () => {
    it('should leave a call successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.leaveCall('c1');
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/leave', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.leaveCall('c1')).rejects.toThrow('Failed to leave call');
    });
  });

  describe('endCall', () => {
    it('should end a call successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.endCall('c1');
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/end', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.endCall('c1')).rejects.toThrow('Failed to end call');
    });
  });

  describe('getActiveCalls', () => {
    it('should fetch active calls', async () => {
      const calls = [{ id: 'c1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(calls));
      const result = await service.getActiveCalls();
      expect(result).toEqual(calls);
      expect(fetch).toHaveBeenCalledWith('/api/calls/active');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getActiveCalls()).rejects.toThrow('Failed to fetch active calls');
    });
  });

  describe('getCallHistory', () => {
    it('should fetch call history', async () => {
      const history = [{ id: 'h1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(history));
      const result = await service.getCallHistory();
      expect(result).toEqual(history);
      expect(fetch).toHaveBeenCalledWith('/api/calls/history');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getCallHistory()).rejects.toThrow('Failed to fetch call history');
    });
  });

  describe('getCallById', () => {
    it('should fetch a call by id', async () => {
      const call = { id: 'c1' };
      (global.fetch as any).mockResolvedValue(mockResponse(call));
      const result = await service.getCallById('c1');
      expect(result).toEqual(call);
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getCallById('c1')).rejects.toThrow('Failed to fetch call');
    });
  });

  describe('toggleMute', () => {
    it('should toggle mute', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.toggleMute('c1', true);
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/mute', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ muted: true })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.toggleMute('c1', false)).rejects.toThrow('Failed to toggle mute');
    });
  });

  describe('toggleVideo', () => {
    it('should toggle video', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.toggleVideo('c1', false);
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/video', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ enabled: false })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.toggleVideo('c1', true)).rejects.toThrow('Failed to toggle video');
    });
  });

  describe('startScreenShare', () => {
    it('should start screen share', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.startScreenShare('c1');
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/screen-share', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.startScreenShare('c1')).rejects.toThrow('Failed to start screen share');
    });
  });

  describe('stopScreenShare', () => {
    it('should stop screen share', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.stopScreenShare('c1');
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/screen-share', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.stopScreenShare('c1')).rejects.toThrow('Failed to stop screen share');
    });
  });

  describe('startRecording', () => {
    it('should start recording', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.startRecording('c1');
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/recording', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.startRecording('c1')).rejects.toThrow('Failed to start recording');
    });
  });

  describe('stopRecording', () => {
    it('should stop recording', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.stopRecording('c1');
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/recording', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.stopRecording('c1')).rejects.toThrow('Failed to stop recording');
    });
  });

  describe('getCallSettings', () => {
    it('should fetch call settings', async () => {
      const settings = { volume: 50, videoEnabled: true };
      (global.fetch as any).mockResolvedValue(mockResponse(settings));
      const result = await service.getCallSettings();
      expect(result).toEqual(settings);
      expect(fetch).toHaveBeenCalledWith('/api/calls/settings');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getCallSettings()).rejects.toThrow('Failed to fetch settings');
    });
  });

  describe('updateCallSettings', () => {
    it('should update call settings', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      const settings = { volume: 80 };
      await service.updateCallSettings(settings);
      expect(fetch).toHaveBeenCalledWith('/api/calls/settings', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(settings)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.updateCallSettings({ volume: 80 })).rejects.toThrow('Failed to update settings');
    });
  });

  describe('getMediaDevices', () => {
    it('should get media devices', async () => {
      const devices = await service.getMediaDevices();
      expect(mockEnumerateDevices).toHaveBeenCalled();
      expect(devices).toEqual([
        { id: 'dev1', label: 'Mic', kind: 'audioinput' },
        { id: 'dev2', label: 'Cam', kind: 'videoinput' }
      ]);
    });

    it('should generate fallback label when device.label is missing', async () => {
      mockEnumerateDevices.mockResolvedValue([
        { deviceId: 'dev3', kind: 'videoinput' } // no label
      ]);
      const devices = await service.getMediaDevices();
      expect(devices[0].label).toMatch(/^videoinput dev3/);
    });

    it('should throw when enumerateDevices fails', async () => {
      mockEnumerateDevices.mockRejectedValue(new Error('Not allowed'));
      await expect(service.getMediaDevices()).rejects.toThrow('Failed to enumerate media devices');
    });
  });

  describe('getCallQuality', () => {
    it('should fetch call quality', async () => {
      const quality = { bitrate: 1024 };
      (global.fetch as any).mockResolvedValue(mockResponse(quality));
      const result = await service.getCallQuality('c1');
      expect(result).toEqual(quality);
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/quality');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getCallQuality('c1')).rejects.toThrow('Failed to fetch call quality');
    });
  });

  describe('inviteParticipant', () => {
    it('should invite a participant', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.inviteParticipant('c1', 'u2');
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/invite', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: 'u2' })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.inviteParticipant('c1', 'u2')).rejects.toThrow('Failed to invite participant');
    });
  });

  describe('removeParticipant', () => {
    it('should remove a participant', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.removeParticipant('c1', 'u2');
      expect(fetch).toHaveBeenCalledWith('/api/calls/c1/participants/u2', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false });
      await expect(service.removeParticipant('c1', 'u2')).rejects.toThrow('Failed to remove participant');
    });
  });

  describe('subscribeToCallEvents', () => {
    it('should subscribe to call events and return unsubscribe', async () => {
      const callback = vi.fn();
      const unsubscribe = service.subscribeToCallEvents('c1', callback);

      // Find the created WebSocket instance
      const ws = MockWebSocket.instances.find(inst => inst.url.includes('/ws/calls/c1'));
      expect(ws).toBeDefined();
      // Simulate a received event
      const eventData = { type: 'event', payload: { foo: 'bar' } };
      ws!.onmessage!({ data: JSON.stringify(eventData) });
      expect(callback).toHaveBeenCalledWith(eventData);

      // Call unsubscribe
      const closeSpy = vi.spyOn(ws!, 'close');
      unsubscribe();
      expect(closeSpy).toHaveBeenCalled();
    });

    it('should construct correct WebSocket URL', () => {
      const callback = vi.fn();
      service.subscribeToCallEvents('c1', callback);
      const ws = MockWebSocket.instances[0];
      expect(ws.url).toContain(`wss://${window.location.host}/ws/calls/c1`);
    });
  });

  describe('subscribeToIncomingCalls', () => {
    it('should subscribe to incoming calls and return unsubscribe', async () => {
      const callback = vi.fn();
      const unsubscribe = service.subscribeToIncomingCalls(callback);

      const ws = MockWebSocket.instances.find(inst => inst.url.includes('/ws/calls/incoming'));
      expect(ws).toBeDefined();

      const incomingCall = { id: 'new' };
      ws!.onmessage!({ data: JSON.stringify(incomingCall) });
      expect(callback).toHaveBeenCalledWith(incomingCall);

      const closeSpy = vi.spyOn(ws!, 'close');
      unsubscribe();
      expect(closeSpy).toHaveBeenCalled();
    });

    it('should construct correct WebSocket URL for incoming calls', () => {
      const callback = vi.fn();
      service.subscribeToIncomingCalls(callback);
      const ws = MockWebSocket.instances[0];
      expect(ws.url).toContain(`wss://${window.location.host}/ws/calls/incoming`);
    });
  });
});
