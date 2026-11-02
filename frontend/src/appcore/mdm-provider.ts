export interface ReferenceDataItem {
  id: string;
  code: string;
  name: string;
  description?: string;
  parentId?: string;
  sortOrder: number;
  isActive: boolean;
  metadata?: Record<string, any>;
  createdAt: Date;
  updatedAt: Date;
}

export interface ReferenceDataSet {
  name: string;
  version: string;
  items: ReferenceDataItem[];
  lastSyncAt: Date;
  expiresAt?: Date;
}

export interface ValidationRule {
  id: string;
  fieldName: string;
  ruleType: 'required' | 'minLength' | 'maxLength' | 'pattern' | 'enum' | 'range';
  parameters: Record<string, any>;
  errorMessage: string;
  isActive: boolean;
}

export interface DataValidationSchema {
  entityType: string;
  version: string;
  rules: ValidationRule[];
}

export interface MDMConfig {
  apiUrl: string;
  cacheEnabled: boolean;
  cacheExpiry: number;
  syncInterval: number;
  enableRealTime: boolean;
  retryAttempts: number;
  retryDelay: number;
}

class MDMProvider {
  private config: MDMConfig;
  private cache: Map<string, { data: any; expiresAt: Date }> = new Map();
  private referenceData: Map<string, ReferenceDataSet> = new Map();
  private validationSchemas: Map<string, DataValidationSchema> = new Map();
  public syncInterval: number = 0;
  private isOnline: boolean = navigator.onLine;
  private eventListeners: Map<string, Set<Function>> = new Map();

  constructor(config: MDMConfig) {
    this.config = config;
    this.setupNetworkListeners();

    this.loadFromCache();
  }

  async getReferenceData(datasetName: string, forceRefresh = false): Promise<ReferenceDataSet> {
    const cacheKey = `refdata:${datasetName}`;

    if (!forceRefresh && this.config.cacheEnabled) {
      const cached = this.getFromCache(cacheKey);
      if (cached) return cached;
    }

    try {
      const response = await this.makeAuthenticatedRequest(`/mdm/reference-data/${datasetName}`);
      const dataset: ReferenceDataSet = {
        name: response.name,
        version: response.version,
        items: response.items,
        lastSyncAt: new Date(response.lastSyncAt),
        expiresAt: response.expiresAt ? new Date(response.expiresAt) : undefined
      };

      this.referenceData.set(datasetName, dataset);

      if (this.config.cacheEnabled) {
        const expiresAt = new Date(Date.now() + this.config.cacheExpiry);
        this.setCache(cacheKey, dataset, expiresAt);
      }

      this.emitEvent('mdm:reference-data-loaded', { datasetName, dataset });
      return dataset;

    } catch (error) {
      const cached = this.getFromCache(cacheKey);
      if (cached) {
        console.warn(`Using cached reference data for ${datasetName} due to error:`, error);
        return cached;
      }
      throw error;
    }
  }

  async getReferenceDataItem(datasetName: string, itemId: string): Promise<ReferenceDataItem | null> {
    const dataset = await this.getReferenceData(datasetName);
    return dataset.items.find(item => item.id === itemId) || null;
  }

  async getReferenceDataByCode(datasetName: string, code: string): Promise<ReferenceDataItem | null> {
    const dataset = await this.getReferenceData(datasetName);
    return dataset.items.find(item => item.code === code) || null;
  }

  async getReferenceDataChildren(datasetName: string, parentId: string): Promise<ReferenceDataItem[]> {
    const dataset = await this.getReferenceData(datasetName);
    return dataset.items
      .filter(item => item.parentId === parentId)
      .sort((a, b) => a.sortOrder - b.sortOrder);
  }

  async getReferenceDataTree(datasetName: string): Promise<ReferenceDataItem[]> {
    const dataset = await this.getReferenceData(datasetName);
    const items = dataset.items.filter(item => item.isActive);
    const itemMap = new Map(items.map(item => [item.id, { ...item, children: [] as ReferenceDataItem[] }]));
    const rootItems: ReferenceDataItem[] = [];

    items.forEach(item => {
      const node = itemMap.get(item.id)!;
      if (item.parentId && itemMap.has(item.parentId)) {
        itemMap.get(item.parentId)!.children.push(node);
      } else {
        rootItems.push(node);
      }
    });

    return rootItems;
  }

