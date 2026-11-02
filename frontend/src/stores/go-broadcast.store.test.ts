import { describe, it, expect, vi, beforeEach } from 'vitest';
import { goBroadcastStore } from './go-all';
import { Go } from '../lib/api';

vi.mock('../lib/api', () => ({
  Go: {
    sendBroadcast: vi.fn(),
    getBroadcastStatus: vi.fn(),
    getBroadcasts: vi.fn(),
  }
}));

describe('GoBroadcastStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    goBroadcastStore.reset();
    goBroadcastStore.subscribe(s => { state = s; });
  });

  describe('sendBroadcast', () => {
    it('should send broadcast and add to broadcasts', async () => {
      const broadcast = { id: 'b1', message: 'Hi' };
      (Go.sendBroadcast as any).mockResolvedValue(broadcast);
      state.broadcasts = [];

      const result = await goBroadcastStore.sendBroadcast({ message: 'Hi' });

      expect(result).toEqual(broadcast);
      expect(state.broadcasts).toContain(broadcast);
    });

    it('should handle error', async () => {
      (Go.sendBroadcast as any).mockRejectedValue(new Error('Send failed'));
      await expect(goBroadcastStore.sendBroadcast({})).rejects.toThrow('Send failed');
      expect(state.error).toBe('Send failed');
    });
  });

  describe('getBroadcastStatus', () => {
    it('should get status and update broadcast', async () => {
      const broadcast = { id: 'b1', delivered: false };
      state.broadcasts = [broadcast];
      const status = { delivered: true };
      (Go.getBroadcastStatus as any).mockResolvedValue(status);

      const result = await goBroadcastStore.getBroadcastStatus('b1');

      expect(result).toEqual(status);
      expect(state.broadcasts[0].delivered).toBe(true);
    });

    it('should handle error', async () => {
      (Go.getBroadcastStatus as any).mockRejectedValue(new Error('Status failed'));
      await expect(goBroadcastStore.getBroadcastStatus('b1')).rejects.toThrow('Status failed');
      expect(state.error).toBe('Status failed');
    });
  });

  describe('getBroadcasts', () => {
    it('should fetch broadcasts', async () => {
      const broadcasts = [{ id: 'b1' }, { id: 'b2' }];
      (Go.getBroadcasts as any).mockResolvedValue(broadcasts);

      const result = await goBroadcastStore.getBroadcasts();

      expect(result).toEqual(broadcasts);
      expect(state.broadcasts).toEqual(broadcasts);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.getBroadcasts as any).mockRejectedValue(new Error('Get failed'));
      await expect(goBroadcastStore.getBroadcasts()).rejects.toThrow('Get failed');
      expect(state.error).toBe('Get failed');
    });
  });

  describe('createBroadcast', () => {
    it('should create broadcast and add to list', async () => {
      const broadcast = { id: 'b3', message: 'New' };
      (Go.sendBroadcast as any).mockResolvedValue(broadcast); // createBroadcast uses Go.sendBroadcast internally
      state.broadcasts = [];

      const result = await goBroadcastStore.createBroadcast({ message: 'New' });

      expect(result).toEqual(broadcast);
      expect(state.broadcasts).toContain(broadcast);
    });

    it('should handle error', async () => {
      (Go.sendBroadcast as any).mockRejectedValue(new Error('Create failed')); // actual internal call
      await expect(goBroadcastStore.createBroadcast({})).rejects.toThrow('Create failed');
      expect(state.error).toBe('Create failed');
    });
  });

  describe('deleteBroadcast', () => {
    it('should delete broadcast from list', async () => {
      state.broadcasts = [{ id: 'b1' }, { id: 'b2' }];

      await goBroadcastStore.deleteBroadcast('b1');

      expect(state.broadcasts).toHaveLength(1);
      expect(state.broadcasts[0].id).toBe('b2');
    });

    // deleteBroadcast does not call external API and does not throw; no error case to test
  });

  describe('reset', () => {
    it('should clear broadcasts', () => {
      state.broadcasts = [{ id: 'b1' }];
      state.error = 'err';
      goBroadcastStore.reset();
      expect(state.broadcasts).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Go.sendBroadcast as any).mockRejectedValue(new Error('test'));
      try { await goBroadcastStore.sendBroadcast({}); } catch (e) {}
      expect(state.error).toBe('test');
      goBroadcastStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
