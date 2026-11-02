export interface EventHandler<T = any> {
  (event: T): void | Promise<void>;
}

export interface EventSubscription {
  id: string;
  eventType: string;
  handler: EventHandler;
  once?: boolean;
  createdAt: Date;
}

export interface Event<T = any> {
  type: string;
  data: T;
  timestamp: Date;
  source?: string;
  correlationId?: string;
  causationId?: string;
}

export interface EventBusConfig {
  maxHistory?: number;
  enablePersistence?: boolean;
  enableMetrics?: boolean;
  debugMode?: boolean;
}

export class EventBus {
  private handlers: Map<string, Set<EventHandler>> = new Map();
  private subscriptions: Map<string, EventSubscription> = new Map();
  private eventHistory: Event[] = [];
  private metrics: {
    totalEvents: number;
    eventsByType: Map<string, number>;
    errors: number;
    avgProcessingTime: number;
  } = {
    totalEvents: 0,
    eventsByType: new Map(),
    errors: 0,
    avgProcessingTime: 0
  };
  
  private config: Required<EventBusConfig> = {
    maxHistory: 1000,
    enablePersistence: false,
    enableMetrics: true,
    debugMode: false
  };

  constructor(config: EventBusConfig = {}) {
    this.config = { ...this.config, ...config };
    this.setupPersistence();
    this.setupDebugMode();
  }

  subscribe<T = any>(
    eventType: string, 
    handler: EventHandler<T>, 
    options: { once?: boolean; id?: string } = {}
  ): string {
    const subscription: EventSubscription = {
      id: options.id || this.generateSubscriptionId(),
      eventType,
      handler,
      once: options.once,
      createdAt: new Date()
    };

    if (!this.handlers.has(eventType)) {
      this.handlers.set(eventType, new Set());
    }

    this.handlers.get(eventType)!.add(handler);
    this.subscriptions.set(subscription.id, subscription);

    if (this.config.debugMode) {
      console.debug(`EventBus: Subscribed to ${eventType}`, { subscription });
    }

    return subscription.id;
  }

  unsubscribe(subscriptionId: string): boolean {
    const subscription = this.subscriptions.get(subscriptionId);
    if (!subscription) return false;

    const handlers = this.handlers.get(subscription.eventType);
    if (handlers) {
      handlers.delete(subscription.handler);
      if (handlers.size === 0) {
        this.handlers.delete(subscription.eventType);
      }
    }

    this.subscriptions.delete(subscriptionId);

    if (this.config.debugMode) {
      console.debug(`EventBus: Unsubscribed from ${subscription.eventType}`, { subscription });
    }

    return true;
  }

  async publish<T = any>(event: Event<T>): Promise<void> {
    const startTime = performance.now();
    
    try {
      const enrichedEvent: Event<T> = {
        ...event,
        timestamp: event.timestamp || new Date(),
        correlationId: event.correlationId || this.generateCorrelationId()
      };

      await this.addToHistory(enrichedEvent);
      await this.executeHandlers(enrichedEvent);
      
      if (this.config.enablePersistence) {
        await this.persistEvent(enrichedEvent);
      }

      this.updateMetrics(enrichedEvent, startTime, null);

      if (this.config.debugMode) {
        console.debug(`EventBus: Published ${enrichedEvent.type}`, enrichedEvent);
      }

    } catch (error) {
      this.updateMetrics(event, startTime, error instanceof Error ? error : new Error(String(error)));
      this.metrics.errors++;
      
      console.error(`EventBus: Error publishing ${event.type}`, error);
      throw error;
    }
  }

  async publishAndForget<T = any>(event: Event<T>): Promise<void> {
    this.publish(event).catch(error => {
      console.error(`EventBus: Unhandled error in publishAndForget`, error);
    });
  }

  async publishBatch<T = any>(events: Event<T>[]): Promise<void> {
    const promises = events.map(event => this.publish(event));
    await Promise.allSettled(promises);
  }

  private async executeHandlers<T = any>(event: Event<T>): Promise<void> {
    const handlers = this.handlers.get(event.type);
    if (!handlers || handlers.size === 0) return;

    const promises = Array.from(handlers).map(async (handler) => {
      try {
        await handler(event);
      } catch (error) {
        console.error(`EventBus: Error in handler for ${event.type}`, error);
        this.metrics.errors++;
      }
    });

    await Promise.allSettled(promises);

    const subscriptionsToRemove: string[] = [];
    for (const [id, subscription] of this.subscriptions) {
      if (subscription.once && subscription.eventType === event.type) {
        subscriptionsToRemove.push(id);
      }
    }

    subscriptionsToRemove.forEach(id => {
      this.unsubscribe(id);
    });
  }

  private async addToHistory<T = any>(event: Event<T>): Promise<void> {
    this.eventHistory.push(event);

    if (this.eventHistory.length > this.config.maxHistory) {
      this.eventHistory = this.eventHistory.slice(-this.config.maxHistory);
    }
  }

