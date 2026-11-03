import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flightsStore, flights, bookings, flightsLoading, flightsError } from './flightsStore';
import { FlightsService } from '../services/flightsService';
import type { Flight, FlightBooking } from '../contracts/flightsTypes';

function getCurrentState() {
  let current: any;
  const unsub = flightsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockFlight = (overrides: Partial<Flight> = {}): Flight => ({
  id: `flight-${Math.random().toString(36).substr(2, 5)}`,
  number: 'FL123',
  origin: 'SGN',
  destination: 'HAN',
  departure: now,
  arrival: new Date(now.getTime() + 2 * 60 * 60 * 1000),
  price: 200,
  seats: 150,
  ...overrides
});

const mockBooking = (overrides: Partial<FlightBooking> = {}): FlightBooking => ({
  id: `booking-${Math.random().toString(36).substr(2, 5)}`,
  flightId: 'flight-1',
  userId: 'user-1',
  seatNumber: '12A',
  status: 'confirmed',
  createdAt: now,
  ...overrides
});

describe('FlightsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    flightsStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and defaults', () => {
      const state = getCurrentState();
      expect(state.flights).toEqual([]);
      expect(state.bookings).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let f: Flight[] = [], b: FlightBooking[] = [], l = false, e: string | null = null;
      const unsubF = flights.subscribe(s => { f = s; });
      const unsubB = bookings.subscribe(s => { b = s; });
      const unsubL = flightsLoading.subscribe(s => { l = s; });
      const unsubE = flightsError.subscribe(s => { e = s; });
      expect(f).toEqual([]);
      expect(b).toEqual([]);
      expect(l).toBe(false);
      expect(e).toBeNull();
      unsubF(); unsubB(); unsubL(); unsubE();
    });
  });

  describe('searchFlights', () => {
    it('loads flights', async () => {
      const flights = [mockFlight(), mockFlight()];
      vi.spyOn(FlightsService.prototype, 'searchFlights').mockResolvedValue(flights);
      const result = await flightsStore.searchFlights({ origin: 'SGN', destination: 'HAN', date: '2025-06-25' });
      expect(result).toEqual(flights);
      const state = getCurrentState();
      expect(state.flights).toEqual(flights);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(FlightsService.prototype, 'searchFlights').mockRejectedValue(new Error('Search failed'));
      try {
        await flightsStore.searchFlights({ origin: 'SGN', destination: 'HAN', date: '2025-06-25' });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Search failed');
      expect(state.flights).toEqual([]);
    });
  });

  describe('loadFlight', () => {
    it('adds new flight when not present', async () => {
      const flight = mockFlight({ id: 'f1' });
      vi.spyOn(FlightsService.prototype, 'getFlight').mockResolvedValue(flight);
      await flightsStore.loadFlight('f1');
      const state = getCurrentState();
      // store state holds FlightWithPrice wrappers; getFlight payload is wrapped
      expect(state.flights).toContainEqual({ flight, prices: {} });
    });

    it('replaces existing flight', async () => {
      const first = mockFlight({ id: 'f1', number: 'FL111' });
      const second = mockFlight({ id: 'f1', number: 'FL222' });
      // Add first
      vi.spyOn(FlightsService.prototype, 'getFlight').mockResolvedValueOnce(first);
      await flightsStore.loadFlight('f1');
      let state = getCurrentState();
      expect(state.flights[0]).toEqual({ flight: first, prices: {} });
      // Replace with second
      vi.spyOn(FlightsService.prototype, 'getFlight').mockResolvedValueOnce(second);
      await flightsStore.loadFlight('f1');
      state = getCurrentState();
      expect(state.flights).toHaveLength(1);
      expect(state.flights[0]).toEqual({ flight: second, prices: {} });
    });
  });

  describe('loadBookings', () => {
    it('loads bookings', async () => {
      const bookings = [mockBooking(), mockBooking()];
      vi.spyOn(FlightsService.prototype, 'getBookings').mockResolvedValue(bookings);
      await flightsStore.loadBookings();
      const state = getCurrentState();
      expect(state.bookings).toEqual(bookings);
      expect(state.loading).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(FlightsService.prototype, 'getBookings').mockRejectedValue(new Error('Fail'));
      await flightsStore.loadBookings();
      const state = getCurrentState();
      expect(state.error).toBe('Fail');
    });
  });

  describe('bookFlight', () => {
    it('creates booking and adds to bookings', async () => {
      const booking = mockBooking({ id: 'b1' });
      vi.spyOn(FlightsService.prototype, 'bookFlight').mockResolvedValue(booking);
      const result = await flightsStore.bookFlight({ flightId: 'f1', seatNumber: '12A' });
      expect(result).toEqual(booking);
      const state = getCurrentState();
      expect(state.bookings).toContainEqual(booking);
    });

    it('handles error', async () => {
      vi.spyOn(FlightsService.prototype, 'bookFlight').mockRejectedValue(new Error('Booking failed'));
      try {
        await flightsStore.bookFlight({ flightId: 'f1', seatNumber: '12A' });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Booking failed');
    });
  });

  describe('cancelBooking', () => {
    it('cancels booking and updates bookings list', async () => {
      const booking = mockBooking({ id: 'b1', status: 'confirmed' });
      // create booking
      vi.spyOn(FlightsService.prototype, 'bookFlight').mockResolvedValue(booking);
      await flightsStore.bookFlight({ flightId: 'f1', seatNumber: '12A' });
      let state = getCurrentState();
      expect(state.bookings).toHaveLength(1);
      // cancel
      const canceled = mockBooking({ id: 'b1', status: 'cancelled' });
      vi.spyOn(FlightsService.prototype, 'cancelBooking').mockResolvedValue(canceled);
      const result = await flightsStore.cancelBooking('b1');
      expect(result).toEqual(canceled);
      state = getCurrentState();
      expect(state.bookings).toHaveLength(1);
      expect(state.bookings[0].status).toBe('cancelled');
    });

    it('handles error', async () => {
      vi.spyOn(FlightsService.prototype, 'cancelBooking').mockRejectedValue(new Error('Cancel failed'));
      try {
        await flightsStore.cancelBooking('b1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Cancel failed');
    });
  });

  describe('clearFlights', () => {
    it('clears flights array', async () => {
      const flights = [mockFlight(), mockFlight()];
      vi.spyOn(FlightsService.prototype, 'searchFlights').mockResolvedValue(flights);
      await flightsStore.searchFlights({ origin: 'SGN', destination: 'HAN', date: '2025-06-25' });
      let state = getCurrentState();
      expect(state.flights.length).toBeGreaterThan(0);
      flightsStore.clearFlights();
      state = getCurrentState();
      expect(state.flights).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(FlightsService.prototype, 'searchFlights').mockRejectedValue(new Error('test'));
      try {
        await flightsStore.searchFlights({ origin: 'SGN', destination: 'HAN', date: '2025-06-25' });
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      flightsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets store to initial state', async () => {
      vi.spyOn(FlightsService.prototype, 'searchFlights').mockResolvedValue([mockFlight()]);
      await flightsStore.searchFlights({ origin: 'SGN', destination: 'HAN', date: '2025-06-25' });
      let state = getCurrentState();
      expect(state.flights.length).toBeGreaterThan(0);
      flightsStore.reset();
      state = getCurrentState();
      expect(state.flights).toEqual([]);
      expect(state.bookings).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
