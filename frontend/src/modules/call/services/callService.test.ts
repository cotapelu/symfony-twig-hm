import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { CallService } from './callService';
import { __testing__ } from './callService';

describe('CallService', () => {
  let service: CallService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new CallService();
  });

  describe('getCalls', () => {
    it('should fetch all calls', async () => {
      const calls = [{ id: 'c1', phoneNumber: '1234567890' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(calls) });

      const result = await service.getCalls();

      expect(result).toEqual(calls);
      expect(global.fetch).toHaveBeenCalledWith('/api/calls');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getCalls()).rejects.toThrow('Failed to fetch calls');
    });
  });

  describe('initiateCall', () => {
    const request = { phoneNumber: '1234567890', contactName: 'John' };

    it('should initiate call successfully', async () => {
      const call = { id: 'c1', ...request, status: 'ringing' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(call) });

      const result = await service.initiateCall(request);

      expect(result).toEqual(call);
      expect(global.fetch).toHaveBeenCalledWith('/api/calls', expect.objectContaining({
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: expect.stringContaining('"phoneNumber":"1234567890"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.initiateCall(request)).rejects.toThrow('Failed to initiate call');
    });
  });

  describe('endCall', () => {
    it('should end call', async () => {
      const call = { id: 'c1', status: 'ended' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(call) });

      const result = await service.endCall('c1');

      expect(result).toEqual(call);
      expect(global.fetch).toHaveBeenCalledWith('/api/calls/c1/end', expect.objectContaining({ method: 'PUT' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.endCall('c1')).rejects.toThrow('Failed to end call');
    });
  });

  describe('answerCall', () => {
    it('should answer call', async () => {
      const call = { id: 'c1', status: 'connected' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(call) });

      const result = await service.answerCall('c1');

      expect(result).toEqual(call);
      expect(global.fetch).toHaveBeenCalledWith('/api/calls/c1/answer', expect.objectContaining({ method: 'PUT' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.answerCall('c1')).rejects.toThrow('Failed to answer call');
    });
  });
});
