// Calls Store - TypeScript Version
// Zig/WebRTC Call Module with type safety

import { writable, derived } from "svelte/store";
import { Zig } from "../lib/api";
import type { Call } from "../lib/types";

interface CallsState {
  activeCalls: Call[];
  currentCall: Call | null;
  localStream: MediaStream | null;
  remoteStreams: Record<string, MediaStream>;
  loading: boolean;
  error: string | null;
}

function createCallsStore() {
  const initialState: CallsState = {
    activeCalls: [],
    currentCall: null,
    localStream: null,
    remoteStreams: {},
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<CallsState>(initialState);

  return {
    subscribe,

    loadActiveCalls: async (): Promise<Call[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const calls = await Zig.getActiveCalls();
        const activeCalls = calls || [];
        set({
          ...initialState,
          activeCalls,
          loading: false,
        });
        return activeCalls;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to load active calls";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    initiateCall: async (data: {
      participantId: string;
      type: "audio" | "video";
    }): Promise<Call> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const call = await Zig.initiateCall(data);
        update((state) => ({
          ...state,
          activeCalls: [...state.activeCalls, call],
          currentCall: call,
          loading: false,
        }));
        return call;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to initiate call";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    joinCall: async (callId: string): Promise<Call> => {
      try {
        const call = await Zig.joinCall(callId);
        update((state) => ({
          ...state,
          currentCall: call,
        }));
        return call;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to join call";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    leaveCall: async (): Promise<void> => {
      try {
        update((state) => {
          if (state.currentCall?.callId) {
            Zig.endCall(state.currentCall.callId).catch(console.error);
          }
          return {
            ...state,
            currentCall: null,
            localStream: null,
            remoteStreams: {},
          };
        });
      } catch (error) {
        console.error("Error ending call:", error);
      }
    },

    endCall: async (callId: string): Promise<void> => {
      try {
        await Zig.endCall(callId);
        update((state) => ({
          ...state,
          activeCalls: state.activeCalls.filter((c) => c.callId !== callId),
          currentCall:
            state.currentCall?.callId === callId ? null : state.currentCall,
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to end call";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    setLocalStream: (stream: MediaStream | null): void => {
      update((state) => ({ ...state, localStream: stream }));
    },

    addRemoteStream: (userId: string, stream: MediaStream): void => {
      update((state) => ({
        ...state,
        remoteStreams: {
          ...state.remoteStreams,
          [userId]: stream,
        },
      }));
    },

    removeRemoteStream: (userId: string): void => {
      update((state) => {
        const { [userId]: _removed, ...rest } = state.remoteStreams;
        return { ...state, remoteStreams: rest };
      });
    },

    addActiveCall: (call: Call): void => {
      update((state) => ({
        ...state,
        activeCalls: [...state.activeCalls, call],
      }));
    },

    updateActiveCall: (call: Call): void => {
      update((state) => ({
        ...state,
        activeCalls: state.activeCalls.map((c) =>
          c.callId === call.callId ? { ...c, ...call } : c,
        ),
        currentCall:
          state.currentCall?.callId === call.callId
            ? { ...state.currentCall, ...call }
            : state.currentCall,
      }));
    },

    removeActiveCall: (callId: string): void => {
      update((state) => ({
        ...state,
        activeCalls: state.activeCalls.filter((c) => c.callId !== callId),
        currentCall:
          state.currentCall?.callId === callId ? null : state.currentCall,
      }));
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        activeCalls: [],
        currentCall: null,
        localStream: null,
        remoteStreams: {},
        loading: false,
        error: null,
      });
    },
  };
}

export const callsStore = createCallsStore();

export const {
  loadActiveCalls,
  initiateCall,
  joinCall,
  leaveCall,
  endCall,
  setLocalStream,
  addRemoteStream,
  removeRemoteStream,
  addActiveCall,
  updateActiveCall,
  removeActiveCall,
  clearError,
} = callsStore;

export const activeCalls = derived(callsStore, ($state) => $state.activeCalls);
export const currentCall = derived(callsStore, ($state) => $state.currentCall);
