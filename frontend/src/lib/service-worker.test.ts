import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';


describe('service-worker', () => {
  let selfMock: any;

  beforeEach(() => {
    vi.resetModules();

    // Setup global self with required APIs
    selfMock = {
      addEventListener: vi.fn(),
      skipWaiting: vi.fn(),
      registration: { showNotification: vi.fn() },
      clients: {
        matchAll: vi.fn().mockResolvedValue([]),
        openWindow: vi.fn()
      },
      caches: {
        open: vi.fn().mockResolvedValue({
          addAll: vi.fn().mockResolvedValue(undefined),
          match: vi.fn().mockResolvedValue(undefined),
          put: vi.fn().mockResolvedValue(undefined),
          delete: vi.fn().mockResolvedValue(undefined)
        })
      }
    };
    (global as any).self = selfMock;
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should set CACHE constant correctly', async () => {
    const sw = await import('../service-worker');
    expect(sw.CACHE).toBe('cache-1.0.0');
  });

  it('should set ASSETS array including build and files', async () => {
    const sw = await import('../service-worker');
    expect(sw.ASSETS).toEqual([
      '/index.html',
      '/app.css',
      '/favicon.ico',
      '/offline'
    ]);
  });

  it('registers event listeners on self', async () => {
    await import('../service-worker');
    // self.addEventListener should have been called for install, activate, message, fetch, push, notificationclick, sync
    expect(selfMock.addEventListener).toHaveBeenCalled();
  });
});
