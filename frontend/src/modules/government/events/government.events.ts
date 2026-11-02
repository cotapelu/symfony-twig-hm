// Government Module Events - Event definitions for cross-module communication

import type { Citizen, GovernmentDocument, AdministrativeProcedure, ProcedureApplication } from '../contracts/government.types';

// ============================================
// Citizen Events
// ============================================
export interface CitizenCreatedEvent {
  type: 'government.citizen.created';
  payload: {
    citizen: Citizen;
    timestamp: string;
  };
}

export interface CitizenUpdatedEvent {
  type: 'government.citizen.updated';
  payload: {
    citizen: Citizen;
    changes: Partial<Citizen>;
    timestamp: string;
  };
}

export interface CitizenStatusChangedEvent {
  type: 'government.citizen.status_changed';
  payload: {
    citizenId: string;
    oldStatus: boolean;
    newStatus: boolean;
    timestamp: string;
  };
}

export interface CitizenSearchEvent {
  type: 'government.citizen.search';
  payload: {
    query: string;
    resultsCount: number;
    timestamp: string;
  };
}

// ============================================
// Document Events
// ============================================
export interface DocumentCreatedEvent {
  type: 'government.document.created';
  payload: {
    document: GovernmentDocument;
    timestamp: string;
  };
}

export interface DocumentPublishedEvent {
  type: 'government.document.published';
  payload: {
    documentId: string;
    publishedAt: string;
    timestamp: string;
  };
}

export interface DocumentArchivedEvent {
  type: 'government.document.archived';
  payload: {
    documentId: string;
    archivedAt: string;
    timestamp: string;
  };
}

export interface DocumentSearchEvent {
  type: 'government.document.search';
  payload: {
    keyword: string;
    resultsCount: number;
    timestamp: string;
  };
}

// ============================================
// Procedure Events
// ============================================
export interface ProcedureCreatedEvent {
  type: 'government.procedure.created';
  payload: {
    procedure: AdministrativeProcedure;
    timestamp: string;
  };
}

export interface ProcedureUpdatedEvent {
  type: 'government.procedure.updated';
  payload: {
    procedure: AdministrativeProcedure;
    changes: Partial<AdministrativeProcedure>;
    timestamp: string;
  };
}

// ============================================
// Application Events
// ============================================
export interface ApplicationSubmittedEvent {
  type: 'government.application.submitted';
  payload: {
    application: ProcedureApplication;
    timestamp: string;
  };
}

export interface ApplicationStatusChangedEvent {
  type: 'government.application.status_changed';
  payload: {
    applicationId: string;
    oldStatus: string;
    newStatus: string;
    processedBy: string | null;
    notes: string | null;
    timestamp: string;
  };
}

export interface ApplicationCompletedEvent {
  type: 'government.application.completed';
  payload: {
    application: ProcedureApplication;
    result: string;
    completedAt: string;
    timestamp: string;
  };
}

// ============================================
// Export all event types
// ============================================
export type GovernmentEvent =
  | CitizenCreatedEvent
  | CitizenUpdatedEvent
  | CitizenStatusChangedEvent
  | CitizenSearchEvent
  | DocumentCreatedEvent
  | DocumentPublishedEvent
  | DocumentArchivedEvent
  | DocumentSearchEvent
  | ProcedureCreatedEvent
  | ProcedureUpdatedEvent
  | ApplicationSubmittedEvent
  | ApplicationStatusChangedEvent
  | ApplicationCompletedEvent;

// ============================================
// Event Bus Helper
// ============================================
export class GovernmentEventBus {
  private static listeners: Map<string, Set<(event: GovernmentEvent) => void>> = new Map();
  
  static subscribe<T extends GovernmentEvent>(
    eventType: T['type'],
    handler: (event: T) => void
  ): () => void {
    if (!this.listeners.has(eventType)) {
      this.listeners.set(eventType, new Set());
    }
    
    const handlers = this.listeners.get(eventType)!;
    const wrappedHandler = handler as (event: GovernmentEvent) => void;
    handlers.add(wrappedHandler);
    
    return () => {
      handlers.delete(wrappedHandler);
    };
  }
  
  static publish<T extends GovernmentEvent>(event: T): void {
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
