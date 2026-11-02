import { describe, it, expect } from 'vitest';
import AuthEvent, {
  createAuthEvent,
  createLoginSuccessEvent,
  createLoginFailureEvent,
  createLogoutEvent
} from './auth.events';

describe('AuthEvent', () => {
  describe('Event Constants', () => {
    it('should have correct event type constants', () => {
      expect(AuthEvent.LOGIN_SUCCESS).toBe('auth:login_success');
      expect(AuthEvent.LOGIN_FAILURE).toBe('auth:login_failure');
      expect(AuthEvent.LOGOUT).toBe('auth:logout');
      expect(AuthEvent.REGISTRATION_SUCCESS).toBe('auth:registration_success');
      expect(AuthEvent.REGISTRATION_FAILURE).toBe('auth:registration_failure');
      expect(AuthEvent.PASSWORD_RESET_REQUESTED).toBe('auth:password_reset_requested');
      expect(AuthEvent.PASSWORD_RESET_SUCCESS).toBe('auth:password_reset_success');
      expect(AuthEvent.TOKEN_REFRESHED).toBe('auth:token_refreshed');
    });
  });

  describe('AuthEvent Constructor', () => {
    it('should create event with type only', () => {
      const event = new AuthEvent('test:type');
      expect(event.type).toBe('test:type');
      expect(event.data).toBeUndefined();
      expect(event.timestamp).toBeInstanceOf(Date);
    });

    it('should create event with type and data', () => {
      const data = { user: { id: 1 } };
      const event = new AuthEvent('test:type', data);
      expect(event.type).toBe('test:type');
      expect(event.data).toEqual(data);
    });

    it('should create event with custom timestamp', () => {
      const timestamp = new Date('2024-01-01');
      const event = new AuthEvent('test:type', {}, timestamp);
      expect(event.timestamp).toBe(timestamp);
    });
  });

  describe('createAuthEvent', () => {
    it('should create auth event with type only', () => {
      const event = createAuthEvent('custom:type');
      expect(event).toBeInstanceOf(AuthEvent);
      expect(event.type).toBe('custom:type');
      expect(event.data).toBeUndefined();
    });

    it('should create auth event with type and data', () => {
      const data = { token: 'abc123' };
      const event = createAuthEvent('custom:type', data);
      expect(event.type).toBe('custom:type');
      expect(event.data).toEqual(data);
    });
  });

  describe('createLoginSuccessEvent', () => {
    it('should create login success event with user data', () => {
      const user = { id: 1, name: 'John Doe', email: 'john@example.com' };
      const event = createLoginSuccessEvent(user);
      
      expect(event).toBeInstanceOf(AuthEvent);
      expect(event.type).toBe(AuthEvent.LOGIN_SUCCESS);
      expect(event.data).toEqual({ user });
    });
  });

  describe('createLoginFailureEvent', () => {
    it('should create login failure event with error message', () => {
      const error = 'Invalid credentials';
      const event = createLoginFailureEvent(error);
      
      expect(event).toBeInstanceOf(AuthEvent);
      expect(event.type).toBe(AuthEvent.LOGIN_FAILURE);
      expect(event.data).toEqual({ error });
    });
  });

  describe('createLogoutEvent', () => {
    it('should create logout event', () => {
      const event = createLogoutEvent();
      
      expect(event).toBeInstanceOf(AuthEvent);
      expect(event.type).toBe(AuthEvent.LOGOUT);
      expect(event.data).toBeUndefined();
    });
  });
});
