export interface AuthEvent {
  type: 'LOGIN_SUCCESS' | 'LOGIN_FAILURE' | 'LOGOUT' | 'REGISTER_SUCCESS' | 'REGISTER_FAILURE' | 'PASSWORD_RESET_REQUESTED';
  payload?: any;
  timestamp: Date;
}

export interface LoginSuccessEvent {
  type: 'LOGIN_SUCCESS';
  payload: {
    userId: string;
    email: string;
  };
  timestamp: Date;
}

export interface LoginFailureEvent {
  type: 'LOGIN_FAILURE';
  payload: {
    error: string;
    email?: string;
  };
  timestamp: Date;
}

export interface LogoutEvent {
  type: 'LOGOUT';
  payload: {
    userId?: string;
  };
  timestamp: Date;
}

export interface RegisterSuccessEvent {
  type: 'REGISTER_SUCCESS';
  payload: {
    userId: string;
    email: string;
    name: string;
  };
  timestamp: Date;
}

export interface RegisterFailureEvent {
  type: 'REGISTER_FAILURE';
  payload: {
    error: string;
    email?: string;
  };
  timestamp: Date;
}

export interface PasswordResetRequestedEvent {
  type: 'PASSWORD_RESET_REQUESTED';
  payload: {
    email: string;
  };
  timestamp: Date;
}