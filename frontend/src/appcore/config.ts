export interface APIEndpoints {
  iam: string;
  mdm: string;
  events: string;
  billing: string;
  orders: string;
  users: string;
  notifications: string;
  files: string;
  search: string;
  analytics: string;
}

export interface FeatureFlags {
  enableBetaFeatures: boolean;
  enableRealTimeUpdates: boolean;
  enableAdvancedAnalytics: boolean;
  enableFileSharing: boolean;
  enableVideoConferencing: boolean;
  enableAIAssistant: boolean;
  enableDarkMode: boolean;
  enableNotifications: boolean;
  enableOfflineMode: boolean;
}

export interface EnvironmentConfig {
  name: 'development' | 'staging' | 'production';
  apiUrl: string;
  wsUrl: string;
  cdnUrl: string;
  apiEndpoints: APIEndpoints;
  featureFlags: FeatureFlags;
  logging: {
    enabled: boolean;
    level: 'debug' | 'info' | 'warn' | 'error';
    remoteEndpoint?: string;
  };
  performance: {
    enableMetrics: boolean;
    enableProfiling: boolean;
    sampleRate: number;
  };
  security: {
    enableCSP: boolean;
    enableHSTS: boolean;
    tokenRefreshThreshold: number;
    sessionTimeout: number;
  };
  ui: {
    theme: 'light' | 'dark' | 'auto';
    language: string;
    timezone: string;
    dateFormat: string;
    numberFormat: string;
  };
  cache: {
    enableServiceWorker: boolean;
    cacheStrategy: 'networkFirst' | 'cacheFirst' | 'staleWhileRevalidate';
    maxAge: number;
  };
}

export class ConfigManager {
  private config: EnvironmentConfig;
  private schema: any;
  private watchers: Map<string, Set<(newValue: any, oldValue: any) => void>> = new Map();
  private overrides: Map<string, any> = new Map();

  constructor(initialConfig: Partial<EnvironmentConfig>) {
    this.schema = this.buildConfigSchema();
    this.config = this.validateAndMerge(this.getDefaultConfig(), initialConfig);
    this.setupEnvironmentDetection();
    this.setupFeatureFlagOverrides();
    this.persistToStorage();
  }

  get<T = any>(key: string): T {
    const keys = key.split('.');
    let value: any = this.config;

    for (const k of keys) {
      if (value && typeof value === 'object' && k in value) {
        value = value[k];
      } else {
        value = undefined;
        break;
      }
    }

    return this.overrides.has(key) ? this.overrides.get(key) : value;
  }

  set(key: string, value: any): void {
    const schemaField = this.getFieldSchema(key);
    if (!schemaField) {
      throw new Error(`Unknown configuration key: ${key}`);
    }

    if (schemaField.validator && !schemaField.validator(value)) {
      throw new Error(`Invalid value for ${key}: ${value}`);
    }

    const oldValue = this.get(key);
    
    if (schemaField.type === 'object' && typeof value === 'object') {
      this.setNestedValue(key, value);
    } else {
      this.overrides.set(key, value);
    }

    this.notifyWatchers(key, value, oldValue);
    this.persistToStorage();
  }

  watch(key: string, callback: (newValue: any, oldValue: any) => void): () => void {
    if (!this.watchers.has(key)) {
      this.watchers.set(key, new Set());
    }

    this.watchers.get(key)!.add(callback);

    return () => {
      const watchers = this.watchers.get(key);
      if (watchers) {
        watchers.delete(callback);
        if (watchers.size === 0) {
          this.watchers.delete(key);
        }
      }
    };
  }

  getAll(): EnvironmentConfig {
    const result = { ...this.config };

    for (const [key, value] of this.overrides) {
      this.setNestedValue(key, value, result);
    }

    return result;
  }

  reset(key?: string): void {
    if (key) {
      this.overrides.delete(key);
      const defaultValue = this.getDefaultValue(key);
      if (defaultValue !== undefined) {
        this.setNestedValue(key, defaultValue);
      }
    } else {
      this.overrides.clear();
      this.config = this.getDefaultConfig();
    }

    this.persistToStorage();
  }

