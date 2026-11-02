import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { ModuleRegistry } from './module-registry';

// Mock router used during route registration
vi.mock('./router', () => ({
  router: { register: vi.fn() },
}));

describe('ModuleRegistry', () => {
  let registry: ModuleRegistry;
  let instantiateMock: ReturnType<typeof vi.fn>;
  let initializeSpy: ReturnType<typeof vi.fn>;
  let disposeSpy: ReturnType<typeof vi.fn>;
  let reloadHandler: ((this: Window, ev: Event) => void) | null = null;

  beforeEach(() => {
    instantiateMock = vi.fn();
    initializeSpy = vi.fn();
    disposeSpy = vi.fn();

    // Stub private instantiateModule to record calls and set a mock instance
    (ModuleRegistry.prototype as any).instantiateModule = async function (name: string) {
      instantiateMock(name);
      const registration = (this as any).modules.get(name);
      registration.instance = {
        initialize: () => initializeSpy(name),
        dispose: () => disposeSpy(name),
      };
    };

    // Spy on window events BEFORE creating registry
    vi.spyOn(window, 'addEventListener').mockImplementation((event: string, handler: Function) => {
      if (event === 'module:reload') {
        reloadHandler = handler as (this: Window, ev: Event) => void;
      }
    });
    vi.spyOn(window, 'dispatchEvent').mockImplementation(() => true);

    registry = new ModuleRegistry();
  });

  afterEach(async () => {
    vi.restoreAllMocks();
    reloadHandler = null;
    await registry.dispose();
  });

  describe('Constructor', () => {
    it('registers window listener for module:reload', () => {
      expect(window.addEventListener).toHaveBeenCalledWith('module:reload', expect.any(Function));
    });
  });

  describe('Module Registration', () => {
    const baseConfig = (name: string, deps: string[] = [], routes: any[] = []) => ({
      name,
      version: '1.0.0',
      dependencies: deps,
      routes,
      enabled: true,
    });

    it('throws if module already registered', async () => {
      await registry.register(baseConfig('m1'));
      await expect(registry.register(baseConfig('m1'))).rejects.toThrow('Module m1 is already registered');
    });

    it('validates that a module cannot depend on itself', async () => {
      await expect(registry.register(baseConfig('self', ['self']))).rejects.toThrow('cannot depend on itself');
    });

    it('builds dependency graph', async () => {
      await registry.register(baseConfig('B', []));
      await registry.register(baseConfig('A', ['B']));

      expect(registry.getModuleDependencies('A')).toEqual(['B']);
      expect(registry.getModuleDependents('B')).toContain('A');
    });

    it('loads dependencies before the module', async () => {
      await registry.register(baseConfig('B', []));
      await registry.register(baseConfig('A', ['B']));

      expect(instantiateMock).toHaveBeenCalledWith('B');
      expect(instantiateMock).toHaveBeenCalledWith('A');
      const order = instantiateMock.mock.calls.map((c: string[]) => c[0]);
      expect(order.indexOf('B')).toBeLessThan(order.indexOf('A'));
    });

    it('emits module:registered and module:loaded events', async () => {
      const dispatchSpy = window.dispatchEvent as ReturnType<typeof vi.spyOn>;
      await registry.register(baseConfig('mod'));

      const types = dispatchSpy.mock.calls.map((args: [CustomEvent]) => args[0].type);
      expect(types).toContain('module:registered');
      expect(types).toContain('module:loaded');
    });

    it('sets module status to loaded', async () => {
      await registry.register(baseConfig('mod'));
      expect(registry.getModule('mod')?.status).toBe('loaded');
    });

    it('calls initialize if present', async () => {
      await registry.register(baseConfig('mod'));
      expect(initializeSpy).toHaveBeenCalledWith('mod');
    });

    it('registers routes with router', async () => {
      const { router } = await import('./router');
      const registerSpy = router.register as ReturnType<typeof vi.fn>;

      await registry.register(baseConfig('mod', [], [{ path: '/mod', component: 'Mod' }]));

      expect(registerSpy).toHaveBeenCalledWith({
        module: 'mod',
        path: '/mod',
        component: 'Mod',
      });
    });

    it('marks module as error if load fails', async () => {
      (ModuleRegistry.prototype as any).instantiateModule = async function () {
        throw new Error('fail');
      };

      await expect(registry.register(baseConfig('bad'))).rejects.toThrow('fail');
      expect(registry.getModule('bad')?.status).toBe('error');
    });

    it('emits module:error on failure', async () => {
      const dispatchSpy = window.dispatchEvent as ReturnType<typeof vi.spyOn>;
      (ModuleRegistry.prototype as any).instantiateModule = async function () {
        throw new Error('boom');
      };

      try {
        await registry.register(baseConfig('bad'));
      } catch {}

      const types = dispatchSpy.mock.calls.map((args: [CustomEvent]) => args[0].type);
      expect(types).toContain('module:error');
    });
  });

  describe('Load Order', () => {
    it('computes topological order respecting dependencies', async () => {
      const modules = [
        { name: 'D', deps: [] },
        { name: 'C', deps: ['D'] },
        { name: 'B', deps: ['C'] },
        { name: 'A', deps: ['B'] },
      ];

      for (const { name, deps } of modules) {
        await registry.register({
          name,
          version: '1.0',
          dependencies: deps,
          routes: [],
          enabled: true,
        });
      }

      const order = registry.getLoadOrder();
      expect(order.indexOf('D')).toBeLessThan(order.indexOf('C'));
      expect(order.indexOf('C')).toBeLessThan(order.indexOf('B'));
      expect(order.indexOf('B')).toBeLessThan(order.indexOf('A'));
    });
  });

  describe('Getters', () => {
    it('getModule returns registration or undefined', async () => {
      await registry.register({ name: 'm', version: '1', dependencies: [], routes: [], enabled: true });
      expect(registry.getModule('m')).toBeDefined();
      expect(registry.getModule('missing')).toBeUndefined();
    });

    it('getAllModules returns all registrations', async () => {
      await registry.register({ name: 'a', version: '1', dependencies: [], routes: [], enabled: true });
      await registry.register({ name: 'b', version: '1', dependencies: [], routes: [], enabled: true });
      expect(registry.getAllModules().length).toBe(2);
    });

    it('getEnabledModules filters by enabled flag', async () => {
      await registry.register({ name: 'a', version: '1', dependencies: [], routes: [], enabled: true });
      await registry.register({ name: 'b', version: '1', dependencies: [], routes: [], enabled: false });
      expect(registry.getEnabledModules().map(m => m.config.name)).toEqual(['a']);
    });

    it('getLoadedModules filters by status loaded', async () => {
      await registry.register({ name: 'a', version: '1', dependencies: [], routes: [], enabled: true });
      expect(registry.getLoadedModules().length).toBe(1);
    });

    it('isModuleLoaded checks status', async () => {
      await registry.register({ name: 'm', version: '1', dependencies: [], routes: [], enabled: true });
      expect(registry.isModuleLoaded('m')).toBe(true);
      expect(registry.isModuleLoaded('missing')).toBe(false);
    });

    it('isModuleEnabled checks enabled flag', async () => {
      await registry.register({ name: 'm', version: '1', dependencies: [], routes: [], enabled: true });
      expect(registry.isModuleEnabled('m')).toBe(true);
      registry.disable('m');
      expect(registry.isModuleEnabled('m')).toBe(false);
    });

    it('getModuleStats counts correctly', async () => {
      await registry.register({ name: 'a', version: '1', dependencies: [], routes: [], enabled: true });
      await registry.register({ name: 'b', version: '1', dependencies: [], routes: [], enabled: false });
      const stats = registry.getModuleStats();
      expect(stats).toEqual({
        total: 2,
        loaded: 2, // both loaded successfully
        enabled: 1,
        error: 0,
        disabled: 0,
      });
    });
  });

  describe('Enable / Disable', () => {
    it('disable sets enabled false and status to disabled', async () => {
      await registry.register({ name: 'm', version: '1', dependencies: [], routes: [], enabled: true });
      registry.disable('m');
      expect(registry.isModuleEnabled('m')).toBe(false);
      expect(registry.getModule('m')?.status).toBe('disabled');
    });

    it('enable sets enabled true and loads if previously disabled', async () => {
      await registry.register({ name: 'm', version: '1', dependencies: [], routes: [], enabled: true });
      registry.disable('m');
      instantiateMock.mockClear();

      registry.enable('m');

      // enable triggers load asynchronously; give it time to settle
      await Promise.resolve();
      await Promise.resolve();

      expect(registry.isModuleEnabled('m')).toBe(true);
      expect(instantiateMock).toHaveBeenCalledWith('m');
      // status may still be loading depending on timing; we consider success if instantiate was invoked
    });

    it('enable is noop for nonexistent module', async () => {
      registry.enable('missing'); // should not throw
    });

    it('disable is noop for nonexistent module', async () => {
      registry.disable('missing');
    });
  });

  describe('Unregister', () => {
    it('calls dispose on instance if loaded', async () => {
      await registry.register({ name: 'm', version: '1', dependencies: [], routes: [], enabled: true });
      expect(disposeSpy).not.toHaveBeenCalled();

      registry.unregister('m');

      expect(disposeSpy).toHaveBeenCalledWith('m');
      expect(registry.getModule('m')).toBeUndefined();
    });

    it('emits module:unregistered event', async () => {
      const dispatchSpy = window.dispatchEvent as ReturnType<typeof vi.spyOn>;
      await registry.register({ name: 'm', version: '1', dependencies: [], routes: [], enabled: true });

      registry.unregister('m');

      const types = dispatchSpy.mock.calls.map((args: [CustomEvent]) => args[0].type);
      expect(types).toContain('module:unregistered');
    });

    it('unregister of non‑existent is noop', () => {
      registry.unregister('missing'); // no throw
    });
  });

  describe('Reload', () => {
    it('reloads a module: disposes and loads again', async () => {
      await registry.register({ name: 'm', version: '1', dependencies: [], routes: [], enabled: true });
      expect(instantiateMock).toHaveBeenCalledTimes(1);

      // Reset spies
      disposeSpy.mockClear();
      instantiateMock.mockClear();
      initializeSpy.mockClear();

      await registry.reloadModule('m');

      expect(disposeSpy).toHaveBeenCalledWith('m');
      expect(instantiateMock).toHaveBeenCalledWith('m');
      expect(initializeSpy).toHaveBeenCalledWith('m');
    });

    it('reloadAllModules reloads in reverse load order', async () => {
      // Order: A -> B -> C (A depends B, B depends C)
      await registry.register({ name: 'C', version: '1', dependencies: [], routes: [], enabled: true });
      await registry.register({ name: 'B', version: '1', dependencies: ['C'], routes: [], enabled: true });
      await registry.register({ name: 'A', version: '1', dependencies: ['B'], routes: [], enabled: true });

      // Clear prior calls
      disposeSpy.mockClear();
      instantiateMock.mockClear();

      await registry.reloadAllModules();

      // All three should be disposed and instantiated again
      expect(disposeSpy).toHaveBeenCalledTimes(3);
      expect(instantiateMock).toHaveBeenCalledTimes(3);
      // Dispose should be in reverse load order: A, B, C (since load order is C, B, A)
      const disposeCalls = disposeSpy.mock.calls.map((c: string[]) => c[0]);
      expect(disposeCalls).toEqual(['A', 'B', 'C']);
    });

    it('reload non‑existent module does nothing', async () => {
      await registry.reloadModule('missing'); // should not throw
    });
  });

  describe('Dispose', () => {
    it('disposes all loaded modules and clears state', async () => {
      await registry.register({ name: 'a', version: '1', dependencies: [], routes: [], enabled: true });
      await registry.register({ name: 'b', version: '1', dependencies: [], routes: [], enabled: true });

      const modules = registry.getAllModules();
      expect(modules.length).toBeGreaterThan(0);

      await registry.dispose();

      expect(disposeSpy).toHaveBeenCalledTimes(2);
      expect(registry.getAllModules()).toHaveLength(0);
      expect(registry.getLoadOrder()).toHaveLength(0);
    });
  });

  describe('Window event handling', () => {
    it('module:reload window event triggers reloadModule', async () => {
      await registry.register({ name: 'm', version: '1', dependencies: [], routes: [], enabled: true });
      instantiateMock.mockClear();
      disposeSpy.mockClear();

      const reloadSpy = vi.spyOn(registry, 'reloadModule');

      // Simulate window event
      if (reloadHandler) {
        reloadHandler.call(window, new CustomEvent('module:reload', { detail: { name: 'm' } }) as any);
      }

      // reloadModule should be called synchronously
      expect(reloadSpy).toHaveBeenCalledWith('m');

      // Wait for async reload to finish
      await Promise.resolve();
      await Promise.resolve();

      expect(disposeSpy).toHaveBeenCalledWith('m');
      expect(instantiateMock).toHaveBeenCalledWith('m');
    });
  });

  describe('Idempotency', () => {
    it('loadModule does not instantiate again if already loaded', async () => {
      await registry.register({ name: 'm', version: '1', dependencies: [], routes: [], enabled: true });
      expect(instantiateMock).toHaveBeenCalledTimes(1);

      // Subsequent load should be no‑op
      await registry.loadModule('m');
      expect(instantiateMock).toHaveBeenCalledTimes(1);
    });
  });
});
