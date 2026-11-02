import { writable, derived } from 'svelte/store';

// Browser detection without SvelteKit virtual module to improve testability
const isBrowser = typeof window !== 'undefined' && typeof document !== 'undefined';

interface User {
  id: string;
  email: string;
  name: string;
  avatar?: string;
  role?: string;
}

interface AuthState {
  user: User | null;
  token: string | null;
  loading: boolean;
  error: string | null;
}

export function createAuthStore() {
  const { subscribe, set, update } = writable<AuthState>({
    user: null,
    token: null,
    loading: false,
    error: null
  });

  return {
    subscribe,

    login: async (email: string, password: string) => {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const response = await fetch('/api/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password })
        });

        if (!response.ok) {
          throw new Error('Login failed');
        }

        const { user, token } = await response.json();
        
        update(state => ({ ...state, user, token, loading: false }));
        
        if (isBrowser) {
          localStorage.setItem('auth_token', token);
        }

        return user;
      } catch (error) {
        update(state => ({ 
          ...state, 
          loading: false, 
          error: (error as Error).message 
        }));
        throw error;
      }
    },

    register: async (name: string, email: string, password: string) => {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const response = await fetch('/api/auth/register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, email, password })
        });

        if (!response.ok) {
          throw new Error('Registration failed');
        }

        const { user, token } = await response.json();
        
        update(state => ({ ...state, user, token, loading: false }));
        
        if (isBrowser) {
          localStorage.setItem('auth_token', token);
        }

        return user;
      } catch (error) {
        update(state => ({ 
          ...state, 
          loading: false, 
          error: (error as Error).message 
        }));
        throw error;
      }
    },

    logout: () => {
      update(state => ({ 
        ...state, 
        user: null, 
        token: null, 
        error: null 
      }));
      
      if (isBrowser) {
        localStorage.removeItem('auth_token');
      }
    },

    setUser: (user: User, token?: string) => {
      update(state => ({ ...state, user, token: token || state.token }));
      
      if (isBrowser && token) {
        localStorage.setItem('auth_token', token);
      }
    },

    clearError: () => {
      update(state => ({ ...state, error: null }));
    },
    reset: () => {
      set({ user: null, token: null, loading: false, error: null });
      if (isBrowser) localStorage.removeItem('auth_token');
    }
  };
}

export const authStore = createAuthStore();

export const isAuthenticated = derived(
  authStore,
  $authStore => !!$authStore.user && !!$authStore.token
);

export const currentUser = derived(
  authStore,
  $authStore => $authStore.user
);