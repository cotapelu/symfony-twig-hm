export interface Session {
  id: string;
  device: string;
  location: string;
  lastActive: string;
  current: boolean;
  ipAddress?: string;
  userAgent?: string;
}

export interface AuditLog {
  id: string;
  action: string;
  timestamp: string;
  ip: string;
  details?: string;
}

export interface SecurityState {
  twoFactorEnabled: boolean;
  sessions: Session[];
  auditLogs: AuditLog[];
  loading: boolean;
  error: string | null;
}

export interface Enable2FARequest {
  code: string;
}

export interface RevokeSessionRequest {
  sessionId: string;
}