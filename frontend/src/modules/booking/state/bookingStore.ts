import { writable, derived } from 'svelte/store';
import type { Booking, BookingState, CreateBookingRequest } from '../contracts/bookingTypes';
import { bookingService } from '../services/bookingService';

const initialState: BookingState = {
  bookings: [],
  currentBooking: null,
  loading: false,
  error: null
};

function createBookingStore() {
  const { subscribe, set, update } = writable<BookingState>(initialState);

  return {
    subscribe,

    async loadBookings() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const bookings = await bookingService.getBookings();
        update(s => ({ ...s, bookings, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadBooking(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const booking = await bookingService.getBooking(id);
        update(s => ({ ...s, currentBooking: booking, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async createBooking(request: CreateBookingRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const booking = await bookingService.createBooking(request);
        update(s => ({
          ...s,
          bookings: [...s.bookings, booking],
          currentBooking: booking,
          loading: false
        }));
        return booking;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async cancelBooking(bookingId: string, reason?: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const booking = await bookingService.cancelBooking({ bookingId, reason });
        update(s => ({
          ...s,
          bookings: s.bookings.map(b => b.id === bookingId ? booking : b),
          currentBooking: s.currentBooking?.id === bookingId ? booking : s.currentBooking,
          loading: false
        }));
        return booking;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async confirmBooking(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const booking = await bookingService.confirmBooking(id);
        update(s => ({
          ...s,
          bookings: s.bookings.map(b => b.id === id ? booking : b),
          currentBooking: s.currentBooking?.id === id ? booking : s.currentBooking,
          loading: false
        }));
        return booking;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadUpcomingBookings() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const bookings = await bookingService.getUpcomingBookings();
        update(s => ({ ...s, bookings, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadPastBookings() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const bookings = await bookingService.getPastBookings();
        update(s => ({ ...s, bookings, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    clearCurrentBooking() {
      update(s => ({ ...s, currentBooking: null }));
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const bookingStore = createBookingStore();

export const bookings = derived(bookingStore, $s => $s.bookings);
export const currentBooking = derived(bookingStore, $s => $s.currentBooking);
export const bookingLoading = derived(bookingStore, $s => $s.loading);
export const bookingError = derived(bookingStore, $s => $s.error);

export const upcomingBookings = derived(bookingStore, $s =>
  $s.bookings.filter(b => b.status === 'confirmed' && new Date(b.startDate) > new Date())
);

export const pastBookings = derived(bookingStore, $s =>
  $s.bookings.filter(b => b.status === 'completed' || new Date(b.startDate) < new Date())
);
