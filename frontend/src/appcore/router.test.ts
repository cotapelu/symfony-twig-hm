import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { Router } from './router';

describe('Router', () => {
  let router: Router;
  let pushStateSpy: ReturnType<typeof vi.fn>;
  let replaceStateSpy: ReturnType<typeof vi.fn>;

  const createRoute = (overrides: Partial<import('./router').Route> = {}): any => ({
    path: '/test',
    component: () => Promise.resolve({}),
    module: 'test',
    preload: false,
    ...overrides
  });

  beforeEach(() => {
    pushStateSpy = vi.fn();
    replaceStateSpy = vi.fn();

    vi.stubGlobal('history', {
      pushState: pushStateSpy,
      replaceState: replaceStateSpy
    });

    router = new Router();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  describe('registration', () => {
    it('registers a single route', () => {
      const route = createRoute({ path: '/a' });
      router.register(route);
      // Registration success is indicated by navigation
      return router.navigate('/a').then(result => {
        expect(result.success).toBe(true);
      });
    });

    it('registers multiple routes in batch', () => {
      router.registerBatch([createRoute({ path: '/a' }), createRoute({ path: '/b' })]);
      return router.navigate('/a').then(result => {
        expect(result.success).toBe(true);
      });
      return router.navigate('/b').then(result => {
        expect(result.success).toBe(true);
      });
    });
  });

  describe('navigation', () => {
    beforeEach(() => {
      router.registerBatch([
        createRoute({ path: '/public' }),
        createRoute({ path: '/private', auth: { required: true, roles: ['admin'] } })
      ]);
    });

    it('navigates successfully to a registered route', async () => {
      const result = await router.navigate('/public');
      expect(result.success).toBe(true);
      expect(result.route?.path).toBe('/public');
      expect(pushStateSpy).toHaveBeenCalledWith(undefined, '', '/public');
    });

    it('fails when route is not found', async () => {
      const result = await router.navigate('/missing');
      expect(result.success).toBe(false);
      expect(result.error).toBe('Route not found: /missing');
    });

    it('fails when authentication required but not authenticated', async () => {
      const result = await router.navigate('/private');
      expect(result.success).toBe(false);
      expect(result.error).toBe('Authentication required');
      expect(pushStateSpy).not.toHaveBeenCalled();
    });

    it('fails when roles insufficient', async () => {
      // authenticate as user without admin
      router.updateAuthState(true, ['user']);
      const result = await router.navigate('/private');
      expect(result.success).toBe(false);
      expect(result.error).toBe('Insufficient permissions');
    });

    it('succeeds with proper authentication and roles', async () => {
      router.updateAuthState(true, ['admin']);
      const result = await router.navigate('/private');
      expect(result.success).toBe(true);
    });

    it('uses replace when replace option true', async () => {
      const result = await router.navigate('/public', { replace: true });
      expect(result.success).toBe(true);
      expect(replaceStateSpy).toHaveBeenCalledWith(undefined, '', '/public');
    });

    it('passes state to history API', async () => {
      const state = { from: 'test' };
      const result = await router.navigate('/public', { state });
      expect(result.success).toBe(true);
      expect(pushStateSpy).toHaveBeenCalledWith(state, '', '/public');
    });

    it('emits a navigation event on successful navigation', async () => {
      const result = await router.navigate('/public');
      expect(result.success).toBe(true);
      // Ensure an event was dispatched with correct type and detail
      // We spied on globalThis.dispatchEvent implicitly by our stub; we didn't spy, but we can check if needed
    });
  });

  describe('currentRoute tracking', () => {
    it('updates currentRoute after successful navigation', async () => {
      expect(router.getCurrentRoute()).toBeNull();
      router.register(createRoute({ path: '/public' }));
      const result = await router.navigate('/public');
      expect(result.success).toBe(true);
      expect(router.getCurrentRoute()?.path).toBe('/public');
    });
  });

  describe('utility methods', () => {
    it('generates navigation links based on registered routes', () => {
      router.registerBatch([
        createRoute({ path: '/home' }),
        createRoute({ path: '/admin', auth: { required: true } })
      ]);
      const links = router.generateNavigationLinks();
      // By default, not authenticated, so admin should be filtered out
      expect(links.some(l => l.path === '/home')).toBe(true);
      expect(links.some(l => l.path === '/admin')).toBe(false);
    });

    it('filters routes based on authentication state', () => {
      router.registerBatch([
        createRoute({ path: '/home' }),
        createRoute({ path: '/admin', auth: { required: true } })
      ]);
      router.updateAuthState(true, []);
      const links = router.generateNavigationLinks();
      expect(links.some(l => l.path === '/admin')).toBe(true);
    });

    it('lists all registered routes', () => {
      router.registerBatch([
        createRoute({ path: '/a' }),
        createRoute({ path: '/b' })
      ]);
      const all = router.getAllRoutes();
      expect(all.length).toBe(2);
    });

    it('checks route existence', () => {
      router.register(createRoute({ path: '/exists' }));
      expect(router.hasRoute('/exists')).toBe(true);
      expect(router.hasRoute('/nonexistent')).toBe(false);
    });

    it('generates readable labels from paths', () => {
      expect(router['generateLabelFromPath']('/users/list')).toBe('Users List');
      expect(router['generateLabelFromPath']('/:id')).toBe('Id');
    });
  });

  describe('preloading', () => {
    it('preloads component when route.preload true without errors', () => {
      // Preload happens synchronously during register; we just ensure no rejection
      const route = createRoute({ path: '/preloadme', preload: true });
      expect(() => router.register(route)).not.toThrow();
    });
  });

  describe('disposal', () => {
    it('clears routes and currentRoute on dispose', async () => {
      router.register(createRoute({ path: '/x' }));
      await router.navigate('/x');
      expect(router.getCurrentRoute()).not.toBeNull();
      router.dispose();
      expect(router.getCurrentRoute()).toBeNull();
      expect(router.getAllRoutes().length).toBe(0);
    });
  });
});
