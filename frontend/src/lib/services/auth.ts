// Auth Service - Connects to Go authservice
import { getApiUrl } from '../config/api';
import type { User } from '../types';

export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  email: string;
  password: string;
  username: string;
}

export interface AuthResponse {
  access_token: string;
  refresh_token: string;
  expires_in: number;
  token_type: string;
  user: User;
}

export interface UserResponse {
  id: string;
  email: string;
  username: string;
  role: string;
  created_at: string;
  updated_at: string;
  is_active: boolean;
  is_mfa_enabled: boolean;
}

class AuthService {
  async login(request: LoginRequest): Promise<AuthResponse> {
    const url = getApiUrl('authservice', '/api/auth/login');
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(request),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ error: 'Login failed' }));
      throw new Error(error.error || 'Login failed');
    }

    return response.json();
  }

  async register(request: RegisterRequest): Promise<AuthResponse> {
    const url = getApiUrl('authservice', '/api/auth/register');
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(request),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ error: 'Registration failed' }));
      throw new Error(error.error || 'Registration failed');
    }

    return response.json();
  }

  async logout(userId: string, sessionId: string): Promise<void> {
    const url = getApiUrl('authservice', '/api/auth/logout');
    await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ user_id: userId, session_id: sessionId }),
    });
  }

  async refreshToken(refreshToken: string): Promise<AuthResponse> {
    const url = getApiUrl('authservice', '/api/auth/refresh');
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ refresh_token: refreshToken }),
    });

    if (!response.ok) {
      throw new Error('Token refresh failed');
    }

    return response.json();
  }

  async getMe(accessToken: string): Promise<UserResponse> {
    const url = getApiUrl('authservice', '/api/auth/me');
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${accessToken}`,
      },
    });

    if (!response.ok) {
      throw new Error('Failed to get user info');
    }

    return response.json();
  }

  async setupMFA(userId: string): Promise<{ secret: string; qr_code_url: string; backup_codes: string[] }> {
    const url = getApiUrl('authservice', '/api/auth/mfa/setup');
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ user_id: userId }),
    });

    if (!response.ok) {
      throw new Error('MFA setup failed');
    }

    return response.json();
  }

  async verifyMFA(userId: string, code: string, backupCode?: string): Promise<void> {
    const url = getApiUrl('authservice', '/api/auth/mfa/verify');
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ user_id: userId, code, backup_code: backupCode }),
    });

    if (!response.ok) {
      throw new Error('MFA verification failed');
    }
  }
}

export const authService = new AuthService();
