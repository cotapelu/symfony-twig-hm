import type { Route } from './router';

export interface ModuleConfig {
  name: string;
  version: string;
  dependencies: string[];
  routes: Route[];
  state?: Record<string, any>;
  services?: Record<string, any>;
  contracts?: Record<string, any>;
  events?: Record<string, any>;
  enabled: boolean;
}

export interface ModuleRegistration {
  config: ModuleConfig;
  instance: any;
  loadedAt: Date;
  status: 'loading' | 'loaded' | 'error' | 'disabled';
}

export class ModuleRegistry {
  private modules: Map<string, ModuleRegistration> = new Map();
  private dependencyGraph: Map<string, Set<string>> = new Map();
  private loadOrder: string[] = [];
  private loadingPromises: Map<string, Promise<void>> = new Map();

  constructor() {
    this.setupModuleLifecycleEvents();
  }

  async register(config: ModuleConfig): Promise<void> {
    if (this.modules.has(config.name)) {
      throw new Error(`Module ${config.name} is already registered`);
    }

    this.validateDependencies(config);
    this.buildDependencyGraph(config);

    const registration: ModuleRegistration = {
      config,
      instance: null,
      loadedAt: new Date(),
      status: 'loading'
    };

    this.modules.set(config.name, registration);

    try {
      await this.loadModule(config.name);
      this.emitModuleEvent('module:registered', { name: config.name, config });
    } catch (error) {
      registration.status = 'error';
      this.emitModuleEvent('module:error', { name: config.name, error });
      throw error;
    }
  }

  async loadModule(name: string): Promise<void> {
    if (this.loadingPromises.has(name)) {
      return this.loadingPromises.get(name)!;
    }

    const loadPromise = this.performModuleLoad(name);
    this.loadingPromises.set(name, loadPromise);

    try {
      await loadPromise;
    } finally {
      this.loadingPromises.delete(name);
    }
  }

  private async performModuleLoad(name: string): Promise<void> {
    const registration = this.modules.get(name);
    if (!registration) {
      throw new Error(`Module ${name} not found`);
    }

    if (registration.status === 'loaded') {
      return;
    }

    registration.status = 'loading';

    try {
      await this.loadDependencies(name);
      await this.instantiateModule(name);
      await this.initializeModule(name);
      
      registration.status = 'loaded';
      registration.loadedAt = new Date();
      
      this.emitModuleEvent('module:loaded', { name });
    } catch (error) {
      registration.status = 'error';
      throw error;
    }
  }

  private async loadDependencies(name: string): Promise<void> {
    const registration = this.modules.get(name);
    if (!registration) return;

    for (const depName of registration.config.dependencies) {
      if (!this.modules.has(depName)) {
        throw new Error(`Dependency ${depName} not found for module ${name}`);
      }

      await this.loadModule(depName);
    }
  }

  private async instantiateModule(name: string): Promise<void> {
    const registration = this.modules.get(name);
    if (!registration) return;

    try {
      const modulePath = `/src/modules/${name}`;
      const moduleExports = await import(modulePath);
      
      registration.instance = moduleExports.default || moduleExports;
    } catch (error) {
      throw new Error(`Failed to instantiate module ${name}: ${error}`);
    }
  }

  private async initializeModule(name: string): Promise<void> {
    const registration = this.modules.get(name);
    if (!registration || !registration.instance) return;

    if (typeof registration.instance.initialize === 'function') {
      await registration.instance.initialize();
    }

    if (registration.config.routes) {
      await this.registerModuleRoutes(name, registration.config.routes);
    }
  }

  private async registerModuleRoutes(moduleName: string, routes: Route[]): Promise<void> {
    const { router } = await import('./router');
    
    routes.forEach(route => {
      router.register({
        ...route,
        module: moduleName
      });
    });
  }

  unregister(name: string): void {
    const registration = this.modules.get(name);
    if (!registration) return;

    if (registration.status === 'loaded' && registration.instance) {
      if (typeof registration.instance.dispose === 'function') {
        registration.instance.dispose();
      }
    }

    this.modules.delete(name);
    this.dependencyGraph.delete(name);
    this.emitModuleEvent('module:unregistered', { name });
  }

  enable(name: string): void {
    const registration = this.modules.get(name);
    if (!registration) return;

    registration.config.enabled = true;
    
    if (registration.status === 'disabled') {
      this.loadModule(name);
    }
    
    this.emitModuleEvent('module:enabled', { name });
  }

  disable(name: string): void {
    const registration = this.modules.get(name);
    if (!registration) return;

    registration.config.enabled = false;
    registration.status = 'disabled';
    
    this.emitModuleEvent('module:disabled', { name });
  }