  validate(config: Partial<EnvironmentConfig>): { isValid: boolean; errors: string[] } {
    const errors: string[] = [];

    for (const [key, schema] of Object.entries<any>(this.schema)) {
      const value = this.getNestedValue(config, key);
      
      if (schema.required && (value === undefined || value === null)) {
        errors.push(`Required field ${key} is missing`);
        continue;
      }

      if (value !== undefined && schema.validator && !schema.validator(value)) {
        errors.push(`Invalid value for field ${key}`);
      }
    }

    return { isValid: errors.length === 0, errors };
  }

  getFeatureFlags(): FeatureFlags {
    return this.get<FeatureFlags>('featureFlags');
  }

  isFeatureEnabled(feature: keyof FeatureFlags): boolean {
    const flags = this.getFeatureFlags();
    
    const urlParams = new URLSearchParams(window.location.search);
    const overrideValue = urlParams.get(`feature_${feature}`);
    
    if (overrideValue !== null) {
      return overrideValue === 'true' || overrideValue === '1';
    }

    return flags[feature];
  }

  getAPIEndpoints(): APIEndpoints {
    const baseUrl = this.get<string>('apiUrl');
    const endpoints = this.get<APIEndpoints>('apiEndpoints');

    return {
      iam: `${baseUrl}${endpoints.iam}`,
      mdm: `${baseUrl}${endpoints.mdm}`,
      events: `${baseUrl}${endpoints.events}`,
      billing: `${baseUrl}${endpoints.billing}`,
      orders: `${baseUrl}${endpoints.orders}`,
      users: `${baseUrl}${endpoints.users}`,
      notifications: `${baseUrl}${endpoints.notifications}`,
      files: `${baseUrl}${endpoints.files}`,
      search: `${baseUrl}${endpoints.search}`,
      analytics: `${baseUrl}${endpoints.analytics}`
    };
  }

  isDevelopment(): boolean {
    return this.get<string>('name') === 'development';
  }

  isProduction(): boolean {
    return this.get<string>('name') === 'production';
  }

  private getDefaultConfig(): EnvironmentConfig {
    return {
      name: 'development',
      apiUrl: 'http://localhost:5173/api',
      wsUrl: 'ws://localhost:5173/ws',
      cdnUrl: 'http://localhost:5173',
      apiEndpoints: {
        iam: '/iam',
        mdm: '/mdm',
        events: '/events',
        billing: '/billing',
        orders: '/orders',
        users: '/users',
        notifications: '/notifications',
        files: '/files',
        search: '/search',
        analytics: '/analytics'
      },
      featureFlags: {
        enableBetaFeatures: false,
        enableRealTimeUpdates: true,
        enableAdvancedAnalytics: false,
        enableFileSharing: true,
        enableVideoConferencing: true,
        enableAIAssistant: false,
        enableDarkMode: true,
        enableNotifications: true,
        enableOfflineMode: false
      },
      logging: {
        enabled: true,
        level: 'info',
        remoteEndpoint: undefined
      },
      performance: {
        enableMetrics: false,
        enableProfiling: false,
        sampleRate: 0.1
      },
      security: {
        enableCSP: false,
        enableHSTS: false,
        tokenRefreshThreshold: 300,
        sessionTimeout: 3600
      },
      ui: {
        theme: 'auto',
        language: 'en',
        timezone: 'UTC',
        dateFormat: 'YYYY-MM-DD',
        numberFormat: 'en-US'
      },
      cache: {
        enableServiceWorker: true,
        cacheStrategy: 'networkFirst',
        maxAge: 86400
      }
    };
  }

