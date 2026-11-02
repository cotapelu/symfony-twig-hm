import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { ErrorBoundaryManager, createErrorBoundaryManager } from './error-boundary';

// Custom error types
class NetworkError extends Error { constructor() { super('Network error'); } }
class TimeoutError extends Error { constructor() { super('Timeout'); } }

const baseConfig = (overrides: Partial<any> = {}) => ({
  enableLogging: false,
  enableTelemetry: false,
  maxRetries: 3,
  retryDelay: 0,
  onError: undefined,
  onRecovery: undefined,
  logEndpoint: undefined,
  telemetryEndpoint: undefined,
  ...overrides,
});

describe('ErrorBoundaryManager', () => {
  let fetchMock: ReturnType<typeof vi.fn>;
  let localStorageMock: Record<string, string>;
  let sessionStorageMock: Record<string, string>;
  let addEventListenerSpy: ReturnType<typeof vi.spyOn>;
  let dispatchEventSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    vi.useFakeTimers();
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    localStorageMock = {};
    const localStorage = {
      getItem: vi.fn((k: string) => localStorageMock[k] ?? null),
      setItem: vi.fn((k: string, v: string) => { localStorageMock[k] = v; }),
      removeItem: vi.fn((k: string) => { delete localStorageMock[k]; }),
    };
    vi.stubGlobal('localStorage', localStorage);

    sessionStorageMock = {};
    const sessionStorage = {
      getItem: vi.fn((k: string) => sessionStorageMock[k] ?? null),
      setItem: vi.fn((k: string, v: string) => { sessionStorageMock[k] = v; }),
      removeItem: vi.fn((k: string) => { delete sessionStorageMock[k]; }),
      clear: vi.fn(() => { for (const k in sessionStorageMock) delete sessionStorageMock[k]; }),
      get length() { return Object.keys(sessionStorageMock).length; },
      key: vi.fn((i: number) => Object.keys(sessionStorageMock)[i] ?? null),
    };
    vi.stubGlobal('sessionStorage', sessionStorage);

    addEventListenerSpy = vi.spyOn(window, 'addEventListener');
    dispatchEventSpy = vi.spyOn(window, 'dispatchEvent').mockImplementation(() => true);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
    vi.useRealTimers();
    vi.clearAllTimers();
  });

  describe('Factory', () => {
    it('createErrorBoundaryManager creates a new instance', () => {
      const mgr = createErrorBoundaryManager(baseConfig());
      expect(mgr).toBeInstanceOf(ErrorBoundaryManager);
    });
  });

  describe('Configuration and Setup', () => {
    it('registers global error and unhandledrejection listeners', () => {
      createErrorBoundaryManager(baseConfig());
      expect(addEventListenerSpy).toHaveBeenCalledWith('error', expect.any(Function));
      expect(addEventListenerSpy).toHaveBeenCalledWith('unhandledrejection', expect.any(Function));
    });

    it('generateSessionId creates unique IDs', () => {
      const mgr = createErrorBoundaryManager(baseConfig());
      // Access private via bracket since not accessible otherwise; we trust it works.
      const id1 = (mgr as any).generateSessionId();
      const id2 = (mgr as any).generateSessionId();
      expect(id1).toMatch(/^session_\d+_[a-z0-9]+$/);
      expect(id1).not.toBe(id2);
    });
  });

  describe('Error Capture', () => {
    it('captureError returns an ErrorInfo with correct fields', () => {
      const mgr = createErrorBoundaryManager(baseConfig());
      const err = new Error('test');
      const info = mgr.captureError(err, { componentStack: 'Comp' }, { foo: 'bar' });
      expect(info.error).toBe(err);
      expect(info.errorInfo.componentStack).toBe('Comp');
      expect(info.context).toEqual({ foo: 'bar' });
      expect(info.timestamp).toBeInstanceOf(Date);
      expect(info.userAgent).toBe(navigator.userAgent);
      expect(info.url).toBe(window.location.href);
      expect(info.sessionId).toBeDefined();
    });

    it('adds error to history', () => {
      const mgr = createErrorBoundaryManager(baseConfig());
      mgr.captureError(new Error('e1'));
      mgr.captureError(new Error('e2'));
      const hist = mgr.getErrorHistory();
      expect(hist.length).toBe(2);
    });

    it('includes userId from localStorage when available', () => {
      localStorageMock['user_id'] = 'u123';
      const mgr = createErrorBoundaryManager(baseConfig());
      const info = mgr.captureError(new Error('x'));
      expect(info.userId).toBe('u123');
    });
  });

  describe('Error Handling', () => {
    it('handleError logs when enableLogging true and logEndpoint set', async () => {
      const mgr = createErrorBoundaryManager(baseConfig({
        enableLogging: true,
        logEndpoint: 'https://log',
      }));
      const errInfo = {
        error: new Error('fail'),
        errorInfo: { componentStack: '' },
        context: {},
        timestamp: new Date(),
        userAgent: navigator.userAgent,
        url: window.location.href,
        sessionId: 'sess',
      } as any;
      fetchMock.mockResolvedValue({ ok: true });
      await mgr.handleError(errInfo);
      expect(fetchMock).toHaveBeenCalledWith(
        'https://log',
        expect.objectContaining({ method: 'POST', body: expect.stringContaining('"level":"error"') })
      );
    });

    it('handleError sends telemetry when enableTelemetry true', async () => {
      const mgr = createErrorBoundaryManager(baseConfig({
        enableTelemetry: true,
        telemetryEndpoint: 'https://tel',
      }));
      const errInfo = {
        error: new Error('fail'),
        errorInfo: { componentStack: '' },
        context: {},
        timestamp: new Date(),
        userAgent: navigator.userAgent,
        url: window.location.href,
        sessionId: 'sess',
      } as any;
      fetchMock.mockResolvedValue({ ok: true });
      await mgr.handleError(errInfo);
      expect(fetchMock).toHaveBeenCalledWith(
        'https://tel',
        expect.objectContaining({ method: 'POST', body: expect.stringContaining('"eventType":"error"') })
      );
    });

    it('handleError invokes onError callback', async () => {
      const cb = vi.fn();
      const mgr = createErrorBoundaryManager(baseConfig({ onError: cb }));
      const errInfo = mgr.captureError(new Error('x'));
      await mgr.handleError(errInfo);
      expect(cb).toHaveBeenCalledWith(errInfo);
    });
  });

  describe('Retry Logic', () => {
    it('canRetry returns true for retryable errors below max', () => {
      const mgr = createErrorBoundaryManager(baseConfig({ maxRetries: 2 }));
      const errInfo = { error: new NetworkError(), errorInfo: { componentStack: '' } } as any;
      expect(mgr.canRetry(errInfo)).toBe(true);
    });

    it('canRetry returns false after reaching maxRetries', () => {
      const mgr = createErrorBoundaryManager(baseConfig({ maxRetries: 1 }));
      const errInfo = { error: new NetworkError(), errorInfo: { componentStack: '' } } as any;
      const key = (mgr as any).getRetryKey(errInfo);
      sessionStorageMock[`error_retry_${key}`] = '1';
      expect(mgr.canRetry(errInfo)).toBe(false);
    });

    it('performRetry executes callback and clears retry count on success', async () => {
      const config = baseConfig({ maxRetries: 3, retryDelay: 0 });
      const mgr = createErrorBoundaryManager(config);
      const errInfo = { error: new NetworkError(), errorInfo: { componentStack: '' }, context: {}, timestamp: new Date(), userAgent: navigator.userAgent, url: window.location.href, sessionId: 's' } as any;
      const callback = vi.fn().mockResolvedValue(undefined);
      const key = (mgr as any).getRetryKey(errInfo);
      sessionStorageMock[`error_retry_${key}`] = '1';

      const promise = (mgr as any).performRetry(callback, errInfo);
      await vi.runAllTimersAsync(); // triggers setTimeout (0)
      await promise;

      expect(callback).toHaveBeenCalled();
      expect(sessionStorageMock[`error_retry_${key}`]).toBeUndefined();
    });

    it('performRetry respects maxRetries and does not retry', async () => {
      const config = baseConfig({ maxRetries: 2, retryDelay: 10 });
      const mgr = createErrorBoundaryManager(config);
      const errInfo = { error: new NetworkError(), errorInfo: { componentStack: '' }, context: {}, timestamp: new Date(), userAgent: navigator.userAgent, url: window.location.href, sessionId: 's' } as any;
      const callback = vi.fn().mockRejectedValue(new Error('fail'));
      const key = (mgr as any).getRetryKey(errInfo);
      sessionStorageMock[`error_retry_${key}`] = '2';

      await (mgr as any).performRetry(callback, errInfo);
      // No retry because already at max
      expect(callback).not.toHaveBeenCalled();
    });
  });

  describe('Stats and History', () => {
    it('getErrorHistory sorts by timestamp desc', () => {
      const mgr = createErrorBoundaryManager(baseConfig());
      const now = Date.now();
      const e1 = mgr.captureError(new Error('first'));
      (e1.timestamp as any) = new Date(now - 1000);
      const e2 = mgr.captureError(new Error('second'));
      (e2.timestamp as any) = new Date(now);
      const list = mgr.getErrorHistory();
      expect(list[0].error.message).toBe('second');
      expect(list[1].error.message).toBe('first');
    });

    it('getErrorStats aggregates counts', () => {
      const mgr = createErrorBoundaryManager(baseConfig());
      const a = mgr.captureError(new NetworkError());
      const b = mgr.captureError(new TimeoutError());
      (b as any).errorInfo.componentStack = 'CompX';
      const c = mgr.captureError(new NetworkError());
      const stats = mgr.getErrorStats();
      expect(stats.total).toBe(3);
      expect(stats.byType['NetworkError']).toBe(2);
      expect(stats.byType['TimeoutError']).toBe(1);
      expect(stats.byComponent['CompX']).toBe(1);
    });

    it('clearErrorHistory removes all errors and retry entries', () => {
      const mgr = createErrorBoundaryManager(baseConfig());
      sessionStorageMock['error_retry_abc'] = '1';
      mgr.captureError(new Error('x'));
      mgr.clearErrorHistory();
      expect(mgr.getErrorHistory().length).toBe(0);
      expect(sessionStorageMock['error_retry_abc']).toBeUndefined();
    });
  });

  describe('Recovery Strategies', () => {
    it('registerRecoveryStrategy and attemptRecovery executes strategy', async () => {
      const mgr = createErrorBoundaryManager(baseConfig());
      const strat = vi.fn().mockResolvedValue(true);
      mgr.registerRecoveryStrategy('NetworkError', strat);
      const errInfo = { error: new NetworkError(), errorInfo: { componentStack: '' }, context: {}, timestamp: new Date(), userAgent: '', url: '', sessionId: '' } as any;
      const ok = await mgr.attemptRecovery(errInfo);
      expect(ok).toBe(true);
      expect(strat).toHaveBeenCalled();
    });

    it('attemptRecovery returns false when no strategy', async () => {
      const mgr = createErrorBoundaryManager(baseConfig());
      const errInfo = { error: new Error('Other'), errorInfo: { componentStack: '' }, context: {}, timestamp: new Date(), userAgent: '', url: '', sessionId: '' } as any;
      const ok = await mgr.attemptRecovery(errInfo);
      expect(ok).toBe(false);
    });
  });
});