  async getValidationSchema(entityType: string, version?: string): Promise<DataValidationSchema> {
    const cacheKey = `validation:${entityType}:${version || 'latest'}`;

    if (this.config.cacheEnabled) {
      const cached = this.getFromCache(cacheKey);
      if (cached) return cached;
    }

    try {
      const response = await this.makeAuthenticatedRequest(
        `/mdm/validation-schema/${entityType}${version ? `?version=${version}` : ''}`
      );

      const schema: DataValidationSchema = {
        entityType: response.entityType,
        version: response.version,
        rules: response.rules
      };

      this.validationSchemas.set(cacheKey, schema);

      if (this.config.cacheEnabled) {
        const expiresAt = new Date(Date.now() + this.config.cacheExpiry);
        this.setCache(cacheKey, schema, expiresAt);
      }

      this.emitEvent('mdm:validation-schema-loaded', { entityType, schema });
      return schema;

    } catch (error) {
      const cached = this.getFromCache(cacheKey);
      if (cached) {
        console.warn(`Using cached validation schema for ${entityType} due to error:`, error);
        return cached;
      }
      throw error;
    }
  }

  async validateData(entityType: string, data: Record<string, any>): Promise<{
    isValid: boolean;
    errors: Array<{ field: string; rule: string; message: string }>;
  }> {
    const schema = await this.getValidationSchema(entityType);
    const errors: Array<{ field: string; rule: string; message: string }> = [];

    for (const rule of schema.rules) {
      if (!rule.isActive) continue;

      const fieldValue = data[rule.fieldName];
      const isValid = this.validateField(rule, fieldValue);

      if (!isValid) {
        errors.push({
          field: rule.fieldName,
          rule: rule.ruleType,
          message: rule.errorMessage
        });
      }
    }

    return {
      isValid: errors.length === 0,
      errors
    };
  }

  private validateField(rule: ValidationRule, value: any): boolean {
    switch (rule.ruleType) {
      case 'required':
        return value !== null && value !== undefined && value !== '';

      case 'minLength':
        return typeof value === 'string' && value.length >= rule.parameters.minLength;

      case 'maxLength':
        return typeof value === 'string' && value.length <= rule.parameters.maxLength;

      case 'pattern':
        return typeof value === 'string' && new RegExp(rule.parameters.pattern).test(value);

      case 'enum':
        return rule.parameters.allowedValues.includes(value);

      case 'range':
        return typeof value === 'number' && 
               value >= rule.parameters.min && 
               value <= rule.parameters.max;

      default:
        return true;
    }
  }

  async syncReferenceData(datasetNames?: string[]): Promise<void> {
    const datasetsToSync = datasetNames || Array.from(this.referenceData.keys());

    for (const datasetName of datasetsToSync) {
      try {
        await this.getReferenceData(datasetName, true);
      } catch (error) {
        console.error(`Failed to sync reference data ${datasetName}:`, error);
      }
    }

    this.emitEvent('mdm:sync-completed', { datasets: datasetsToSync });
  }

  async invalidateCache(pattern?: string): Promise<void> {
    if (pattern) {
      const regex = new RegExp(pattern);
      for (const [key] of this.cache) {
        if (regex.test(key)) {
          this.cache.delete(key);
        }
      }
    } else {
      this.cache.clear();
    }

    this.saveToCache();
    this.emitEvent('mdm:cache-invalidated', { pattern });
  }

  getCacheStats(): {
    size: number;
    keys: string[];
    expiredCount: number;
  } {
    const now = new Date();
    const expiredKeys: string[] = [];

    for (const [key, entry] of this.cache) {
      if (entry.expiresAt < now) {
        expiredKeys.push(key);
      }
    }

    return {
      size: this.cache.size,
      keys: Array.from(this.cache.keys()),
      expiredCount: expiredKeys.length
    };
  }

  private getFromCache(key: string): any | null {
    const entry = this.cache.get(key);
    if (!entry) return null;

    if (entry.expiresAt < new Date()) {
      this.cache.delete(key);
      return null;
    }

    return entry.data;
  }

  private setCache(key: string, data: any, expiresAt: Date): void {
    this.cache.set(key, { data, expiresAt });
    this.saveToCache();
  }

