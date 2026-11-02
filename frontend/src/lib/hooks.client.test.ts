import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock the global stores
vi.mock('$lib/stores/global', () => ({
  user: { set: vi.fn(), subscribe: vi.fn(() => () => {}) },
  isAuthenticated: { set: vi.fn(), subscribe: vi.fn(() => () => {}) },
  connectRealtime: vi.fn(),
  disconnectRealtime: vi.fn()
}));

describe('hooks.client', () => {
  let fetchMock: vi.Mock;
  let removeItemSpy: vi.Spy;

  beforeEach(() => {
    vi.resetModules();
    localStorage.clear();

    fetchMock = vi.fn();
    global.fetch = fetchMock;

    // Spy on localStorage.removeItem for assertions
    removeItemSpy = vi.spyOn(localStorage, 'removeItem');

    // Ensure window.location writable (setupTests already does)
    if (!window.location) window.location = {} as any;
    // @ts-ignore
    window.location.href = '';
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('initializes from localStorage when valid token and user data present', async () => {
    const userData = { id: 1, name: 'Test' };
    localStorage.setItem('auth_token', 'token123');
    localStorage.setItem('user_data', JSON.stringify(userData));

    fetchMock.mockResolvedValueOnce(new Response(JSON.stringify({ user: userData }), { status: 200 }));

    await import('../hooks.client');

    // Wait for async token validation to complete
    await Promise.resolve();

    const stores = await import('$lib/stores/global');
    expect(stores.user.set).toHaveBeenCalledWith(userData);
    expect(stores.isAuthenticated.set).toHaveBeenCalledWith(true);
    expect(stores.connectRealtime).toHaveBeenCalled();
  });

  it('handles invalid JSON in localStorage', async () => {
    localStorage.setItem('auth_token', 'token');
    localStorage.setItem('user_data', 'invalid{json');

    await import('../hooks.client');
    await Promise.resolve();

    expect(removeItemSpy).toHaveBeenCalledWith('auth_token');
    expect(removeItemSpy).toHaveBeenCalledWith('user_data');
    const stores = await import('$lib/stores/global');
    expect(stores.user.set).not.toHaveBeenCalled();
  });

  it('does nothing when no token in localStorage', async () => {
    await import('../hooks.client');

    expect(fetchMock).not.toHaveBeenCalled();
    const stores = await import('$lib/stores/global');
    expect(stores.user.set).not.toHaveBeenCalled();
    expect(stores.isAuthenticated.set).not.toHaveBeenCalled();
  });

  it('validateTokenOnLoad clears auth on 401', async () => {
    const userData = { id: 1, name: 'Test' };
    localStorage.setItem('auth_token', 'token123');
    localStorage.setItem('user_data', JSON.stringify(userData));

    fetchMock.mockResolvedValueOnce(new Response('', { status: 401 }));

    await import('../hooks.client');
    await Promise.resolve();

    expect(removeItemSpy).toHaveBeenCalledWith('auth_token');
    expect(removeItemSpy).toHaveBeenCalledWith('user_data');
    const stores = await import('$lib/stores/global');
    expect(stores.user.set).toHaveBeenCalledWith(null);
    expect(stores.isAuthenticated.set).toHaveBeenCalledWith(false);
  });

  it('fetch wrapper redirects on 401', async () => {
    await import('../hooks.client');

    // Prepare the mock for the wrapper call
    fetchMock.mockResolvedValueOnce(new Response('Unauthorized', { status: 401 }));

    const fetchPromise = window.fetch('/api/test');
    // The wrapper should have called original fetch immediately
    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, init] = fetchMock.mock.calls[0];
    expect(url).toBe('/api/test');
    expect(init).toBeUndefined();
    const response = await fetchPromise;

    expect(response.status).toBe(401);
    expect(window.location.href).toBe('/auth');
    expect(removeItemSpy).toHaveBeenCalledWith('auth_token');
    expect(removeItemSpy).toHaveBeenCalledWith('user_data');
    const stores = await import('$lib/stores/global');
    expect(stores.user.set).toHaveBeenCalledWith(null);
    expect(stores.isAuthenticated.set).toHaveBeenCalledWith(false);
  });

  it('shows global error on 403', async () => {
    await import('../hooks.client');
    const dispatchSpy = vi.spyOn(window, 'dispatchEvent');

    fetchMock.mockResolvedValueOnce(new Response('Forbidden', { status: 403 }));
    await window.fetch('/api/test');

    // The dispatchEvent should have been called with a CustomEvent containing the expected message
    const [event] = dispatchSpy.mock.calls[0];
    const customEvent = event as CustomEvent;
    expect(customEvent.detail.message).toBe('You do not have permission to perform this action.');
  });

  it('shows global error on 5xx', async () => {
    await import('../hooks.client');
    const dispatchSpy = vi.spyOn(window, 'dispatchEvent');

    fetchMock.mockResolvedValueOnce(new Response('Server error', { status: 500 }));
    await window.fetch('/api/test');

    const [event] = dispatchSpy.mock.calls[0];
    const customEvent = event as CustomEvent;
    expect(customEvent.detail.message).toBe('Server error. Please try again later.');
  });

  it('shows toast on network error when offline', async () => {
    await import('../hooks.client');
    // navigator is writable from setupTests
    navigator.onLine = false;
    const dispatchSpy = vi.spyOn(window, 'dispatchEvent');

    fetchMock.mockRejectedValueOnce(new Error('offline'));
    try {
      await window.fetch('/api/test');
    } catch {
      // expected
    }

    const [event] = dispatchSpy.mock.calls[0];
    const customEvent = event as CustomEvent;
    expect(customEvent.detail.message).toBe('You are offline. Please check your connection.');
    expect(customEvent.detail.type).toBe('warning');
  });

  it('shows global error on network error when online', async () => {
    await import('../hooks.client');
    navigator.onLine = true;
    const dispatchSpy = vi.spyOn(window, 'dispatchEvent');

    fetchMock.mockRejectedValueOnce(new Error('network'));
    try {
      await window.fetch('/api/test');
    } catch {
      // expected
    }

    const [event] = dispatchSpy.mock.calls[0];
    const customEvent = event as CustomEvent;
    expect(customEvent.detail.message).toBe('Network error. Please check your connection.');
  });
});
