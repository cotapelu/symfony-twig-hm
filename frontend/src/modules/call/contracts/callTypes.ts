export interface Call {
  id: string;
  callerId: string;
  calleeId: string;
  status: 'initiated' | 'ringing' | 'connected' | 'ended' | 'missed';
  type: 'audio' | 'video';
  startedAt?: string;
  endedAt?: string;
  duration?: number;
}

export interface CallRequest {
  calleeId: string;
  type: 'audio' | 'video';
}

export interface CallsState {
  calls: Call[];
  activeCall: Call | null;
  loading: boolean;
  error: string | null;
}
