import { describe, it, expect, vi, beforeEach } from 'vitest';
import { authStore, isAuthenticated, currentUser } from './auth-store';

const fetchMock = vi.fn();
vi.stubGlobal('fetch', fetchMock);

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => { store[key] = value; },
    removeItem: (key: string) => { delete store[key]; },
    clear: () => { Object.keys(store).forEach(k => delete store[k]); }
  };
})();
Object.defineProperty(globalThis, 'localStorage', { value: localStorageMock });

describe('AuthStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    fetchMock.mockReset();
    localStorageMock.clear();
    authStore.reset();
    authStore.subscribe((s: any) => { state = s; });
  });

  describe('initial state', () => {
    it('should have empty user, token, not loading, no error', () => {
      expect(state.user).toBeNull();
      expect(state.token).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('isAuthenticated derived is false initially', () => {
      let auth = false;
      isAuthenticated.subscribe(v => auth = v);
      expect(auth).toBe(false);
    });

    it('currentUser derived is null initially', () => {
      let user = null;
      currentUser.subscribe(v => user = v);
      expect(user).toBeNull();
    });
  });

  describe('login', () => {
    const mockUser = { id: 'u1', email: 'test@example.com', name: 'Test User' };
    const mockToken = 'token123';

    beforeEach(() => {
      // Ensure localStorage is clean
      localStorageMock.clear();
    });

    it('login success sets user and token', async () => {
      fetchMock.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ user: mockUser, token: mockToken })
      });

      const result = await authStore.login('test@example.com', 'pass');

      expect(result).toEqual(mockUser);
      expect(state.user).toEqual(mockUser);
      expect(state.token).toBe(mockToken);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(localStorageMock.getItem('auth_token')).toBe(mockToken);
      expect(fetchMock).toHaveBeenCalledWith('/api/auth/login', expect.objectContaining({
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: 'test@example.com', password: 'pass' })
      }));
    });

    it('login failure sets error', async () => {
      fetchMock.mockResolvedValue({ ok: false });
      await expect(authStore.login('test@example.com', 'pass')).rejects.toThrow('Login failed');
      expect(state.user).toBeNull();
      expect(state.token).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBe('Login failed');
    });

    it('login network error sets error', async () => {
      fetchMock.mockRejectedValue(new Error('Network down'));
      await expect(authStore.login('test@example.com', 'pass')).rejects.toThrow('Network down');
      expect(state.error).toBe('Network down');
      expect(state.loading).toBe(false);
    });
  });

  describe('register', () => {
    const mockUser = { id: 'u2', email: 'new@example.com', name: 'New User' };
    const mockToken = 'newtoken';

    it('register success', async () => {
      fetchMock.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ user: mockUser, token: mockToken })
      });

      const result = await authStore.register('New User', 'new@example.com', 'pass');

      expect(result).toEqual(mockUser);
      expect(state.user).toEqual(mockUser);
      expect(state.token).toBe(mockToken);
      expect(localStorageMock.getItem('auth_token')).toBe(mockToken);
    });

    it('register failure', async () => {
      fetchMock.mockResolvedValue({ ok: false });
      await expect(authStore.register('New', 'new@example.com', 'pass')).rejects.toThrow('Registration failed');
      expect(state.error).toBe('Registration failed');
    });
  });

  describe('logout', () => {
    it('clears user, token and localStorage', async () => {
      // Set up authenticated state
      fetchMock.mockResolvedValue({ ok: true, json: () => Promise.resolve({ user: { id: 'u1', email: 'a', name: 'A' }, token: 't1' }) });
      await authStore.login('a@b.com', 'p');
      // Now logout
      authStore.logout();
      expect(state.user).toBeNull();
      expect(state.token).toBeNull();
      expect(localStorageMock.getItem('auth_token')).toBeNull();
    });
  });

  describe('setUser', () => {
    it('sets user and optionally token', () => {
      const user = { id: 'u1', email: 'a@b.c', name: 'A' };
      authStore.setUser(user);
      expect(state.user).toEqual(user);
      expect(state.token).toBeNull();

      authStore.setUser(user, 'token456');
      expect(state.token).toBe('token456');
      expect(localStorageMock.getItem('auth_token')).toBe('token456');
    });
  });

  describe('clearError', () => {
    it('clears error', () => {
      // Simulate error by directly setting state? Not recommended but we can call a method that sets error, e.g., login fails.
      // Instead, we can setState via internal? But clearError is simple.
      // We'll set error via login failure, then clear.
      fetchMock.mockResolvedValue({ ok: false });
      authStore.login('a@b.c', 'p').catch(() => {});
      // Wait for state update: use microtask
      return new Promise<void>(resolve => {
        setTimeout(() => {
          expect(state.error).toBe('Login failed');
          authStore.clearError();
          expect(state.error).toBeNull();
          resolve();
        }, 0);
      });
    });
  });

  describe('reset', () => {
    it('resets state and clears localStorage', async () => {
      // Authenticate first
      fetchMock.mockResolvedValue({ ok: true, json: () => Promise.resolve({ user: { id: 'u1', email: 'a@b.c', name: 'A' }, token: 't1' }) });
      await authStore.login('a@b.c', 'p');
      expect(state.user).not.toBeNull();

      // Reset
      authStore.reset();
      expect(state.user).toBeNull();
      expect(state.token).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(localStorageMock.getItem('auth_token')).toBeNull();
    });
  });

  describe('derived stores', () => {
    it('isAuthenticated true when user and token present', async () => {
      let auth = false;
      isAuthenticated.subscribe(v => auth = v);
      expect(auth).toBe(false);

      fetchMock.mockResolvedValue({ ok: true, json: () => Promise.resolve({ user: { id: 'u1', email: 'a', name: 'A' }, token: 't' }) });
      await authStore.login('a@b.c', 'p');
      expect(auth).toBe(true);
    });

    it('currentUser returns current user', async () => {
      let user = null;
      currentUser.subscribe(v => user = v);
      expect(user).toBeNull();

      const mockUser = { id: 'u1', email: 'a@b.c', name: 'A' };
      fetchMock.mockResolvedValue({ ok: true, json: () => Promise.resolve({ user: mockUser, token: 't' }) });
      await authStore.login('a@b.c', 'p');
      expect(user).toEqual(mockUser);
    });
  });
});
