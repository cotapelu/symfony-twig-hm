import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  restaurantsStore,
  restaurants,
  reservations,
  restaurantsLoading,
  restaurantsError
} from './restaurantsStore';
import { RestaurantsService } from '../services/restaurantsService';
import type { Restaurant, Reservation } from '../contracts/restaurantsTypes';

function getCurrentState() {
  let current: any;
  const unsub = restaurantsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockRestaurant = (overrides: Partial<Restaurant> = {}): Restaurant => ({
  id: `rest-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Restaurant Name',
  address: '123 Main St',
  cuisine: 'Italian',
  rating: 4.5,
  priceLevel: 2,
  phone: '123-456-7890',
  openingHours: {},
  createdAt: now,
  updatedAt: now,
  ...overrides
});

const mockReservation = (overrides: Partial<Reservation> = {}): Reservation => ({
  id: `res-${Math.random().toString(36).substr(2, 5)}`,
  restaurantId: 'rest-1',
  userId: 'user-1',
  dateTime: now,
  partySize: 2,
  status: 'confirmed',
  createdAt: now,
  ...overrides
});

describe('RestaurantsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    restaurantsStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.restaurants).toEqual([]);
      expect(state.reservations).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let r: Restaurant[] = [], res: Reservation[] = [], l = false, e: string | null = null;
      const unsubR = restaurants.subscribe(s => { r = s; });
      const unsubRes = reservations.subscribe(s => { res = s; });
      const unsubL = restaurantsLoading.subscribe(s => { l = s; });
      const unsubE = restaurantsError.subscribe(s => { e = s; });
      expect(r).toEqual([]);
      expect(res).toEqual([]);
      expect(l).toBe(false);
      expect(e).toBeNull();
      unsubR(); unsubRes(); unsubL(); unsubE();
    });
  });

  describe('search', () => {
    it('loads restaurants', async () => {
      const rests = [mockRestaurant(), mockRestaurant()];
      vi.spyOn(RestaurantsService.prototype, 'search').mockResolvedValue(rests);
      await restaurantsStore.search({ location: 'Hanoi', cuisine: 'Italian' });
      const state = getCurrentState();
      expect(state.restaurants).toEqual(rests);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(RestaurantsService.prototype, 'search').mockReturnValue(p as any);
      const searchPromise = restaurantsStore.search({ location: 'Hanoi' });
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockRestaurant()]);
      await searchPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates restaurants derived on success', async () => {
      const rests = [mockRestaurant()];
      vi.spyOn(RestaurantsService.prototype, 'search').mockResolvedValue(rests);
      await restaurantsStore.search({ location: 'Hanoi' });
      let r: Restaurant[] = [];
      const unsub = restaurants.subscribe(s => { r = s; });
      expect(r).toEqual(rests);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(RestaurantsService.prototype, 'search').mockResolvedValue([]);
      await restaurantsStore.search({ location: 'Hanoi' });
      expect(getCurrentState().restaurants).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(RestaurantsService.prototype, 'search').mockRejectedValue(new Error('Search failed'));
      await restaurantsStore.search({ location: 'Hanoi' });
      const state = getCurrentState();
      expect(state.error).toBe('Search failed');
      expect(state.loading).toBe(false);
      expect(state.restaurants).toEqual([]);
    });
  });

  describe('load', () => {
    it('adds new restaurant', async () => {
      const rest = mockRestaurant({ id: 'r1' });
      vi.spyOn(RestaurantsService.prototype, 'get').mockResolvedValue(rest);
      const result = await restaurantsStore.load('r1');
      expect(result).toEqual(rest);
      const state = getCurrentState();
      expect(state.restaurants).toContainEqual(rest);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('replaces existing restaurant', async () => {
      const first = mockRestaurant({ id: 'r1', name: 'First' });
      const second = mockRestaurant({ id: 'r1', name: 'Second' });
      // Load first
      vi.spyOn(RestaurantsService.prototype, 'get').mockResolvedValueOnce(first);
      await restaurantsStore.load('r1');
      let state = getCurrentState();
      expect(state.restaurants[0].name).toBe('First');
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      // Replace
      vi.spyOn(RestaurantsService.prototype, 'get').mockResolvedValueOnce(second);
      await restaurantsStore.load('r1');
      state = getCurrentState();
      expect(state.restaurants).toHaveLength(1);
      expect(state.restaurants[0].name).toBe('Second');
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(RestaurantsService.prototype, 'get').mockReturnValue(p as any);
      const loadPromise = restaurantsStore.load('r1');
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise(mockRestaurant({ id: 'r1' }));
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates restaurants derived on success', async () => {
      const rest = mockRestaurant({ id: 'r1' });
      vi.spyOn(RestaurantsService.prototype, 'get').mockResolvedValue(rest);
      await restaurantsStore.load('r1');
      let r: Restaurant[] = [];
      const unsub = restaurants.subscribe(s => { r = s; });
      expect(r).toContainEqual(rest);
      unsub();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(RestaurantsService.prototype, 'get').mockRejectedValue(new Error('Not found'));
      try {
        await restaurantsStore.load('r1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
    });
  });

  describe('loadReservations', () => {
    it('loads reservations', async () => {
      const resers = [mockReservation(), mockReservation()];
      vi.spyOn(RestaurantsService.prototype, 'getReservations').mockResolvedValue(resers);
      await restaurantsStore.loadReservations();
      const state = getCurrentState();
      expect(state.reservations).toEqual(resers);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(RestaurantsService.prototype, 'getReservations').mockReturnValue(p as any);
      const loadPromise = restaurantsStore.loadReservations();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockReservation()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates reservations derived on success', async () => {
      const resers = [mockReservation()];
      vi.spyOn(RestaurantsService.prototype, 'getReservations').mockResolvedValue(resers);
      await restaurantsStore.loadReservations();
      let r: Reservation[] = [];
      const unsub = reservations.subscribe(s => { r = s; });
      expect(r).toEqual(resers);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(RestaurantsService.prototype, 'getReservations').mockResolvedValue([]);
      await restaurantsStore.loadReservations();
      expect(getCurrentState().reservations).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(RestaurantsService.prototype, 'getReservations').mockRejectedValue(new Error('Reservations failed'));
      await restaurantsStore.loadReservations();
      const state = getCurrentState();
      expect(state.error).toBe('Reservations failed');
      expect(state.loading).toBe(false);
      expect(state.reservations).toEqual([]);
    });
  });

  describe('reserve', () => {
    it('creates reservation and adds to reservations', async () => {
      const reservation = mockReservation({ id: 'res1' });
      vi.spyOn(RestaurantsService.prototype, 'reserve').mockResolvedValue(reservation);
      const result = await restaurantsStore.reserve({ restaurantId: 'r1', dateTime: now, partySize: 2 });
      expect(result).toEqual(reservation);
      const state = getCurrentState();
      expect(state.reservations).toContainEqual(reservation);
    });

    it('propagates error', async () => {
      vi.spyOn(RestaurantsService.prototype, 'reserve').mockRejectedValue(new Error('Reserve failed'));
      try {
        await restaurantsStore.reserve({ restaurantId: 'r1', dateTime: now, partySize: 2 });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Reserve failed');
    });
  });

  describe('cancel', () => {
    it('cancels reservation and updates list', async () => {
      const reservation = mockReservation({ id: 'res1', status: 'confirmed' });
      // Prepopulate via loadReservations
      vi.spyOn(RestaurantsService.prototype, 'getReservations').mockResolvedValue([reservation]);
      await restaurantsStore.loadReservations();
      // Cancel
      const canceled = mockReservation({ id: 'res1', status: 'cancelled' });
      vi.spyOn(RestaurantsService.prototype, 'cancel').mockResolvedValue(canceled);
      const result = await restaurantsStore.cancel('res1');
      expect(result).toEqual(canceled);
      const state = getCurrentState();
      expect(state.reservations[0].status).toBe('cancelled');
    });

    it('handles error', async () => {
      const reservation = mockReservation({ id: 'res1' });
      vi.spyOn(RestaurantsService.prototype, 'getReservations').mockResolvedValue([reservation]);
      await restaurantsStore.loadReservations();
      vi.spyOn(RestaurantsService.prototype, 'cancel').mockRejectedValue(new Error('Cancel failed'));
      try {
        await restaurantsStore.cancel('res1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Cancel failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(RestaurantsService.prototype, 'search').mockRejectedValue(new Error('err'));
      await restaurantsStore.search({ location: 'Hanoi' });
      let state = getCurrentState();
      expect(state.error).toBe('err');
      restaurantsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(RestaurantsService.prototype, 'search').mockResolvedValue([mockRestaurant()]);
      await restaurantsStore.search({ location: 'Hanoi' });
      let state = getCurrentState();
      expect(state.restaurants.length).toBeGreaterThan(0);
      restaurantsStore.reset();
      state = getCurrentState();
      expect(state.restaurants).toEqual([]);
      expect(state.reservations).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
