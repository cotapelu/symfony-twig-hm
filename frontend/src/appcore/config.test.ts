import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { ConfigManager } from './config';

describe('ConfigManager', () => {
  let storage: Record<string, string>;
  let localStorageMock: any;
  let locationMock: { hostname: string; search: string };

  function createManager(initialConfig?: Partial<EnvironmentConfig>): ConfigManager {
    return new ConfigManager(initialConfig || {});
  }

  beforeEach(() => {
    storage = {};
    localStorageMock = {
      getItem: vi.fn((key: string) => storage[key] ?? null),
      setItem: vi.fn((key: string, value: string) => { storage[key] = value; }),
      removeItem: vi.fn((key: string) => { delete storage[key]; }),
      clear: vi.fn(() => { Object.keys(storage).forEach(k => delete storage[k]); }),
      get length() { return Object.keys(storage).length; },
      key: vi.fn((i: number) => Object.keys(storage)[i] ?? null),
    };
    Object.defineProperty(global, 'localStorage', { value: localStorageMock, writable: true });

    // Mock window.location
    locationMock = { hostname: 'localhost', search: '' };
    vi.spyOn(window, 'location', 'get').mockReturnValue(locationMock as any);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('construction', () => {
    it('initializes with defaults', () => {
      const manager = createManager();
      expect(manager.get('name')).toBe('development');
      expect(manager.get('apiUrl')).toBe('http://localhost:5173/api');
      expect(manager.get('featureFlags.enableBetaFeatures')).toBe(false);
      expect(manager.get('logging.enabled')).toBe(true);
      expect(manager.get('security.tokenRefreshThreshold')).toBe(300);
    });

    it('overrides default apiUrl via initialConfig', () => {
      const manager = createManager({ apiUrl: 'https://api.example.com' });
      expect(manager.get('apiUrl')).toBe('https://api.example.com');
    });

    it('detects environment from hostname', () => {
      // localhost -> development
      locationMock.hostname = 'localhost';
      let manager = createManager();
      expect(manager.get('name')).toBe('development');

      // staging
      locationMock.hostname = 'app.staging.example.com';
      manager = createManager();
      expect(manager.get('name')).toBe('staging');

      // production
      locationMock.hostname = 'app.example.com';
      manager = createManager();
      expect(manager.get('name')).toBe('production');
    });

    it('applies feature flag overrides from URL search params', () => {
      locationMock.search = '?feature_enableBetaFeatures=true';
      const manager = createManager();
      expect(manager.get('featureFlags.enableBetaFeatures')).toBe(true);
    });
  });

  describe('get', () => {
    it('retrieves top-level values', () => {
      const manager = createManager();
      expect(manager.get('name')).toBe('development');
      expect(manager.get('apiUrl')).toBe('http://localhost:5173/api');
    });

    it('retrieves nested values using dot notation', () => {
      const manager = createManager();
      expect(manager.get('security.tokenRefreshThreshold')).toBe(300);
      expect(manager.get('logging.enabled')).toBe(true);
    });

    it('returns undefined for keys not in schema or config', () => {
      const manager = createManager();
      expect(manager.get('unknown.key')).toBeUndefined();
    });

    it('respects overrides', () => {
      const manager = createManager();
      manager.set('logging.enabled', false);
      expect(manager.get('logging.enabled')).toBe(false);
    });
  });

  describe('set', () => {
    it('updates allowed override values', () => {
      const manager = createManager();
      manager.set('featureFlags.enableBetaFeatures', true);
      expect(manager.get('featureFlags.enableBetaFeatures')).toBe(true);
    });

    it('validates against schema', () => {
      const manager = createManager();
      // Invalid name
      expect(() => manager.set('name', 'invalid')).toThrow('Invalid value');
      // Invalid tokenRefreshThreshold
      expect(() => manager.set('security.tokenRefreshThreshold', 0)).toThrow('Invalid value');
    });

    it('throws for keys not present in schema', () => {
      const manager = createManager();
      expect(() => manager.set('ui.theme', 'dark')).toThrow('Unknown configuration key');
    });

    it('calls watchers on change', () => {
      const manager = createManager();
      const watcher = vi.fn();
      manager.watch('featureFlags.enableBetaFeatures', watcher);
      manager.set('featureFlags.enableBetaFeatures', true);
      expect(watcher).toHaveBeenCalledWith(true, false);
    });

    it('calls watchers even if value unchanged (current behavior)', () => {
      const manager = createManager();
      const watcher = vi.fn();
      manager.watch('logging.enabled', watcher);
      manager.set('logging.enabled', true); // same as default
      expect(watcher).toHaveBeenCalledWith(true, true);
    });
  });

  describe('watch', () => {
    it('returns unsubscribe function', () => {
      const manager = createManager();
      const watcher = vi.fn();
      const unsubscribe = manager.watch('security.tokenRefreshThreshold', watcher);
      unsubscribe();
      manager.set('security.tokenRefreshThreshold', 500);
      expect(watcher).not.toHaveBeenCalled();
    });

    it('allows multiple watchers on same key', () => {
      const manager = createManager();
      const w1 = vi.fn();
      const w2 = vi.fn();
      manager.watch('apiUrl', w1);
      manager.watch('apiUrl', w2);
      manager.set('apiUrl', 'https://new.example.com');
      expect(w1).toHaveBeenCalledWith('https://new.example.com', 'http://localhost:5173/api');
      expect(w2).toHaveBeenCalled();
    });
  });

  describe('getAll', () => {
    it('returns full config including overrides', () => {
      const manager = createManager();
      manager.set('logging.enabled', false);
      const all = manager.getAll();
      expect(all.logging.enabled).toBe(false);
      expect(all.name).toBe('development');
    });
  });

  describe('reset', () => {
    it('clears override for a key and reverts to config value', () => {
      const manager = createManager();
      manager.set('logging.enabled', false);
      expect(manager.get('logging.enabled')).toBe(false);
      manager.reset('logging.enabled');
      expect(manager.get('logging.enabled')).toBe(true);
    });

    it('clears all overrides when called without key', () => {
      const manager = createManager();
      manager.set('logging.enabled', false);
      manager.set('featureFlags.enableBetaFeatures', true);
      manager.reset();
      expect(manager.get('logging.enabled')).toBe(true);
      expect(manager.get('featureFlags.enableBetaFeatures')).toBe(false);
    });
  });

  describe('persistToStorage', () => {
    it('saves config on construction', () => {
      createManager();
      const call = localStorageMock.setItem.mock.calls.find(([k]: [string, any]) => k === 'app_config');
      expect(call).toBeDefined();
      const saved = JSON.parse(call![1]);
      expect(saved).toHaveProperty('apiUrl');
      expect(saved).toHaveProperty('logging.enabled');
    });

    it('saves on set', () => {
      const manager = createManager();
      localStorageMock.setItem.mockClear();
      manager.set('security.tokenRefreshThreshold', 450);
      const call = localStorageMock.setItem.mock.calls.find(([k]: [string, any]) => k === 'app_config');
      expect(call).toBeDefined();
      const saved = JSON.parse(call![1]);
      expect(saved.security.tokenRefreshThreshold).toBe(450);
    });

    it('saves on reset', () => {
      const manager = createManager();
      manager.set('logging.enabled', false);
      localStorageMock.setItem.mockClear();
      manager.reset('logging.enabled');
      const call = localStorageMock.setItem.mock.calls.find(([k]: [string, any]) => k === 'app_config');
      expect(call).toBeDefined();
      const saved = JSON.parse(call![1]);
      expect(saved.logging.enabled).toBe(true);
    });
  });

  describe('isFeatureEnabled', () => {
    it('returns value from config', () => {
      const manager = createManager();
      expect(manager.isFeatureEnabled('enableBetaFeatures')).toBe(false);
      expect(manager.isFeatureEnabled('enableDarkMode')).toBe(true);
    });

    it('overrides via URL parameter for enableBetaFeatures', () => {
      locationMock.search = '?feature_enableBetaFeatures=true';
      const manager = createManager();
      expect(manager.isFeatureEnabled('enableBetaFeatures')).toBe(true);
    });

    it('URL parameter "1" works', () => {
      locationMock.search = '?feature_enableBetaFeatures=1';
      const manager = createManager();
      expect(manager.isFeatureEnabled('enableBetaFeatures')).toBe(true);
    });

    it('URL parameter "false" yields false for BetaFeatures', () => {
      locationMock.search = '?feature_enableBetaFeatures=false';
      const manager = createManager();
      expect(manager.isFeatureEnabled('enableBetaFeatures')).toBe(false);
    });
  });

  describe('getAPIEndpoints', () => {
    it('combines base apiUrl with endpoint paths', () => {
      const manager = createManager({ apiUrl: 'https://api.example.com/v1' });
      const eps = manager.getAPIEndpoints();
      expect(eps.iam).toBe('https://api.example.com/v1/iam');
      expect(eps.mdm).toBe('https://api.example.com/v1/mdm');
      expect(eps.events).toBe('https://api.example.com/v1/events');
      expect(eps.billing).toBe('https://api.example.com/v1/billing');
      expect(eps.orders).toBe('https://api.example.com/v1/orders');
      expect(eps.users).toBe('https://api.example.com/v1/users');
      expect(eps.notifications).toBe('https://api.example.com/v1/notifications');
      expect(eps.files).toBe('https://api.example.com/v1/files');
      expect(eps.search).toBe('https://api.example.com/v1/search');
      expect(eps.analytics).toBe('https://api.example.com/v1/analytics');
    });
  });

  describe('validate', () => {
    it('detects missing required fields', () => {
      const manager = createManager();
      const result = manager.validate({} as any);
      expect(result.isValid).toBe(false);
      expect(result.errors.some(e => e.includes('name'))).toBe(true);
      expect(result.errors.some(e => e.includes('apiUrl'))).toBe(true);
    });

    it('detects invalid value for field', () => {
      const manager = createManager();
      const result = manager.validate({ name: 'badenv' } as any);
      expect(result.isValid).toBe(false);
      expect(result.errors[0]).toContain('Invalid value for field name');
    });

    it('passes with full valid config', () => {
      const manager = createManager();
      const fullConfig: Partial<EnvironmentConfig> = {
        name: 'production',
        apiUrl: 'https://api.test',
        featureFlags: { enableBetaFeatures: true },
        logging: { enabled: false },
        security: { tokenRefreshThreshold: 400 },
      };
      const result = manager.validate(fullConfig);
      expect(result.isValid).toBe(true);
    });
  });
});
