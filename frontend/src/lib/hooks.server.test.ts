import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock @sveltejs/kit's redirect
vi.mock('@sveltejs/kit', () => {
  const mockRedirect = vi.fn((status: number, location: string) => {
    return { status, location };
  });
  return { redirect: mockRedirect };
});

let hooks: any;

const defaultEnv = {
  VITE_API_BASE: 'http://api.test',
  VITE_MERCURE_URL: 'wss://mercure.test',
};

describe('hooks.server', () => {
  beforeEach(async () => {
    vi.resetModules();
    process.env.VITE_API_BASE = defaultEnv.VITE_API_BASE;
    process.env.VITE_MERCURE_URL = defaultEnv.VITE_MERCURE_URL;
    hooks = await import('../hooks.server');
  });

  afterEach(() => {
    delete process.env.VITE_API_BASE;
    delete process.env.VITE_MERCURE_URL;
    vi.restoreAllMocks();
  });

  describe('handleAuthentication', () => {
    it('does not redirect for public routes', async () => {
      const event = {
        url: new URL('http://localhost/public/page'),
        cookies: { get: vi.fn(() => null), delete: vi.fn() },
        request: new Request('http://localhost/public/page'),
        locals: {}
      };
      await expect(hooks.handleAuthentication(event)).resolves.toBeUndefined();
    });

    it('redirects to /auth if no token on protected route', async () => {
      const event = {
        url: new URL('http://localhost/messages'),
        cookies: { get: vi.fn(() => null), delete: vi.fn() },
        request: new Request('http://localhost/messages'),
        locals: {}
      };
      await expect(hooks.handleAuthentication(event)).rejects.toEqual({
        status: 302,
        location: '/auth'
      });
    });

    it('redirects to /auth if token invalid (API returns non-ok)', async () => {
      const fakeResponse = new Response('Unauthorized', { status: 401 });
      global.fetch = vi.fn().mockResolvedValueOnce(fakeResponse);

      const event = {
        url: new URL('http://localhost/messages'),
        cookies: { get: vi.fn(() => 'invalid-token'), delete: vi.fn() },
        request: new Request('http://localhost/messages'),
        locals: {}
      };
      await expect(hooks.handleAuthentication(event)).rejects.toEqual({
        status: 302,
        location: '/auth'
      });
      expect(global.fetch).toHaveBeenCalledWith(
        `${defaultEnv.VITE_API_BASE}/api/user/profile`,
        expect.objectContaining({
          headers: expect.objectContaining({
            Authorization: 'Bearer invalid-token'
          })
        })
      );
      expect(event.cookies.delete).toHaveBeenCalledWith('auth_token', { path: '/' });
    });

    it('sets locals.user and locals.token when token valid', async () => {
      const token = 'valid-token';
      const fakeUser = { user: { id: 1, name: 'Test' } };
      const fakeResponse = new Response(JSON.stringify(fakeUser), { status: 200 });
      global.fetch = vi.fn().mockResolvedValueOnce(fakeResponse);

      const event = {
        url: new URL('http://localhost/messages'),
        cookies: { get: vi.fn(() => token), delete: vi.fn() },
        request: new Request('http://localhost/messages'),
        locals: {}
      };

      await expect(hooks.handleAuthentication(event)).resolves.toBeUndefined();
      expect(event.locals.user).toEqual(fakeUser.user);
      expect(event.locals.token).toBe(token);
    });

    it('redirects authenticated user away from /auth routes', async () => {
      const token = 'valid-token';
      const fakeUser = { user: { id: 1 } };
      global.fetch = vi.fn().mockResolvedValueOnce(new Response(JSON.stringify(fakeUser), { status: 200 }));

      const event = {
        url: new URL('http://localhost/auth/login'),
        cookies: { get: vi.fn(() => token), delete: vi.fn() },
        request: new Request('http://localhost/auth/login'),
        locals: { user: fakeUser.user, token }
      };
      await expect(hooks.handleAuthentication(event)).rejects.toEqual({
        status: 302,
        location: '/dashboard'
      });
    });
  });

  describe('handleApiRoute', () => {
    it('forwards API requests to backend', async () => {
      const fakeBackendResponse = new Response('OK', { status: 200 });
      global.fetch = vi.fn().mockResolvedValueOnce(fakeBackendResponse);

      const event = {
        url: new URL('http://localhost/api/test?foo=bar'),
        request: new Request('http://localhost/api/test?foo=bar', { method: 'POST', body: 'body' })
      };

      const result = await hooks.handleApiRoute(event);
      expect(result).toBeInstanceOf(Response);
      expect(result.status).toBe(200);
      expect(global.fetch).toHaveBeenCalledWith(
        `${defaultEnv.VITE_API_BASE}/api/test?foo=bar`,
        expect.objectContaining({
          method: 'POST',
          body: 'body',
          headers: expect.objectContaining({
            host: new URL(defaultEnv.VITE_API_BASE).host
          })
        })
      );
    });

    it('passes GET requests without body', async () => {
      const fakeBackendResponse = new Response('OK', { status: 200 });
      global.fetch = vi.fn().mockResolvedValueOnce(fakeBackendResponse);

      const event = {
        url: new URL('http://localhost/api/data'),
        request: new Request('http://localhost/api/data')
      };

      await hooks.handleApiRoute(event);
      expect(global.fetch).toHaveBeenCalledWith(
        `${defaultEnv.VITE_API_BASE}/api/data`,
        expect.objectContaining({
          method: 'GET',
          body: undefined
        })
      );
    });

    it('returns 503 on fetch error', async () => {
      global.fetch = vi.fn().mockRejectedValueOnce(new Error('Network error'));

      const event = {
        url: new URL('http://localhost/api/test'),
        request: new Request('http://localhost/api/test')
      };

      const result = await hooks.handleApiRoute(event);
      expect(result.status).toBe(503);
      const body = await result.json();
      expect(body).toEqual({ error: 'Backend service unavailable' });
    });
  });

  describe('addSecurityHeaders', () => {
    it('sets security headers on response', () => {
      const response = new Response('test');
      hooks.addSecurityHeaders(response);
      expect(response.headers.get('X-Frame-Options')).toBe('DENY');
      expect(response.headers.get('X-Content-Type-Options')).toBe('nosniff');
      expect(response.headers.get('Referrer-Policy')).toBe('strict-origin-when-cross-origin');
      expect(response.headers.get('Permissions-Policy')).toBe('camera=(), microphone=(), geolocation=()');
      const csp = response.headers.get('Content-Security-Policy');
      expect(csp).toMatch('default-src');
      expect(csp).toContain(defaultEnv.VITE_MERCURE_URL);
    });

    it('adds Cache-Control for CSS/JS assets', () => {
      const response = new Response('css content', {
        headers: { 'Content-Type': 'text/css' }
      });
      hooks.addSecurityHeaders(response);
      expect(response.headers.get('Cache-Control')).toBe('public, max-age=31536000, immutable');
    });
  });

  describe('handle', () => {
    it('calls handleApiRoute for /api paths', async () => {
      const fakeResponse = new Response('API OK');
      global.fetch = vi.fn().mockResolvedValueOnce(fakeResponse);
      const event = {
        url: new URL('http://localhost/api/hello'),
        request: new Request('http://localhost/api/hello'),
        cookies: { get: vi.fn(), delete: vi.fn() },
        locals: {}
      };
      const resolve = vi.fn().mockResolvedValueOnce(new Response('Page'));
      const result = await hooks.handle({ event, resolve });
      expect(result).toBeInstanceOf(Response);
      expect(result.status).toBe(200);
      expect(global.fetch).toHaveBeenCalled();
      expect(resolve).not.toHaveBeenCalled();
    });

    it('processes non-api pages with resolve and adds security headers', async () => {
      const pageResponse = new Response('Dashboard page');
      // Use a public route not requiring auth
      const event = {
        url: new URL('http://localhost/help'),
        request: new Request('http://localhost/help'),
        cookies: { get: vi.fn(() => null), delete: vi.fn() },
        locals: {}
      };
      const resolve = vi.fn().mockResolvedValueOnce(pageResponse);
      const result = await hooks.handle({ event, resolve });
      expect(result).toBe(pageResponse);
      expect(resolve).toHaveBeenCalled();
      // Check that security headers were added (the response object should have them)
      expect(result.headers.get('X-Frame-Options')).toBe('DENY');
    });
  });

  describe('handleError', () => {
    it('returns generic error message', () => {
      const result = hooks.handleError({ error: new Error('oops'), event: {} });
      expect(result).toEqual({ message: 'Internal server error', code: 500 });
    });
  });

  describe('handleFetch', () => {
    it('rewrites /api requests to API_BASE', async () => {
      // Use a fake request object with a pathname that starts with /api/
      const fakeRequest = { url: '/api/foo' } as any;
      const fetchMock = vi.fn().mockResolvedValue(new Response('ok'));
      await hooks.handleFetch({ request: fakeRequest, fetch: fetchMock });
      const [rewrittenRequest] = fetchMock.mock.calls[0];
      expect(rewrittenRequest).toBeInstanceOf(Request);
      expect(rewrittenRequest.url).toBe(`${defaultEnv.VITE_API_BASE}/api/foo`);
    });

    it('passes through non-api requests unchanged', async () => {
      const fakeRequest = { url: '/other' } as any;
      const fetchMock = vi.fn().mockResolvedValue(new Response('ok'));
      await hooks.handleFetch({ request: fakeRequest, fetch: fetchMock });
      expect(fetchMock).toHaveBeenCalledWith(fakeRequest);
    });
  });
});
