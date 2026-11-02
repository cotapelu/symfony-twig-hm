import { writable } from 'svelte/store';
import type { Location, Place, SharedLocation, Route, LocationState, ShareLocationRequest, DirectionsRequest } from '../contracts/locationTypes';
import { LocationService } from '../services/locationService';

const initialState: LocationState = {
  currentLocation: null,
  nearbyPlaces: [],
  sharedLocations: [],
  selectedPlace: null,
  currentRoute: null,
  loading: false,
  error: null,
  isTracking: false
};

function createLocationStore() {
  const { subscribe, set, update } = writable<LocationState>(initialState);
  const service = new LocationService();
  let watchId: number | null = null;

  return {
    subscribe,

    async getCurrentLocation() {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const location = await service.getCurrentLocation();
        update(state => ({ ...state, currentLocation: location, loading: false }));
        return location;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to get location',
          loading: false
        }));
        throw error;
      }
    },

    trackLocation(enabled: boolean) {
      if (enabled) {
        if ('geolocation' in navigator) {
          watchId = navigator.geolocation.watchPosition(
            (position) => {
              const location: Location = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                altitude: position.coords.altitude || undefined,
                heading: position.coords.heading || undefined,
                speed: position.coords.speed || undefined,
                timestamp: position.timestamp
              };
              update(state => ({ ...state, currentLocation: location, isTracking: true }));
            },
            (error) => {
              update(state => ({ ...state, error: error.message, isTracking: false }));
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
          );
        }
      } else {
        if (watchId !== null) {
          navigator.geolocation.clearWatch(watchId);
          watchId = null;
        }
        update(state => ({ ...state, isTracking: false }));
      }
    },

    async searchNearbyPlaces(query?: string, radius: number = 5000) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const places = await service.searchNearby(query, radius);
        update(state => ({ ...state, nearbyPlaces: places, loading: false }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to search places',
          loading: false
        }));
      }
    },

    async getNearbyPlaces(radius: number = 5000) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const places = await service.getNearbyPlaces(radius);
        update(state => ({ ...state, nearbyPlaces: places, loading: false }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to get nearby places',
          loading: false
        }));
      }
    },

    async shareLocation(request: ShareLocationRequest) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const result = await service.shareLocation(request);
        update(state => ({ ...state, loading: false }));
        return result;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to share location',
          loading: false
        }));
        throw error;
      }
    },

    async getSharedLocations() {
      try {
        const locations = await service.getSharedLocations();
        update(state => ({ ...state, sharedLocations: locations }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to get shared locations'
        }));
      }
    },

    async getDirections(request: DirectionsRequest) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const route = await service.getDirections(request);
        update(state => ({ ...state, currentRoute: route, loading: false }));
        return route;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to get directions',
          loading: false
        }));
        throw error;
      }
    },

    selectPlace(place: Place | null) {
      update(state => ({ ...state, selectedPlace: place }));
    },

    clearRoute() {
      update(state => ({ ...state, currentRoute: null }));
    },

    clearError() {
      update(state => ({ ...state, error: null }));
    },
    reset() {
      if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
      }
      set(initialState);
    }
  };
}

export const locationStore = createLocationStore();
export const { getCurrentLocation, trackLocation, shareLocation, clearError } = locationStore;
