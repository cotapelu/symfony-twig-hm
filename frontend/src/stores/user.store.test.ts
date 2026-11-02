import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { userStore, isLoggedIn, userId, userName, userEmail, userAvatar, isAdmin } from './user';
import { authService } from '../lib/services/auth';
import { PHP } from '../lib/api';

// Mock services
vi.mock('../lib/services/auth', () => ({
  authService: {
    login: vi.fn(),
    register: vi.fn(),
  }
}));

vi.mock('../lib/api', () => ({
  PHP: {
    getProfile: vi.fn(),
    updateProfile: vi.fn(),
    forgotPassword: vi.fn(),
    sendSMSVerification: vi.fn(),
    logout: vi.fn(),
  }
}));

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => { store[key] = value; },
    removeItem: (key: string) => { delete store[key]; },
    clear: () => { store = {}; }
  };
})();

Object.defineProperty(global, 'localStorage', { value: localStorageMock });

describe('UserStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.clear();
    userStore.reset();
    userStore.subscribe(s => { state = s; });
  });

  describe('login', () => {
    it('should log in successfully', async () => {
      const user = { id: 'u1', email: 'test@example.com', name: 'Test' };
      (authService.login as any).mockResolvedValue({ access_token: 'tok', user: { id: 'u1', email: 'test@example.com', username: 'Test' } });
      const result = await userStore.login('test@example.com', 'pass');
      expect(result).toEqual({ token: 'tok', user });
      expect(state.user).toEqual(user);
      expect(state.isAuthenticated).toBe(true);
      expect(localStorageMock.getItem('auth_token')).toBe('tok');
      const storedUser = JSON.parse(localStorageMock.getItem('user')!);
      expect(storedUser).toEqual(user);
    });

    it('should handle login error', async () => {
      (authService.login as any).mockRejectedValue(new Error('Invalid credentials'));
      await expect(userStore.login('e','p')).rejects.toThrow('Invalid credentials');
      expect(state.error).toBe('Invalid credentials');
      expect(state.loading).toBe(false);
    });
  });

  describe('register', () => {
    it('should register successfully', async () => {
      const user = { id: 'u2', email: 'new@example.com', name: 'New' };
      (authService.register as any).mockResolvedValue({ access_token: 'regTok', user: { id: 'u2', email: 'new@example.com', username: 'New' } });
      const result = await userStore.register('New', 'new@example.com', 'pwd');
      expect(result).toEqual({ token: 'regTok', user });
      expect(state.user).toEqual(user);
      expect(state.isAuthenticated).toBe(true);
      expect(localStorageMock.getItem('auth_token')).toBe('regTok');
    });

    it('should handle register error', async () => {
      (authService.register as any).mockRejectedValue(new Error('Reg failed'));
      await expect(userStore.register('N','n@e.com','p')).rejects.toThrow('Reg failed');
      expect(state.error).toBe('Reg failed');
    });
  });

  describe('logout', () => {
    it('should log out and reset state', async () => {
      // set state as logged in
      state.user = { id: 'u1', email: 'e', name: 'U' };
      state.isAuthenticated = true;
      (PHP.logout as any).mockResolvedValue(undefined);
      localStorageMock.setItem('auth_token', 'tok');
      localStorageMock.setItem('user', JSON.stringify(state.user));

      await userStore.logout();

      expect(PHP.logout).toHaveBeenCalled();
      expect(localStorageMock.getItem('auth_token')).toBeNull();
      expect(localStorageMock.getItem('user')).toBeNull();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });

    it('should handle logout API error but still reset', async () => {
      state.user = { id: 'u1', email: 'e', name: 'U' };
      state.isAuthenticated = true;
      localStorageMock.setItem('auth_token', 'tok');
      localStorageMock.setItem('user', JSON.stringify(state.user));
      (PHP.logout as any).mockRejectedValue(new Error('Logout failed'));

      await userStore.logout();

      // Should still reset despite API error
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
      expect(localStorageMock.getItem('auth_token')).toBeNull();
    });
  });

  describe('loadProfile', () => {
    it('should load profile', async () => {
      const profile = { id: 'u1', email: 'e', name: 'U' };
      (PHP.getProfile as any).mockResolvedValue(profile);

      const result = await userStore.loadProfile();

      expect(result).toEqual(profile);
      expect(state.user).toEqual(profile);
      expect(state.isAuthenticated).toBe(true);
      expect(localStorageMock.getItem('user')).toBe(JSON.stringify(profile));
    });

    it('should handle error', async () => {
      (PHP.getProfile as any).mockRejectedValue(new Error('Profile load error'));
      await expect(userStore.loadProfile()).rejects.toThrow('Profile load error');
      expect(state.error).toBe('Profile load error');
    });
  });

  describe('updateProfile', () => {
    it('should update profile', async () => {
      const updated = { id: 'u1', email: 'e', name: 'New' };
      (PHP.updateProfile as any).mockResolvedValue(updated);
      state.user = { id: 'u1', email: 'e', name: 'Old' };

      const result = await userStore.updateProfile({ name: 'New' });

      expect(result).toEqual(updated);
      expect(state.user).toEqual(updated);
    });

    it('should handle error', async () => {
      (PHP.updateProfile as any).mockRejectedValue(new Error('Update failed'));
      await expect(userStore.updateProfile({ name: 'New' })).rejects.toThrow('Update failed');
      expect(state.error).toBe('Update failed');
    });
  });

  describe('registerDevice', () => {
    it('should register device token', async () => {
      (PHP.sendSMSVerification as any).mockResolvedValue(undefined);

      await userStore.registerDevice('deviceToken');

      expect(PHP.sendSMSVerification).toHaveBeenCalledWith('deviceToken');
    });

    it('should handle error without throwing (method catches)', async () => {
      (PHP.sendSMSVerification as any).mockRejectedValue(new Error('SMS failed'));
      // The method catches and logs; it does not throw
      await expect(userStore.registerDevice('tok')).resolves.toBeUndefined();
    });
  });

  describe('forgotPassword', () => {
    it('should send forgot password request', async () => {
      (PHP.forgotPassword as any).mockResolvedValue({ success: true, message: 'Password reset email sent' });
      const result = await userStore.forgotPassword('email@example.com');
      expect(result).toEqual({ success: true, message: 'Password reset email sent' });
    });

    it('should handle error', async () => {
      (PHP.forgotPassword as any).mockRejectedValue(new Error('Forgot failed'));
      const result = await userStore.forgotPassword('e');
      expect(result).toEqual({ success: false, message: 'Forgot failed' });
      expect(state.error).toBe('Forgot failed');
    });
  });

  describe('reset', () => {
    it('should clear storage and reset state', () => {
      state.user = { id: 'u1', email: 'e' };
      state.isAuthenticated = true;
      state.error = 'err';
      localStorageMock.setItem('auth_token', 'tok');
      localStorageMock.setItem('user', JSON.stringify({ id: 'u1' }));

      userStore.reset();

      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(localStorageMock.getItem('auth_token')).toBeNull();
      expect(localStorageMock.getItem('user')).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (authService.login as any).mockRejectedValue(new Error('test'));
      try { await userStore.login('e','p'); } catch (e) {}
      expect(state.error).toBe('test');
      userStore.clearError();
      expect(state.error).toBeNull();
    });
  });

  describe('derived stores', () => {
    it('isLoggedIn reflects isAuthenticated state', async () => {
      userStore.reset();
      let result = false;
      const unsub = isLoggedIn.subscribe((val) => { result = val; });
      expect(result).toBe(false);

      const user = { id: 'u1', email: 'test@example.com', name: 'Test' };
      (authService.login as any).mockResolvedValue({ access_token: 'tok', user: { id: 'u1', email: 'test@example.com', username: 'Test' } });
      await userStore.login('test@example.com', 'pass');
      expect(result).toBe(true);
      unsub();
    });

    it('userId reflects user.id', async () => {
      userStore.reset();
      let result = null;
      const unsub = userId.subscribe((val) => { result = val; });
      expect(result).toBeNull();

      const user = { id: 'u42', email: 'a@b.com', name: 'A' };
      (authService.login as any).mockResolvedValue({ access_token: 'tok', user: { id: 'u42', email: 'a@b.com', username: 'A' } });
      await userStore.login('a@b.com', 'p');
      expect(result).toBe('u42');
      unsub();
    });

    it('userName reflects user.name', async () => {
      userStore.reset();
      let result = null;
      const unsub = userName.subscribe(val => { result = val; });
      const user = { id: 'u1', email: 't@e.com', name: 'Alice' };
      (authService.login as any).mockResolvedValue({ access_token: 't', user: { id: 'u1', email: 't@e.com', username: 'Alice' } });
      await userStore.login('t@e.com', 'p');
      expect(result).toBe('Alice');
      unsub();
    });

    it('isAdmin reflects user.role === admin', () => {
      userStore.reset();
      let result = false;
      const unsub = isAdmin.subscribe(val => { result = val; });
      expect(result).toBe(false);

      const adminUser = { id: 'u1', email: 'admin@example.com', name: 'Admin', role: 'admin' };
      userStore.setUser(adminUser);
      expect(result).toBe(true);
      unsub();
    });
  });
});
