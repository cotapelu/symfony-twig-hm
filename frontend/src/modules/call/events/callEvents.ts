export interface CallInitiatedEvent { type: 'call.initiated'; payload: { callId: string; callerId: string; calleeId: string }; }
export interface CallConnectedEvent { type: 'call.connected'; payload: { callId: string }; }
export interface CallEndedEvent { type: 'call.ended'; payload: { callId: string; duration: number }; }
export type CallEvent = CallInitiatedEvent | CallConnectedEvent | CallEndedEvent;

export const callEvents = {
  callInitiated: (callId: string, callerId: string, calleeId: string): CallInitiatedEvent => ({
    type: 'call.initiated', payload: { callId, callerId, calleeId }
  }),
  callConnected: (callId: string): CallConnectedEvent => ({ type: 'call.connected', payload: { callId } }),
  callEnded: (callId: string, duration: number): CallEndedEvent => ({ type: 'call.ended', payload: { callId, duration } })
};
