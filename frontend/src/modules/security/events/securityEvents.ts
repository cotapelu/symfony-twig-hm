import type { Session, AuditLog } from '../contracts/securityTypes';

export const SecurityEvents = {
  TWO_FA_ENABLED: 'security:2fa_enabled',
  TWO_FA_DISABLED: 'security:2fa_disabled',
  SESSION_REVOKED: 'security:session_revoked',
  SECURITY_ERROR: 'security:error'
};

export interface SecurityEventDetail {
  enabled?: boolean;
  sessionId?: string;
  error?: string;
}

export function dispatchSecurityEvent(event: string, detail?: SecurityEventDetail) {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent(event, { detail }));
  }
}