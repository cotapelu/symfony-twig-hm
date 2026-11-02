import { describe, it, expect, vi, beforeEach } from 'vitest';
import { userStore } from './user';
import { authService } from '../lib/services/auth';
import { PHP } from '../lib/api';

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
Object.defineProperty(window, 'localStorage', { value: localStorageMock });

vi.mock('../lib/services/auth', () => ({
  authService: {
    login: vi.fn(),
    register: vi.fn(),
    getProfile: vi.fn(),
    updateProfile: vi.fn(),
    forgotPassword: vi.fn(),
    refreshToken: vi.fn(),
  }
}));

vi.mock('../lib/api', () => ({
  PHP: {
    logout: vi.fn(),
    registerDevice: vi.fn(),
    updateProfile: vi.fn(),
    forgotPassword: vi.fn(),
    getProfile: vi.fn(),
    sendSMSVerification: vi.fn(),
  }
}));

describe('UserStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.clear();
    userStore.reset();
    userStore.subscribe(s => { state = s; });
  });

  const mockUser = (id: string, name: string, email: string): any => ({
    id,
    name,
    username: name,
    email
  });

  describe('init', () => {
    it('should restore from localStorage when token and user exist', () => {
      const user = mockUser('u1', 'John', 'john@example.com');
      localStorageMock.setItem('auth_token', 'token123');
      localStorageMock.setItem('user', JSON.stringify(user));

      userStore.init();

      expect(state.user).toEqual(user);
      expect(state.isAuthenticated).toBe(true);
      expect(state.loading).toBe(false);
    });

    it('should reset when no token/user', () => {
      userStore.init();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });

    it('should handle corrupted user data', () => {
      localStorageMock.setItem('auth_token', 'tok');
      localStorageMock.setItem('user', 'invalid json');
      userStore.init();
      expect(state.user).toBeNull();
      expect(localStorageMock.getItem('user')).toBeNull();
    });
  });

  describe('login', () => {
    it('should login successfully', async () => {
      const userId = 'u1';
      const userName = 'John';
      const userEmail = 'john@example.com';
      const expectedUser = { id: userId, name: userName, email: userEmail };
      const authResponseUser = { ...expectedUser, username: userName };
      const authResponse = { access_token: 'tok123', user: authResponseUser };
      (authService.login as any).mockResolvedValue(authResponse);

      const result = await userStore.login(userEmail, 'pass');

      expect(result).toEqual({ token: 'tok123', user: expectedUser });
      expect(state.user).toEqual(expectedUser);
      expect(state.isAuthenticated).toBe(true);
      expect(localStorageMock.getItem('auth_token')).toBe('tok123');
      expect(localStorageMock.getItem('user')).toBe(JSON.stringify(expectedUser));
    });

    it('should handle login failure', async () => {
      (authService.login as any).mockRejectedValue(new Error('Invalid credentials'));
      await expect(userStore.login('john@example.com', 'pass')).rejects.toThrow('Invalid credentials');
      expect(state.error).toBe('Invalid credentials');
      expect(state.isAuthenticated).toBe(false);
    });
  });

  describe('register', () => {
    it('should register successfully', async () => {
      const userId = 'u1';
      const userName = 'John';
      const userEmail = 'john@example.com';
      const expectedUser = { id: userId, name: userName, email: userEmail };
      const authResponseUser = { ...expectedUser, username: userName };
      const authResponse = { access_token: 'tok', user: authResponseUser };
      (authService.register as any).mockResolvedValue(authResponse);

      const result = await userStore.register(userName, userEmail, 'pass');

      expect(result).toEqual({ token: 'tok', user: expectedUser });
      expect(state.user).toEqual(expectedUser);
      expect(state.isAuthenticated).toBe(true);
    });

    it('should handle register error', async () => {
      (authService.register as any).mockRejectedValue(new Error('Email exists'));
      await expect(userStore.register('John', 'john@example.com', 'pass')).rejects.toThrow('Email exists');
      expect(state.error).toBe('Email exists');
    });
  });

  describe('logout', () => {
    it('should logout and clear state', async () => {
      (PHP.logout as any).mockResolvedValue(undefined);
      // Set authenticated state first
      state.user = mockUser('u1', 'John', 'john@example.com');
      state.isAuthenticated = true;
      localStorageMock.setItem('auth_token', 'tok');
      localStorageMock.setItem('user', JSON.stringify(state.user));

      await userStore.logout();

      expect(PHP.logout).toHaveBeenCalled();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
      expect(localStorageMock.getItem('auth_token')).toBeNull();
      expect(localStorageMock.getItem('user')).toBeNull();
    });

    it('should handle logout API error but still clear state', async () => {
      (PHP.logout as any).mockRejectedValue(new Error('API down'));
      state.user = mockUser('u1', 'John', 'john@example.com');
      state.isAuthenticated = true;
      localStorageMock.setItem('auth_token', 'tok');
      localStorageMock.setItem('user', JSON.stringify(state.user));

      await userStore.logout();

      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });
  });

  describe('loadProfile', () => {
    it('should load profile and set user', async () => {
      const user = mockUser('u1', 'John', 'john@example.com');
      (PHP.getProfile as any).mockResolvedValue(user);

      const result = await userStore.loadProfile();

      expect(result).toEqual(user);
      expect(state.user).toEqual(user);
      expect(state.isAuthenticated).toBe(true);
      expect(state.loading).toBe(false);
    });

    it('should handle loadProfile error', async () => {
      (PHP.getProfile as any).mockRejectedValue(new Error('API fail'));
      await expect(userStore.loadProfile()).rejects.toThrow('API fail');
      expect(state.error).toBe('API fail');
    });
  });

  describe('updateProfile', () => {
    it('should update profile', async () => {
      const updated = mockUser('u1', 'John Updated', 'john@example.com');
      (PHP.updateProfile as any).mockResolvedValue(updated);

      const result = await userStore.updateProfile({ name: 'John Updated' });

      expect(result).toEqual(updated);
      expect(state.user).toEqual(updated);
    });

    it('should handle update error', async () => {
      (PHP.updateProfile as any).mockRejectedValue(new Error('Update failed'));
      await expect(userStore.updateProfile({ name: 'New' })).rejects.toThrow('Update failed');
      expect(state.error).toBe('Update failed');
    });
  });

  describe('registerDevice', () => {
    it('should register device', async () => {
      (PHP.sendSMSVerification as any).mockResolvedValue(undefined);
      await userStore.registerDevice('token123', 'ios');
      expect(PHP.sendSMSVerification).toHaveBeenCalledWith('token123');
    });

    it('should handle registerDevice error', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      (PHP.sendSMSVerification as any).mockRejectedValue(new Error('Fail'));
      await userStore.registerDevice('tok');
      expect(consoleSpy).toHaveBeenCalled();
      consoleSpy.mockRestore();
    });
  });

  describe('setUser', () => {
    it('should set user directly', () => {
      const user = mockUser('u1', 'John', 'john@example.com');
      userStore.setUser(user);
      expect(state.user).toEqual(user);
      expect(state.isAuthenticated).toBe(true);
    });
  });

  describe('clearError', () => {
    it('should clear error', () => {
      state.error = 'test';
      userStore.clearError();
      expect(state.error).toBeNull();
    });
  });

  describe('forgotPassword', () => {
    it('should send reset request', async () => {
      (PHP.forgotPassword as any).mockResolvedValue({ success: true });
      const result = await userStore.forgotPassword('john@example.com');
      expect(result).toEqual({ success: true, message: 'Password reset email sent' });
      expect(PHP.forgotPassword).toHaveBeenCalledWith('john@example.com');
    });

    it('should handle forgotPassword error', async () => {
      (PHP.forgotPassword as any).mockRejectedValue(new Error('Send failed'));
      const result = await userStore.forgotPassword('john@example.com');
      expect(result).toEqual({ success: false, message: 'Send failed' });
      expect(state.error).toBe('Send failed');
    });
  });
});
