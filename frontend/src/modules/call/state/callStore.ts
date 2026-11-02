import { writable, derived } from 'svelte/store';
import type { Call, CallsState } from '../contracts/callTypes';
import { callService } from '../services/callService';

const initialState: CallsState = {
  calls: [],
  activeCall: null,
  loading: false,
  error: null
};

function createCallStore() {
  const { subscribe, set, update } = writable<CallsState>(initialState);
  return {
    subscribe,
    async loadCalls() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const calls = await callService.getCalls();
        update(s => ({ ...s, calls, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async initiateCall(calleeId: string, type: 'audio' | 'video') {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const call = await callService.initiateCall({ calleeId, type });
        update(s => ({ ...s, activeCall: call, loading: false }));
        return call;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },
    async endCall(id: string) {
      const call = await callService.endCall(id);
      update(s => ({ ...s, activeCall: null, calls: [...s.calls, call] }));
      return call;
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const callStore = createCallStore();
export const calls = derived(callStore, $s => $s.calls);
export const activeCall = derived(callStore, $s => $s.activeCall);
export const callLoading = derived(callStore, $s => $s.loading);
