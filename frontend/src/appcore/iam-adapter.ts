export interface User {
  id: string;
  email: string;
  username?: string;
  firstName?: string;
  lastName?: string;
  roles: string[];
  permissions: string[];
  profile: Record<string, any>;
  createdAt: Date;
  updatedAt: Date;
  lastLoginAt?: Date;
}

export interface AuthTokens {
  accessToken: string;
  refreshToken: string;
  idToken?: string;
  expiresAt: Date;
  tokenType: 'Bearer';
}

export interface AuthState {
  isAuthenticated: boolean;
  user: User | null;
  tokens: AuthTokens | null;
  isLoading: boolean;
  error: string | null;
}

export interface LoginRequest {
  email: string;
  password: string;
  rememberMe?: boolean;
}

export interface RegisterRequest {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
  username?: string;
}

export interface ResetPasswordRequest {
  email: string;
  token: string;
  newPassword: string;
}

export interface ChangePasswordRequest {
  currentPassword: string;
  newPassword: string;
}

export interface IAMConfig {
  apiUrl: string;
  clientId: string;
  redirectUri: string;
  scopes: string[];
  tokenRefreshThreshold: number;
  autoRefresh: boolean;
  enableSSO: boolean;
  ssoProviders: string[];
}

class IAMAdapter {
  private config: IAMConfig;
  private state: AuthState = $state<AuthState>({
    isAuthenticated: false,
    user: null,
    tokens: null,
    isLoading: false,
    error: null
  });
  
  private refreshTimer: NodeJS.Timeout | null = null;
  private refreshInterval: NodeJS.Timeout | null = null;
  private eventListeners: Map<string, Set<Function>> = new Map();

  constructor(config: IAMConfig) {
    this.config = config;
    this.initializeFromStorage();
    this.setupAutoRefresh();
    this.setupNetworkListeners();
  }

  async login(request: LoginRequest): Promise<void> {
    this.setLoading(true);
    this.clearError();

    try {
      const response = await this.makeRequest('/auth/login', {
        method: 'POST',
        body: JSON.stringify({
          email: request.email,
          password: request.password,
          client_id: this.config.clientId,
          grant_type: 'password'
        })
      });

      const { access_token, refresh_token, id_token, expires_in } = response;

      const tokens: AuthTokens = {
        accessToken: access_token,
        refreshToken: refresh_token,
        idToken: id_token,
        expiresAt: new Date(Date.now() + (expires_in * 1000)),
        tokenType: 'Bearer'
      };

      await this.setTokens(tokens);

      this.persistToStorage();
      this.emitEvent('auth:login', { user: this.state.user });

    } catch (error) {
      this.setError(this.formatError(error));
      throw error;
    } finally {
      this.setLoading(false);
    }
  }

  async register(request: RegisterRequest): Promise<void> {
    this.setLoading(true);
    this.clearError();

    try {
      await this.makeRequest('/auth/register', {
        method: 'POST',
        body: JSON.stringify({
          email: request.email,
          password: request.password,
          first_name: request.firstName,
          last_name: request.lastName,
          username: request.username,
          client_id: this.config.clientId
        })
      });

      this.emitEvent('auth:registered', { email: request.email });

    } catch (error) {
      this.setError(this.formatError(error));
      throw error;
    } finally {
      this.setLoading(false);
    }
  }

