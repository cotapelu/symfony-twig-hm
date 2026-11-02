import { loginSchema, registerSchema } from '$lib/validation';

export class AuthService {
  private baseUrl = '/api/auth';

  async login(credentials: { email: string; password: string }) {
    const validation = loginSchema.safeParse(credentials);
    if (!validation.success) {
      const errors = validation.error.flatten().fieldErrors;
      throw new Error(Object.values(errors).flat().join(', '));
    }

    const response = await fetch(`${this.baseUrl}/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(credentials)
    });

    if (!response.ok) {
      throw new Error('Login failed');
    }

    return response.json();
  }

  async register(userData: { name: string; email: string; password: string }) {
    const validation = registerSchema.safeParse(userData);
    if (!validation.success) {
      const errors = validation.error.flatten().fieldErrors;
      throw new Error(Object.values(errors).flat().join(', '));
    }

    const response = await fetch(`${this.baseUrl}/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(userData)
    });

    if (!response.ok) {
      throw new Error('Registration failed');
    }

    return response.json();
  }

  async logout() {
    await fetch(`${this.baseUrl}/logout`, { method: 'POST' });
  }

  async forgotPassword(email: string) {
    const response = await fetch(`${this.baseUrl}/forgot-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });

    if (!response.ok) {
      throw new Error('Failed to send reset email');
    }

    return response.json();
  }

  async refreshToken() {
    const response = await fetch(`${this.baseUrl}/refresh`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    });

    if (!response.ok) {
      throw new Error('Token refresh failed');
    }

    return response.json();
  }
}