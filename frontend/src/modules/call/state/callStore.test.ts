import { describe, it, expect, vi, beforeEach } from 'vitest';
import { callStore, calls, activeCall, callLoading } from './callStore';
import { callService } from '../services/callService';
import type { Call } from '../contracts/callTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = callStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockCall = (overrides: Partial<Call> = {}): Call => ({
  id: `call-${Math.random().toString(36).substr(2, 5)}`,
  callerId: 'user1',
  calleeId: 'user2',
  type: 'audio' as const,
  status: 'initiated' as const,
  startedAt: new Date().toISOString(),
  ...overrides
});

describe('CallStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    callStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.calls).toEqual([]);
      expect(state.activeCall).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let c: Call[] = [], ac: Call | null = null, load = false;
      const unsubC = calls.subscribe(s => { c = s; });
      const unsubA = activeCall.subscribe(s => { ac = s; });
      const unsubL = callLoading.subscribe(s => { load = s; });
      expect(c).toEqual([]);
      expect(ac).toBeNull();
      expect(load).toBe(false);
      unsubC(); unsubA(); unsubL();
    });
  });

  describe('loadCalls', () => {
    it('loads calls successfully', async () => {
      const callsList = [mockCall(), mockCall()];
      vi.spyOn(callService, 'getCalls').mockResolvedValue(callsList);

      await callStore.loadCalls();

      expect(callService.getCalls).toHaveBeenCalledTimes(1);
      const state = getState();
      expect(state.calls).toEqual(callsList);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(callService, 'getCalls').mockReturnValue(p as any);

      const loadPromise = callStore.loadCalls();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockCall()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Network error');
      vi.spyOn(callService, 'getCalls').mockRejectedValue(error);

      await callStore.loadCalls();

      const state = getState();
      expect(state.error).toBe('Network error');
      expect(state.loading).toBe(false);
      expect(state.calls).toEqual([]);
    });
  });

  describe('initiateCall', () => {
    it('initiates call successfully and sets activeCall', async () => {
      const call = mockCall({ id: 'new-call' });
      vi.spyOn(callService, 'initiateCall').mockResolvedValue(call);

      const result = await callStore.initiateCall('callee-123', 'audio');

      expect(result).toEqual(call);
      expect(callService.initiateCall).toHaveBeenCalledWith({ calleeId: 'callee-123', type: 'audio' });
      const state = getState();
      expect(state.activeCall).toEqual(call);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(callService, 'initiateCall').mockReturnValue(p as any);

      const loadPromise = callStore.initiateCall('callee-1', 'video');

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(mockCall());
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error and does not set activeCall', async () => {
      const error = new Error('Failed to initiate');
      vi.spyOn(callService, 'initiateCall').mockRejectedValue(error);

      await expect(callStore.initiateCall('callee-1', 'audio')).rejects.toThrow('Failed to initiate');

      const state = getState();
      expect(state.error).toBe('Failed to initiate');
      expect(state.activeCall).toBeNull();
      expect(state.loading).toBe(false);
    });
  });

  describe('endCall', () => {
    it('ends call and moves it to calls list', async () => {
      const active = mockCall({ id: 'call-1' });
      const endedCall = mockCall({ id: 'call-1', status: 'ended' });
      vi.spyOn(callService, 'endCall').mockResolvedValue(endedCall);

      // Pre-set activeCall via initiateCall (since setState not exported)
      vi.spyOn(callService, 'initiateCall').mockResolvedValue(active);
      await callStore.initiateCall('callee-1', 'audio');
      expect(getState().activeCall).toEqual(active);

      const result = await callStore.endCall('call-1');

      expect(result).toEqual(endedCall);
      expect(callService.endCall).toHaveBeenCalledWith('call-1');
      const state = getState();
      expect(state.activeCall).toBeNull();
      expect(state.calls).toContainEqual(endedCall);
    });

    it('handles error', async () => {
      const error = new Error('End failed');
      vi.spyOn(callService, 'endCall').mockRejectedValue(error);

      // Set activeCall via initiateCall
      const active = mockCall({ id: 'call-1' });
      vi.spyOn(callService, 'initiateCall').mockResolvedValue(active);
      await callStore.initiateCall('callee-1', 'audio');
      expect(getState().activeCall).not.toBeNull();

      await expect(callStore.endCall('call-1')).rejects.toThrow('End failed');

      const state = getState();
      // endCall does not catch errors, so error state remains unchanged (null)
      expect(state.error).toBeNull();
      // activeCall should remain since endCall threw before updating
      expect(state.activeCall).toEqual(active);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      // Force an error state
      vi.spyOn(callService, 'getCalls').mockRejectedValue(new Error('test error'));
      await callStore.loadCalls().catch(() => {});

      expect(getState().error).toBe('test error');

      callStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Set some state: load calls to populate calls array
      const callsList = [mockCall(), mockCall()];
      vi.spyOn(callService, 'getCalls').mockResolvedValue(callsList);
      await callStore.loadCalls();
      expect(getState().calls).toHaveLength(2);

      // Set activeCall via initiateCall
      const active = mockCall();
      vi.spyOn(callService, 'initiateCall').mockResolvedValue(active);
      await callStore.initiateCall('callee-1', 'audio');
      expect(getState().activeCall).toEqual(active);

      // Now reset
      callStore.reset();

      const state = getState();
      expect(state.calls).toEqual([]);
      expect(state.activeCall).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
