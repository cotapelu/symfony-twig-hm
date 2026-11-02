export default class AuthEvent {
  static readonly LOGIN_SUCCESS = 'auth:login_success';
  static readonly LOGIN_FAILURE = 'auth:login_failure';
  static readonly LOGOUT = 'auth:logout';
  static readonly REGISTRATION_SUCCESS = 'auth:registration_success';
  static readonly REGISTRATION_FAILURE = 'auth:registration_failure';
  static readonly PASSWORD_RESET_REQUESTED = 'auth:password_reset_requested';
  static readonly PASSWORD_RESET_SUCCESS = 'auth:password_reset_success';
  static readonly TOKEN_REFRESHED = 'auth:token_refreshed';

  constructor(
    public readonly type: string,
    public readonly data?: any,
    public readonly timestamp: Date = new Date()
  ) {}
}

export function createAuthEvent(type: string, data?: any): AuthEvent {
  return new AuthEvent(type, data);
}

export function createLoginSuccessEvent(user: any): AuthEvent {
  return new AuthEvent(AuthEvent.LOGIN_SUCCESS, { user });
}

export function createLoginFailureEvent(error: string): AuthEvent {
  return new AuthEvent(AuthEvent.LOGIN_FAILURE, { error });
}

export function createLogoutEvent(): AuthEvent {
  return new AuthEvent(AuthEvent.LOGOUT);
}