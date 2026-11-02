interface ComponentErrorInfo { componentStack?: string }

export interface ErrorInfo {
  error: Error;
  errorInfo: ComponentErrorInfo;
  context: Record<string, any>;
  timestamp: Date;
  userAgent: string;
  url: string;
  userId?: string;
  sessionId: string;
}

export interface ErrorBoundaryConfig {
  enableLogging: boolean;
  enableTelemetry: boolean;
  maxRetries: number;
  retryDelay: number;
  fallbackComponent?: any;
  onError?: (error: ErrorInfo) => void;
  onRecovery?: () => void;
  logEndpoint?: string;
  telemetryEndpoint?: string;
}

export interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
  errorInfo: ComponentErrorInfo | null;
  retryCount: number;
  isRetrying: boolean;
}

class ErrorBoundaryManager {
  private static instance: ErrorBoundaryManager;
  private config: ErrorBoundaryConfig;
  private errorHistory: ErrorInfo[] = [];
  private sessionId: string;
  private recoveryStrategies: Map<string, () => Promise<void>> = new Map();

  constructor(config: ErrorBoundaryConfig) {
    this.config = config;
    this.sessionId = this.generateSessionId();
    this.setupGlobalErrorHandlers();
    this.setupPerformanceMonitoring();
  }

  static getInstance(config?: ErrorBoundaryConfig): ErrorBoundaryManager {
    if (!ErrorBoundaryManager.instance) {
      if (!config) {
        throw new Error('ErrorBoundaryManager requires config on first instantiation');
      }
      ErrorBoundaryManager.instance = new ErrorBoundaryManager(config);
    }
    return ErrorBoundaryManager.instance;
  }

  captureError(error: Error, errorInfo?: ComponentErrorInfo, context?: Record<string, any>): ErrorInfo {
    const errorInfoData: ErrorInfo = {
      error,
      errorInfo: errorInfo || { componentStack: '' },
      context: context || {},
      timestamp: new Date(),
      userAgent: navigator.userAgent,
      url: window.location.href,
      userId: this.getUserId(),
      sessionId: this.sessionId
    };

    this.errorHistory.push(errorInfoData);
    this.processError(errorInfoData);

    return errorInfoData;
  }

  async handleError(errorInfo: ErrorInfo, retryCallback?: () => void): Promise<void> {
    if (this.config.enableLogging) {
      await this.logError(errorInfo);
    }

    if (this.config.enableTelemetry) {
      await this.sendTelemetry(errorInfo);
    }

    if (this.config.onError) {
      this.config.onError(errorInfo);
    }

    if (retryCallback && this.canRetry(errorInfo)) {
      await this.performRetry(retryCallback, errorInfo);
    }
  }

  canRetry(errorInfo: ErrorInfo): boolean {
    const retryCount = this.getRetryCountForError(errorInfo);
    return retryCount < this.config.maxRetries && this.isRetryableError(errorInfo.error);
  }

  private isRetryableError(error: Error): boolean {
    const retryablePatterns = [
      /network/i,
      /timeout/i,
      /connection/i,
      /fetch/i,
      /cors/i,
      /502/,
      /503/,
      /504/
    ];

    return retryablePatterns.some(pattern => pattern.test(error.message));
  }

  private async performRetry(retryCallback: () => void, errorInfo: ErrorInfo): Promise<void> {
    const retryKey = this.getRetryKey(errorInfo);
    const currentRetryCount = this.getRetryCountForError(errorInfo);
    
    if (currentRetryCount >= this.config.maxRetries) {
      return;
    }

    this.setRetryCount(retryKey, currentRetryCount + 1);

    await new Promise(resolve => setTimeout(resolve, this.config.retryDelay));

    try {
      await retryCallback();
      this.clearRetryCount(retryKey);
      
      if (this.config.onRecovery) {
        this.config.onRecovery();
      }
    } catch (retryError) {
      console.warn('Retry failed:', retryError);
    }
  }

  private getRetryKey(errorInfo: ErrorInfo): string {
    const errorType = errorInfo.error.constructor.name;
    const componentName = errorInfo.errorInfo.componentStack.split('\n')[0] || 'Unknown';
    return `${errorType}:${componentName}`;
  }

  private getRetryCountForError(errorInfo: ErrorInfo): number {
    const retryKey = this.getRetryKey(errorInfo);
    const retryData = sessionStorage.getItem(`error_retry_${retryKey}`);
    return retryData ? parseInt(retryData, 10) : 0;
  }

  private setRetryCount(retryKey: string, count: number): void {
    sessionStorage.setItem(`error_retry_${retryKey}`, count.toString());
  }

  private clearRetryCount(retryKey: string): void {
    sessionStorage.removeItem(`error_retry_${retryKey}`);
  }