  async logout(): Promise<void> {
    try {
      if (this.state.tokens) {
        await this.makeRequest('/auth/logout', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${this.state.tokens.accessToken}`
          }
        });
      }
    } catch (error) {
      console.warn('Logout request failed:', error);
    } finally {
      this.clearAuth();
      this.clearStorage();
      this.emitEvent('auth:logout', {});
    }
  }

  async refreshToken(): Promise<void> {
    if (!this.state.tokens?.refreshToken) {
      throw new Error('No refresh token available');
    }

    try {
      const response = await this.makeRequest('/auth/refresh', {
        method: 'POST',
        body: JSON.stringify({
          refresh_token: this.state.tokens.refreshToken,
          client_id: this.config.clientId,
          grant_type: 'refresh_token'
        })
      });

      const { access_token, refresh_token, id_token, expires_in } = response;

      const tokens: AuthTokens = {
        accessToken: access_token,
        refreshToken: refresh_token || this.state.tokens!.refreshToken,
        idToken: id_token,
        expiresAt: new Date(Date.now() + (expires_in * 1000)),
        tokenType: 'Bearer'
      };

      await this.setTokens(tokens);
      this.persistToStorage();
      this.emitEvent('auth:token-refreshed', {});

    } catch (error) {
      this.clearAuth();
      this.clearStorage();
      this.emitEvent('auth:session-expired', {});
      throw error;
    }
  }

  async resetPassword(email: string): Promise<void> {
    this.setLoading(true);
    this.clearError();

    try {
      await this.makeRequest('/auth/reset-password', {
        method: 'POST',
        body: JSON.stringify({ email })
      });

      this.emitEvent('auth:password-reset-requested', { email });

    } catch (error) {
      this.setError(this.formatError(error));
      throw error;
    } finally {
      this.setLoading(false);
    }
  }

  async changePassword(request: ChangePasswordRequest): Promise<void> {
    this.setLoading(true);
    this.clearError();

    try {
      await this.makeAuthenticatedRequest('/auth/change-password', {
        method: 'POST',
        body: JSON.stringify({
          current_password: request.currentPassword,
          new_password: request.newPassword
        })
      });

      this.emitEvent('auth:password-changed', {});

    } catch (error) {
      this.setError(this.formatError(error));
      throw error;
    } finally {
      this.setLoading(false);
    }
  }

  async updateProfile(profile: Partial<User>): Promise<void> {
    this.setLoading(true);
    this.clearError();

    try {
      const response = await this.makeAuthenticatedRequest('/auth/profile', {
        method: 'PUT',
        body: JSON.stringify(profile)
      });

      this.state.user = { ...this.state.user!, ...response };
      this.persistToStorage();
      this.emitEvent('auth:profile-updated', { user: this.state.user });

    } catch (error) {
      this.setError(this.formatError(error));
      throw error;
    } finally {
      this.setLoading(false);
    }
  }

  hasRole(role: string): boolean {
    return this.state.user?.roles.includes(role) ?? false;
  }

  hasPermission(permission: string): boolean {
    return this.state.user?.permissions.includes(permission) ?? false;
  }

  hasAnyRole(roles: string[]): boolean {
    return roles.some(role => this.hasRole(role));
  }

  hasAnyPermission(permissions: string[]): boolean {
    return permissions.some(permission => this.hasPermission(permission));
  }

  hasAllRoles(roles: string[]): boolean {
    return roles.every(role => this.hasRole(role));
  }

  hasAllPermissions(permissions: string[]): boolean {
    return permissions.every(permission => this.hasPermission(permission));
  }

  getAuthHeader(): string | null {
    return this.state.tokens ? `${this.state.tokens.tokenType} ${this.state.tokens.accessToken}` : null;
  }

  isTokenExpired(): boolean {
    if (!this.state.tokens) return true;
    return new Date() >= this.state.tokens.expiresAt;
  }

  isTokenExpiringSoon(thresholdSeconds: number = 300): boolean {
    if (!this.state.tokens) return true;
    const threshold = new Date(Date.now() + (thresholdSeconds * 1000));
    return this.state.tokens.expiresAt <= threshold;
  }

  getState(): AuthState {
    return { ...this.state };
  }

  getUser(): User | null {
    return this.state.user;
  }

  isAuthenticated(): boolean {
    return this.state.isAuthenticated && !this.isTokenExpired();
  }

  private async fetchUserProfile(): Promise<void> {
    if (!this.state.tokens) return;

    const user = await this.makeAuthenticatedRequest('/auth/profile');
    this.state.user = user;
    this.state.isAuthenticated = true;
  }

  private async setTokens(tokens: AuthTokens): Promise<void> {
    this.state.tokens = tokens;
    await this.fetchUserProfile();
    this.setupRefreshTimer();
  }

  private clearAuth(): void {
    this.state.isAuthenticated = false;
    this.state.user = null;
    this.state.tokens = null;
    this.state.error = null;
    this.clearRefreshTimer();
  }

  private setLoading(loading: boolean): void {
    this.state.isLoading = loading;
  }

  private setError(error: string | null): void {
    this.state.error = error;
  }

  private clearError(): void {
    this.state.error = null;
  }

  private setupAutoRefresh(): void {
    if (!this.config.autoRefresh) return;

    this.setupRefreshTimer();
    this.refreshInterval = setInterval(() => {
      if (this.isAuthenticated() && this.isTokenExpiringSoon(this.config.tokenRefreshThreshold)) {
        this.refreshToken().catch(console.error);
      }
    }, 60000);
  }

  private setupRefreshTimer(): void {
    this.clearRefreshTimer();
    
    if (!this.state.tokens) return;

    const timeUntilExpiry = this.state.tokens.expiresAt.getTime() - Date.now();
    const refreshTime = timeUntilExpiry - (this.config.tokenRefreshThreshold * 1000);

    if (refreshTime > 0) {
      this.refreshTimer = setTimeout(() => {
        this.refreshToken().catch(console.error);
      }, refreshTime);
    }
  }

  private clearRefreshTimer(): void {
    if (this.refreshTimer) {
      clearTimeout(this.refreshTimer);
      this.refreshTimer = null;
    }
  }

  private setupNetworkListeners(): void {
    window.addEventListener('online', () => {
      if (this.isTokenExpired() && this.state.tokens?.refreshToken) {
        this.refreshToken().catch(console.error);
      }
    });
  }

  private async makeRequest(endpoint: string, options: RequestInit = {}): Promise<any> {
    const url = `${this.config.apiUrl}${endpoint}`;
    
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options.headers
      }
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Network error' }));
      throw new Error(error.message || `HTTP ${response.status}`);
    }

    return response.json();
  }

  private async makeAuthenticatedRequest(endpoint: string, options: RequestInit = {}): Promise<any> {
    const token = this.state.tokens?.accessToken;
    if (!token) {
      throw new Error('No authentication token available');
    }

    return this.makeRequest(endpoint, {
      ...options,
      headers: {
        'Authorization': `Bearer ${token}`,
        ...options.headers
      }
    });
  }

  private formatError(error: any): string {
    if (error?.message) return error.message;
    if (typeof error === 'string') return error;
    return 'An unexpected error occurred';
  }

  private persistToStorage(): void {
    if (typeof localStorage === 'undefined') return;

    const storageData = {
      tokens: this.state.tokens,
      user: this.state.user,
      isAuthenticated: this.state.isAuthenticated
    };

    localStorage.setItem('iam_auth', JSON.stringify(storageData));
  }

  private initializeFromStorage(): void {
    if (typeof localStorage === 'undefined') return;

    try {
      const stored = localStorage.getItem('iam_auth');
      if (!stored) return;

      const data = JSON.parse(stored);
      
      if (data.tokens && data.user) {
        // Convert dates back to Date objects
        if (data.tokens.expiresAt && typeof data.tokens.expiresAt === 'string') {
          data.tokens.expiresAt = new Date(data.tokens.expiresAt);
        }
        if (data.user.createdAt && typeof data.user.createdAt === 'string') {
          data.user.createdAt = new Date(data.user.createdAt);
        }
        if (data.user.updatedAt && typeof data.user.updatedAt === 'string') {
          data.user.updatedAt = new Date(data.user.updatedAt);
        }
        this.state.tokens = data.tokens;
        this.state.user = data.user;
        this.state.isAuthenticated = data.isAuthenticated && !this.isTokenExpired();
        
        if (this.state.isAuthenticated) {
          this.setupRefreshTimer();
        } else {
          this.clearStorage();
        }
      }
    } catch (error) {
      console.warn('Failed to load auth state from storage:', error);
      this.clearStorage();
    }
  }

  private clearStorage(): void {
    if (typeof localStorage === 'undefined') return;
    localStorage.removeItem('iam_auth');
  }

  private emitEvent(eventType: string, data: any): void {
    const event = new CustomEvent(eventType, { detail: data });
    window.dispatchEvent(event);

    const listeners = this.eventListeners.get(eventType);
    if (listeners) {
      listeners.forEach(listener => {
        try {
          listener(data);
        } catch (error) {
          console.error(`Error in IAM event listener for ${eventType}:`, error);
        }
      });
    }
  }

  addEventListener(eventType: string, listener: Function): () => void {
    if (!this.eventListeners.has(eventType)) {
      this.eventListeners.set(eventType, new Set());
    }

    this.eventListeners.get(eventType)!.add(listener);

    return () => {
      const listeners = this.eventListeners.get(eventType);
      if (listeners) {
        listeners.delete(listener);
        if (listeners.size === 0) {
          this.eventListeners.delete(eventType);
        }
      }
    };
  }

  removeEventListener(eventType: string, listener: Function): void {
    const listeners = this.eventListeners.get(eventType);
    if (listeners) {
      listeners.delete(listener);
    }
  }

  dispose(): void {
    this.clearRefreshTimer();
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
      this.refreshInterval = null;
    }
    this.clearAuth();
    this.clearStorage();
    this.eventListeners.clear();
  }
}

export const createIAMAdapter = (config: IAMConfig): IAMAdapter => {
  return new IAMAdapter(config);
};

export { IAMAdapter };

export type { IAMAdapter };