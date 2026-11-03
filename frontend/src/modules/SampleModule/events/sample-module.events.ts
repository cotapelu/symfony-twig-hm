// sample-module Module Events - Event definitions for cross-module communication
// Replace sample-module with the actual module name during generation

import type { SampleModuleItem } from '../contracts/sample-module.types';

// ============================================
// SampleModule Events
// ============================================
export interface SampleModuleCreatedEvent {
  type: 'sample-module.item.created';
  payload: {
    item: SampleModuleItem;
    timestamp: string;
  };
}

export interface SampleModuleUpdatedEvent {
  type: 'sample-module.item.updated';
  payload: {
    item: SampleModuleItem;
    changes: Partial<SampleModuleItem>;
    timestamp: string;
  };
}

export interface SampleModuleDeletedEvent {
  type: 'sample-module.item.deleted';
  payload: {
    id: string;
    timestamp: string;
  };
}

export interface SampleModuleStatusChangedEvent {
  type: 'sample-module.item.status_changed';
  payload: {
    id: string;
    oldStatus: boolean;
    newStatus: boolean;
    timestamp: string;
  };
}

// ============================================
// Export all event types
// ============================================
export interface SampleModuleSearchedEvent {
  type: 'sample-module.item.search';
  payload: {
    query: string;
    resultsCount: number;
    timestamp: string;
  };
}

export type SampleModuleEvent =
  | SampleModuleCreatedEvent
  | SampleModuleUpdatedEvent
  | SampleModuleDeletedEvent
  | SampleModuleStatusChangedEvent
  | SampleModuleSearchedEvent;

// ============================================
// Event Bus Helper
// ============================================
export class SampleModuleEventBus {
  private static listeners: Map<string, Set<(event: SampleModuleEvent) => void>> = new Map();
  
  static subscribe<T extends SampleModuleEvent>(
    eventType: T['type'],
    handler: (event: T) => void
  ): () => void {
    if (!this.listeners.has(eventType)) {
      this.listeners.set(eventType, new Set());
    }
    
    const handlers = this.listeners.get(eventType)!;
    const wrappedHandler = handler as (event: SampleModuleEvent) => void;
    handlers.add(wrappedHandler);
    
    return () => {
      handlers.delete(wrappedHandler);
    };
  }
  
  static publish<T extends SampleModuleEvent>(event: T): void {
    const handlers = this.listeners.get(event.type);
    if (handlers) {
      handlers.forEach(handler => handler(event));
    }
    
    // Also dispatch to global event bus if available
    if (typeof window !== 'undefined' && window.dispatchEvent) {
      window.dispatchEvent(new CustomEvent(event.type, { detail: event.payload }));
    }
  }
  
  static unsubscribeAll(): void {
    this.listeners.clear();
  }
}