  getModule(name: string): ModuleRegistration | undefined {
    return this.modules.get(name);
  }

  getAllModules(): ModuleRegistration[] {
    return Array.from(this.modules.values());
  }

  getEnabledModules(): ModuleRegistration[] {
    return this.getAllModules().filter(m => m.config.enabled);
  }

  getLoadedModules(): ModuleRegistration[] {
    return this.getAllModules().filter(m => m.status === 'loaded');
  }

  isModuleLoaded(name: string): boolean {
    const registration = this.modules.get(name);
    return registration?.status === 'loaded';
  }

  isModuleEnabled(name: string): boolean {
    const registration = this.modules.get(name);
    return registration?.config.enabled ?? false;
  }

  getModuleDependencies(name: string): string[] {
    const registration = this.modules.get(name);
    return registration?.config.dependencies || [];
  }

  getModuleDependents(name: string): string[] {
    const dependents: string[] = [];
    
    for (const [moduleName, registration] of this.modules) {
      if (registration.config.dependencies.includes(name)) {
        dependents.push(moduleName);
      }
    }
    
    return dependents;
  }

  getLoadOrder(): string[] {
    if (this.loadOrder.length === 0) {
      this.calculateLoadOrder();
    }
    return [...this.loadOrder];
  }

  private calculateLoadOrder(): void {
    const visited = new Set<string>();
    const visiting = new Set<string>();
    const order: string[] = [];

    const visit = (name: string): void => {
      if (visited.has(name)) return;
      if (visiting.has(name)) {
        throw new Error(`Circular dependency detected involving module ${name}`);
      }

      visiting.add(name);

      const dependencies = this.getModuleDependencies(name);
      for (const dep of dependencies) {
        visit(dep);
      }

      visiting.delete(name);
      visited.add(name);
      order.push(name);
    };

    for (const name of this.modules.keys()) {
      visit(name);
    }

    this.loadOrder = order;
  }

  private validateDependencies(config: ModuleConfig): void {
    for (const dep of config.dependencies) {
      if (dep === config.name) {
        throw new Error(`Module ${config.name} cannot depend on itself`);
      }
    }
  }

  private buildDependencyGraph(config: ModuleConfig): void {
    if (!this.dependencyGraph.has(config.name)) {
      this.dependencyGraph.set(config.name, new Set());
    }

    for (const dep of config.dependencies) {
      this.dependencyGraph.get(config.name)!.add(dep);
    }
  }

  private setupModuleLifecycleEvents(): void {
    window.addEventListener('module:reload', (event: any) => {
      const { name } = event.detail;
      if (this.modules.has(name)) {
        this.reloadModule(name).catch(console.error);
      }
    });
  }

  private async reloadModule(name: string): Promise<void> {
    const registration = this.modules.get(name);
    if (!registration) return;

    if (registration.status === 'loaded' && registration.instance) {
      if (typeof registration.instance.dispose === 'function') {
        await registration.instance.dispose();
      }
    }

    registration.status = 'loading';
    registration.instance = null;

    try {
      await this.performModuleLoad(name);
      this.emitModuleEvent('module:reloaded', { name });
    } catch (error) {
      registration.status = 'error';
      this.emitModuleEvent('module:error', { name, error });
    }
  }

  private emitModuleEvent(eventType: string, data: any): void {
    const event = new CustomEvent(eventType, { detail: data });
    window.dispatchEvent(event);
  }

  async reloadAllModules(): Promise<void> {
    const loadOrder = this.getLoadOrder();
    
    for (const name of loadOrder.reverse()) {
      await this.reloadModule(name);
    }
  }

  getModuleStats(): {
    total: number;
    loaded: number;
    enabled: number;
    error: number;
    disabled: number;
  } {
    const modules = this.getAllModules();
    
    return {
      total: modules.length,
      loaded: modules.filter(m => m.status === 'loaded').length,
      enabled: modules.filter(m => m.config.enabled).length,
      error: modules.filter(m => m.status === 'error').length,
      disabled: modules.filter(m => m.status === 'disabled').length
    };
  }

  dispose(): void {
    for (const registration of this.modules.values()) {
      if (registration.status === 'loaded' && registration.instance) {
        if (typeof registration.instance.dispose === 'function') {
          registration.instance.dispose();
        }
      }
    }
    
    this.modules.clear();
    this.dependencyGraph.clear();
    this.loadOrder = [];
    this.loadingPromises.clear();
  }
}

export const moduleRegistry = new ModuleRegistry();

export type { ModuleRegistry };