  async logError(errorInfo: ErrorInfo): Promise<void> {
    if (!this.config.logEndpoint) return;

    try {
      await fetch(this.config.logEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          level: 'error',
          message: errorInfo.error.message,
          stack: errorInfo.error.stack,
          componentStack: errorInfo.errorInfo.componentStack,
          context: errorInfo.context,
          timestamp: errorInfo.timestamp.toISOString(),
          userAgent: errorInfo.userAgent,
          url: errorInfo.url,
          userId: errorInfo.userId,
          sessionId: errorInfo.sessionId
        })
      });
    } catch (loggingError) {
      console.error('Failed to log error:', loggingError);
    }
  }

  async sendTelemetry(errorInfo: ErrorInfo): Promise<void> {
    if (!this.config.telemetryEndpoint) return;

    try {
      await fetch(this.config.telemetryEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          eventType: 'error',
          errorType: errorInfo.error.constructor.name,
          errorMessage: errorInfo.error.message,
          context: errorInfo.context,
          timestamp: errorInfo.timestamp.toISOString(),
          userAgent: errorInfo.userAgent,
          url: errorInfo.url,
          userId: errorInfo.userId,
          sessionId: errorInfo.sessionId
        })
      });
    } catch (telemetryError) {
      console.error('Failed to send telemetry:', telemetryError);
    }
  }

  private processError(errorInfo: ErrorInfo): void {
    this.handleError(errorInfo);
    this.cleanupOldErrors();
  }

  private cleanupOldErrors(): void {
    const maxAge = 24 * 60 * 60 * 1000;
    const now = Date.now();
    
    this.errorHistory = this.errorHistory.filter(
      error => now - error.timestamp.getTime() < maxAge
    );
  }

  registerRecoveryStrategy(errorType: string, strategy: () => Promise<void>): void {
    this.recoveryStrategies.set(errorType, strategy);
  }

  async attemptRecovery(errorInfo: ErrorInfo): Promise<boolean> {
    const errorType = errorInfo.error.constructor.name;
    const strategy = this.recoveryStrategies.get(errorType);

    if (!strategy) return false;

    try {
      await strategy();
      return true;
    } catch (recoveryError) {
      console.error(`Recovery strategy failed for ${errorType}:`, recoveryError);
      return false;
    }
  }

  getErrorHistory(limit?: number): ErrorInfo[] {
    const sorted = [...this.errorHistory].sort(
      (a, b) => b.timestamp.getTime() - a.timestamp.getTime()
    );
    
    return limit ? sorted.slice(0, limit) : sorted;
  }

  getErrorStats(): {
    total: number;
    last24Hours: number;
    byType: Record<string, number>;
    byComponent: Record<string, number>;
  } {
    const now = Date.now();
    const last24Hours = now - (24 * 60 * 60 * 1000);
    
    const recentErrors = this.errorHistory.filter(
      error => error.timestamp.getTime() > last24Hours
    );

    const byType: Record<string, number> = {};
    const byComponent: Record<string, number> = {};

    this.errorHistory.forEach(error => {
      const errorType = error.error.constructor.name;
      const component = error.errorInfo.componentStack.split('\n')[0] || 'Unknown';

      byType[errorType] = (byType[errorType] || 0) + 1;
      byComponent[component] = (byComponent[component] || 0) + 1;
    });

    return {
      total: this.errorHistory.length,
      last24Hours: recentErrors.length,
      byType,
      byComponent
    };
  }

  clearErrorHistory(): void {
    this.errorHistory = [];
    this.recoveryStrategies.clear();
    
    for (let i = sessionStorage.length - 1; i >= 0; i--) {
      const key = sessionStorage.key(i);
      if (key && key.startsWith('error_retry_')) {
        sessionStorage.removeItem(key);
      }
    }
  }

  private getUserId(): string | undefined {
    return localStorage.getItem('user_id') || undefined;
  }

  private generateSessionId(): string {
    return `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private setupGlobalErrorHandlers(): void {
    window.addEventListener('error', (event) => {
      this.captureError(event.error || new Error(event.message), {
        componentStack: 'Global Error Handler'
      }, {
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno
      });
    });

    window.addEventListener('unhandledrejection', (event) => {
      const error = event.reason instanceof Error 
        ? event.reason 
        : new Error(`Unhandled promise rejection: ${event.reason}`);

      this.captureError(error, {
        componentStack: 'Unhandled Promise Rejection'
      }, {
        promise: event.promise
      });
    });
  }

  private setupPerformanceMonitoring(): void {
    if ('PerformanceObserver' in window) {
      const observer = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          if (entry.entryType === 'error') {
            const errorEntry = entry as any;
            this.captureError(new Error(errorEntry.message), {
              componentStack: 'Performance Error'
            }, {
              performanceEntry: errorEntry
            });
          }
        }
      });

      observer.observe({ entryTypes: ['error'] });
    }
  }

  dispose(): void {
    window.removeEventListener('error', this.handleGlobalError);
    window.removeEventListener('unhandledrejection', this.handleUnhandledRejection);
    this.clearErrorHistory();
  }

  private handleGlobalError = (event: ErrorEvent): void => {
    this.captureError(event.error || new Error(event.message), {
      componentStack: 'Global Error Handler'
    }, {
      filename: event.filename,
      lineno: event.lineno,
      colno: event.colno
    });
  };

  private handleUnhandledRejection = (event: PromiseRejectionEvent): void => {
    const error = event.reason instanceof Error 
      ? event.reason 
      : new Error(`Unhandled promise rejection: ${event.reason}`);

    this.captureError(error, {
      componentStack: 'Unhandled Promise Rejection'
    }, {
      promise: event.promise
    });
  };
}

export const createErrorBoundaryManager = (config: ErrorBoundaryConfig): ErrorBoundaryManager => {
  return new ErrorBoundaryManager(config);
};

export { ErrorBoundaryManager };

export type { ErrorBoundaryManager };