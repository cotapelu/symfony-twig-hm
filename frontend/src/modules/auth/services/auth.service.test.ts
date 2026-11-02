import { vi, describe, it, expect, beforeEach } from 'vitest';

// Mock Svelte environment
vi.mock('$app/environment', () => ({ browser: true }));

// Mock dependencies at top-level for hoisting
vi.mock('../state/auth-store', () => {
  const mockAuthStore = {
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    setUser: vi.fn(),
    subscribe: vi.fn(() => {}),
  };
  return { authStore: mockAuthStore };
});

vi.mock('../../../lib/api-client', () => {
  const mockApiClient = {
    post: vi.fn(),
    get: vi.fn(),
  };
  return { ApiClient: vi.fn(() => mockApiClient) };
});

// Import after mocks are defined
import { AuthService } from './auth.service';
import { authStore as mockedAuthStore } from '../state/auth-store';
import { ApiClient as MockedApiClient } from '../../../lib/api-client';

describe('AuthService', () => {
  let authService: AuthService;
  let mockAuthStore: any;
  let mockApiClient: any;

  beforeEach(() => {
    // Reset mocks
    vi.clearAllMocks();
    
    // Get the mocked instances (they are created by the mock factories)
    mockAuthStore = mockedAuthStore;
    mockApiClient = MockedApiClient();

    // Create a fresh service instance for each test
    authService = new AuthService(mockAuthStore, mockApiClient);
  });

  describe('constructor', () => {
    it('should create an instance', () => {
      expect(authService).toBeInstanceOf(AuthService);
    });

    it('should use provided authStore', () => {
      const service = new AuthService(mockAuthStore, mockApiClient);
      // @ts-ignore
      expect(service['authStore']).toBe(mockAuthStore);
    });

    it('should use provided ApiClient', () => {
      const service = new AuthService(mockAuthStore, mockApiClient);
      // @ts-ignore
      expect(service['apiClient']).toBe(mockApiClient);
    });

    it('should create default instances when none provided', () => {
      // Default mock uses the globally mocked dependencies
      const service = new AuthService();
      expect(service).toBeInstanceOf(AuthService);
    });
  });

  describe('login', () => {
    it('should call authStore.login with correct parameters', async () => {
      mockAuthStore.login.mockResolvedValue({ token: 'test-token' });

      await authService.login('test@example.com', 'password123');

      expect(mockAuthStore.login).toHaveBeenCalledWith('test@example.com', 'password123');
    });

    it('should validate email and password', async () => {
      // Invalid email
      await expect(authService.login('invalid-email', 'password123'))
        .rejects.toThrow('Invalid email format');

      // Short password
      await expect(authService.login('test@example.com', '123'))
        .rejects.toThrow('Password must be at least 8 characters');
    });
  });

  describe('register', () => {
    it('should call authStore.register with correct parameters', async () => {
      mockAuthStore.register.mockResolvedValue({ token: 'test-token' });

      await authService.register('John Doe', 'test@example.com', 'password123');

      expect(mockAuthStore.register).toHaveBeenCalledWith('John Doe', 'test@example.com', 'password123');
    });

    it('should validate name, email, and password', async () => {
      // Short name
      await expect(authService.register('A', 'test@example.com', 'password123'))
        .rejects.toThrow('Name must be at least 2 characters');

      // Invalid email
      await expect(authService.register('John Doe', 'invalid-email', 'password123'))
        .rejects.toThrow('Invalid email format');

      // Short password
      await expect(authService.register('John Doe', 'test@example.com', '123'))
        .rejects.toThrow('Password must be at least 8 characters');
    });
  });

  describe('logout', () => {
    it('should call authStore.logout', async () => {
      await authService.logout();

      expect(mockAuthStore.logout).toHaveBeenCalled();
    });
  });

  describe('forgotPassword', () => {
    it('should call apiClient.post with correct parameters', async () => {
      const mockResponse = { success: true };
      mockApiClient.post.mockResolvedValue(mockResponse);

      const result = await authService.forgotPassword('test@example.com');

      expect(mockApiClient.post).toHaveBeenCalledWith('/auth/forgot-password', { email: 'test@example.com' });
      expect(result).toEqual({ success: true });
    });

    it('should validate email', async () => {
      await expect(authService.forgotPassword('invalid-email'))
        .rejects.toThrow('Invalid email format');
    });

    it('should handle API errors', async () => {
      mockApiClient.post.mockRejectedValue(new Error('API error'));

      const result = await authService.forgotPassword('test@example.com');
      
      expect(result).toEqual({ success: false, message: 'API error' });
    });
  });

  describe('resetPassword', () => {
    it('should call apiClient.post with correct parameters', async () => {
      const mockResponse = { success: true };
      mockApiClient.post.mockResolvedValue(mockResponse);

      const result = await authService.resetPassword('token123', 'newpassword123');

      expect(mockApiClient.post).toHaveBeenCalledWith('/auth/reset-password', { token: 'token123', password: 'newpassword123' });
      expect(result).toEqual({ success: true });
    });

    it('should validate token and password', async () => {
      // Empty token
      await expect(authService.resetPassword('', 'newpassword123'))
        .rejects.toThrow('Token is required');

      // Short password
      await expect(authService.resetPassword('token123', '123'))
        .rejects.toThrow('Password must be at least 8 characters');
    });

    it('should handle API errors', async () => {
      mockApiClient.post.mockRejectedValue(new Error('API error'));

      const result = await authService.resetPassword('token123', 'newpassword123');
      
      expect(result).toEqual({ success: false, message: 'API error' });
    });
  });

  describe('setUser', () => {
    it('should call authStore.setUser with correct parameters', async () => {
      authService.setUser({ id: 1, name: 'John Doe' }, 'test-token');

      expect(mockAuthStore.setUser).toHaveBeenCalledWith({ id: 1, name: 'John Doe' }, 'test-token');
    });
  });

  describe('private methods', () => {
    describe('validateLoginInput', () => {
      it('should validate email and password', () => {
        // @ts-ignore - accessing private method
        const validateFn = authService['validateLoginInput'].bind(authService);
        
        // Valid input should not throw
        expect(() => validateFn('test@example.com', 'password123')).not.toThrow();
        
        // Invalid email
        expect(() => validateFn('invalid-email', 'password123')).toThrow('Invalid email format');
        
        // Short password
        expect(() => validateFn('test@example.com', '123')).toThrow('Password must be at least 8 characters');
      });
    });

    describe('validateRegisterInput', () => {
      it('should validate name, email, and password', () => {
        // @ts-ignore - accessing private method
        const validateFn = authService['validateRegisterInput'].bind(authService);
        
        // Valid input should not throw
        expect(() => validateFn('John Doe', 'test@example.com', 'password123')).not.toThrow();
        
        // Short name
        expect(() => validateFn('A', 'test@example.com', 'password123')).toThrow('Name must be at least 2 characters');
        
        // Invalid email
        expect(() => validateFn('John Doe', 'invalid-email', 'password123')).toThrow('Invalid email format');
        
        // Short password
        expect(() => validateFn('John Doe', 'test@example.com', '123')).toThrow('Password must be at least 8 characters');
      });
    });

    describe('validateEmail', () => {
      it('should validate email format', () => {
        // @ts-ignore - accessing private method
        const validateFn = authService['validateEmail'].bind(authService);
        
        // Valid emails
        expect(() => validateFn('test@example.com')).not.toThrow();
        expect(() => validateFn('user.name@domain.co.uk')).not.toThrow();
        
        // Invalid emails
        expect(() => validateFn('invalid-email')).toThrow('Invalid email format');
        expect(() => validateFn('@domain.com')).toThrow('Invalid email format');
        expect(() => validateFn('user@')).toThrow('Invalid email format');
        expect(() => validateFn('user@domain')).toThrow('Invalid email format');
      });
    });

    describe('validatePassword', () => {
      it('should validate password length', () => {
        // @ts-ignore - accessing private method
        const validateFn = authService['validatePassword'].bind(authService);
        
        // Valid passwords
        expect(() => validateFn('password123')).not.toThrow();
        expect(() => validateFn('a'.repeat(8))).not.toThrow();
        
        // Invalid passwords
        expect(() => validateFn('')).toThrow('Password must be at least 8 characters');
        expect(() => validateFn('123')).toThrow('Password must be at least 8 characters');
        expect(() => validateFn('a'.repeat(7))).toThrow('Password must be at least 8 characters');
      });
    });

    describe('validateToken', () => {
      it('should validate token presence', () => {
        // @ts-ignore - accessing private method
        const validateFn = authService['validateToken'].bind(authService);
        
        // Valid token
        expect(() => validateFn('valid-token')).not.toThrow();
        
        // Invalid tokens
        expect(() => validateFn('')).toThrow('Token is required');
        expect(() => validateFn('   ')).toThrow('Token is required');
      });
    });

    describe('handleError', () => {
      it('should handle Error objects', () => {
        // @ts-ignore - accessing private method
        const handleFn = authService['handleError'].bind(authService);
        
        const error = new Error('Test error');
        const result = handleFn(error);
        
        expect(result).toEqual({ success: false, message: 'Test error' });
      });

      it('should handle non-Error objects', () => {
        // @ts-ignore - accessing private method
        const handleFn = authService['handleError'].bind(authService);
        
        const result = handleFn('Some error message');
        
        expect(result).toEqual({ success: false, message: 'An unexpected error occurred' });
      });
    });
  });
});