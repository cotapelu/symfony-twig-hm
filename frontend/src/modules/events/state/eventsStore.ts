import { writable, derived } from 'svelte/store';
import type { Event, EventTicket, EventsState, CreateEventRequest, PurchaseTicketsRequest } from '../contracts/eventsTypes';
import { eventsService } from '../services/eventsService';

const initialState: EventsState = {
  events: [],
  tickets: [],
  loading: false,
  error: null
};

function createEventsStore() {
  const { subscribe, set, update } = writable<EventsState>(initialState);

  return {
    subscribe,

    async loadEvents() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const events = await eventsService.getEvents();
        update(s => ({ ...s, events, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadEvent(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const event = await eventsService.getEvent(id);
        update(s => {
          const exists = s.events.find(e => e.id === id);
          return {
            ...s,
            events: exists ? s.events.map(e => e.id === id ? event : e) : [...s.events, event],
            loading: false
          };
        });
        return event;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async createEvent(request: CreateEventRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const event = await eventsService.createEvent(request);
        update(s => ({ ...s, events: [...s.events, event], loading: false }));
        return event;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadTickets() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const tickets = await eventsService.getTickets();
        update(s => ({ ...s, tickets, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async purchaseTickets(request: PurchaseTicketsRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const ticket = await eventsService.purchaseTickets(request);
        update(s => ({ ...s, tickets: [...s.tickets, ticket], loading: false }));
        return ticket;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const eventsStore = createEventsStore();

export const events = derived(eventsStore, $s => $s.events);
export const tickets = derived(eventsStore, $s => $s.tickets);
export const eventsLoading = derived(eventsStore, $s => $s.loading);
export const eventsError = derived(eventsStore, $s => $s.error);

export const upcomingEvents = derived(eventsStore, $s =>
  $s.events.filter(e => new Date(e.startDate) > new Date() && e.status === 'published')
);
