import { writable } from 'svelte/store';
import type { SecurityState } from '../contracts/securityTypes';
import { securityService } from '../services/securityService';

const initialState: SecurityState = {
  twoFactorEnabled: false,
  sessions: [],
  auditLogs: [],
  loading: false,
  error: null
};

export const securityStore = writable<SecurityState>(initialState);

export async function loadSecurityData() {
  securityStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    const [twoFactorStatus, sessions, auditLogs] = await Promise.all([
      securityService.get2FAStatus(),
      securityService.getSessions(),
      securityService.getAuditLogs()
    ]);
    
    securityStore.update(s => ({
      ...s,
      twoFactorEnabled: twoFactorStatus.enabled,
      sessions,
      auditLogs,
      loading: false
    }));
  } catch (e) {
    securityStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
  }
}

export async function enable2FA(code: string) {
  securityStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    await securityService.enable2FA({ code });
    securityStore.update(s => ({ ...s, twoFactorEnabled: true, loading: false }));
  } catch (e) {
    securityStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
    throw e;
  }
}

export async function disable2FA() {
  securityStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    await securityService.disable2FA();
    securityStore.update(s => ({ ...s, twoFactorEnabled: false, loading: false }));
  } catch (e) {
    securityStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
    throw e;
  }
}

export async function revokeSession(sessionId: string) {
  securityStore.update(s => ({ ...s, loading: true, error: null }));
  try {
    await securityService.revokeSession({ sessionId });
    securityStore.update(s => ({
      ...s,
      sessions: s.sessions.filter(sess => sess.id !== sessionId),
      loading: false
    }));
  } catch (e) {
    securityStore.update(s => ({ ...s, error: (e as Error).message, loading: false }));
    throw e;
  }
}

export function clearError() {
  securityStore.update(s => ({ ...s, error: null }));
}