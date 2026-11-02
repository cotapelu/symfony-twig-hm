// Smart Incident Reporting Events - Client-side domain events

export type SmartIncidentEventType = 
  | 'smartincident.created'
  | 'smartincident.updated'
  | 'smartincident.status_changed'
  | 'smartincident.assigned'
  | 'smartincident.resolved'
  | 'smartincident.escalated'
  | 'smartincident.closed'
  | 'smartincident.message_sent'
  | 'smartincident.attachment_added'
  | 'smartincident.location_updated'
  | 'smartincident.classified'
  | 'smartincident.duplicate_detected';

export interface SmartIncidentEvent {
  type: SmartIncidentEventType;
  payload: any;
  timestamp: string;
  correlationId?: string;
}

const _eventBusInstance = new class {
  private listeners: Map<SmartIncidentEventType, Set<Function>> = new Map();
  private allListeners: Set<Function> = new Set();

  subscribe(eventType: SmartIncidentEventType, listener: Function): () => void {
    if (!this.listeners.has(eventType)) {
      this.listeners.set(eventType, new Set());
    }
    this.listeners.get(eventType)!.add(listener);
    
    return () => this.unsubscribe(eventType, listener);
  }

  subscribeToAll(listener: Function): () => void {
    this.allListeners.add(listener);
    return () => this.allListeners.delete(listener);
  }

  unsubscribe(eventType: SmartIncidentEventType, listener: Function): void {
    const listeners = this.listeners.get(eventType);
    if (listeners) {
      listeners.delete(listener);
    }
  }

  publish(event: SmartIncidentEvent): void {
    event.timestamp = new Date().toISOString();
    
    const listeners = this.listeners.get(event.type);
    if (listeners) {
      listeners.forEach(listener => {
        try {
          listener(event);
        } catch (error) {
          console.error(`Error in event listener for ${event.type}:`, error);
        }
      });
    }
    
    this.allListeners.forEach(listener => {
      try {
        listener(event);
      } catch (error) {
        console.error(`Error in universal event listener:`, error);
      }
    });
  }

  clear(): void {
    this.listeners.clear();
    this.allListeners.clear();
  }
}();

export const SmartIncidentEventBus = _eventBusInstance;

// ============================================
// Event Creators
// ============================================
export const SmartIncidentEvents = {
  created(incidentId: number, incidentData: any): SmartIncidentEvent {
    return {
      type: 'smartincident.created',
      payload: { incidentId, ...incidentData },
    };
  },

  updated(incidentId: number, changes: any): SmartIncidentEvent {
    return {
      type: 'smartincident.updated',
      payload: { incidentId, changes },
    };
  },

  statusChanged(incidentId: number, oldStatus: string, newStatus: string, changedBy: string): SmartIncidentEvent {
    return {
      type: 'smartincident.status_changed',
      payload: { incidentId, oldStatus, newStatus, changedBy },
    };
  },

  assigned(incidentId: number, officerId: string, team?: string, assignedBy: string): SmartIncidentEvent {
    return {
      type: 'smartincident.assigned',
      payload: { incidentId, officerId, team, assignedBy },
    };
  },

  resolved(incidentId: number, summary: string, resolvedBy: string, resolutionTime: string): SmartIncidentEvent {
    return {
      type: 'smartincident.resolved',
      payload: { incidentId, summary, resolvedBy, resolutionTime },
    };
  },

  escalated(incidentId: number, reason: string, escalatedBy: string): SmartIncidentEvent {
    return {
      type: 'smartincident.escalated',
      payload: { incidentId, reason, escalatedBy },
    };
  },

  closed(incidentId: number, closedBy: string): SmartIncidentEvent {
    return {
      type: 'smartincident.closed',
      payload: { incidentId, closedBy },
    };
  },

  messageSent(incidentId: number, messageId: number, sender: string, senderName: string): SmartIncidentEvent {
    return {
      type: 'smartincident.message_sent',
      payload: { incidentId, messageId, sender, senderName },
    };
  },

  attachmentAdded(incidentId: number, attachmentId: number, fileType: string, uploadedBy: string): SmartIncidentEvent {
    return {
      type: 'smartincident.attachment_added',
      payload: { incidentId, attachmentId, fileType, uploadedBy },
    };
  },

  locationUpdated(incidentId: number, latitude: number, longitude: number, address?: string): SmartIncidentEvent {
    return {
      type: 'smartincident.location_updated',
      payload: { incidentId, latitude, longitude, address },
    };
  },

  classified(incidentId: number, aiType: string, aiSeverity: string, confidence: number): SmartIncidentEvent {
    return {
      type: 'smartincident.classified',
      payload: { incidentId, aiType, aiSeverity, confidence },
    };
  },

  duplicateDetected(incidentId: number, similarIncidentIds: number[], similarityScore: number): SmartIncidentEvent {
    return {
      type: 'smartincident.duplicate_detected',
      payload: { incidentId, similarIncidentIds, similarityScore },
    };
  },
};
