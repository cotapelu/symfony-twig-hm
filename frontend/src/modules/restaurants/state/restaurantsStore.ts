import { writable, derived } from 'svelte/store';
import type { Restaurant, Reservation, RestaurantsState, SearchRestaurantsRequest, CreateReservationRequest } from '../contracts/restaurantsTypes';
import { restaurantsService } from '../services/restaurantsService';

const initialState: RestaurantsState = { restaurants: [], reservations: [], loading: false, error: null };

function createRestaurantsStore() {
  const { subscribe, set, update } = writable<RestaurantsState>(initialState);
  return {
    subscribe,
    async search(request: SearchRestaurantsRequest) { update(s => ({ ...s, loading: true, error: null })); try { const restaurants = await restaurantsService.search(request); update(s => ({ ...s, restaurants, loading: false })); } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); } },
    async load(id: string) { update(s => ({ ...s, loading: true, error: null })); try { const r = await restaurantsService.get(id); update(s => ({ ...s, restaurants: [...s.restaurants.filter(x => x.id !== id), r], loading: false })); return r; } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); throw e; } },
    async loadReservations() { update(s => ({ ...s, loading: true, error: null })); try { const reservations = await restaurantsService.getReservations(); update(s => ({ ...s, reservations, loading: false })); } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); } },
    async reserve(request: CreateReservationRequest) { update(s => ({ ...s, loading: true, error: null })); try { const reservation = await restaurantsService.reserve(request); update(s => ({ ...s, reservations: [...s.reservations, reservation], loading: false })); return reservation; } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); throw e; } },
    async cancel(id: string) { update(s => ({ ...s, loading: true, error: null })); try { const reservation = await restaurantsService.cancel(id); update(s => ({ ...s, reservations: s.reservations.map(x => x.id === id ? reservation : x), loading: false })); return reservation; } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); throw e; } },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const restaurantsStore = createRestaurantsStore();
export const restaurants = derived(restaurantsStore, $s => $s.restaurants);
export const reservations = derived(restaurantsStore, $s => $s.reservations);
export const restaurantsLoading = derived(restaurantsStore, $s => $s.loading);
export const restaurantsError = derived(restaurantsStore, $s => $s.error);
