import { describe, it, expect, vi, beforeEach } from 'vitest';
import { hotelsStore, hotels, bookings, hotelsLoading, hotelsError } from './hotelsStore';
import { HotelsService } from '../services/hotelsService';
import type { Hotel, HotelBooking } from '../contracts/hotelsTypes';

function getCurrentState() {
  let current: any;
  const unsub = hotelsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockHotel = (overrides: Partial<Hotel> = {}): Hotel => ({
  id: `hotel-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Grand Hotel',
  location: 'Hanoi',
  price: 100,
  amenities: ['wifi', 'pool'],
  createdAt: now,
  updatedAt: now,
  ...overrides
});

const mockBooking = (overrides: Partial<HotelBooking> = {}): HotelBooking => ({
  id: `booking-${Math.random().toString(36).substr(2, 5)}`,
  hotelId: 'hotel-1',
  userId: 'user-1',
  checkIn: '2025-06-25',
  checkOut: '2025-06-30',
  status: 'confirmed',
  createdAt: now,
  ...overrides
});

describe('HotelsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    hotelsStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and defaults', () => {
      const state = getCurrentState();
      expect(state.hotels).toEqual([]);
      expect(state.bookings).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let h: Hotel[] = [], b: HotelBooking[] = [], l = false, e: string | null = null;
      const unsubH = hotels.subscribe(s => { h = s; });
      const unsubB = bookings.subscribe(s => { b = s; });
      const unsubL = hotelsLoading.subscribe(s => { l = s; });
      const unsubE = hotelsError.subscribe(s => { e = s; });
      expect(h).toEqual([]);
      expect(b).toEqual([]);
      expect(l).toBe(false);
      expect(e).toBeNull();
      unsubH(); unsubB(); unsubL(); unsubE();
    });
  });

  describe('searchHotels', () => {
    it('loads hotels', async () => {
      const hotels = [mockHotel(), mockHotel()];
      vi.spyOn(HotelsService.prototype, 'searchHotels').mockResolvedValue(hotels);
      const result = await hotelsStore.searchHotels({ location: 'Hanoi', checkIn: '2025-06-25', checkOut: '2025-06-30', guests: 2 });
      expect(result).toEqual(hotels);
      const state = getCurrentState();
      expect(state.hotels).toEqual(hotels);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(HotelsService.prototype, 'searchHotels').mockReturnValue(p as any);
      const searchPromise = hotelsStore.searchHotels({ location: 'Hanoi', checkIn: '2025-06-25', checkOut: '2025-06-30', guests: 2 });
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockHotel()]);
      await searchPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates hotels derived on success', async () => {
      const hotelList = [mockHotel()];
      vi.spyOn(HotelsService.prototype, 'searchHotels').mockResolvedValue(hotelList);
      await hotelsStore.searchHotels({ location: 'Hanoi', checkIn: '2025-06-25', checkOut: '2025-06-30', guests: 2 });
      let h: Hotel[] = [];
      const unsub = hotels.subscribe(s => { h = s; });
      expect(h).toEqual(hotelList);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(HotelsService.prototype, 'searchHotels').mockResolvedValue([]);
      await hotelsStore.searchHotels({ location: 'Hanoi', checkIn: '2025-06-25', checkOut: '2025-06-30', guests: 2 });
      expect(getCurrentState().hotels).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(HotelsService.prototype, 'searchHotels').mockRejectedValue(new Error('Search failed'));
      try {
        await hotelsStore.searchHotels({ location: 'Hanoi', checkIn: '2025-06-25', checkOut: '2025-06-30', guests: 2 });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Search failed');
      expect(state.loading).toBe(false);
      expect(state.hotels).toEqual([]);
    });
  });

  describe('loadHotel', () => {
    it('fetches single hotel and adds/replaces in hotels list', async () => {
      const first = mockHotel({ id: 'h1', name: 'First' });
      const second = mockHotel({ id: 'h1', name: 'Second' });
      // First call adds
      vi.spyOn(HotelsService.prototype, 'getHotel').mockResolvedValueOnce(first);
      await hotelsStore.loadHotel('h1');
      let state = getCurrentState();
      expect(state.hotels).toHaveLength(1);
      expect(state.hotels[0]).toEqual(first);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      // Second call replaces
      vi.spyOn(HotelsService.prototype, 'getHotel').mockResolvedValueOnce(second);
      await hotelsStore.loadHotel('h1');
      state = getCurrentState();
      expect(state.hotels).toHaveLength(1);
      expect(state.hotels[0]).toEqual(second);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('adds new hotel if not present', async () => {
      const newHotel = mockHotel({ id: 'h2', name: 'New' });
      vi.spyOn(HotelsService.prototype, 'getHotel').mockResolvedValue(newHotel);
      await hotelsStore.loadHotel('h2');
      const state = getCurrentState();
      expect(state.hotels).toContainEqual(newHotel);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(HotelsService.prototype, 'getHotel').mockReturnValue(p as any);
      const loadPromise = hotelsStore.loadHotel('h1');
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise(mockHotel({ id: 'h1' }));
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates hotels derived on success', async () => {
      const hotel = mockHotel({ id: 'h1' });
      vi.spyOn(HotelsService.prototype, 'getHotel').mockResolvedValue(hotel);
      await hotelsStore.loadHotel('h1');
      let h: Hotel[] = [];
      const unsub = hotels.subscribe(s => { h = s; });
      expect(h).toContainEqual(hotel);
      unsub();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(HotelsService.prototype, 'getHotel').mockRejectedValue(new Error('Fetch failed'));
      try {
        await hotelsStore.loadHotel('h1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Fetch failed');
      expect(state.loading).toBe(false);
    });
  });

  describe('loadBookings', () => {
    it('loads bookings', async () => {
      const bookings = [mockBooking(), mockBooking()];
      vi.spyOn(HotelsService.prototype, 'getBookings').mockResolvedValue(bookings);
      await hotelsStore.loadBookings();
      const state = getCurrentState();
      expect(state.bookings).toEqual(bookings);
      expect(state.loading).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(HotelsService.prototype, 'getBookings').mockRejectedValue(new Error('Fail'));
      await hotelsStore.loadBookings();
      const state = getCurrentState();
      expect(state.error).toBe('Fail');
    });
  });

  describe('bookHotel', () => {
    it('creates booking and adds to bookings', async () => {
      const booking = mockBooking({ id: 'b1' });
      vi.spyOn(HotelsService.prototype, 'bookHotel').mockResolvedValue(booking);
      const result = await hotelsStore.bookHotel({ hotelId: 'h1', checkIn: '2025-06-25', checkOut: '2025-06-30', guests: 2 });
      expect(result).toEqual(booking);
      const state = getCurrentState();
      expect(state.bookings).toContainEqual(booking);
    });

    it('handles error', async () => {
      vi.spyOn(HotelsService.prototype, 'bookHotel').mockRejectedValue(new Error('Booking failed'));
      try {
        await hotelsStore.bookHotel({ hotelId: 'h1', checkIn: '2025-06-25', checkOut: '2025-06-30', guests: 2 });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Booking failed');
    });
  });

  describe('cancelBooking', () => {
    it('cancels booking and updates bookings list', async () => {
      const booking = mockBooking({ id: 'b1', status: 'confirmed' });
      // First, create a booking via bookHotel
      vi.spyOn(HotelsService.prototype, 'bookHotel').mockResolvedValue(booking);
      await hotelsStore.bookHotel({ hotelId: 'h1', checkIn: '2025-06-25', checkOut: '2025-06-30', guests: 2 });
      let state = getCurrentState();
      expect(state.bookings).toHaveLength(1);
      // Now cancel
      const canceled = mockBooking({ id: 'b1', status: 'cancelled' });
      vi.spyOn(HotelsService.prototype, 'cancelBooking').mockResolvedValue(canceled);
      const result = await hotelsStore.cancelBooking('b1');
      expect(result).toEqual(canceled);
      state = getCurrentState();
      expect(state.bookings).toHaveLength(1);
      expect(state.bookings[0].status).toBe('cancelled');
    });

    it('handles error', async () => {
      vi.spyOn(HotelsService.prototype, 'cancelBooking').mockRejectedValue(new Error('Cancel failed'));
      try {
        await hotelsStore.cancelBooking('b1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Cancel failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(HotelsService.prototype, 'searchHotels').mockRejectedValue(new Error('test'));
      try {
        await hotelsStore.searchHotels({ location: 'Hanoi', checkIn: '2025-06-25', checkOut: '2025-06-30', guests: 2 });
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      hotelsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets store to initial state', async () => {
      vi.spyOn(HotelsService.prototype, 'searchHotels').mockResolvedValue([mockHotel()]);
      await hotelsStore.searchHotels({ location: 'Hanoi', checkIn: '2025-06-25', checkOut: '2025-06-30', guests: 2 });
      let state = getCurrentState();
      expect(state.hotels.length).toBeGreaterThan(0);
      hotelsStore.reset();
      state = getCurrentState();
      expect(state.hotels).toEqual([]);
      expect(state.bookings).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
