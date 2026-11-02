import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { EventBus, Event } from './event-bus';

describe('EventBus', () => {
  let bus: EventBus;

  beforeEach(() => {
    bus = new EventBus({
      maxHistory: 10,
      enablePersistence: false,
      enableMetrics: true,
      debugMode: false,
    });
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.restoreAllMocks();
 vi.useRealTimers();
  });

  describe('constructor', () => {
    it('initializes with default config values', () => {
      const defaultBus = new EventBus();
      expect(defaultBus).toBeInstanceOf(EventBus);
    });

    it('respects provided config', () => {
      const customBus = new EventBus({
        maxHistory: 50,
        enablePersistence: true,
        enableMetrics: false,
        debugMode: true,
      });
      expect(customBus).toBeInstanceOf(EventBus);
    });
  });

  describe('subscribe', () => {
    it('registers a handler and returns a subscription ID', () => {
      const id = bus.subscribe('test', () => {});
      expect(typeof id).toBe('string');
      expect(id.length).toBeGreaterThan(0);
    });

    it('allows multiple handlers for same event type', async () => {
      const handler1 = vi.fn();
      const handler2 = vi.fn();
      bus.subscribe('event', handler1);
      bus.subscribe('event', handler2);
      await bus.publish({ type: 'event', data: 1 });
      expect(handler1).toHaveBeenCalledWith(expect.objectContaining({ type: 'event', data: 1 }));
      expect(handler2).toHaveBeenCalledWith(expect.objectContaining({ type: 'event', data: 1 }));
    });

    it('supports once option: handler auto-unsubscribes after first call', async () => {
      const handler = vi.fn();
      bus.subscribe('once-event', handler, { once: true });
      await bus.publish({ type: 'once-event', data: 'a' });
      await bus.publish({ type: 'once-event', data: 'b' });
      expect(handler).toHaveBeenCalledTimes(1);
      expect(handler).toHaveBeenCalledWith(expect.objectContaining({ type: 'once-event', data: 'a' }));
    });

    it('uses custom ID if provided', () => {
      const id = bus.subscribe('custom-id-event', () => {}, { id: 'my-handler' });
      expect(id).toBe('my-handler');
    });
  });

  describe('publish', () => {
    it('calls all matching handlers with the full Event object', async () => {
      const handler = vi.fn();
      bus.subscribe('test', handler);
      await bus.publish({ type: 'test', data: { foo: 'bar' } });
      expect(handler).toHaveBeenCalledWith(expect.objectContaining({ type: 'test', data: { foo: 'bar' } }));
    });

    it('adds timestamp and correlationId if missing', async () => {
      const received: Event[] = [];
      bus.subscribe('meta', (e) => received.push(e));
      await bus.publish({ type: 'meta', data: 123 });
      expect(received[0].timestamp).toBeInstanceOf(Date);
      expect(received[0].correlationId).toBeDefined();
    });

    it('continues emitting if a handler throws and increments errors metric', async () => {
      const errorHandler = vi.fn(() => { throw new Error('handler error'); });
      const okHandler = vi.fn();
      bus.subscribe('mixed', errorHandler);
      bus.subscribe('mixed', okHandler);
      await expect(bus.publish({ type: 'mixed', data: 1 })).resolves.toBeUndefined();
      expect(errorHandler).toHaveBeenCalled();
      expect(okHandler).toHaveBeenCalled();
      const metrics = bus.getMetrics();
      expect(metrics.errors).toBe(1);
    });

    it('records metrics: totalEvents and eventsByType', async () => {
      bus.subscribe('typeA', () => {});
      await bus.publish({ type: 'typeA', data: 1 });
      await bus.publish({ type: 'typeA', data: 2 });
      await bus.publish({ type: 'typeB', data: 3 });
      const metrics = bus.getMetrics();
      expect(metrics.totalEvents).toBe(3);
      expect(metrics.eventsByType.typeA).toBe(2);
      expect(metrics.eventsByType.typeB).toBe(1);
    });

    it('stores events in history up to maxHistory', async () => {
      bus = new EventBus({ maxHistory: 5 });
      for (let i = 0; i < 10; i++) {
        await bus.publish({ type: 'loop', data: i });
      }
      const history = bus.getEventHistory();
      expect(history.length).toBe(5);
      for (let i = 0; i < 5; i++) {
        expect(history[i].data).toBe(5 + i);
      }
    });

    it('getEventHistory filters by event type', async () => {
      await bus.publish({ type: 'type1', data: 1 });
      await bus.publish({ type: 'type2', data: 2 });
      await bus.publish({ type: 'type1', data: 3 });
      const type1History = bus.getEventHistory('type1');
      expect(type1History.map(e => e.data)).toEqual([1, 3]);
    });

    it('getEventHistory respects limit', async () => {
      await bus.publish({ type: 'l', data: 1 });
      await bus.publish({ type: 'l', data: 2 });
      await bus.publish({ type: 'l', data: 3 });
      const limited = bus.getEventHistory(undefined, 2);
      expect(limited.length).toBe(2);
    });

    it('preserves provided timestamp and correlationId/causationId', async () => {
      const received: Event[] = [];
      bus.subscribe('meta', (e) => received.push(e));
      const customTime = new Date(2000, 0, 1);
      const event: Event = {
        type: 'meta',
        data: 123,
        timestamp: customTime,
        correlationId: 'corr-123',
        causationId: 'cause-456',
      };
      await bus.publish(event);
      expect(received[0].timestamp).toBe(customTime);
      expect(received[0].correlationId).toBe('corr-123');
      expect(received[0].causationId).toBe('cause-456');
    });
  });

  describe('publishAndForget', () => {
    it('executes handlers in background and does not throw', async () => {
      const handler = vi.fn().mockImplementation(() => new Promise(res => setTimeout(res, 1000)));
      bus.subscribe('fire', handler);
      await expect(bus.publishAndForget({ type: 'fire', data: 'test' })).resolves.toBeUndefined();
      // Advance timers to let the slow handler resolve
      await vi.runAllTimersAsync();
      expect(handler).toHaveBeenCalled();
    });

    it('logs handler errors and does not throw', async () => {
      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      const failingHandler = vi.fn(() => { throw new Error('fail'); });
      bus.subscribe('err', failingHandler);
      await expect(bus.publishAndForget({ type: 'err', data: 1 })).resolves.toBeUndefined();
      await vi.runAllTimersAsync();
      expect(consoleErrorSpy).toHaveBeenCalledWith(
        expect.stringContaining('EventBus: Error in handler for err'),
        expect.any(Error)
      );
      consoleErrorSpy.mockRestore();
    });
  });

  describe('publishBatch', () => {
    it('publishes multiple events and waits for all handlers', async () => {
      const handler = vi.fn();
      bus.subscribe('batch', handler);
      await bus.publishBatch([
        { type: 'batch', data: 1 },
        { type: 'batch', data: 2 },
        { type: 'batch', data: 3 },
      ]);
      expect(handler).toHaveBeenCalledTimes(3);
    });
  });

  describe('unsubscribe', () => {
    it('removes handler based on subscription ID', async () => {
      const h = vi.fn();
      const id = bus.subscribe('rem', h);
      expect(bus.unsubscribe(id)).toBe(true);
      await bus.publish({ type: 'rem', data: 1 });
      expect(h).not.toHaveBeenCalled();
    });

    it('returns false for unknown ID', () => {
      expect(bus.unsubscribe('unknown')).toBe(false);
    });
  });

  describe('getMetrics', () => {
    it('returns metrics object with required fields', async () => {
      bus.subscribe('m', () => {});
      await bus.publish({ type: 'm', data: 1 });
      const m = bus.getMetrics();
      expect(m).toHaveProperty('totalEvents');
      expect(m).toHaveProperty('eventsByType');
      expect(m.eventsByType).toHaveProperty('m');
      expect(m).toHaveProperty('errors');
      expect(m).toHaveProperty('avgProcessingTime');
      expect(m).toHaveProperty('activeSubscriptions');
    });

    it('tracks active subscriptions count', () => {
      const id1 = bus.subscribe('sub', () => {});
      const id2 = bus.subscribe('sub', () => {});
      let m = bus.getMetrics();
      expect(m.activeSubscriptions).toBe(2);
      bus.unsubscribe(id1);
      m = bus.getMetrics();
      expect(m.activeSubscriptions).toBe(1);
    });
  });

  describe('debugMode', () => {
    it('logs debug messages when debugMode enabled', async () => {
      const debugSpy = vi.spyOn(console, 'debug').mockImplementation(() => {});
      const debugBus = new EventBus({ debugMode: true });
      debugBus.subscribe('debug-event', () => {});
      await debugBus.publish({ type: 'debug-event', data: 'test' });
      // Should have two calls: one for subscribe, one for publish
      // Check first call: "EventBus: Subscribed to debug-event" and subscription object
      expect(debugSpy).toHaveBeenNthCalledWith(1,
        expect.stringContaining('EventBus:'),
        expect.objectContaining({ subscription: expect.objectContaining({ eventType: 'debug-event' }) })
      );
      // Second call: "EventBus: Published debug-event" and event object with type
      expect(debugSpy).toHaveBeenNthCalledWith(2,
        expect.stringContaining('EventBus:'),
        expect.objectContaining({ type: 'debug-event' })
      );
      debugSpy.mockRestore();
    });
  });
});
