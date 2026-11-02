import { writable, derived } from 'svelte/store';
import { goto } from '$app/navigation';
import { browser } from '$app/environment';

export interface LoginData {
  email: string;
  password: string;
  rememberMe: boolean;
}

export interface RegisterData {
  email: string;
  password: string;
  confirmPassword: string;
  name: string;
}

export interface User {
  id: string;
  email: string;
  name: string;
  avatar?: string;
  verified: boolean;
  createdAt: string;
}

export interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  loading: boolean;
  error: string | null;
  operationInProgress: string | null;
}

export const authStore = writable<AuthState>({
  user: null,
  isAuthenticated: false,
  loading: false,
  error: null,
  operationInProgress: null
});

export const user = derived(authStore, $state => $state.user);
export const isAuthenticated = derived(authStore, $state => $state.isAuthenticated);
export const loading = derived(authStore, $state => $state.loading);
export const error = derived(authStore, $state => $state.error);
export const operationInProgress = derived(authStore, $state => $state.operationInProgress);

function isValidEmail(email: string): boolean {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function isValidPassword(password: string): { valid: boolean; message: string } {
  if (password.length < 8) {
    return { valid: false, message: 'Password must be at least 8 characters' };
  }
  if (!/[A-Z]/.test(password)) {
    return { valid: false, message: 'Password must contain at least one uppercase letter' };
  }
  if (!/[a-z]/.test(password)) {
    return { valid: false, message: 'Password must contain at least one lowercase letter' };
  }
  if (!/[0-9]/.test(password)) {
    return { valid: false, message: 'Password must contain at least one number' };
  }
  return { valid: true, message: '' };
}

export async function login(email: string, password: string) {
  let currentOp: string | null = null;
  
  authStore.update(state => {
    currentOp = state.operationInProgress;
    if (state.operationInProgress && state.operationInProgress !== 'logout') {
      throw new Error('Another operation is in progress');
    }
    return { ...state, loading: true, error: null, operationInProgress: 'login' };
  });
  
  if (!isValidEmail(email)) {
    authStore.update(s => ({ ...s, loading: false, error: 'Invalid email format', operationInProgress: null }));
    throw new Error('Invalid email format');
  }
  
  if (!password) {
    authStore.update(s => ({ ...s, loading: false, error: 'Password is required', operationInProgress: null }));
    throw new Error('Password is required');
  }
  
  try {
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    
    if (!response.ok) {
      const data = await response.json().catch(() => ({}));
      throw new Error(data.message || 'Login failed');
    }
    
    const { user: userData } = await response.json();
    
    authStore.update(s => ({
      ...s,
      user: userData,
      isAuthenticated: true,
      loading: false,
      operationInProgress: null
    }));
    
    if (browser) {
      goto('/');
    }
  } catch (err) {
    authStore.update(s => ({
      ...s,
      loading: false,
      error: (err as Error).message,
      operationInProgress: null
    }));
    throw err;
  }
}

export async function register(name: string, email: string, password: string) {
  authStore.update(state => {
    if (state.operationInProgress) {
      throw new Error('Another operation is in progress');
    }
    return { ...state, loading: true, error: null, operationInProgress: 'register' };
  });
  
  if (!name || name.trim().length < 2) {
    authStore.update(s => ({ ...s, loading: false, error: 'Name must be at least 2 characters', operationInProgress: null }));
    throw new Error('Name must be at least 2 characters');
  }
  
  if (!isValidEmail(email)) {
    authStore.update(s => ({ ...s, loading: false, error: 'Invalid email format', operationInProgress: null }));
    throw new Error('Invalid email format');
  }
  
  const passwordValidation = isValidPassword(password);
  if (!passwordValidation.valid) {
    authStore.update(s => ({ ...s, loading: false, error: passwordValidation.message, operationInProgress: null }));
    throw new Error(passwordValidation.message);
  }
  
  try {
    const response = await fetch('/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email, password })
    });
    
    if (!response.ok) {
      const data = await response.json().catch(() => ({}));
      throw new Error(data.message || 'Registration failed');
    }
    
    const { user: userData } = await response.json();
    
    authStore.update(s => ({
      ...s,
      user: userData,
      isAuthenticated: true,
      loading: false,
      operationInProgress: null
    }));
    
    if (browser) {
      goto('/');
    }
  } catch (err) {
    authStore.update(s => ({
      ...s,
      loading: false,
      error: (err as Error).message,
      operationInProgress: null
    }));
    throw err;
  }
}

export async function logout() {
  let currentOp: string | null = null;
  
  authStore.update(state => {
    currentOp = state.operationInProgress;
    return { ...state, loading: true, operationInProgress: 'logout' };
  });
  
  try {
    await fetch('/api/auth/logout', { method: 'POST' });
  } catch (err) {
    console.error('Logout error:', err);
  }
  
  authStore.update(s => ({
    ...s,
    user: null,
    isAuthenticated: false,
    loading: false,
    error: null,
    operationInProgress: null
  }));
  
  if (browser) {
    goto('/auth');
  }
}

export async function forgotPassword(email: string) {
  try {
    const response = await fetch('/api/auth/forgot-password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    
    if (!response.ok) {
      throw new Error('Failed to send reset email');
    }
    
    return { success: true, message: 'Password reset email sent' };
  } catch (err) {
    return { 
      success: false, 
      message: (err as Error).message || 'Failed to send reset email' 
    };
  }
}

export function clearError() {
  authStore.update(state => ({ ...state, error: null }));
}