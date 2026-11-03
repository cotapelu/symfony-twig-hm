export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
}

export interface User {
  // login payload may carry username — added additively
  username?: string;
  id: string;
  email: string;
  name: string;
  avatar?: string;
  role?: string;
}

export interface AuthResponse {
  user: User;
  token: string;
}

export interface OAuthProvider {
  provider: 'google' | 'github' | 'facebook';
}

export interface ForgotPasswordRequest {
  email: string;
}

export interface ResetPasswordRequest {
  token: string;
  password: string;
}

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message?: string;
}