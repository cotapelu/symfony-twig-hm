import { describe, it, expect, vi, beforeEach } from 'vitest';
import { authStore } from '../../src/modules/auth/state/auth-store';
import type { User } from '../../src/modules/auth/state/auth-store';

// Mock fetch and localStorage
global.fetch = vi.fn();
const mockLocalStorage = {
  store: {} as Record<string, string>,
  getItem: function (key: string) { return this.store[key] || null; },
  setItem: function (key: string, value: string) { this.store[key] = value; },
  removeItem: function (key: string) { delete this.store[key]; },
  clear: function () { this.store = {}; }
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('AuthStore', () => {
  let state: any;

  beforeEach(() => {
    // Reset the singleton store before each test
    authStore.reset();
    vi.clearAllMocks();
    mockLocalStorage.clear();
  });

  const mockUser: User = { id: '123', email: 'test@example.com', name: 'Test User' };
  const mockToken = 'mock-jwt-token';

  const okResponse = (data: any) => ({
    ok: true,
    json: vi.fn().mockResolvedValue(data)
  });

  const errorResponse = () => ({
    ok: false,
    json: vi.fn().mockResolvedValue({})
  });

  // Helper to subscribe and get latest state
  const subscribe = () => {
    authStore.subscribe(s => { state = s; });
  };

  describe('initial state', () => {
    it('should have default values', () => {
      subscribe();
      expect(state.user).toBeNull();
      expect(state.token).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('login', () => {
    it('should set user and token on successful login', async () => {
      (global.fetch as any).mockResolvedValue(okResponse({ user: mockUser, token: mockToken }));
      subscribe();

      await authStore.login('test@example.com', 'password123');

      expect(fetch).toHaveBeenCalledWith('/api/auth/login', expect.objectContaining({
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: 'test@example.com', password: 'password123' })
      }));
      expect(state.user).toEqual(mockUser);
      expect(state.token).toBe(mockToken);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(mockLocalStorage.getItem('auth_token')).toBe(mockToken);
    });

    it('should handle API error response', async () => {
      (global.fetch as any).mockResolvedValue(errorResponse());
      subscribe();

      await expect(authStore.login('email', 'pass')).rejects.toThrow('Login failed');
      expect(state.loading).toBe(false);
      expect(state.error).toBe('Login failed');
    });

    it('should handle network errors', async () => {
      (global.fetch as any).mockRejectedValue(new Error('Network error'));
      subscribe();

      await expect(authStore.login('email', 'pass')).rejects.toThrow('Network error');
      expect(state.loading).toBe(false);
      expect(state.error).toBe('Network error');
    });
  });

  describe('register', () => {
    it('should set user and token on successful registration', async () => {
      (global.fetch as any).mockResolvedValue(okResponse({ user: mockUser, token: mockToken }));
      subscribe();

      await authStore.register('Test User', 'test@example.com', 'password123');

      expect(fetch).toHaveBeenCalledWith('/api/auth/register', expect.any(Object));
      expect(state.user).toEqual(mockUser);
      expect(state.token).toBe(mockToken);
      expect(mockLocalStorage.getItem('auth_token')).toBe(mockToken);
    });

    it('should handle registration failure', async () => {
      (global.fetch as any).mockResolvedValue(errorResponse());
      subscribe();

      await expect(authStore.register('Test', 'email', 'pass')).rejects.toThrow('Registration failed');
      expect(state.error).toBe('Registration failed');
    });
  });

  describe('logout', () => {
    it('should clear user, token and error', async () => {
      // First set user and token
      authStore.setUser(mockUser, mockToken);
      subscribe();
      expect(state.user).toEqual(mockUser);
      expect(state.token).toBe(mockToken);

      authStore.logout();

      expect(state.user).toBeNull();
      expect(state.token).toBeNull();
      expect(state.error).toBeNull();
      expect(mockLocalStorage.getItem('auth_token')).toBeNull();
    });
  });

  describe('setUser', () => {
    it('should set user and token', () => {
      subscribe();
      authStore.setUser(mockUser, mockToken);
      expect(state.user).toEqual(mockUser);
      expect(state.token).toBe(mockToken);
      expect(mockLocalStorage.getItem('auth_token')).toBe(mockToken);
    });

    it('should not change token if not provided', () => {
      authStore.setUser(mockUser, 'token1');
      subscribe();
      authStore.setUser({ ...mockUser, name: 'Changed' }); // token undefined
      expect(state.user.name).toBe('Changed');
      expect(state.token).toBe('token1');
    });
  });

  describe('clearError', () => {
    it('should clear error after a failed action', async () => {
      (global.fetch as any).mockRejectedValue(new Error('Oops'));
      subscribe();

      await expect(authStore.login('email', 'pass')).rejects.toThrow('Oops');
      expect(state.error).toBe('Oops');

      authStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
