import { createConfigManager } from './config';
import { createIAMAdapter } from './iam-adapter';
import { createMDMProvider } from './mdm-provider';
import { createErrorBoundaryManager } from './error-boundary';
import { eventBus } from './event-bus';
import { router } from './router';

export { router, type Route, type NavigationResult } from './router';
export { 
  moduleRegistry, 
  type ModuleConfig, 
  type ModuleRegistration 
} from './module-registry';
export { 
  eventBus, 
  type EventHandler, 
  type EventSubscription, 
  type Event, 
  type EventBusConfig, 
  type ScopedEventBus 
} from './event-bus';
export { 
  createIAMAdapter, 
  type IAMAdapter, 
  type User, 
  type AuthTokens, 
  type AuthState, 
  type LoginRequest, 
  type RegisterRequest, 
  type ResetPasswordRequest, 
  type ChangePasswordRequest, 
  type IAMConfig 
} from './iam-adapter';
export { 
  createMDMProvider, 
  type MDMProvider, 
  type ReferenceDataItem, 
  type ReferenceDataSet, 
  type ValidationRule, 
  type DataValidationSchema, 
  type MDMConfig 
} from './mdm-provider';
export { 
  createConfigManager, 
  type ConfigManager, 
  type EnvironmentConfig, 
  type APIEndpoints, 
  type FeatureFlags 
} from './config';
export { 
  createErrorBoundaryManager, 
  type ErrorBoundaryManager, 
  type ErrorInfo, 
  type ErrorBoundaryConfig, 
  type ErrorBoundaryState 
} from './error-boundary';

import type { IAMConfig } from './iam-adapter';
import type { MDMConfig } from './mdm-provider';
import type { EnvironmentConfig } from './config';
import type { ErrorBoundaryConfig } from './error-boundary';

export interface AppCoreConfig extends Partial<EnvironmentConfig> {
  iam: IAMConfig;
  mdm: MDMConfig;
  errorBoundary: ErrorBoundaryConfig;
}

class AppCore {
  private static instance: AppCore;
  private initialized: boolean = false;

  constructor(private config: AppCoreConfig) {
    if (AppCore.instance) {
      throw new Error('AppCore is a singleton. Use AppCore.getInstance()');
    }
  }

  static getInstance(config?: AppCoreConfig): AppCore {
    if (!AppCore.instance) {
      if (!config) {
        throw new Error('AppCore requires config on first instantiation');
      }
      AppCore.instance = new AppCore(config);
    }
    return AppCore.instance;
  }

  async initialize(): Promise<void> {
    if (this.initialized) {
      console.warn('AppCore already initialized');
      return;
    }

    try {
      await this.initializeConfig();
      await this.initializeIAM();
      await this.initializeMDM();
      await this.initializeErrorBoundary();
      await this.initializeEventBus();
      await this.initializeRouter();

      this.initialized = true;
      console.log('AppCore initialized successfully');

    } catch (error) {
      console.error('Failed to initialize AppCore:', error);
      throw error;
    }
  }

  private async initializeConfig(): Promise<void> {
    const configManager = createConfigManager(this.config);
    globalThis.appConfig = configManager;
  }

  private async initializeIAM(): Promise<void> {
    const iamAdapter = createIAMAdapter(this.config.iam);
    globalThis.appIAM = iamAdapter;
  }

  private async initializeMDM(): Promise<void> {
    const mdmProvider = createMDMProvider(this.config.mdm);
    globalThis.appMDM = mdmProvider;
  }

  private async initializeErrorBoundary(): Promise<void> {
    const errorBoundary = createErrorBoundaryManager(this.config.errorBoundary);
    globalThis.appErrorBoundary = errorBoundary;
  }

  private async initializeEventBus(): Promise<void> {
    globalThis.appEventBus = eventBus;
  }

  private async initializeRouter(): Promise<void> {
    globalThis.appRouter = router;
  }

  isInitialized(): boolean {
    return this.initialized;
  }

  dispose(): void {
    if (globalThis.appErrorBoundary) {
      globalThis.appErrorBoundary.dispose();
    }
    if (globalThis.appIAM) {
      globalThis.appIAM.dispose();
    }
    if (globalThis.appMDM) {
      globalThis.appMDM.dispose();
    }
    if (globalThis.appRouter) {
      globalThis.appRouter.dispose();
    }
    if (globalThis.appEventBus) {
      globalThis.appEventBus.dispose();
    }

    this.initialized = false;
  }
}

declare global {
  var appConfig: import('./config').ConfigManager;
  var appIAM: import('./iam-adapter').IAMAdapter;
  var appMDM: import('./mdm-provider').MDMProvider;
  var appErrorBoundary: import('./error-boundary').ErrorBoundaryManager;
  var appEventBus: import('./event-bus').EventBus;
  var appRouter: import('./router').Router;
}

export const initializeAppCore = (config: AppCoreConfig): AppCore => {
  const appCore = AppCore.getInstance(config);
  appCore.initialize().catch(console.error);
  return appCore;
};

export type { AppCore };