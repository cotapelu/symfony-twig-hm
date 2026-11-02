import { authStore } from '../state/auth-store';
import { ApiClient } from '../../../lib/api-client';

export class AuthService {
  private authStore: any;
  private apiClient: ApiClient;

  constructor(authStoreInstance: any = authStore, apiClient: ApiClient = new ApiClient()) {
    this.authStore = authStoreInstance;
    this.apiClient = apiClient;
  }

  async login(email: string, password: string): Promise<any> {
    this.validateLoginInput(email, password);
    return await this.authStore.login(email, password);
  }

  async register(name: string, email: string, password: string): Promise<any> {
    this.validateRegisterInput(name, email, password);
    return await this.authStore.register(name, email, password);
  }

  async logout(): Promise<void> {
    this.authStore.logout();
  }

  async forgotPassword(email: string): Promise<{ success: boolean; message?: string }> {
    this.validateEmail(email);
    try {
      const response = await this.apiClient.post('/auth/forgot-password', { email });
      return { success: true };
    } catch (error) {
      return this.handleError(error);
    }
  }

  async resetPassword(token: string, newPassword: string): Promise<{ success: boolean; message?: string }> {
    this.validateToken(token);
    this.validatePassword(newPassword);
    try {
      const response = await this.apiClient.post('/auth/reset-password', { token, password: newPassword });
      return { success: true };
    } catch (error) {
      return this.handleError(error);
    }
  }

  setUser(user: any, token?: string): void {
    this.authStore.setUser(user, token);
  }

  private validateLoginInput(email: string, password: string): void {
    this.validateEmail(email);
    this.validatePassword(password);
  }

  private validateRegisterInput(name: string, email: string, password: string): void {
    if (!name || name.trim().length < 2) {
      throw new Error('Name must be at least 2 characters');
    }
    this.validateEmail(email);
    this.validatePassword(password);
  }

  private validateEmail(email: string): void {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      throw new Error('Invalid email format');
    }
  }

  private validatePassword(password: string): void {
    if (!password || password.length < 8) {
      throw new Error('Password must be at least 8 characters');
    }
  }

  private validateToken(token: string): void {
    if (!token || token.trim().length === 0) {
      throw new Error('Token is required');
    }
  }

  private handleError(error: unknown): { success: false; message: string } {
    console.error('Auth service error:', error);
    if (error instanceof Error) {
      return { success: false, message: error.message };
    }
    return { success: false, message: 'An unexpected error occurred' };
  }
}

export const authService = new AuthService();