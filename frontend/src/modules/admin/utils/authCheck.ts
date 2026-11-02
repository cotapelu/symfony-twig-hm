import { writable, derived, get } from 'svelte/store';
import type { AdminUser, AdminState } from '../contracts/adminTypes';

const ADMIN_ROLES = ['superadmin', 'admin', 'moderator', 'support'] as const;
type AdminRole = typeof ADMIN_ROLES[number];

const ROLE_HIERARCHY: Record<AdminRole, number> = {
  superadmin: 4,
  admin: 3,
  moderator: 2,
  support: 1
};

const PERMISSIONS: Record<string, AdminRole[]> = {
  'user:read': ['superadmin', 'admin', 'moderator', 'support'],
  'user:create': ['superadmin', 'admin'],
  'user:update': ['superadmin', 'admin'],
  'user:delete': ['superadmin'],
  'user:status': ['superadmin', 'admin'],
  'document:read': ['superadmin', 'admin', 'moderator', 'support'],
  'document:approve': ['superadmin', 'admin', 'moderator'],
  'document:reject': ['superadmin', 'admin', 'moderator'],
  'population:read': ['superadmin', 'admin', 'moderator', 'support'],
  'population:write': ['superadmin', 'admin'],
  'logs:read': ['superadmin', 'admin', 'moderator'],
  'stats:read': ['superadmin', 'admin'],
  'settings:read': ['superadmin', 'admin'],
  'settings:write': ['superadmin']
};

function createAuthStore() {
  const { subscribe, set, update } = writable<{
    currentUser: AdminUser | null;
    isAuthenticated: boolean;
    loading: boolean;
  }>({
    currentUser: null,
    isAuthenticated: false,
    loading: true
  });

  return {
    subscribe,
    
    setUser(user: AdminUser | null) {
      update(state => ({
        ...state,
        currentUser: user,
        isAuthenticated: user !== null,
        loading: false
      }));
    },

    logout() {
      set({
        currentUser: null,
        isAuthenticated: false,
        loading: false
      });
    },

    setLoading(loading: boolean) {
      update(state => ({ ...state, loading }));
    }
  };
}

export const authStore = createAuthStore();

export function hasRole(requiredRole: AdminRole): boolean {
  const state = get(authStore);
  if (!state.currentUser) return false;
  return ROLE_HIERARCHY[state.currentUser.role] >= ROLE_HIERARCHY[requiredRole];
}

export function hasPermission(permission: string): boolean {
  const state = get(authStore);
  if (!state.currentUser) return false;
  
  const allowedRoles = PERMISSIONS[permission];
  if (!allowedRoles) return false;
  
  return allowedRoles.includes(state.currentUser.role);
}

export function canPerformAction(action: string, targetUserId?: string): boolean {
  const state = get(authStore);
  if (!state.currentUser) return false;
  
  const userRole = state.currentUser.role;
  
  if (targetUserId && targetUserId === state.currentUser.id) {
    if (action === 'delete' || action === 'status') {
      return userRole === 'superadmin';
    }
  }
  
  return hasPermission(`${action.split(':')[0]}:${action.split(':')[1]}`);
}

export function isRole(role: AdminRole): boolean {
  return hasRole(role);
}

export const canManageUsers = derived(authStore, $auth => 
  $auth.currentUser && ['superadmin', 'admin'].includes($auth.currentUser.role)
);

export const canApproveDocuments = derived(authStore, $auth =>
  $auth.currentUser && ['superadmin', 'admin', 'moderator'].includes($auth.currentUser.role)
);

export const canViewLogs = derived(authStore, $auth =>
  $auth.currentUser && ['superadmin', 'admin', 'moderator'].includes($auth.currentUser.role)
);

export const canViewStats = derived(authStore, $auth =>
  $auth.currentUser && ['superadmin', 'admin'].includes($auth.currentUser.role)
);

export function requireAuth(requiredRole?: AdminRole): boolean {
  const state = get(authStore);
  if (!state.isAuthenticated || !state.currentUser) {
    return false;
  }
  
  if (requiredRole && !hasRole(requiredRole)) {
    return false;
  }
  
  return true;
}

export function checkAccessDenied(permission: string): { allowed: boolean; message?: string } {
  const state = get(authStore);
  
  if (!state.isAuthenticated) {
    return { allowed: false, message: 'Authentication required' };
  }
  
  if (!hasPermission(permission)) {
    return { allowed: false, message: 'Insufficient permissions' };
  }
  
  return { allowed: true };
}