  private loadFromCache(): void {
    if (!this.config.cacheEnabled || typeof localStorage === 'undefined') return;

    try {
      const cached = localStorage.getItem('mdm_cache');
      if (!cached) return;

      const data = JSON.parse(cached);
      const now = new Date();

      for (const [key, entry] of Object.entries(data) as any) {
        if (new Date(entry.expiresAt) > now) {
          this.cache.set(key, entry);
        }
      }
    } catch (error) {
      console.warn('Failed to load MDM cache:', error);
    }
  }

  private saveToCache(): void {
    if (!this.config.cacheEnabled || typeof localStorage === 'undefined') return;

    try {
      const data = Object.fromEntries(this.cache);
      localStorage.setItem('mdm_cache', JSON.stringify(data));
    } catch (error) {
      console.warn('Failed to save MDM cache:', error);
    }
  }

  private setupNetworkListeners(): void {
    window.addEventListener('online', () => {
      this.isOnline = true;
      this.emitEvent('mdm:online', {});
    });

    window.addEventListener('offline', () => {
      this.isOnline = false;
      this.emitEvent('mdm:offline', {});
    });
  }

  private async makeAuthenticatedRequest(endpoint: string, options: RequestInit = {}): Promise<any> {
    const token = this.getAuthToken();
    if (!token) {
      throw new Error('No authentication token available');
    }

    let attempts = 0;
    const maxAttempts = this.config.retryAttempts;

    while (attempts < maxAttempts) {
      try {
        const response = await fetch(`${this.config.apiUrl}${endpoint}`, {
          ...options,
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...options.headers
          }
        });

        if (!response.ok) {
          const error = await response.json().catch(() => ({ message: 'Network error' }));
          throw new Error(error.message || `HTTP ${response.status}`);
        }

        return response.json();

      } catch (error) {
        attempts++;
        if (attempts >= maxAttempts) throw error;
        
        if (this.config.retryDelay > 0) {
          await new Promise(resolve => setTimeout(resolve, this.config.retryDelay * 1000));
        } else {
          await Promise.resolve();
        }
      }
    }

    throw new Error('Max retry attempts exceeded');
  }

  private getAuthToken(): string | null {
    return localStorage.getItem('iam_auth_token');
  }

  private emitEvent(eventType: string, data: any): void {
    const event = new CustomEvent(eventType, { detail: data });
    window.dispatchEvent(event);

    const listeners = this.eventListeners.get(eventType);
    if (listeners) {
      listeners.forEach(listener => {
        try {
          listener(data);
        } catch (error) {
          console.error(`Error in MDM event listener for ${eventType}:`, error);
        }
      });
    }
  }

  addEventListener(eventType: string, listener: Function): () => void {
    if (!this.eventListeners.has(eventType)) {
      this.eventListeners.set(eventType, new Set());
    }

    this.eventListeners.get(eventType)!.add(listener);

    return () => {
      const listeners = this.eventListeners.get(eventType);
      if (listeners) {
        listeners.delete(listener);
        if (listeners.size === 0) {
          this.eventListeners.delete(eventType);
        }
      }
    };
  }

  removeEventListener(eventType: string, listener: Function): void {
    const listeners = this.eventListeners.get(eventType);
    if (listeners) {
      listeners.delete(listener);
    }
  }

  dispose(): void {
    if (this.syncInterval !== 0) {
      clearInterval(this.syncInterval);
      this.syncInterval = 0;
    }

    this.cache.clear();
    this.referenceData.clear();
    this.validationSchemas.clear();
    this.eventListeners.clear();

    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('mdm_cache');
    }
  }

  startAutoSync(): void {
    if (this.syncInterval !== 0) return;
    if (this.config.syncInterval <= 0) return;
    this.syncInterval = window.setInterval(() => {
      if (this.isOnline) {
        this.syncReferenceData().catch(console.error);
      }
    }, this.config.syncInterval * 1000);
  }

  stopAutoSync(): void {
    if (this.syncInterval !== 0) {
      clearInterval(this.syncInterval);
      this.syncInterval = 0;
    }
  }

  clearCache(): void {
    this.cache.clear();
    this.referenceData.clear();
    this.validationSchemas.clear();
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('mdm_cache');
    }
  }
}

export const createMDMProvider = (config: MDMConfig): MDMProvider => {
  return new MDMProvider(config);
};

export type { MDMProvider };