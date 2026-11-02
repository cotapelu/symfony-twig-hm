import { writable, derived } from 'svelte/store';
import type { TripPlannerState } from '../contracts/tripPlannerTypes';
import { tripPlannerService } from '../services/tripPlannerService';

const initialState: TripPlannerState = {
  trips: [],
  currentTrip: null,
  loading: false,
  error: null,
  pagination: { page: 1, limit: 20, total: 0, totalPages: 0 }
};

function createTripPlannerStore() {
  const { subscribe, set, update } = writable<TripPlannerState>(initialState);
  return {
    subscribe,
    async loadTrips(page: number = 1, limit: number = 20) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[TripPlannerStore] Loading trips: page=${page}, limit=${limit}`);
        const response = await tripPlannerService.getTrips(page, limit);
        update(s => ({
          ...s,
          trips: response.items,
          loading: false,
          pagination: {
            page: response.page,
            limit: response.limit,
            total: response.total,
            totalPages: response.totalPages
          }
        }));
        console.info(`[TripPlannerStore] Loaded ${response.items.length} trips, total=${response.total}`);
      } catch (error) {
        console.error('[TripPlannerStore] Failed to load trips:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async loadTrip(tripId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[TripPlannerStore] Loading trip: ${tripId}`);
        const trip = await tripPlannerService.getTrip(tripId);
        update(s => ({ ...s, currentTrip: trip, loading: false }));
        console.info(`[TripPlannerStore] Loaded trip: ${tripId}`);
      } catch (error) {
        console.error('[TripPlannerStore] Failed to load trip:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async createTrip(trip: { destination: string; startDate: string; endDate: string; activities: string[] }) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info('[TripPlannerStore] Creating trip');
        const newTrip = await tripPlannerService.createTrip(trip);
        update(s => ({ ...s, trips: [...s.trips, newTrip], loading: false }));
        console.info('[TripPlannerStore] Trip created');
        return newTrip;
      } catch (error) {
        console.error('[TripPlannerStore] Failed to create trip:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },
    async deleteTrip(tripId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[TripPlannerStore] Deleting trip: ${tripId}`);
        await tripPlannerService.deleteTrip(tripId);
        update(s => ({
          ...s,
          trips: s.trips.filter(t => t.id !== tripId),
          loading: false
        }));
        console.info(`[TripPlannerStore] Trip deleted: ${tripId}`);
      } catch (error) {
        console.error('[TripPlannerStore] Failed to delete trip:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const tripPlannerStore = createTripPlannerStore();
export const tripPlans = derived(tripPlannerStore, $s => $s.trips);
export const tripPlannerLoading = derived(tripPlannerStore, $s => $s.loading);
export const tripPlannerPagination = derived(tripPlannerStore, $s => $s.pagination);
