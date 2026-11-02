import { describe, it, expect, vi, beforeEach } from 'vitest';
import { get } from 'svelte/store';
import {
  authStore,
  user,
  isAuthenticated,
  loading,
  error,
  login,
  register,
  logout,
  forgotPassword,
  clearError,
  type AuthState
} from './authStore';

// Mock fetch
const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

// Mock goto
vi.mock('$app/navigation', () => ({
  goto: vi.fn()
}));

// Mock browser environment
vi.mock('$app/environment', () => ({
  browser: true
}));

describe('authStore', () => {
  beforeEach(() => {
    // Reset store to initial state
    authStore.set({
      user: null,
      isAuthenticated: false,
      loading: false,
      error: null,
      operationInProgress: null
    });
    mockFetch.mockReset();
  });

  describe('initial state', () => {
    it('should have correct initial state', () => {
      const state = get(authStore);
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.operationInProgress).toBeNull();
    });

    it('should expose derived stores', () => {
      expect(get(user)).toBeNull();
      expect(get(isAuthenticated)).toBe(false);
      expect(get(loading)).toBe(false);
      expect(get(error)).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error state', () => {
      authStore.update(s => ({ ...s, error: 'Some error' }));
      expect(get(authStore).error).toBe('Some error');
      
      clearError();
      expect(get(authStore).error).toBeNull();
    });
  });

  describe('login', () => {
    it('should login successfully', async () => {
      const mockUser = { id: '1', email: 'test@test.com', name: 'Test', verified: true, createdAt: '2024-01-01' };
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ user: mockUser })
      });

      await login('test@test.com', 'password123');

      const state = get(authStore);
      expect(state.user).toEqual(mockUser);
      expect(state.isAuthenticated).toBe(true);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('should fail with invalid email format', async () => {
      await expect(login('invalid-email', 'password123')).rejects.toThrow('Invalid email format');
      
      const state = get(authStore);
      expect(state.error).toBe('Invalid email format');
      expect(state.loading).toBe(false);
    });

    it('should fail with empty password', async () => {
      await expect(login('test@test.com', '')).rejects.toThrow('Password is required');
      
      const state = get(authStore);
      expect(state.error).toBe('Password is required');
    });

    it('should handle API error response', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        json: async () => ({ message: 'Invalid credentials' })
      });

      await expect(login('test@test.com', 'password123')).rejects.toThrow('Invalid credentials');
      
      const state = get(authStore);
      expect(state.error).toBe('Invalid credentials');
    });

    it('should handle network error', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      await expect(login('test@test.com', 'password123')).rejects.toThrow('Network error');
      
      const state = get(authStore);
      expect(state.error).toBe('Network error');
    });
  });

  describe('register', () => {
    it('should register successfully', async () => {
      const mockUser = { id: '1', email: 'test@test.com', name: 'Test User', verified: false, createdAt: '2024-01-01' };
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ user: mockUser })
      });

      await register('Test User', 'test@test.com', 'Password123');

      const state = get(authStore);
      expect(state.user).toEqual(mockUser);
      expect(state.isAuthenticated).toBe(true);
    });

    it('should fail with short name', async () => {
      await expect(register('T', 'test@test.com', 'Password123')).rejects.toThrow('Name must be at least 2 characters');
    });

    it('should fail with invalid email', async () => {
      await expect(register('Test', 'invalid', 'Password123')).rejects.toThrow('Invalid email format');
    });

    it('should fail with weak password', async () => {
      await expect(register('Test', 'test@test.com', 'weak')).rejects.toThrow('Password must be at least 8 characters');
    });

    it('should fail with password without uppercase', async () => {
      await expect(register('Test', 'test@test.com', 'password123')).rejects.toThrow('Password must contain at least one uppercase letter');
    });

    it('should fail with password without lowercase', async () => {
      await expect(register('Test', 'test@test.com', 'PASSWORD123')).rejects.toThrow('Password must contain at least one lowercase letter');
    });

    it('should fail with password without number', async () => {
      await expect(register('Test', 'test@test.com', 'PasswordABC')).rejects.toThrow('Password must contain at least one number');
    });
  });

  describe('logout', () => {
    it('should logout successfully', async () => {
      // First login
      authStore.set({
        user: { id: '1', email: 'test@test.com', name: 'Test', verified: true, createdAt: '2024-01-01' },
        isAuthenticated: true,
        loading: false,
        error: null,
        operationInProgress: null
      });

      mockFetch.mockResolvedValueOnce({ ok: true });

      await logout();

      const state = get(authStore);
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
      expect(state.loading).toBe(false);
    });

    it('should logout even if API fails', async () => {
      authStore.set({
        user: { id: '1', email: 'test@test.com', name: 'Test', verified: true, createdAt: '2024-01-01' },
        isAuthenticated: true,
        loading: false,
        error: null,
        operationInProgress: null
      });

      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      await logout();

      const state = get(authStore);
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });
  });

  describe('forgotPassword', () => {
    it('should send reset email successfully', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });

      const result = await forgotPassword('test@test.com');
      expect(result.success).toBe(true);
    });

    it('should handle failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });

      const result = await forgotPassword('test@test.com');
      expect(result.success).toBe(false);
    });

    it('should handle network error', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      const result = await forgotPassword('test@test.com');
      expect(result.success).toBe(false);
    });
  });

  describe('operationInProgress', () => {
    it('should block concurrent operations', async () => {
      authStore.update(s => ({ ...s, operationInProgress: 'login' }));

      await expect(login('test@test.com', 'password123')).rejects.toThrow('Another operation is in progress');
    });

    it('should block register during login', async () => {
      authStore.update(s => ({ ...s, operationInProgress: 'login' }));

      await expect(register('Test', 'test@test.com', 'Password123')).rejects.toThrow('Another operation is in progress');
    });
  });
});
