import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { IAMAdapter } from './iam-adapter';

interface IAMConfig {
  apiUrl: string;
  clientId: string;
  redirectUri: string;
  scopes: string[];
  tokenRefreshThreshold: number;
  autoRefresh: boolean;
  enableSSO: boolean;
  ssoProviders: string[];
}

const createConfig = (overrides: Partial<IAMConfig> = {}): IAMConfig => ({
  apiUrl: 'https://api.example.com',
  clientId: 'test-client',
  redirectUri: 'https://app.example.com/callback',
  scopes: ['openid', 'profile'],
  tokenRefreshThreshold: 300,
  autoRefresh: true,
  enableSSO: false,
  ssoProviders: [],
  ...overrides,
});

const mockUser = {
  id: 'u1',
  email: 'user@example.com',
  username: 'user1',
  firstName: 'First',
  lastName: 'Last',
  roles: ['user'],
  permissions: ['read'],
  profile: {},
  createdAt: new Date(),
  updatedAt: new Date(),
};

const mockTokens = (expiresIn = 3600) => ({
  accessToken: 'access-token',
  refreshToken: 'refresh-token',
  idToken: 'id-token',
  expiresAt: new Date(Date.now() + expiresIn * 1000),
  tokenType: 'Bearer',
});

describe('IAMAdapter', () => {
  let fetchMock: ReturnType<typeof vi.fn>;
  let localStorageMock: Record<string, string>;
  let onlineHandler: ((this: Window, ev: Event) => void) | null = null;

  beforeEach(() => {
    vi.useFakeTimers();
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    localStorageMock = {};
    const localStorage = {
      getItem: vi.fn((key: string) => localStorageMock[key] ?? null),
      setItem: vi.fn((key: string, value: string) => { localStorageMock[key] = value; }),
      removeItem: vi.fn((key: string) => { delete localStorageMock[key]; }),
    };
    vi.stubGlobal('localStorage', localStorage);

    vi.spyOn(window, 'addEventListener').mockImplementation((event: string, handler: Function) => {
      if (event === 'online') onlineHandler = handler as (this: Window, ev: Event) => void;
    });
    vi.spyOn(window, 'dispatchEvent').mockImplementation(() => true);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
    vi.useRealTimers();
    vi.clearAllTimers();
  });

  describe('Initialization', () => {
    it('starts with default state when no storage', () => {
      const adapter = new IAMAdapter(createConfig());
      expect(adapter.getState()).toMatchObject({
        isAuthenticated: false,
        user: null,
        tokens: null,
        isLoading: false,
        error: null,
      });
    });

    it('restores valid auth state from localStorage', () => {
      const tokens = mockTokens();
      localStorageMock['iam_auth'] = JSON.stringify({ tokens, user: mockUser, isAuthenticated: true });
      const adapter = new IAMAdapter(createConfig());
      expect(adapter.isAuthenticated()).toBe(true);
      expect(adapter.getUser()).toEqual(mockUser);
    });

    it('clears storage when tokens expired', () => {
      const tokens = mockTokens(-10);
      localStorageMock['iam_auth'] = JSON.stringify({ tokens, user: mockUser, isAuthenticated: true });
      const adapter = new IAMAdapter(createConfig());
      expect(adapter.isAuthenticated()).toBe(false);
      expect(localStorage.removeItem).toHaveBeenCalledWith('iam_auth');
    });

    it('handles corrupted storage gracefully', () => {
      localStorageMock['iam_auth'] = 'not json';
      const adapter = new IAMAdapter(createConfig());
      expect(adapter.isAuthenticated()).toBe(false);
      expect(localStorage.removeItem).toHaveBeenCalledWith('iam_auth');
    });
  });

  describe('Login', () => {
    const credentials = { email: 'u@e.com', password: 'p' };

    it('logs in successfully and updates state', async () => {
      fetchMock
        .mockResolvedValueOnce({ ok: true, json: async () => ({ access_token: 'a', refresh_token: 'r', id_token: 'i', expires_in: 3600 }) }) // login
        .mockResolvedValueOnce({ ok: true, json: async () => mockUser }); // profile

      const adapter = new IAMAdapter(createConfig());
      await adapter.login(credentials);

      expect(adapter.isAuthenticated()).toBe(true);
      expect(adapter.getAuthHeader()).toBe('Bearer a');
      expect(localStorage.setItem).toHaveBeenCalled();
    });

    it('emits auth:login event on success', async () => {
      fetchMock
        .mockResolvedValueOnce({ ok: true, json: async () => ({ access_token: 'a', refresh_token: 'r', expires_in: 3600 }) }) // login
        .mockResolvedValueOnce({ ok: true, json: async () => mockUser }); // profile

      const adapter = new IAMAdapter(createConfig());
      const listener = vi.fn();
      adapter.addEventListener('auth:login', listener);
      await adapter.login(credentials);
      expect(listener).toHaveBeenCalledWith({ user: expect.any(Object) });
    });

    it('handles login failure', async () => {
      fetchMock.mockResolvedValueOnce({ ok: false, json: async () => ({ message: 'Invalid' }) });
      const adapter = new IAMAdapter(createConfig());
      await expect(adapter.login(credentials)).rejects.toThrow('Invalid');
      expect(adapter.getState().error).toBe('Invalid');
    });
  });

  describe('Logout', () => {
    it('clears state and storage, even if API fails', async () => {
      fetchMock.mockRejectedValue(new TypeError('fail'));
      const adapter = new IAMAdapter(createConfig());
      (adapter as any).state.tokens = mockTokens();
      (adapter as any).state.user = mockUser;
      (adapter as any).state.isAuthenticated = true;

      await adapter.logout();

      expect(adapter.isAuthenticated()).toBe(false);
      expect(adapter.getUser()).toBeNull();
      expect(localStorage.removeItem).toHaveBeenCalledWith('iam_auth');
    });

    it('does not call API if no tokens', async () => {
      const adapter = new IAMAdapter(createConfig());
      await adapter.logout();
      expect(fetchMock).not.toHaveBeenCalled();
    });
  });

  describe('Refresh Token', () => {
    it('refreshes tokens successfully', async () => {
      const old = mockTokens();
      fetchMock
        .mockResolvedValueOnce({ ok: true, json: async () => ({ access_token: 'new', refresh_token: 'newR', expires_in: 3600 }) })
        .mockResolvedValueOnce({ ok: true, json: async () => mockUser });

      const adapter = new IAMAdapter(createConfig());
      (adapter as any).state.tokens = old;

      await adapter.refreshToken();

      expect(adapter.getAuthHeader()).toBe('Bearer new');
    });

    it('clears auth on refresh failure', async () => {
      fetchMock.mockResolvedValueOnce({ ok: false, json: async () => ({ message: 'bad refresh' }) });
      const adapter = new IAMAdapter(createConfig());
      (adapter as any).state.tokens = mockTokens();

      await expect(adapter.refreshToken()).rejects.toThrow('bad refresh');
      expect(adapter.isAuthenticated()).toBe(false);
    });

    it('throws if no refresh token', async () => {
      const adapter = new IAMAdapter(createConfig());
      await expect(adapter.refreshToken()).rejects.toThrow('No refresh token available');
    });
  });

  describe('Authorization Checks', () => {
    let adapter: IAMAdapter;

    beforeEach(() => {
      adapter = new IAMAdapter(createConfig());
      (adapter as any).state.user = { ...mockUser, roles: ['user', 'admin'], permissions: ['read', 'write'] };
    });

    it('checks roles correctly', () => {
      expect(adapter.hasRole('user')).toBe(true);
      expect(adapter.hasRole('guest')).toBe(false);
      expect(adapter.hasAnyRole(['guest', 'admin'])).toBe(true);
      expect(adapter.hasAllRoles(['user', 'admin'])).toBe(true);
      expect(adapter.hasAllRoles(['user', 'guest'])).toBe(false);
    });

    it('checks permissions correctly', () => {
      expect(adapter.hasPermission('write')).toBe(true);
      expect(adapter.hasPermission('delete')).toBe(false);
      expect(adapter.hasAnyPermission(['delete', 'read'])).toBe(true);
      expect(adapter.hasAllPermissions(['read', 'write'])).toBe(true);
      expect(adapter.hasAllPermissions(['read', 'delete'])).toBe(false);
    });

    it('returns false when no user', () => {
      (adapter as any).state.user = null;
      expect(adapter.hasRole('user')).toBe(false);
      expect(adapter.hasPermission('read')).toBe(false);
    });
  });

  describe('Event System', () => {
    let adapter: IAMAdapter;

    beforeEach(() => {
      adapter = new IAMAdapter(createConfig());
    });

    it('adds and invokes event listeners', () => {
      const listener = vi.fn();
      adapter.addEventListener('test', listener);
      (adapter as any).emitEvent('test', { foo: 'bar' });
      expect(listener).toHaveBeenCalledWith({ foo: 'bar' });
    });

    it('removes event listeners', () => {
      const listener = vi.fn();
      adapter.addEventListener('test', listener);
      adapter.removeEventListener('test', listener);
      (adapter as any).emitEvent('test', {});
      expect(listener).not.toHaveBeenCalled();
    });

    it('returns unsubscribe function', () => {
      const listener = vi.fn();
      const unsub = adapter.addEventListener('test', listener);
      unsub();
      (adapter as any).emitEvent('test', {});
      expect(listener).not.toHaveBeenCalled();
    });

    it('dispatches window CustomEvent', () => {
      const adapter = new IAMAdapter(createConfig());
      (adapter as any).emitEvent('auth:login', { user: mockUser });
      expect(window.dispatchEvent).toHaveBeenCalledWith(expect.any(CustomEvent));
    });
  });

  describe('Token Utilities', () => {
    let adapter: IAMAdapter;

    beforeEach(() => {
      adapter = new IAMAdapter(createConfig());
    });

    it('getAuthHeader returns null when no tokens', () => {
      expect(adapter.getAuthHeader()).toBeNull();
    });

    it('getAuthHeader returns correct header', () => {
      (adapter as any).state.tokens = { ...mockTokens(), tokenType: 'Bearer' };
      expect(adapter.getAuthHeader()).toBe('Bearer access-token');
    });

    it('isTokenExpired computes correctly', () => {
      (adapter as any).state.tokens = { ...mockTokens(), expiresAt: new Date(Date.now() + 100000) };
      expect(adapter.isTokenExpired()).toBe(false);
      (adapter as any).state.tokens.expiresAt = new Date(Date.now() - 1000);
      expect(adapter.isTokenExpired()).toBe(true);
    });

    it('isTokenExpiringSoon detects near expiry', () => {
      (adapter as any).state.tokens = { ...mockTokens(), expiresAt: new Date(Date.now() + 100) };
      expect(adapter.isTokenExpiringSoon()).toBe(true);
      // Set expiry far beyond threshold (300s)
      (adapter as any).state.tokens.expiresAt = new Date(Date.now() + 400000);
      expect(adapter.isTokenExpiringSoon()).toBe(false);
    });

    it('isAuthenticated combines flags', () => {
      (adapter as any).state.isAuthenticated = true;
      (adapter as any).state.tokens = mockTokens();
      expect(adapter.isAuthenticated()).toBe(true);
      (adapter as any).state.tokens.expiresAt = new Date(Date.now() - 1000);
      expect(adapter.isAuthenticated()).toBe(false);
    });
  });

  describe('API Helpers', () => {
    let adapter: IAMAdapter;

    beforeEach(() => {
      adapter = new IAMAdapter(createConfig());
    });

    it('makeRequest sends correct headers', async () => {
      fetchMock.mockResolvedValueOnce({ ok: true, json: async () => ({ ok: true }) });
      await (adapter as any).makeRequest('/test');
      expect(fetchMock).toHaveBeenCalledWith(
        'https://api.example.com/test',
        expect.objectContaining({ headers: { 'Content-Type': 'application/json' } })
      );
    });

    it('makeAuthenticatedRequest adds Authorization', async () => {
      fetchMock.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      (adapter as any).state.tokens = mockTokens();
      await (adapter as any).makeAuthenticatedRequest('/secure');
      expect(fetchMock).toHaveBeenCalledWith(
        expect.stringContaining('/secure'),
        expect.objectContaining({
          headers: expect.objectContaining({ Authorization: 'Bearer access-token' }),
        })
      );
    });

    it('makeAuthenticatedRequest throws if no token', async () => {
      await expect((adapter as any).makeAuthenticatedRequest('/secure')).rejects.toThrow('No authentication token available');
    });
  });

  describe('Dispose', () => {
    it('cleans up everything', () => {
      const adapter = new IAMAdapter(createConfig());
      (adapter as any).state.tokens = mockTokens();
      (adapter as any).state.user = mockUser;
      (adapter as any).state.isAuthenticated = true;

      adapter.dispose();

      expect(adapter.isAuthenticated()).toBe(false);
      expect(adapter.getUser()).toBeNull();
      expect(localStorage.removeItem).toHaveBeenCalledWith('iam_auth');
      expect(vi.getTimerCount()).toBe(0);
    });
  });
});
