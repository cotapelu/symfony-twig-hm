export interface CallParticipant {
  id: string;
  name: string;
  avatar?: string;
  isHost: boolean;
  isMuted: boolean;
  isVideoOn: boolean;
  isScreenSharing: boolean;
  isSpeaking: boolean;
  joinedAt: string;
}

export interface Call {
  id: string;
  roomId: string;
  type: 'voice' | 'video';
  status: 'ringing' | 'ongoing' | 'ended' | 'missed';
  participants: CallParticipant[];
  hostId: string;
  startTime?: string;
  endTime?: string;
  duration?: number; // in seconds
  recordingEnabled: boolean;
  isGroupCall: boolean;
  maxParticipants: number;
}

export interface CallHistory {
  id: string;
  roomId: string;
  type: 'voice' | 'video';
  status: 'completed' | 'missed' | 'declined';
  participants: CallParticipant[];
  startTime: string;
  endTime?: string;
  duration: number;
}

export interface CreateCallRequest {
  type: 'voice' | 'video';
  participantIds: string[];
  roomId?: string;
}

export interface JoinCallRequest {
  callId: string;
  roomId: string;
}

export interface CallState {
  currentCall: Call | null;
  callHistory: CallHistory[];
  incomingCall: Call | null;
  loading: boolean;
  error: string | null;
  isMuted: boolean;
  isVideoOn: boolean;
  isScreenSharing: boolean;
  isRecording: boolean;
}

export interface CallSettings {
  defaultAudioInput: string;
  defaultAudioOutput: string;
  defaultVideoInput: string;
  enableNoiseCancellation: boolean;
  enableEchoCancellation: boolean;
  autoAnswer: boolean;
  ringtone: string;
}

export interface MediaDevice {
  id: string;
  label: string;
  kind: 'audioinput' | 'audiooutput' | 'videoinput';
}

export interface CallQuality {
  connectionQuality: 'excellent' | 'good' | 'fair' | 'poor';
  audioQuality: number; // 0-100
  videoQuality: number; // 0-100
  packetLoss: number; // percentage
  latency: number; // ms
}