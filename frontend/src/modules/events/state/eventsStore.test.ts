import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  eventsStore,
  events,
  tickets,
  eventsLoading,
  eventsError,
  upcomingEvents
} from './eventsStore';
import { EventsService } from '../services/eventsService';
import type { Event, EventTicket } from '../contracts/eventsTypes';

function getCurrentState() {
  let current: any;
  const unsub = eventsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockEvent = (overrides: Partial<Event> = {}): Event => ({
  id: `event-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Event Title',
  description: 'Description',
  startDate: new Date(now.getTime() + 24 * 60 * 60 * 1000), // future
  endDate: new Date(now.getTime() + 26 * 60 * 60 * 1000),
  location: 'Venue',
  status: 'published',
  organizerId: 'org-1',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

const mockTicket = (overrides: Partial<EventTicket> = {}): EventTicket => ({
  id: `ticket-${Math.random().toString(36).substr(2, 5)}`,
  eventId: 'event-1',
  userId: 'user-1',
  seat: 'A1',
  price: 50,
  status: 'confirmed',
  createdAt: now,
  ...overrides
});

describe('EventsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    eventsStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.events).toEqual([]);
      expect(state.tickets).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let ev: Event[] = [], tk: EventTicket[] = [], l = false, e: string | null = null;
      const unsubE = events.subscribe(s => { ev = s; });
      const unsubT = tickets.subscribe(s => { tk = s; });
      const unsubL = eventsLoading.subscribe(s => { l = s; });
      const unsubErr = eventsError.subscribe(s => { e = s; });
      expect(ev).toEqual([]);
      expect(tk).toEqual([]);
      expect(l).toBe(false);
      expect(e).toBeNull();
      unsubE(); unsubT(); unsubL(); unsubErr();
    });
  });

  describe('loadEvents', () => {
    it('loads events', async () => {
      const evs = [mockEvent(), mockEvent()];
      vi.spyOn(EventsService.prototype, 'getEvents').mockResolvedValue(evs);
      await eventsStore.loadEvents();
      const state = getCurrentState();
      expect(state.events).toEqual(evs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(EventsService.prototype, 'getEvents').mockReturnValue(p as any);
      const loadPromise = eventsStore.loadEvents();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockEvent()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates events derived on success', async () => {
      const evs = [mockEvent()];
      vi.spyOn(EventsService.prototype, 'getEvents').mockResolvedValue(evs);
      await eventsStore.loadEvents();
      let e: Event[] = [];
      const unsub = events.subscribe(s => { e = s; });
      expect(e).toEqual(evs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(EventsService.prototype, 'getEvents').mockResolvedValue([]);
      await eventsStore.loadEvents();
      expect(getCurrentState().events).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(EventsService.prototype, 'getEvents').mockRejectedValue(new Error('Events failed'));
      await eventsStore.loadEvents();
      const state = getCurrentState();
      expect(state.error).toBe('Events failed');
      expect(state.loading).toBe(false);
      expect(state.events).toEqual([]);
    });
  });

  describe('loadEvent', () => {
    it('adds event if not exists', async () => {
      const ev = mockEvent({ id: 'e1' });
      vi.spyOn(EventsService.prototype, 'getEvent').mockResolvedValue(ev);
      await eventsStore.loadEvent('e1');
      const state = getCurrentState();
      expect(state.events).toContainEqual(ev);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('replaces event if exists', async () => {
      const first = mockEvent({ id: 'e1', title: 'First' });
      const second = mockEvent({ id: 'e1', title: 'Second' });
      vi.spyOn(EventsService.prototype, 'getEvent').mockResolvedValueOnce(first);
      await eventsStore.loadEvent('e1');
      let state = getCurrentState();
      expect(state.events[0].title).toBe('First');
      vi.spyOn(EventsService.prototype, 'getEvent').mockResolvedValueOnce(second);
      await eventsStore.loadEvent('e1');
      state = getCurrentState();
      expect(state.events).toHaveLength(1);
      expect(state.events[0].title).toBe('Second');
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(EventsService.prototype, 'getEvent').mockReturnValue(p as any);
      const loadPromise = eventsStore.loadEvent('e1');
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise(mockEvent({ id: 'e1' }));
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(EventsService.prototype, 'getEvent').mockRejectedValue(new Error('Not found'));
      try {
        await eventsStore.loadEvent('missing');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
    });
  });

  describe('createEvent', () => {
    it('creates event and adds to events', async () => {
      const ev = mockEvent({ id: 'e1' });
      vi.spyOn(EventsService.prototype, 'createEvent').mockResolvedValue(ev);
      const result = await eventsStore.createEvent({ title: 'New', description: '', startDate: now, endDate: now, location: '' });
      expect(result).toEqual(ev);
      const state = getCurrentState();
      expect(state.events).toContainEqual(ev);
    });

    it('propagates error', async () => {
      vi.spyOn(EventsService.prototype, 'createEvent').mockRejectedValue(new Error('Create failed'));
      try {
        await eventsStore.createEvent({ title: 'New', description: '', startDate: now, endDate: now, location: '' });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Create failed');
    });
  });

  describe('loadTickets', () => {
    it('loads tickets', async () => {
      const tk = [mockTicket(), mockTicket()];
      vi.spyOn(EventsService.prototype, 'getTickets').mockResolvedValue(tk);
      await eventsStore.loadTickets();
      const state = getCurrentState();
      expect(state.tickets).toEqual(tk);
    });

    it('handles error', async () => {
      vi.spyOn(EventsService.prototype, 'getTickets').mockRejectedValue(new Error('Ticket load failed'));
      await eventsStore.loadTickets();
      const state = getCurrentState();
      expect(state.error).toBe('Ticket load failed');
    });
  });

  describe('purchaseTickets', () => {
    it('purchases ticket and adds to tickets', async () => {
      const tk = mockTicket({ id: 't1' });
      vi.spyOn(EventsService.prototype, 'purchaseTickets').mockResolvedValue(tk);
      const result = await eventsStore.purchaseTickets({ eventId: 'e1', seat: 'A1', price: 50 });
      expect(result).toEqual(tk);
      const state = getCurrentState();
      expect(state.tickets).toContainEqual(tk);
    });

    it('handles error', async () => {
      vi.spyOn(EventsService.prototype, 'purchaseTickets').mockRejectedValue(new Error('Purchase failed'));
      try {
        await eventsStore.purchaseTickets({ eventId: 'e1', seat: 'A1', price: 50 });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Purchase failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(EventsService.prototype, 'getEvents').mockRejectedValue(new Error('err'));
      await eventsStore.loadEvents();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      eventsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(EventsService.prototype, 'getEvents').mockResolvedValue([mockEvent()]);
      await eventsStore.loadEvents();
      let state = getCurrentState();
      expect(state.events.length).toBeGreaterThan(0);
      eventsStore.reset();
      state = getCurrentState();
      expect(state.events).toEqual([]);
      expect(state.tickets).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('upcomingEvents derived', () => {
    it('filters events that are future and published', async () => {
      const future = mockEvent({ id: 'e1', startDate: new Date(now.getTime() + 100000), status: 'published' });
      const past = mockEvent({ id: 'e2', startDate: new Date(now.getTime() - 100000), status: 'published' });
      const draft = mockEvent({ id: 'e3', startDate: new Date(now.getTime() + 100000), status: 'draft' });
      vi.spyOn(EventsService.prototype, 'getEvents').mockResolvedValue([future, past, draft]);
      await eventsStore.loadEvents();
      let up: Event[] = [];
      const unsub = upcomingEvents.subscribe(s => { up = s; });
      expect(up).toEqual([future]);
      unsub();
    });
  });
});
