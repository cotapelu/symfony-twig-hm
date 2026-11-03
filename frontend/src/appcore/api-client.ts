import { get } from 'svelte/store';
import { authStore } from '../modules/auth/state/authStore';

/**
 * API Client with automatic authentication
 * Centralizes all API calls with proper error handling and auth headers
 */

export class ApiClient {
  private baseUrl: string;
  private defaultHeaders: HeadersInit;

  constructor(baseUrl = '/api') {
    this.baseUrl = baseUrl;
    this.defaultHeaders = {
      'Content-Type': 'application/json',
    };
  }

  private async getAuthToken(): Promise<string | null> {
    // Try to get from localStorage first
    if (typeof localStorage !== 'undefined') {
      const stored = localStorage.getItem('iam_auth');
      if (stored) {
        try {
          const data = JSON.parse(stored);
          if (data.tokens?.accessToken) {
            return data.tokens.accessToken;
          }
        } catch {
          // ignore parse error
        }
      }
    }

    // Fallback to authStore (if in Svelte context)
    try {
      const authState = get(authStore);
      if (authState.tokens?.accessToken) {
        return authState.tokens.accessToken;
      }
    } catch {
      // authStore may not be available outside Svelte components
    }

    return null;
  }

  // public: the module-level apiRequest<T>() helper delegates to this method.
  async request<T>(
    endpoint: string,
    options: RequestInit = {},
    retries: number = 3
  ): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`;
    const token = await this.getAuthToken();

    const headers = new Headers(this.defaultHeaders);
    if (options.headers) {
      Object.entries(options.headers).forEach(([key, value]) => {
        headers.set(key, String(value));
      });
    }

    if (token) {
      headers.set('Authorization', `Bearer ${token}`);
    }

    const config: RequestInit = {
      ...options,
      headers,
    };

    let lastError: Error | null = null;
    let attempt = 0;
    while (attempt <= retries) {
      try {
        const response = await fetch(url, config);

        if (!response.ok) {
          // Determine if we should retry: on 429 or 5xx
          const shouldRetry = (response.status === 429 || response.status >= 500) && attempt < retries;
          const errorData = await response.json().catch(() => ({ 
            message: `HTTP ${response.status}: ${response.statusText}` 
          }));
          const error = new Error(errorData.message || `Request failed: ${response.status}`);
          // Attach status for caller
          (error as any).status = response.status;
          if (!shouldRetry) {
            throw error;
          }
          lastError = error;
        } else {
          // Success
          // Handle 204 No Content
          if (response.status === 204) {
            return null as T;
          }
          return response.json();
        }
      } catch (error) {
        // Network-level errors (offline, CORS, etc.)
        if (attempt === retries) {
          throw error;
        }
        lastError = error as Error;
      }

      // Exponential backoff with jitter
      const baseDelay = 100; // ms
      const factor = 2;
      const jitter = Math.random() * baseDelay;
      const delay = Math.min(baseDelay * Math.pow(factor, attempt) + jitter, 10000); // cap 10s
      await new Promise(resolve => setTimeout(resolve, delay));
      attempt++;
    }
    throw lastError || new Error('Request failed after retries');
  }

  async get<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: 'GET' });
  }

  async post<T>(endpoint: string, body?: any): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: body ? JSON.stringify(body) : undefined,
    });
  }

  async put<T>(endpoint: string, body?: any): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: body ? JSON.stringify(body) : undefined,
    });
  }

  async delete<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: 'DELETE' });
  }

  async patch<T>(endpoint: string, body?: any): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PATCH',
      body: body ? JSON.stringify(body) : undefined,
    });
  }
}

// Export singleton instance
export const apiClient = new ApiClient();

// Convenience function for direct use
export async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  return apiClient.request<T>(endpoint, options);
}
