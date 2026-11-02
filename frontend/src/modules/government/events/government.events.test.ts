import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  GovernmentEventBus,
  type GovernmentEvent,
  type CitizenCreatedEvent,
  type DocumentPublishedEvent,
  type ApplicationStatusChangedEvent
} from './government.events';

describe('GovernmentEventBus', () => {
  beforeEach(() => {
    GovernmentEventBus.unsubscribeAll();
  });

  describe('subscribe and publish', () => {
    it('should subscribe to an event and receive published events', () => {
      const handler = vi.fn();
      const event: CitizenCreatedEvent = {
        type: 'government.citizen.created',
        payload: {
          citizen: { id: '1', name: 'John Doe' } as any,
          timestamp: new Date().toISOString()
        }
      };

      GovernmentEventBus.subscribe('government.citizen.created', handler);
      GovernmentEventBus.publish(event);

      expect(handler).toHaveBeenCalledWith(event);
      expect(handler).toHaveBeenCalledTimes(1);
    });

    it('should return unsubscribe function', () => {
      const handler = vi.fn();
      const event: CitizenCreatedEvent = {
        type: 'government.citizen.created',
        payload: {
          citizen: { id: '1', name: 'John Doe' } as any,
          timestamp: new Date().toISOString()
        }
      };

      const unsubscribe = GovernmentEventBus.subscribe('government.citizen.created', handler);
      unsubscribe();
      GovernmentEventBus.publish(event);

      expect(handler).not.toHaveBeenCalled();
    });

    it('should support multiple subscribers for same event', () => {
      const handler1 = vi.fn();
      const handler2 = vi.fn();
      const event: DocumentPublishedEvent = {
        type: 'government.document.published',
        payload: {
          documentId: 'doc1',
          publishedAt: new Date().toISOString(),
          timestamp: new Date().toISOString()
        }
      };

      GovernmentEventBus.subscribe('government.document.published', handler1);
      GovernmentEventBus.subscribe('government.document.published', handler2);
      GovernmentEventBus.publish(event);

      expect(handler1).toHaveBeenCalledWith(event);
      expect(handler2).toHaveBeenCalledWith(event);
    });

    it('should not call handlers for different event types', () => {
      const handler = vi.fn();
      const event: DocumentPublishedEvent = {
        type: 'government.document.published',
        payload: {
          documentId: 'doc1',
          publishedAt: new Date().toISOString(),
          timestamp: new Date().toISOString()
        }
      };

      GovernmentEventBus.subscribe('government.citizen.created', handler);
      GovernmentEventBus.publish(event);

      expect(handler).not.toHaveBeenCalled();
    });
  });

  describe('unsubscribeAll', () => {
    it('should remove all subscribers', () => {
      const handler1 = vi.fn();
      const handler2 = vi.fn();
      const event: CitizenCreatedEvent = {
        type: 'government.citizen.created',
        payload: {
          citizen: { id: '1', name: 'John Doe' } as any,
          timestamp: new Date().toISOString()
        }
      };

      GovernmentEventBus.subscribe('government.citizen.created', handler1);
      GovernmentEventBus.subscribe('government.citizen.created', handler2);
      GovernmentEventBus.unsubscribeAll();
      GovernmentEventBus.publish(event);

      expect(handler1).not.toHaveBeenCalled();
      expect(handler2).not.toHaveBeenCalled();
    });
  });

  describe('Application Status Changed Event', () => {
    it('should handle application status changed event', () => {
      const handler = vi.fn();
      const event: ApplicationStatusChangedEvent = {
        type: 'government.application.status_changed',
        payload: {
          applicationId: 'app1',
          oldStatus: 'pending',
          newStatus: 'approved',
          processedBy: 'admin1',
          notes: 'Approved after review',
          timestamp: new Date().toISOString()
        }
      };

      GovernmentEventBus.subscribe('government.application.status_changed', handler);
      GovernmentEventBus.publish(event);

      expect(handler).toHaveBeenCalledWith(event);
      expect(event.payload.oldStatus).toBe('pending');
      expect(event.payload.newStatus).toBe('approved');
    });
  });
});
