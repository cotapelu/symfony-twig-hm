import { describe, it, expect, vi, beforeEach } from 'vitest';
import { securityStore, loadSecurityData, enable2FA, disable2FA, revokeSession, clearError } from './securityStore';
import { securityService } from '../services/securityService';
import type { Session, AuditLog } from '../contracts/securityTypes';

function getCurrentState() {
  let current: any;
  const unsub = securityStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date().toISOString();

const mockSession = (overrides: Partial<Session> = {}): Session => ({
  id: `sess-${Math.random().toString(36).substr(2, 5)}`,
  device: 'iPhone',
  location: 'NYC',
  lastActive: now,
  current: true,
  ipAddress: '127.0.0.1',
  userAgent: 'Safari',
  ...overrides
});

const mockAuditLog = (overrides: Partial<AuditLog> = {}): AuditLog => ({
  id: `log-${Math.random().toString(36).substr(2, 5)}`,
  action: 'login',
  timestamp: now,
  ip: '127.0.0.1',
  details: 'User login',
  ...overrides
});

describe('SecurityStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Reset store to initial state
    securityStore.set({ twoFactorEnabled: false, sessions: [], auditLogs: [], loading: false, error: null });
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getCurrentState();
      expect(state.twoFactorEnabled).toBe(false);
      expect(state.sessions).toEqual([]);
      expect(state.auditLogs).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('loadSecurityData', () => {
    it('loads 2FA status, sessions, and audit logs in parallel', async () => {
      const twoFactor = { enabled: true };
      const sessions = [mockSession(), mockSession()];
      const logs = [mockAuditLog(), mockAuditLog(), mockAuditLog()];
      vi.spyOn(securityService, 'get2FAStatus').mockResolvedValue(twoFactor);
      vi.spyOn(securityService, 'getSessions').mockResolvedValue(sessions);
      vi.spyOn(securityService, 'getAuditLogs').mockResolvedValue(logs);
      await loadSecurityData();
      const state = getCurrentState();
      expect(state.twoFactorEnabled).toBe(true);
      expect(state.sessions).toEqual(sessions);
      expect(state.auditLogs).toEqual(logs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(securityService, 'get2FAStatus').mockReturnValue(p as any);
      const loadPromise = loadSecurityData();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise({ enabled: false });
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles empty responses', async () => {
      vi.spyOn(securityService, 'get2FAStatus').mockResolvedValue({ enabled: false });
      vi.spyOn(securityService, 'getSessions').mockResolvedValue([]);
      vi.spyOn(securityService, 'getAuditLogs').mockResolvedValue([]);
      await loadSecurityData();
      expect(getCurrentState().sessions).toEqual([]);
      expect(getCurrentState().auditLogs).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(securityService, 'get2FAStatus').mockRejectedValue(new Error('2FA fail'));
      await loadSecurityData();
      const state = getCurrentState();
      expect(state.error).toBe('2FA fail');
      expect(state.loading).toBe(false);
      expect(state.sessions).toEqual([]);
      expect(state.auditLogs).toEqual([]);
    });
  });

  describe('enable2FA', () => {
    it('enables 2FA on success', async () => {
      // Need initial 2FA status false; no need to load
      vi.spyOn(securityService, 'enable2FA').mockResolvedValue({ success: true });
      await enable2FA('123456');
      const state = getCurrentState();
      expect(state.twoFactorEnabled).toBe(true);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('propagates errors', async () => {
      vi.spyOn(securityService, 'enable2FA').mockRejectedValue(new Error('Invalid code'));
      try {
        await enable2FA('bad');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Invalid code');
      expect(state.twoFactorEnabled).toBe(false);
    });
  });

  describe('disable2FA', () => {
    it('disables 2FA on success', async () => {
      // Start with enabled
      securityStore.set({ twoFactorEnabled: true, sessions: [], auditLogs: [], loading: false, error: null });
      vi.spyOn(securityService, 'disable2FA').mockResolvedValue({ success: true });
      await disable2FA();
      const state = getCurrentState();
      expect(state.twoFactorEnabled).toBe(false);
    });

    it('propagates errors', async () => {
      vi.spyOn(securityService, 'disable2FA').mockRejectedValue(new Error('Failed'));
      try {
        await disable2FA();
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Failed');
    });
  });

  describe('revokeSession', () => {
    it('removes session from list on success', async () => {
      const session1 = mockSession({ id: 's1' });
      const session2 = mockSession({ id: 's2' });
      securityStore.set({ twoFactorEnabled: false, sessions: [session1, session2], auditLogs: [], loading: false, error: null });
      vi.spyOn(securityService, 'revokeSession').mockResolvedValue(undefined);
      await revokeSession('s1');
      const state = getCurrentState();
      expect(state.sessions.find(s => s.id === 's1')).toBeUndefined();
      expect(state.sessions.length).toBe(1);
    });

    it('handles error', async () => {
      const session = mockSession({ id: 's1' });
      securityStore.set({ twoFactorEnabled: false, sessions: [session], auditLogs: [], loading: false, error: null });
      vi.spyOn(securityService, 'revokeSession').mockRejectedValue(new Error('Revoke failed'));
      try {
        await revokeSession('s1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Revoke failed');
      expect(state.sessions.length).toBe(1); // unchanged
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(securityService, 'get2FAStatus').mockRejectedValue(new Error('test'));
      try { await loadSecurityData(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });
});
