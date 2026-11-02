import { writable, derived } from 'svelte/store';
import type { RideRequest, RideState, CreateRideRequest, RideRatingRequest } from '../contracts/rideTypes';
import { rideService } from '../services/rideService';

const initialState: RideState = { rides: [], currentRide: null, loading: false, error: null };

function createRideStore() {
  const { subscribe, set, update } = writable<RideState>(initialState);
  return {
    subscribe,
    async createRide(request: CreateRideRequest) { update(s => ({ ...s, loading: true, error: null })); try { const ride = await rideService.createRide(request); update(s => ({ ...s, rides: [...s.rides, ride], currentRide: ride, loading: false })); return ride; } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); throw e; } },
    async loadRide(id: string) { update(s => ({ ...s, loading: true, error: null })); try { const ride = await rideService.getRide(id); update(s => ({ ...s, currentRide: ride, loading: false })); return ride; } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); throw e; } },
    async loadRides() { update(s => ({ ...s, loading: true, error: null })); try { const rides = await rideService.getRides(); update(s => ({ ...s, rides, loading: false })); } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); } },
    async cancelRide(id: string) { update(s => ({ ...s, loading: true, error: null })); try { const ride = await rideService.cancelRide(id); update(s => ({ ...s, rides: s.rides.map(x => x.id === id ? ride : x), currentRide: s.currentRide?.id === id ? ride : s.currentRide, loading: false })); return ride; } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); throw e; } },
    async rateRide(request: RideRatingRequest) { update(s => ({ ...s, loading: true, error: null })); try { const ride = await rideService.rateRide(request); update(s => ({ ...s, rides: s.rides.map(x => x.id === request.rideId ? ride : x), currentRide: s.currentRide?.id === request.rideId ? ride : s.currentRide, loading: false })); return ride; } catch (e) { update(s => ({ ...s, error: (e as Error).message, loading: false })); throw e; } },
    clearCurrentRide() { update(s => ({ ...s, currentRide: null })); },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const rideStore = createRideStore();
export const rides = derived(rideStore, $s => $s.rides);
export const currentRide = derived(rideStore, $s => $s.currentRide);
export const rideLoading = derived(rideStore, $s => $s.loading);
export const rideError = derived(rideStore, $s => $s.error);
export const activeRide = derived(rideStore, $s => $s.rides.find(r => ['pending', 'accepted', 'arriving', 'in_progress'].includes(r.status)));