  private buildConfigSchema(): any {
    return {
      'name': {
        type: 'string',
        required: true,
        validator: (value) => ['development', 'staging', 'production'].includes(value),
        description: 'Environment name'
      },
      'apiUrl': {
        type: 'string',
        required: true,
        validator: (value) => typeof value === 'string' && value.length > 0,
        description: 'Base API URL'
      },
      'featureFlags.enableBetaFeatures': {
        type: 'boolean',
        required: true,
        default: false,
        description: 'Enable beta features'
      },
      'logging.enabled': {
        type: 'boolean',
        required: true,
        default: true,
        description: 'Enable logging'
      },
      'security.tokenRefreshThreshold': {
        type: 'number',
        required: true,
        validator: (value) => typeof value === 'number' && value > 0,
        default: 300,
        description: 'Token refresh threshold in seconds'
      }
    };
  }

  private getFieldSchema(key: string): any {
    return this.schema[key];
  }

  private getDefaultValue(key: string): any {
    const schema = this.getFieldSchema(key);
    return schema?.default;
  }

  private getNestedValue(obj: any, key: string): any {
    const keys = key.split('.');
    let value = obj;

    for (const k of keys) {
      if (value && typeof value === 'object' && k in value) {
        value = value[k];
      } else {
        return undefined;
      }
    }

    return value;
  }

  private setNestedValue(key: string, value: any, target?: any): void {
    const keys = key.split('.');
    const obj = target || this.config;

    let current = obj;
    for (let i = 0; i < keys.length - 1; i++) {
      const k = keys[i];
      if (!(k in current) || typeof current[k] !== 'object') {
        current[k] = {};
      }
      current = current[k];
    }

    current[keys[keys.length - 1]] = value;
  }

  private validateAndMerge(defaultConfig: EnvironmentConfig, userConfig: Partial<EnvironmentConfig>): EnvironmentConfig {
    const result = { ...defaultConfig };
    const validation = this.validate(userConfig);

    if (!validation.isValid) {
      console.error('Configuration validation errors:', validation.errors);
    }

    for (const [key, value] of Object.entries(userConfig)) {
      if (value !== undefined) {
        this.setNestedValue(key, value, result);
      }
    }

    return result;
  }

  private setupEnvironmentDetection(): void {
    const hostname = window.location.hostname;
    let environment: 'development' | 'staging' | 'production' = 'development';

    if (hostname === 'localhost' || hostname === '127.0.0.1') {
      environment = 'development';
    } else if (hostname.includes('staging') || hostname.includes('test')) {
      environment = 'staging';
    } else {
      environment = 'production';
    }

    this.set('name', environment);
  }

  private setupFeatureFlagOverrides(): void {
    const urlParams = new URLSearchParams(window.location.search);
    
    for (const [key, value] of urlParams) {
      if (key.startsWith('feature_')) {
        const featureName = key.substring(8);
        const isEnabled = value === 'true' || value === '1';
        this.set(`featureFlags.${featureName}`, isEnabled);
      }
    }
  }

  private notifyWatchers(key: string, newValue: any, oldValue: any): void {
    const watchers = this.watchers.get(key);
    if (watchers) {
      watchers.forEach(callback => {
        try {
          callback(newValue, oldValue);
        } catch (error) {
          console.error(`Error in config watcher for ${key}:`, error);
        }
      });
    }
  }

  private persistToStorage(): void {
    if (typeof localStorage === 'undefined') return;

    try {
      const config = this.getAll();
      localStorage.setItem('app_config', JSON.stringify(config));
    } catch (error) {
      console.warn('Failed to persist config to storage:', error);
    }
  }

  loadFromStorage(): void {
    if (typeof localStorage === 'undefined') return;

    try {
      const stored = localStorage.getItem('app_config');
      if (!stored) return;

      const config = JSON.parse(stored);
      this.validateAndMerge(this.config, config);
    } catch (error) {
      console.warn('Failed to load config from storage:', error);
    }
  }
}

export const createConfigManager = (initialConfig?: Partial<EnvironmentConfig>): ConfigManager => {
  const manager = new ConfigManager(initialConfig || {});
  manager.loadFromStorage();
  return manager;
};

// (The class is already exported above; the duplicate type re-export was a
// redeclaration conflict — removed.)