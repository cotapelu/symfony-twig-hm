import { writable, derived } from 'svelte/store';
import type { Booking, BookingSlot, BookingsState } from '../contracts/bookingsTypes';
import { bookingsService } from '../services/bookingsService';

const initialState: BookingsState = {
  bookings: [],
  slots: [],
  loading: false,
  error: null
};

function createBookingsStore() {
  const { subscribe, set, update } = writable<BookingsState>(initialState);

  return {
    subscribe,
    async loadBookings() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const bookings = await bookingsService.getBookings();
        update(s => ({ ...s, bookings, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async createBooking(serviceId: string, scheduledAt: string, location?: string, notes?: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const booking = await bookingsService.createBooking({ serviceId, scheduledAt, location, notes });
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
        const booking = await bookingsService.cancelBooking(id);
        update(s => ({ ...s, bookings: s.bookings.map(b => b.id === id ? booking : b), loading: false }));
        return booking;
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

export const bookingsStore = createBookingsStore();
export const bookings = derived(bookingsStore, $s => $s.bookings);
export const bookingsLoading = derived(bookingsStore, $s => $s.loading);
export const bookingsError = derived(bookingsStore, $s => $s.error);
