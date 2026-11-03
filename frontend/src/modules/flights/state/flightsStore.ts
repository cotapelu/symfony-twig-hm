import { writable, derived } from 'svelte/store';
import type { Flight, FlightWithPrice, FlightBooking, FlightsState, SearchFlightsRequest, BookFlightRequest } from '../contracts/flightsTypes';
import { flightsService } from '../services/flightsService';

const initialState: FlightsState = {
  flights: [],
  bookings: [],
  loading: false,
  error: null
};

function createFlightsStore() {
  const { subscribe, set, update } = writable<FlightsState>(initialState);

  return {
    subscribe,

    async searchFlights(request: SearchFlightsRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const flights = await flightsService.searchFlights(request);
        update(s => ({ ...s, flights, loading: false }));
        return flights;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadFlight(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const flight = await flightsService.getFlight(id);
        // Store state holds FlightWithPrice wrappers; wrap the single-flight
        // payload with an empty price map (prices only apply to search results).
        const wrapper: FlightWithPrice = { flight, prices: {} };
        update(s => {
          const exists = s.flights.find(f => f.flight.id === id);
          return {
            ...s,
            flights: exists ? s.flights.map(f => f.flight.id === id ? wrapper : f) : [...s.flights, wrapper],
            loading: false
          };
        });
        return flight;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadBookings() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const bookings = await flightsService.getBookings();
        update(s => ({ ...s, bookings, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async bookFlight(request: BookFlightRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const booking = await flightsService.bookFlight(request);
        update(s => ({ ...s, bookings: [...s.bookings, booking], loading: false }));
        return booking;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async cancelBooking(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const booking = await flightsService.cancelBooking(id);
        update(s => ({
          ...s,
          bookings: s.bookings.map(b => b.id === id ? booking : b),
          loading: false
        }));
        return booking;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    clearFlights() {
      update(s => ({ ...s, flights: [] }));
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const flightsStore = createFlightsStore();

export const flights = derived(flightsStore, $s => $s.flights);
export const bookings = derived(flightsStore, $s => $s.bookings);
export const flightsLoading = derived(flightsStore, $s => $s.loading);
export const flightsError = derived(flightsStore, $s => $s.error);
