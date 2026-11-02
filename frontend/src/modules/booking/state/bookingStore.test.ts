import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  bookingStore,
  bookings,
  currentBooking,
  bookingLoading,
  bookingError,
  upcomingBookings,
  pastBookings
} from './bookingStore';
import { bookingService } from '../services/bookingService';
import type { Booking } from '../contracts/bookingTypes';

function getCurrentState() {
  let current: any;
  const unsub = bookingStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();
const future = new Date(Date.now() + 86400000).toISOString();
const past = new Date(Date.now() - 86400000).toISOString();

const mockBooking = (overrides: Partial<Booking> = {}): Booking => ({
  id: `book-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  type: 'hotel' as const,
  referenceId: 'ref-1',
  status: 'confirmed' as const,
  startDate: future,
  endDate: future,
  guestCount: 2,
  totalAmount: 100,
  currency: 'USD',
  createdAt: now.toISOString(),
  updatedAt: now.toISOString(),
  ...overrides
});

describe('BookingStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    bookingStore.reset();
  });

  describe('initial state', () => {
    it('has empty bookings and null currentBooking', () => {
      const state = getCurrentState();
      expect(state.bookings).toEqual([]);
      expect(state.currentBooking).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let bookList: Booking[] = [], curr: Booking | null = null, load = false, err: string | null = null;
      const unsubB = bookings.subscribe(s => { bookList = s; });
      const unsubC = currentBooking.subscribe(s => { curr = s; });
      const unsubL = bookingLoading.subscribe(s => { load = s; });
      const unsubE = bookingError.subscribe(s => { err = s; });
      expect(bookList).toEqual([]);
      expect(curr).toBeNull();
      expect(load).toBe(false);
      expect(err).toBeNull();
      unsubB(); unsubC(); unsubL(); unsubE();
    });

    it('upcomingBookings and pastBookings derived are empty', () => {
      let up: Booking[] = [], past: Booking[] = [];
      const unsubU = upcomingBookings.subscribe(s => { up = s; });
      const unsubP = pastBookings.subscribe(s => { past = s; });
      expect(up).toEqual([]);
      expect(past).toEqual([]);
      unsubU(); unsubP();
    });
  });

  describe('loadBookings', () => {
    it('loads all bookings', async () => {
      const list = [mockBooking(), mockBooking()];
      vi.spyOn(bookingService, 'getBookings').mockResolvedValue(list);
      await bookingStore.loadBookings();
      const state = getCurrentState();
      expect(state.bookings).toEqual(list);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(bookingService, 'getBookings').mockReturnValue(p as any);
      const loadPromise = bookingStore.loadBookings();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockBooking()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates bookings derived on success', async () => {
      const list = [mockBooking()];
      vi.spyOn(bookingService, 'getBookings').mockResolvedValue(list);
      await bookingStore.loadBookings();
      let b: Booking[] = [];
      const unsub = bookings.subscribe(s => { b = s; });
      expect(b).toEqual(list);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(bookingService, 'getBookings').mockResolvedValue([]);
      await bookingStore.loadBookings();
      expect(getCurrentState().bookings).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(bookingService, 'getBookings').mockRejectedValue(new Error('Fail'));
      await bookingStore.loadBookings();
      const state = getCurrentState();
      expect(state.error).toBe('Fail');
      expect(state.loading).toBe(false);
      expect(state.bookings).toEqual([]);
    });
  });

  describe('loadBooking', () => {
    it('loads booking and sets as currentBooking', async () => {
      const book = mockBooking({ id: 'b1' });
      vi.spyOn(bookingService, 'getBooking').mockResolvedValue(book);
      await bookingStore.loadBooking('b1');
      const state = getCurrentState();
      expect(state.currentBooking).toEqual(book);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(bookingService, 'getBooking').mockReturnValue(p as any);
      const loadPromise = bookingStore.loadBooking('b1');
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise(mockBooking({ id: 'b1' }));
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates currentBooking derived on success', async () => {
      const book = mockBooking({ id: 'b1' });
      vi.spyOn(bookingService, 'getBooking').mockResolvedValue(book);
      await bookingStore.loadBooking('b1');
      let cb: Booking | null = null;
      const unsub = currentBooking.subscribe(s => { cb = s; });
      expect(cb).toEqual(book);
      unsub();
    });

    it('handles not found error and resets loading', async () => {
      vi.spyOn(bookingService, 'getBooking').mockRejectedValue(new Error('Not found'));
      try {
        await bookingStore.loadBooking('missing');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
      expect(state.currentBooking).toBeNull();
    });
  });

  describe('createBooking', () => {
    it('creates booking and adds to list and currentBooking', async () => {
      const newBook = mockBooking({ id: 'new-b' });
      vi.spyOn(bookingService, 'createBooking').mockResolvedValue(newBook);
      const result = await bookingStore.createBooking({
        type: 'hotel',
        referenceId: 'ref-new',
        startDate: future
      });
      expect(result).toEqual(newBook);
      let state = getCurrentState();
      expect(state.bookings[0]).toEqual(newBook);
      expect(state.currentBooking).toEqual(newBook);
    });

    it('propagates errors', async () => {
      vi.spyOn(bookingService, 'createBooking').mockRejectedValue(new Error('Validation'));
      try {
        await bookingStore.createBooking({ type: 'hotel', referenceId: 'ref', startDate: future });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Validation');
    });
  });

  describe('cancelBooking', () => {
    it('cancels booking and updates list and currentBooking', async () => {
      const original = mockBooking({ id: 'b1', status: 'confirmed' });
      const cancelled = mockBooking({ id: 'b1', status: 'cancelled' });
      vi.spyOn(bookingService, 'getBookings').mockResolvedValue([original]);
      await bookingStore.loadBookings();
      // Set currentBooking
      vi.spyOn(bookingService, 'getBooking').mockResolvedValue(original);
      await bookingStore.loadBooking('b1');
      vi.spyOn(bookingService, 'cancelBooking').mockResolvedValue(cancelled);
      await bookingStore.cancelBooking('b1', 'changed mind');
      let state = getCurrentState();
      expect(state.bookings.find(b => b.id === 'b1')?.status).toBe('cancelled');
      expect(state.currentBooking?.status).toBe('cancelled');
    });

    it('handles error', async () => {
      vi.spyOn(bookingService, 'cancelBooking').mockRejectedValue(new Error('Cancel failed'));
      try {
        await bookingStore.cancelBooking('b1', 'reason');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Cancel failed');
    });
  });

  describe('confirmBooking', () => {
    it('confirms booking and updates list and currentBooking', async () => {
      const original = mockBooking({ id: 'b1', status: 'pending' });
      const confirmed = mockBooking({ id: 'b1', status: 'confirmed' });
      vi.spyOn(bookingService, 'getBookings').mockResolvedValue([original]);
      await bookingStore.loadBookings();
      // Set currentBooking
      vi.spyOn(bookingService, 'getBooking').mockResolvedValue(original);
      await bookingStore.loadBooking('b1');
      vi.spyOn(bookingService, 'confirmBooking').mockResolvedValue(confirmed);
      await bookingStore.confirmBooking('b1');
      let state = getCurrentState();
      expect(state.bookings.find(b => b.id === 'b1')?.status).toBe('confirmed');
      expect(state.currentBooking?.status).toBe('confirmed');
    });

    it('handles error', async () => {
      vi.spyOn(bookingService, 'confirmBooking').mockRejectedValue(new Error('Confirm failed'));
      try {
        await bookingStore.confirmBooking('b1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Confirm failed');
    });
  });

  describe('loadUpcomingBookings', () => {
    it('loads upcoming bookings only', async () => {
      const upcoming = mockBooking({ id: 'u1', status: 'confirmed', startDate: future });
      vi.spyOn(bookingService, 'getUpcomingBookings').mockResolvedValue([upcoming]);
      await bookingStore.loadUpcomingBookings();
      let state = getCurrentState();
      expect(state.bookings).toEqual([upcoming]);
    });

    it('handles error', async () => {
      vi.spyOn(bookingService, 'getUpcomingBookings').mockRejectedValue(new Error('Upcoming fail'));
      await bookingStore.loadUpcomingBookings();
      const state = getCurrentState();
      expect(state.error).toBe('Upcoming fail');
      expect(state.bookings).toEqual([]);
    });
  });

  describe('loadPastBookings', () => {
    it('loads past bookings only', async () => {
      const pastBook = mockBooking({ id: 'p1', status: 'completed', startDate: past });
      vi.spyOn(bookingService, 'getPastBookings').mockResolvedValue([pastBook]);
      await bookingStore.loadPastBookings();
      let state = getCurrentState();
      expect(state.bookings).toEqual([pastBook]);
    });

    it('handles error', async () => {
      vi.spyOn(bookingService, 'getPastBookings').mockRejectedValue(new Error('Past fail'));
      await bookingStore.loadPastBookings();
      const state = getCurrentState();
      expect(state.error).toBe('Past fail');
    });
  });

  describe('clearCurrentBooking', () => {
    it('clears currentBooking', async () => {
      const book = mockBooking();
      vi.spyOn(bookingService, 'getBooking').mockResolvedValue(book);
      await bookingStore.loadBooking('b1');
      let state = getCurrentState();
      expect(state.currentBooking).toBeTruthy();
      bookingStore.clearCurrentBooking();
      state = getCurrentState();
      expect(state.currentBooking).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(bookingService, 'getBookings').mockRejectedValue(new Error('test'));
      try { await bookingStore.loadBookings(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      bookingStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const list = [mockBooking()];
      vi.spyOn(bookingService, 'getBookings').mockResolvedValue(list);
      await bookingStore.loadBookings();
      let state = getCurrentState();
      expect(state.bookings.length).toBeGreaterThan(0);
      bookingStore.reset();
      state = getCurrentState();
      expect(state.bookings).toEqual([]);
      expect(state.currentBooking).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
