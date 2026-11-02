// Auth Guard for Frontend modules (React/Angular/Vue pattern)
// This is a generic pattern - adapt to your framework

// ============================================
// REACT EXAMPLE (use with React Router)
// ============================================

import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from './AuthContext';

// Protected Route component
export const ProtectedRoute = ({ children, requiredRoles = [] }) => {
  const { user, isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return <div>Loading...</div>; // Or spinner
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  // Check roles if required
  if (requiredRoles.length > 0 && user) {
    const hasRole = requiredRoles.some(role => user.roles?.includes(role));
    if (!hasRole) {
      return <Navigate to="/unauthorized" replace />;
    }
  }

  return children || <Outlet />;
};

// Role-based guard
export const RoleGuard = ({ children, allowedRoles }) => {
  const { user } = useAuth();

  if (!user || !user.roles) {
    return <Navigate to="/login" replace />;
  }

  const hasAccess = allowedRoles.some(role => user.roles.includes(role));
  if (!hasAccess) {
    return <Navigate to="/unauthorized" replace />;
  }

  return children;
};

// ============================================
// AUTH CONTEXT (React)
// ============================================

import { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext({});

export const useAuth = () => useContext(AuthContext);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Check for token in localStorage/sessionStorage
    const token = localStorage.getItem('access_token');
    if (token) {
      // Validate token with your backend API
      validateToken(token).then(userData => {
        setUser(userData);
        setIsLoading(false);
      }).catch(() => {
        localStorage.removeItem('access_token');
        setUser(null);
        setIsLoading(false);
      });
    } else {
      setIsLoading(false);
    }
  }, []);

  const login = async (credentials) => {
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(credentials)
    });

    if (!response.ok) throw new Error('Login failed');

    const { access_token, user } = await response.json();
    localStorage.setItem('access_token', access_token);
    setUser(user);
    return user;
  };

  const logout = () => {
    localStorage.removeItem('access_token');
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, isAuthenticated: !!user, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

// ============================================
// VUE 3 EXAMPLE (Composition API)
// ============================================

// authGuard.js (Vue Router)
export const requireAuth = async (to, from, next) => {
  const token = localStorage.getItem('access_token');
  if (!token) {
    return next({ path: '/login', query: { redirect: to.fullPath } });
  }

  // Validate token
  try {
    const response = await fetch('/api/auth/validate', {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (!response.ok) throw new Error('Invalid token');

    const user = await response.json();
    to.meta.requiredRoles // Check if route requires specific roles
    if (to.meta.requiredRoles && !to.meta.requiredRoles.includes(user.role)) {
      return next({ path: '/unauthorized' });
    }

    to.params.user = user; // Pass user to route
    next();
  } catch (error) {
    localStorage.removeItem('access_token');
    return next({ path: '/login' });
  }
};

// ============================================
// ANGULAR EXAMPLE (Route Guard)
// ============================================

// auth.guard.ts
import { Injectable } from '@angular/core';
import { Router, CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot } from '@angular/router';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate {
  constructor(private authService: AuthService, private router: Router) {}

  canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): boolean {
    const requiredRoles = route.data['requiredRoles'] || [];

    if (this.authService.isAuthenticated()) {
      const user = this.authService.getCurrentUser();
      if (requiredRoles.length > 0 && !requiredRoles.some(role => user.roles.includes(role))) {
        this.router.navigate(['/unauthorized']);
        return false;
      }
      return true;
    }

    this.router.navigate(['/login'], { queryParams: { returnUrl: state.url } });
    return false;
  }
}

// ============================================
// APPLICATION-WIDE USAGE
// ============================================

// React Router v6 Example:
/*
<Routes>
  <Route element={<ProtectedRoute><Layout /></ProtectedRoute>}>
    <Route path="/dashboard" element={<Dashboard />} />
    <Route path="/profile" element={<Profile />} />
    <Route path="/admin" element={<ProtectedRoute requiredRoles={['admin']}><AdminPanel /></ProtectedRoute>} />
  </Route>
  <Route path="/login" element={<Login />} />
</Routes>
*/

// Vue Router Example:
/*
const routes = [
  { path: '/dashboard', component: Dashboard, beforeEnter: [requireAuth] },
  { path: '/admin', component: Admin, beforeEnter: [requireAuth, requireRole('admin')] }
];
*/

// Angular Router Example:
/*
const routes: Routes = [
  { path: 'dashboard', component: Dashboard, canActivate: [AuthGuard] },
  { path: 'admin', component: Admin, canActivate: [AuthGuard], data: { requiredRoles: ['admin'] } }
];
*/

// ============================================
// API CALLS WITH AUTH
// ============================================

// Helper to include auth token in API requests
export const authFetch = async (url, options = {}) => {
  const token = localStorage.getItem('access_token');

  const config = {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
      ...options.headers
    }
  };

  const response = await fetch(url, config);

  if (response.status === 401) {
    // Token expired - redirect to login
    localStorage.removeItem('access_token');
    window.location.href = '/login';
    throw new Error('Session expired');
  }

  return response;
};

// ============================================
// KEY POINTS FOR ALL FRONTEND MODULES:
// ============================================
// 1. Wrap protected routes with <ProtectedRoute> or route guards
// 2. Store JWT token in localStorage/sessionStorage (consider secure storage on mobile)
// 3. Include Authorization header in all API calls to protected endpoints
// 4. Handle 401 responses globally (redirect to login)
// 5. Use role-based guards for admin/doctor/patient/etc roles
// 6. Never store sensitive data in localStorage without encryption (consider httpOnly cookies for production)
