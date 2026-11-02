export interface Stream {
  id: string;
  userId: string;
  title: string;
  description?: string;
  thumbnail?: string;
  status: 'preparing' | 'live' | 'ended' | 'paused';
  viewerCount: number;
  totalViewers: number;
  startedAt?: string;
  endedAt?: string;
  duration?: number;
  tags?: string[];
}

export interface StreamSession {
  id: string;
  streamId: string;
  userId: string;
  status: 'active' | 'ended';
  startedAt: string;
  endedAt?: string;
  metrics: {
    peakViewers: number;
    avgViewers: number;
    likes: number;
    shares: number;
    comments: number;
  };
}

export interface Viewer {
  id: string;
  userId?: string;
  streamId: string;
  joinedAt: string;
  leftAt?: string;
  duration?: number;
}

export interface LiveComment {
  id: string;
  streamId: string;
  userId: string;
  username: string;
  message: string;
  timestamp: string;
}

export interface StreamState {
  streams: Stream[];
  currentStream: Stream | null;
  comments: LiveComment[];
  loading: boolean;
  error: string | null;
}

export interface CreateStreamRequest {
  title: string;
  description?: string;
  tags?: string[];
}