  private async persistEvent<T = any>(event: Event<T>): Promise<void> {
    try {
      const storageKey = `eventbus:${event.type}:${event.timestamp.getTime()}`;
      localStorage.setItem(storageKey, JSON.stringify(event));
    } catch (error) {
      console.warn(`EventBus: Failed to persist event`, error);
    }
  }

  private updateMetrics<T = any>(
    event: Event<T>, 
    startTime: number, 
    error: Error | null
  ): void {
    if (!this.config.enableMetrics) return;

    const processingTime = performance.now() - startTime;
    this.metrics.totalEvents++;

    const currentCount = this.metrics.eventsByType.get(event.type) || 0;
    this.metrics.eventsByType.set(event.type, currentCount + 1);

    if (error === null) {
      const totalTime = this.metrics.avgProcessingTime * (this.metrics.totalEvents - 1) + processingTime;
      this.metrics.avgProcessingTime = totalTime / this.metrics.totalEvents;
    }
  }

  getEventHistory(eventType?: string, limit?: number): Event[] {
    let history = this.eventHistory;

    if (eventType) {
      history = history.filter(e => e.type === eventType);
    }

    history.sort((a, b) => b.timestamp.getTime() - a.timestamp.getTime());

    if (limit) {
      history = history.slice(0, limit);
    }

    return history;
  }

  getMetrics(): {
    totalEvents: number;
    eventsByType: Record<string, number>;
    errors: number;
    avgProcessingTime: number;
    activeSubscriptions: number;
  } {
    return {
      ...this.metrics,
      eventsByType: Object.fromEntries(this.metrics.eventsByType),
      activeSubscriptions: this.subscriptions.size
    };
  }

  getSubscriptions(eventType?: string): EventSubscription[] {
    let subscriptions = Array.from(this.subscriptions.values());

    if (eventType) {
      subscriptions = subscriptions.filter(s => s.eventType === eventType);
    }

    return subscriptions.sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime());
  }

  clearHistory(): void {
    this.eventHistory = [];
  }

  resetMetrics(): void {
    this.metrics = {
      totalEvents: 0,
      eventsByType: new Map(),
      errors: 0,
      avgProcessingTime: 0
    };
  }

  private generateSubscriptionId(): string {
    return `sub_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateCorrelationId(): string {
    return `corr_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private setupPersistence(): void {
    if (!this.config.enablePersistence) return;

    window.addEventListener('beforeunload', () => {
      this.clearPersistedEvents();
    });
  }

  private clearPersistedEvents(): void {
    const keys = Object.keys(localStorage);
    keys.forEach(key => {
      if (key.startsWith('eventbus:')) {
        localStorage.removeItem(key);
      }
    });
  }

  private setupDebugMode(): void {
    if (!this.config.debugMode) return;

    window.addEventListener('keydown', (event) => {
      if (event.ctrlKey && event.shiftKey && event.key === 'E') {
        console.group('EventBus Debug Info');
        console.log('Metrics:', this.getMetrics());
        console.log('Subscriptions:', this.getSubscriptions());
        console.log('Recent Events:', this.getEventHistory(undefined, 10));
        console.groupEnd();
      }
    });
  }

  createScopedEventBus(scope: string): ScopedEventBus {
    return new ScopedEventBus(this, scope);
  }

  dispose(): void {
    this.handlers.clear();
    this.subscriptions.clear();
    this.eventHistory = [];
    this.clearPersistedEvents();
  }
}

export class ScopedEventBus {
  constructor(
    private parentBus: EventBus,
    private scope: string
  ) {}

  subscribe<T = any>(
    eventType: string, 
    handler: EventHandler<T>, 
    options: { once?: boolean; id?: string } = {}
  ): string {
    const scopedEventType = `${this.scope}:${eventType}`;
    return this.parentBus.subscribe(scopedEventType, handler, options);
  }

  unsubscribe(subscriptionId: string): boolean {
    return this.parentBus.unsubscribe(subscriptionId);
  }

  async publish<T = any>(event: Event<T>): Promise<void> {
    const scopedEvent: Event<T> = {
      ...event,
      type: `${this.scope}:${event.type}`,
      source: this.scope
    };

    return this.parentBus.publish(scopedEvent);
  }

  async publishAndForget<T = any>(event: Event<T>): Promise<void> {
    return this.parentBus.publishAndForget(event);
  }

  getScope(): string {
    return this.scope;
  }

  getParentBus(): EventBus {
    return this.parentBus;
  }
}

export const eventBus = new EventBus({
  maxHistory: 1000,
  enablePersistence: true,
  enableMetrics: true,
  debugMode: process.env.NODE_ENV === 'development'
});

export type { EventBus, ScopedEventBus };