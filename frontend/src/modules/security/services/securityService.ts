import type { Session, AuditLog, Enable2FARequest, RevokeSessionRequest } from '../contracts/securityTypes';

const API_BASE = '/api/security';

export class SecurityService {
  async get2FAStatus(): Promise<{ enabled: boolean }> {
    const response = await fetch(`${API_BASE}/2fa/status`);
    if (!response.ok) {
      throw new Error('Failed to get 2FA status');
    }
    return response.json();
  }

  async enable2FA(request: Enable2FARequest): Promise<{ success: boolean }> {
    const response = await fetch(`${API_BASE}/2fa/enable`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) {
      throw new Error('Failed to enable 2FA');
    }
    return response.json();
  }

  async disable2FA(): Promise<{ success: boolean }> {
    const response = await fetch(`${API_BASE}/2fa/disable`, {
      method: 'POST'
    });
    if (!response.ok) {
      throw new Error('Failed to disable 2FA');
    }
    return response.json();
  }

  async getSessions(): Promise<Session[]> {
    const response = await fetch(`${API_BASE}/sessions`);
    if (!response.ok) {
      throw new Error('Failed to get sessions');
    }
    return response.json();
  }

  async revokeSession(request: RevokeSessionRequest): Promise<void> {
    const response = await fetch(`${API_BASE}/sessions/${request.sessionId}/revoke`, {
      method: 'POST'
    });
    if (!response.ok) {
      throw new Error('Failed to revoke session');
    }
  }

  async getAuditLogs(): Promise<AuditLog[]> {
    const response = await fetch(`${API_BASE}/audit-logs`);
    if (!response.ok) {
      throw new Error('Failed to get audit logs');
    }
    return response.json();
  }
}

export const securityService = new SecurityService();
export default securityService;