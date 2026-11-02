export interface Voicemail {
  id: string;
  callerId: string;
  callerName: string;
  duration: number;
  timestamp: string;
  listened: boolean;
  audioUrl: string;
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface VoicemailState {
  voicemails: Voicemail[];
  currentVoicemail: Voicemail | null;
  loading: boolean;
  error: string | null;
  pagination: {
    page: number;
    limit: number;
    total: number;
    totalPages: number;
  };
}