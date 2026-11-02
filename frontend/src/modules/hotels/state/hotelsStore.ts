import { writable, derived } from 'svelte/store';
import type { Hotel, HotelBooking, HotelsState, SearchHotelsRequest, BookHotelRequest } from '../contracts/hotelsTypes';
import { hotelsService } from '../services/hotelsService';

const initialState: HotelsState = { hotels: [], bookings: [], loading: false, error: null };

function createHotelsStore() {
  const { subscribe, set, update } = writable<HotelsState>(initialState);
  return {
    subscribe,
    async searchHotels(request: SearchHotelsRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const hotels = await hotelsService.searchHotels(request);
        update(s => ({ ...s, hotels, loading: false }));
        return hotels;
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); throw error; }
    },
    async loadHotel(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const hotel = await hotelsService.getHotel(id);
        update(s => ({ ...s, hotels: [...s.hotels.filter(h => h.id !== id), hotel], loading: false }));
        return hotel;
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); throw error; }
    },
    async loadBookings() {
      update(s => ({ ...s, loading: true, error: null }));
      try { const bookings = await hotelsService.getBookings(); update(s => ({ ...s, bookings, loading: false })); }
      catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    async bookHotel(request: BookHotelRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const booking = await hotelsService.bookHotel(request);
        update(s => ({ ...s, bookings: [...s.bookings, booking], loading: false }));
        return booking;
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); throw error; }
    },
    async cancelBooking(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try { const booking = await hotelsService.cancelBooking(id); update(s => ({ ...s, bookings: s.bookings.map(b => b.id === id ? booking : b), loading: false })); return booking; }
      catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); throw error; }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const hotelsStore = createHotelsStore();
export const hotels = derived(hotelsStore, $s => $s.hotels);
export const bookings = derived(hotelsStore, $s => $s.bookings);
export const hotelsLoading = derived(hotelsStore, $s => $s.loading);
export const hotelsError = derived(hotelsStore, $s => $s.error);
