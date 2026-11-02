// User Store - TypeScript Version
// PHP User Module with type safety

import { authService, type LoginRequest, type RegisterRequest, type AuthResponse as AuthSvcResponse } from "../lib/services/auth";
import type { User } from "../lib/types";
import { writable, derived, type Readable } from "svelte/store";
import { PHP } from "../lib/api";

export interface UserState {
  user: User | null;
  isAuthenticated: boolean;
  loading: boolean;
  error: string | null;
}

export interface LoginParams {
  email: string;
  password: string;
}

export interface RegisterParams {
  name: string;
  email: string;
  password: string;
}

export interface ProfileUpdateParams {
  name?: string;
  bio?: string;
  avatar?: string;
}

export interface AuthResponse {
  token: string;
  user: User;
  refreshToken?: string;
  expiresIn?: number;
  success?: boolean;
  error?: string;
}

export interface ForgotPasswordResponse {
  success: boolean;
  message?: string;
}

function createUserStore(): {
  subscribe: (
    run: (value: UserState) => void,
    invalidate?: (value?: UserState) => void,
  ) => () => void;
  login: (email: string, password: string) => Promise<AuthResponse>;
  register: (
    name: string,
    email: string,
    password: string,
  ) => Promise<AuthResponse>;
  logout: () => Promise<void>;
  loadProfile: () => Promise<User>;
  updateProfile: (data: ProfileUpdateParams) => Promise<User>;
  registerDevice: (token: string, platform?: string) => Promise<void>;
  init: () => void;
  setUser: (user: User) => void;
  clearError: () => void;
  reset: () => void;
  forgotPassword: (email: string) => Promise<ForgotPasswordResponse>;
} {
  const initialState: UserState = {
    user: null,
    isAuthenticated: false,
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<UserState>(initialState);

  function saveUserToStorage(user: User): void {
    if (typeof localStorage !== "undefined") {
      try {
        localStorage.setItem("user", JSON.stringify(user));
      } catch (error) {
        console.error("Failed to save user to storage:", error);
      }
    }
  }

  function clearUserFromStorage(): void {
    if (typeof localStorage !== "undefined") {
      localStorage.removeItem("user");
    }
  }

  function saveTokenToStorage(token: string): void {
    if (typeof localStorage !== "undefined") {
      localStorage.setItem("auth_token", token);
    }
  }

  function clearTokenFromStorage(): void {
    if (typeof localStorage !== "undefined") {
      localStorage.removeItem("auth_token");
    }
  }

  const baseStore = {
    subscribe,
    login: async (email: string, password: string): Promise<AuthResponse> => {
      update((state) => ({ ...state, loading: true, error: null }));

      try {
        const request: LoginRequest = { email, password };
        const data = await authService.login(request);
        
        const user: User = {
          id: data.user.id,
          name: data.user.username || data.user.email,
          email: data.user.email
        };
        
        saveTokenToStorage(data.access_token);
        saveUserToStorage(user);

        update((prev) => ({
          ...prev,
          user: user,
          isAuthenticated: true,
          loading: false,
          error: null,
        }));

        return { token: data.access_token, user };
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Login failed";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    register: async (
      name: string,
      email: string,
      password: string,
    ): Promise<AuthResponse> => {
      update((state) => ({ ...state, loading: true, error: null }));

      try {
        const request: RegisterRequest = { 
          email, 
          password, 
          username: name 
        };
        const data = await authService.register(request);
        
        const user: User = {
          id: data.user.id,
          name: data.user.username || data.user.email,
          email: data.user.email
        };
        
        saveTokenToStorage(data.access_token);
        saveUserToStorage(user);

        update((prev) => ({
          ...prev,
          user: user,
          isAuthenticated: true,
          loading: false,
          error: null,
        }));

        return { token: data.access_token, user };
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Registration failed";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    logout: async (): Promise<void> => {
      try {
        await PHP.logout();
      } catch (error) {
        console.error("Logout API error:", error);
      } finally {
        clearTokenFromStorage();
        clearUserFromStorage();
        set({
          user: null,
          isAuthenticated: false,
          loading: false,
          error: null,
        });
      }
    },

    loadProfile: async (): Promise<User> => {
      update((state) => ({ ...state, loading: true, error: null }));

      try {
        const profile = await PHP.getProfile();
        saveUserToStorage(profile);

        update((prev) => ({
          ...prev,
          user: profile,
          isAuthenticated: true,
          loading: false,
          error: null,
        }));

        return profile;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to load profile";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    updateProfile: async (data: ProfileUpdateParams): Promise<User> => {
      update((state) => ({ ...state, loading: true, error: null }));

      try {
        const updated = await PHP.updateProfile(data);
        saveUserToStorage(updated);

        update((prev) => ({
          ...prev,
          user: updated,
          loading: false,
        }));

        return updated;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to update profile";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    registerDevice: async (
      token: string,
      platform: string = "web",
    ): Promise<void> => {
      try {
        await PHP.sendSMSVerification?.(token);
      } catch (error) {
        console.error("Device token registration error:", error);
      }
    },

    forgotPassword: async (email: string): Promise<ForgotPasswordResponse> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        await PHP.forgotPassword(email);
        update((state) => ({
          ...state,
          loading: false,
          error: null,
        }));
        return { success: true, message: "Password reset email sent" };
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to send reset email";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        return { success: false, message: errorMessage };
      }
    },

    init: (): void => {
      if (typeof localStorage === "undefined") return;

      const token = localStorage.getItem("auth_token");
      const userStr = localStorage.getItem("user");

      if (token && userStr) {
        try {
          const user = JSON.parse(userStr) as User;
          set({ user, isAuthenticated: true, loading: false, error: null });
        } catch (error) {
          console.error("Failed to parse user data:", error);
          localStorage.removeItem("user");
          set(initialState);
        }
      } else {
        set(initialState);
      }
    },

    setUser: (user: User): void => {
      saveUserToStorage(user);
      set({
        user,
        isAuthenticated: true,
        loading: false,
        error: null,
      });
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },

    reset: (): void => {
      clearTokenFromStorage();
      clearUserFromStorage();
      set({
        user: null,
        isAuthenticated: false,
        loading: false,
        error: null,
      });
    },
  };

  return baseStore;
}

export const userStore = createUserStore();

export const isLoggedIn = derived(
  userStore as unknown as Readable<UserState>,
  ($state: UserState) => $state.isAuthenticated,
) as Readable<boolean>;

export const userId = derived(
  userStore as unknown as Readable<UserState>,
  ($state: UserState) => $state.user?.id || null,
) as Readable<string | null>;

export const userName = derived(
  userStore as unknown as Readable<UserState>,
  ($state: UserState) => $state.user?.name || null,
) as Readable<string | null>;

export const userEmail = derived(
  userStore as unknown as Readable<UserState>,
  ($state: UserState) => $state.user?.email || null,
) as Readable<string | null>;

export const userAvatar = derived(
  userStore as unknown as Readable<UserState>,
  ($state: UserState) =>
    $state.user?.avatar || $state.user?.avatarUrl || null,
) as Readable<string | null>;

export const isAdmin = derived(
  userStore as unknown as Readable<UserState>,
  ($state: UserState) => $state.user?.role === "admin",
) as Readable<boolean>;

export const {
  login,
  register,
  logout,
  loadProfile,
  updateProfile,
  registerDevice,
  init,
  setUser,
  clearError,
  reset,
  forgotPassword,
} = userStore;
