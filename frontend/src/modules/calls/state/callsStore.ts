import { writable, get } from 'svelte/store';
import type { Call, CallHistory, CallState, CreateCallRequest, JoinCallRequest } from '../contracts/callTypes';
import { CallService } from '../services/callService';

const initialState: CallState = {
  currentCall: null,
  callHistory: [],
  incomingCall: null,
  loading: false,
  error: null,
  isMuted: false,
  isVideoOn: true,
  isScreenSharing: false,
  isRecording: false
};

function createCallsStore() {
  const store = writable<CallState>(initialState);
  const { subscribe, set, update } = store;
  const service = new CallService();
  let unsubscribeIncoming: (() => void) | null = null;

  return {
    subscribe,

    // Initialize and subscribe to incoming calls
    async init() {
      unsubscribeIncoming = service.subscribeToIncomingCalls((call) => {
        update(state => ({ ...state, incomingCall: call }));
      });
    },

    async createCall(request: CreateCallRequest) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const call = await service.createCall(request);
        update(state => ({
          ...state,
          currentCall: call,
          loading: false
        }));
        return call;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to create call',
          loading: false
        }));
        throw error;
      }
    },

    async joinCall(request: JoinCallRequest) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const call = await service.joinCall(request);
        update(state => ({
          ...state,
          currentCall: call,
          loading: false
        }));
        return call;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to join call',
          loading: false
        }));
        throw error;
      }
    },

    async leaveCall() {
      const currentState = get(store);
      if (!currentState.currentCall) return;

      try {
        await service.leaveCall(currentState.currentCall.id);
        update(state => ({ ...state, currentCall: null }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to leave call'
        }));
      }
    },

    async endCall() {
      const currentState = get(store);
      if (!currentState.currentCall) return;

      try {
        await service.endCall(currentState.currentCall.id);
        update(state => ({ ...state, currentCall: null }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to end call'
        }));
      }
    },

    async acceptIncomingCall() {
      const currentState = get(store);
      if (!currentState.incomingCall) return;

      try {
        const call = await service.joinCall({
          callId: currentState.incomingCall.id,
          roomId: currentState.incomingCall.roomId
        });
        update(state => ({
          ...state,
          currentCall: call,
          incomingCall: null
        }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to accept call'
        }));
      }
    },

    async declineIncomingCall() {
      update(state => ({ ...state, incomingCall: null }));
    },

    async loadActiveCalls() {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const calls = await service.getActiveCalls();
        // If there's an active call for current user, set it
        update(state => ({ ...state, loading: false }));
        return calls;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load calls',
          loading: false
        }));
        return [];
      }
    },

    async loadCallHistory() {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const history = await service.getCallHistory();
        update(state => ({ ...state, callHistory: history, loading: false }));
        return history;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load history',
          loading: false
        }));
        return [];
      }
    },

    async toggleMute() {
      const currentState = get(store);
      if (!currentState.currentCall) return;

      const newMuted = !currentState.isMuted;
      try {
        await service.toggleMute(currentState.currentCall.id, newMuted);
        update(state => ({ ...state, isMuted: newMuted }));
      } catch (error) {
        console.error('Failed to toggle mute:', error);
      }
    },

    async toggleVideo() {
      const currentState = get(store);
      if (!currentState.currentCall) return;

      const newVideoOn = !currentState.isVideoOn;
      try {
        await service.toggleVideo(currentState.currentCall.id, newVideoOn);
        update(state => ({ ...state, isVideoOn: newVideoOn }));
      } catch (error) {
        console.error('Failed to toggle video:', error);
      }
    },

    async toggleScreenShare() {
      const currentState = get(store);
      if (!currentState.currentCall) return;

      const newScreenSharing = !currentState.isScreenSharing;
      try {
        if (newScreenSharing) {
          await service.startScreenShare(currentState.currentCall.id);
        } else {
          await service.stopScreenShare(currentState.currentCall.id);
        }
        update(state => ({ ...state, isScreenSharing: newScreenSharing }));
      } catch (error) {
        console.error('Failed to toggle screen share:', error);
      }
    },

    async toggleRecording() {
      const currentState = get(store);
      if (!currentState.currentCall) return;

      const newRecording = !currentState.isRecording;
      try {
        if (newRecording) {
          await service.startRecording(currentState.currentCall.id);
        } else {
          await service.stopRecording(currentState.currentCall.id);
        }
        update(state => ({ ...state, isRecording: newRecording }));
      } catch (error) {
        console.error('Failed to toggle recording:', error);
      }
    },

    reset() {
      set(initialState);
    },

    clearError() {
      update(state => ({ ...state, error: null }));
    },

    cleanup() {
      if (unsubscribeIncoming) {
        unsubscribeIncoming();
      }
    }
  };
}

export const callsStore = createCallsStore();
