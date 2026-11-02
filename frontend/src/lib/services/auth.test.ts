import { describe, it, expect, vi, beforeEach } from 'vitest';
import { authService } from './auth';
import type { AuthResponse, UserResponse } from './auth';

// Mock global fetch
const fetchMock = vi.fn();
global.fetch = fetchMock;

// Mock localStorage
let storage: Record<string, string> = {};
const localStorageMock = {
  getItem: vi.fn((key: string) => storage[key] ?? null),
  setItem: vi.fn((key: string, value: string) => { storage[key] = value; }),
  removeItem: vi.fn((key: string) => { delete storage[key]; }),
  clear: vi.fn(() => { Object.keys(storage).forEach(k => delete storage[k]); }),
};
Object.defineProperty(global, 'localStorage', { value: localStorageMock, writable: true });

beforeEach(() => {
  vi.clearAllMocks();
  storage = {};
});

const gateway = 'http://localhost:8080';

function mockJsonResponse<T>(data: T) {
  return { ok: true, json: vi.fn().mockResolvedValue(data) } as any;
}

describe('AuthService', () => {
  describe('login', () => {
    it('success returns AuthResponse', async () => {
      const response: AuthResponse = {
        access_token: 'abc123',
        refresh_token: 'ref123',
        expires_in: 3600,
        token_type: 'Bearer',
        user: { id: '1', email: 'test@example.com', username: 'test', role: 'user', created_at: '', updated_at: '', is_active: true, is_mfa_enabled: false } as any,
      };
      fetchMock.mockResolvedValue(mockJsonResponse(response));

      const result = await authService.login({ email: 'test@example.com', password: 'pw' });

      expect(result).toEqual(response);
      expect(fetchMock).toHaveBeenCalledWith(`${gateway}/api/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: 'test@example.com', password: 'pw' }),
      });
    });

    it('failure throws with error message from response', async () => {
      fetchMock.mockResolvedValue({ ok: false, json: vi.fn().mockResolvedValue({ error: 'Invalid credentials' }) });
      await expect(authService.login({ email: 'test@example.com', password: 'pw' })).rejects.toThrow('Invalid credentials');
    });
  });

  describe('register', () => {
    it('success returns AuthResponse', async () => {
      const response: AuthResponse = {
        access_token: 'token',
        refresh_token: 'refresh',
        expires_in: 7200,
        token_type: 'Bearer',
        user: { id: '2', email: 'new@example.com', username: 'new', role: 'member', created_at: '', updated_at: '', is_active: true, is_mfa_enabled: false } as any,
      };
      fetchMock.mockResolvedValue(mockJsonResponse(response));

      const result = await authService.register({ email: 'new@example.com', password: 'pw', username: 'new' });

      expect(result).toEqual(response);
      expect(fetchMock).toHaveBeenCalledWith(`${gateway}/api/auth/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: 'new@example.com', password: 'pw', username: 'new' }),
      });
    });

    it('failure throws', async () => {
      fetchMock.mockResolvedValue({ ok: false, json: vi.fn().mockResolvedValue({ error: 'Email exists' }) });
      await expect(authService.register({ email: 'e@example.com', password: 'pw', username: 'u' })).rejects.toThrow('Email exists');
    });
  });

  describe('logout', () => {
    it('calls POST /api/auth/logout with user_id and session_id', async () => {
      fetchMock.mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await authService.logout('user123', 'sess456');

      expect(fetchMock).toHaveBeenCalledWith(`${gateway}/api/auth/logout`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: 'user123', session_id: 'sess456' }),
      });
    });
  });

  describe('refreshToken', () => {
    it('success returns new AuthResponse', async () => {
      const response: AuthResponse = {
        access_token: 'newtoken',
        refresh_token: 'newrefresh',
        expires_in: 3600,
        token_type: 'Bearer',
        user: { id: '1', email: 'test@example.com', username: 'test', role: 'user', created_at: '', updated_at: '', is_active: true, is_mfa_enabled: false } as any,
      };
      fetchMock.mockResolvedValue(mockJsonResponse(response));

      const result = await authService.refreshToken('oldrefresh');

      expect(result).toEqual(response);
      expect(fetchMock).toHaveBeenCalledWith(`${gateway}/api/auth/refresh`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refresh_token: 'oldrefresh' }),
      });
    });

    it('failure throws generic error', async () => {
      fetchMock.mockResolvedValue({ ok: false });
      await expect(authService.refreshToken('bad')).rejects.toThrow('Token refresh failed');
    });
  });

  describe('getMe', () => {
    it('returns user info with Authorization header', async () => {
      const userResp: UserResponse = {
        id: '1',
        email: 'test@example.com',
        username: 'test',
        role: 'user',
        created_at: '2024-01-01',
        updated_at: '2024-01-02',
        is_active: true,
        is_mfa_enabled: false,
      };
      fetchMock.mockResolvedValue(mockJsonResponse({ user: userResp }));

      const result = await authService.getMe('myaccesstoken');

      expect(result).toEqual({ user: userResp });
      expect(fetchMock).toHaveBeenCalledWith(`${gateway}/api/auth/me`, {
        method: 'GET',
        headers: { Authorization: 'Bearer myaccesstoken' },
      });
    });

    it('throws generic error on failure', async () => {
      fetchMock.mockResolvedValue({ ok: false });
      await expect(authService.getMe('token')).rejects.toThrow('Failed to get user info');
    });
  });

  describe('setupMFA', () => {
    it('returns secret, qr_code_url, backup_codes', async () => {
      const data = { secret: 'abc123', qr_code_url: 'http://qr', backup_codes: ['code1', 'code2'] };
      fetchMock.mockResolvedValue(mockJsonResponse(data));

      const result = await authService.setupMFA('user99');

      expect(result).toEqual(data);
      expect(fetchMock).toHaveBeenCalledWith(`${gateway}/api/auth/mfa/setup`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: 'user99' }),
      });
    });

    it('throws generic error on failure', async () => {
      fetchMock.mockResolvedValue({ ok: false });
      await expect(authService.setupMFA('user99')).rejects.toThrow('MFA setup failed');
    });
  });

  describe('verifyMFA', () => {
    it('sends user_id, code and optional backupCode', async () => {
      fetchMock.mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue({ success: true }) });

      await authService.verifyMFA('user99', '123456', 'backup789');

      expect(fetchMock).toHaveBeenCalledWith(`${gateway}/api/auth/mfa/verify`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: 'user99', code: '123456', backup_code: 'backup789' }),
      });
    });

    it('throws generic error on failure', async () => {
      fetchMock.mockResolvedValue({ ok: false });
      await expect(authService.verifyMFA('user99', '123')).rejects.toThrow('MFA verification failed');
    });

    it('works without backupCode', async () => {
      fetchMock.mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue({}) });
      await authService.verifyMFA('user99', '123456');
      expect(fetchMock).toHaveBeenCalledWith(`${gateway}/api/auth/mfa/verify`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: 'user99', code: '123456' }),
      });
    });
  });
});
