import { describe, it, expect, vi, beforeEach } from 'vitest';
import { SecurityService } from './securityService';

global.fetch = vi.fn();

describe('SecurityService', () => {
  let service: SecurityService;

  beforeEach(() => {
    service = new SecurityService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('get2FAStatus', () => {
    it('should fetch 2FA status', async () => {
      const status = { enabled: true };
      (global.fetch as any).mockResolvedValue(mockResponse(status));
      const result = await service.get2FAStatus();
      expect(result).toEqual(status);
      expect(fetch).toHaveBeenCalledWith('/api/security/2fa/status');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.get2FAStatus()).rejects.toThrow('Failed to get 2FA status');
    });
  });

  describe('enable2FA', () => {
    it('should enable 2FA', async () => {
      const res = { success: true };
      (global.fetch as any).mockResolvedValue(mockResponse(res));
      const result = await service.enable2FA({ secret: 'abc' } as any);
      expect(result).toEqual(res);
      expect(fetch).toHaveBeenCalledWith('/api/security/2fa/enable', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ secret: 'abc' })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.enable2FA({} as any)).rejects.toThrow('Failed to enable 2FA');
    });
  });

  describe('disable2FA', () => {
    it('should disable 2FA', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse({ success: true }));
      const result = await service.disable2FA();
      expect(result).toEqual({ success: true });
      expect(fetch).toHaveBeenCalledWith('/api/security/2fa/disable', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.disable2FA()).rejects.toThrow('Failed to disable 2FA');
    });
  });

  describe('getSessions', () => {
    it('should fetch sessions', async () => {
      const sessions = [{ id: 's1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(sessions));
      const result = await service.getSessions();
      expect(result).toEqual(sessions);
      expect(fetch).toHaveBeenCalledWith('/api/security/sessions');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getSessions()).rejects.toThrow('Failed to get sessions');
    });
  });

  describe('revokeSession', () => {
    it('should revoke a session', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.revokeSession({ sessionId: 's1' } as any);
      expect(fetch).toHaveBeenCalledWith('/api/security/sessions/s1/revoke', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.revokeSession({ sessionId: 's1' } as any)).rejects.toThrow('Failed to revoke session');
    });
  });

  describe('getAuditLogs', () => {
    it('should fetch audit logs', async () => {
      const logs = [{ id: 'l1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(logs));
      const result = await service.getAuditLogs();
      expect(result).toEqual(logs);
      expect(fetch).toHaveBeenCalledWith('/api/security/audit-logs');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getAuditLogs()).rejects.toThrow('Failed to get audit logs');
    });
  });
});
