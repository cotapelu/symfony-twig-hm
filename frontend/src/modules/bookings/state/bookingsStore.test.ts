import { describe, it, expect, vi, beforeEach } from 'vitest';
import { bookingsStore, bookings, bookingsLoading, bookingsError } from './bookingsStore';
import { bookingsService } from '../services/bookingsService';
import type { Booking, CreateBookingRequest } from '../contracts/bookingsTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = bookingsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockBooking = (overrides: Partial<Booking> = {}): Booking => ({
  id: `book-${Math.random().toString(36).substr(2, 5)}`,
  serviceId: 'svc-1',
  scheduledAt: new Date().toISOString(),
  location: '123 Main St',
  notes: 'Test booking',
  status: 'confirmed',
  createdAt: new Date().toISOString(),
  ...overrides
});

const mockCreateRequest = (overrides: Partial<CreateBookingRequest> = {}): CreateBookingRequest => ({
  serviceId: 'svc-1',
  scheduledAt: new Date().toISOString(),
  location: '123 Main St',
  notes: 'Test booking',
  ...overrides
});

describe('BookingsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    bookingsStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.bookings).toEqual([]);
      expect(state.slots).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let b: Booking[] = [], load = false, err: string | null = null;
      const unsubB = bookings.subscribe(s => { b = s; });
      const unsubL = bookingsLoading.subscribe(s => { load = s; });
      const unsubE = bookingsError.subscribe(s => { err = s; });
      expect(b).toEqual([]);
      expect(load).toBe(false);
      expect(err).toBeNull();
      unsubB(); unsubL(); unsubE();
    });
  });

  describe('loadBookings', () => {
    it('loads bookings successfully', async () => {
      const bookings = [mockBooking(), mockBooking()];
      vi.spyOn(bookingsService, 'getBookings').mockResolvedValue(bookings);

      await bookingsStore.loadBookings();

      expect(bookingsService.getBookings).toHaveBeenCalledTimes(1);
      const state = getState();
      expect(state.bookings).toEqual(bookings);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(bookingsService, 'getBookings').mockReturnValue(p as any);

      const loadPromise = bookingsStore.loadBookings();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockBooking()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch bookings');
      vi.spyOn(bookingsService, 'getBookings').mockRejectedValue(error);

      await bookingsStore.loadBookings();

      const state = getState();
      expect(state.error).toBe('Failed to fetch bookings');
      expect(state.loading).toBe(false);
      expect(state.bookings).toEqual([]);
    });
  });

  describe('createBooking', () => {
    it('creates booking and adds to list', async () => {
      const newBooking = mockBooking({ id: 'new-book' });
      vi.spyOn(bookingsService, 'createBooking').mockResolvedValue(newBooking);

      const request = mockCreateRequest();
      const result = await bookingsStore.createBooking(request.serviceId, request.scheduledAt, request.location, request.notes);

      expect(result).toEqual(newBooking);
      // Store packages args into object before calling service
      expect(bookingsService.createBooking).toHaveBeenCalledWith({
        serviceId: request.serviceId,
        scheduledAt: request.scheduledAt,
        location: request.location,
        notes: request.notes
      });
      const state = getState();
      expect(state.bookings).toContainEqual(newBooking);
    });

    it('sets loading during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(bookingsService, 'createBooking').mockReturnValue(p as any);

      const request = mockCreateRequest();
      const loadPromise = bookingsStore.createBooking(request.serviceId, request.scheduledAt, request.location, request.notes);

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(mockBooking());
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('throws and sets error on failure', async () => {
      const error = new Error('Create failed');
      vi.spyOn(bookingsService, 'createBooking').mockRejectedValue(error);

      const request = mockCreateRequest();
      await expect(bookingsStore.createBooking(request.serviceId, request.scheduledAt, request.location, request.notes)).rejects.toThrow('Create failed');

      const state = getState();
      expect(state.error).toBe('Create failed');
      expect(state.bookings).toEqual([]);
    });
  });

  describe('cancelBooking', () => {
    it('cancels booking and updates list', async () => {
      const cancelledBooking = mockBooking({ id: 'book-1', status: 'cancelled' });
      vi.spyOn(bookingsService, 'cancelBooking').mockResolvedValue(cancelledBooking);

      // Pre-populate bookings via loadBookings
      const existing = mockBooking({ id: 'book-1', status: 'confirmed' });
      vi.spyOn(bookingsService, 'getBookings').mockResolvedValue([existing]);
      await bookingsStore.loadBookings();
      expect(getState().bookings).toHaveLength(1);

      const result = await bookingsStore.cancelBooking('book-1');

      expect(result).toEqual(cancelledBooking);
      expect(bookingsService.cancelBooking).toHaveBeenCalledWith('book-1');
      const state = getState();
      expect(state.bookings).toHaveLength(1);
      expect(state.bookings[0].status).toBe('cancelled');
    });

    it('handles error', async () => {
      const error = new Error('Cancel failed');
      vi.spyOn(bookingsService, 'cancelBooking').mockRejectedValue(error);

      const existing = mockBooking({ id: 'book-1' });
      vi.spyOn(bookingsService, 'getBookings').mockResolvedValue([existing]);
      await bookingsStore.loadBookings();

      await expect(bookingsStore.cancelBooking('book-1')).rejects.toThrow('Cancel failed');

      const state = getState();
      expect(state.error).toBe('Cancel failed');
      expect(state.bookings).toHaveLength(1); // unchanged
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(bookingsService, 'getBookings').mockRejectedValue(new Error('test error'));
      await bookingsStore.loadBookings().catch(() => {});

      expect(getState().error).toBe('test error');

      bookingsStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Load some bookings
      const bookings = [mockBooking(), mockBooking()];
      vi.spyOn(bookingsService, 'getBookings').mockResolvedValue(bookings);
      await bookingsStore.loadBookings();
      expect(getState().bookings).toHaveLength(2);

      // Reset
      bookingsStore.reset();

      const state = getState();
      expect(state.bookings).toEqual([]);
      expect(state.slots).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
