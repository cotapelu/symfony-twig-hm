export interface Route {
  path: string;
  component: () => Promise<any>;
  module: string;
  auth?: {
    required: boolean;
    roles?: string[];
  };
  preload?: boolean;
}

export interface NavigationResult {
  success: boolean;
  error?: string;
  route?: Route;
}

export class Router {
  private routes: Map<string, Route> = new Map();
  private currentRoute: Route | null = null;
  private authState = $state<{
    isAuthenticated: boolean;
    userRoles: string[];
  }>({
    isAuthenticated: false,
    userRoles: []
  });

  constructor() {
    this.setupEventListeners();
    this.handleInitialNavigation();
  }

  register(route: Route): void {

    this.routes.set(route.path, route);
    
    if (route.preload) {
      this.preloadComponent(route);
    }
  }

  registerBatch(routes: Route[]): void {
    routes.forEach(route => {
      this.register(route);
    });
  }

  async navigate(path: string, options: {
    replace?: boolean;
    state?: any;
  } = {}): Promise<NavigationResult> {
    try {

      const route = this.findRoute(path);
      
      if (!route) {
        return {
          success: false,
          error: `Route not found: ${path}`
        };
      }

      if (route.auth?.required && !this.authState.isAuthenticated) {
        return {
          success: false,
          error: 'Authentication required'
        };
      }

      if (route.auth?.roles && !this.hasRequiredRoles(route.auth.roles)) {
        return {
          success: false,
          error: 'Insufficient permissions'
        };
      }

      if (options.replace) {
        history.replaceState(options.state, '', path);
      } else {
        history.pushState(options.state, '', path);
      }

      await this.loadRoute(route);
      this.currentRoute = route;

      this.emitNavigationEvent(path, route);

      return {
        success: true,
        route
      };

    } catch (error) {

      return {
        success: false,
        error: error instanceof Error ? error.message : 'Navigation failed'
      };
    }
  }

  private findRoute(path: string): Route | null {
    if (this.routes.has(path)) {
      return this.routes.get(path)!;
    }

    for (const [routePath, route] of this.routes) {
      if (this.isPathMatch(path, routePath)) {
        return route;
      }
    }

    return null;
  }

  private isPathMatch(path: string, pattern: string): boolean {
    const pathSegments = path.split('/').filter(Boolean);
    const patternSegments = pattern.split('/').filter(Boolean);

    if (pathSegments.length !== patternSegments.length) {
      return false;
    }

    return patternSegments.every((segment, index) => {
      return segment.startsWith(':') || segment === pathSegments[index];
    });
  }

  private async loadRoute(route: Route): Promise<void> {
    try {
      await route.component();
    } catch (error) {
      throw new Error(`Failed to load route component for ${route.path}: ${error}`);
    }
  }

  private async preloadComponent(route: Route): Promise<void> {
    try {
      await route.component();
    } catch (error) {
      console.warn(`Failed to preload component for ${route.path}:`, error);
    }
  }

  private hasRequiredRoles(requiredRoles: string[]): boolean {
    return requiredRoles.every(role => 
      this.authState.userRoles.includes(role)
    );
  }

  private setupEventListeners(): void {
    window.addEventListener('popstate', (event) => {
      const path = window.location.pathname;
      this.navigate(path, { replace: true });
    });

    document.addEventListener('click', (event) => {
      const target = event.target as HTMLElement;
      const link = target.closest('a[href]');
      
      if (link && link.getAttribute('data-spa') === 'true') {
        event.preventDefault();
        const path = link.getAttribute('href')!;
        this.navigate(path);
      }
    });
  }

  private handleInitialNavigation(): void {
    const path = window.location.pathname;
    this.navigate(path, { replace: true });
  }

  private emitNavigationEvent(path: string, route: Route): void {
    const event = new CustomEvent('app:navigation', {
      detail: {
        path,
        route: {
          path: route.path,
          module: route.module,
          timestamp: new Date().toISOString()
        }
      }
    });
    
    window.dispatchEvent(event);
  }

  updateAuthState(isAuthenticated: boolean, userRoles: string[] = []): void {
    this.authState.isAuthenticated = isAuthenticated;
    this.authState.userRoles = userRoles;
  }

  getCurrentRoute(): Route | null {
    return this.currentRoute;
  }

  getAllRoutes(): Route[] {
    return Array.from(this.routes.values());
  }

  hasRoute(path: string): boolean {
    return this.routes.has(path) || this.routes.has(this.findMatchingPattern(path));
  }

  private findMatchingPattern(path: string): string {
    for (const routePath of this.routes.keys()) {
      if (this.isPathMatch(path, routePath)) {
        return routePath;
      }
    }
    return '';
  }

  generateNavigationLinks(): Array<{
    path: string;
    label: string;
    module: string;
    isActive: boolean;
  }> {
    return Array.from(this.routes.values())
      .filter(route => !route.auth?.required || this.authState.isAuthenticated)
      .map(route => ({
        path: route.path,
        label: this.generateLabelFromPath(route.path),
        module: route.module,
        isActive: this.currentRoute?.path === route.path
      }))
      .sort((a, b) => a.path.localeCompare(b.path));
  }

  private generateLabelFromPath(path: string): string {
    return path
      .split('/')
      .filter(Boolean)
      .map(segment => 
        segment.startsWith(':') 
          ? segment.substring(1) 
          : segment
      )
      .map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
      )
      .join(' ') || 'Home';
  }

  dispose(): void {
    this.routes.clear();
    this.currentRoute = null;
  }
}

export const router = new Router();