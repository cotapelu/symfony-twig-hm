import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { locationStore } from './locationStore';
import { LocationService } from '../services/locationService';
import type { Location, Place, SharedLocation, Route, ShareLocationRequest, DirectionsRequest } from '../contracts/locationTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = locationStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockLocation = (overrides: Partial<Location> = {}): Location => ({
  latitude: 10.123,
  longitude: 20.456,
  accuracy: 5,
  altitude: 100,
  heading: 90,
  speed: 1.5,
  timestamp: Date.now(),
  ...overrides
});

const mockPlace = (overrides: Partial<Place> = {}): Place => ({
  id: 'place-1',
  name: 'Test Place',
  type: 'restaurant',
  address: '123 Main St',
  latitude: 0,
  longitude: 0,
  distance: 100,
  ...overrides
});

const mockSharedLocation = (overrides: Partial<SharedLocation> = {}): SharedLocation => ({
  id: 'shared-1',
  userId: 'user-1',
  userName: 'Test User',
  latitude: 0,
  longitude: 0,
  timestamp: new Date().toISOString(),
  ...overrides
});

const mockRoute = (overrides: Partial<Route> = {}): Route => ({
  origin: mockLocation(),
  destination: mockLocation({ latitude: 20, longitude: 40 }),
  distance: 1000,
  duration: 600,
  steps: [],
  ...overrides
});

describe('LocationStore', () => {
  let geolocationMock: any;

  beforeEach(() => {
    vi.clearAllMocks();

    // Mock navigator.geolocation before reset (reset may call clearWatch)
    geolocationMock = {
      watchPosition: vi.fn(),
      clearWatch: vi.fn()
    };
    // @ts-ignore
    global.navigator.geolocation = geolocationMock;

    // Reset store to initial state
    locationStore.reset();
  });

  afterEach(() => {
    // @ts-ignore
    delete global.navigator.geolocation;
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.currentLocation).toBeNull();
      expect(state.nearbyPlaces).toEqual([]);
      expect(state.sharedLocations).toEqual([]);
      expect(state.selectedPlace).toBeNull();
      expect(state.currentRoute).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.isTracking).toBe(false);
    });
  });

  describe('getCurrentLocation', () => {
    it('loads location successfully', async () => {
      const loc = mockLocation();
      vi.spyOn(LocationService.prototype, 'getCurrentLocation').mockResolvedValue(loc);

      await locationStore.getCurrentLocation();

      expect(LocationService.prototype.getCurrentLocation).toHaveBeenCalledTimes(1);
      const state = getState();
      expect(state.currentLocation).toEqual(loc);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(LocationService.prototype, 'getCurrentLocation').mockReturnValue(p as any);

      const loadPromise = locationStore.getCurrentLocation();

      // Allow microtask queue to flush
      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(mockLocation());
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Geolocation denied');
      vi.spyOn(LocationService.prototype, 'getCurrentLocation').mockRejectedValue(error);

      await expect(locationStore.getCurrentLocation()).rejects.toThrow('Geolocation denied');

      const state = getState();
      expect(state.error).toBe('Geolocation denied');
      expect(state.loading).toBe(false);
    });
  });

  describe('trackLocation', () => {
    it('does nothing when geolocation not supported', () => {
      // @ts-ignore
      delete global.navigator.geolocation;

      locationStore.trackLocation(true);
      expect(getState().isTracking).toBe(false);
    });

    it('starts tracking and updates location on position', async () => {
      const position = {
        coords: {
          latitude: 10,
          longitude: 20,
          accuracy: 5,
          altitude: 100,
          heading: 90,
          speed: 1.5
        },
        timestamp: Date.now()
      };
      let successCallback: any;
      geolocationMock.watchPosition.mockImplementation((success, error) => {
        successCallback = success;
        return 123; // watchId
      });

      locationStore.trackLocation(true);

      // Verify watchPosition called with correct options
      expect(geolocationMock.watchPosition).toHaveBeenCalledWith(
        expect.any(Function),
        expect.any(Function),
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );

      // Simulate position update
      successCallback(position);

      const state = getState();
      expect(state.isTracking).toBe(true);
      expect(state.currentLocation).toEqual({
        latitude: 10,
        longitude: 20,
        accuracy: 5,
        altitude: 100,
        heading: 90,
        speed: 1.5,
        timestamp: position.timestamp
      });
    });

    it('handles geolocation error callback', () => {
      let errorCallback: any;
      geolocationMock.watchPosition.mockImplementation((success, error) => {
        errorCallback = error;
        return 123;
      });

      locationStore.trackLocation(true);
      const geoError = new Error('Permission denied');
      errorCallback(geoError);

      const state = getState();
      expect(state.error).toBe('Permission denied');
      expect(state.isTracking).toBe(false);
    });

    it('stops tracking when disabled', () => {
      const watchId = 456;
      geolocationMock.watchPosition.mockReturnValue(watchId);

      locationStore.trackLocation(true);
      // The store should have stored the watchId
      locationStore.trackLocation(false);

      expect(geolocationMock.clearWatch).toHaveBeenCalledWith(watchId);
      expect(getState().isTracking).toBe(false);
    });

    it('does not clear watch if not tracking', () => {
      locationStore.trackLocation(false);
      expect(geolocationMock.clearWatch).not.toHaveBeenCalled();
    });
  });

  describe('searchNearbyPlaces', () => {
    it('searches with query and default radius', async () => {
      const places = [mockPlace()];
      vi.spyOn(LocationService.prototype, 'searchNearby').mockResolvedValue(places);

      await locationStore.searchNearbyPlaces('coffee');

      expect(LocationService.prototype.searchNearby).toHaveBeenCalledWith('coffee', 5000);
      const state = getState();
      expect(state.nearbyPlaces).toEqual(places);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('searches without query (radius only)', async () => {
      const places = [mockPlace()];
      vi.spyOn(LocationService.prototype, 'searchNearby').mockResolvedValue(places);

      await locationStore.searchNearbyPlaces(undefined, 2000);

      expect(LocationService.prototype.searchNearby).toHaveBeenCalledWith(undefined, 2000);
      const state = getState();
      expect(state.nearbyPlaces).toEqual(places);
    });

    it('handles error', async () => {
      const error = new Error('Network error');
      vi.spyOn(LocationService.prototype, 'searchNearby').mockRejectedValue(error);

      await locationStore.searchNearbyPlaces('test');

      const state = getState();
      expect(state.error).toBe('Network error');
      expect(state.loading).toBe(false);
      expect(state.nearbyPlaces).toEqual([]); // unchanged
    });

    it('sets loading true during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(LocationService.prototype, 'searchNearby').mockReturnValue(p as any);

      const loadPromise = locationStore.searchNearbyPlaces();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockPlace()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });
  });

  describe('getNearbyPlaces', () => {
    it('loads with default radius', async () => {
      const places = [mockPlace()];
      vi.spyOn(LocationService.prototype, 'getNearbyPlaces').mockResolvedValue(places);

      await locationStore.getNearbyPlaces();

      expect(LocationService.prototype.getNearbyPlaces).toHaveBeenCalledWith(5000);
      const state = getState();
      expect(state.nearbyPlaces).toEqual(places);
    });

    it('loads with custom radius', async () => {
      const places = [mockPlace()];
      vi.spyOn(LocationService.prototype, 'getNearbyPlaces').mockResolvedValue(places);

      await locationStore.getNearbyPlaces(1000);

      expect(LocationService.prototype.getNearbyPlaces).toHaveBeenCalledWith(1000);
      expect(getState().nearbyPlaces).toEqual(places);
    });

    it('handles error', async () => {
      const error = new Error('Service unavailable');
      vi.spyOn(LocationService.prototype, 'getNearbyPlaces').mockRejectedValue(error);

      await locationStore.getNearbyPlaces();

      const state = getState();
      expect(state.error).toBe('Service unavailable');
      expect(state.loading).toBe(false);
    });
  });

  describe('shareLocation', () => {
    const request: ShareLocationRequest = { latitude: 10, longitude: 20, duration: 3600 };

    it('shares successfully', async () => {
      const result = { id: 'share-1' };
      vi.spyOn(LocationService.prototype, 'shareLocation').mockResolvedValue(result);

      const res = await locationStore.shareLocation(request);

      expect(res).toEqual(result);
      const state = getState();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('throws and sets error on failure', async () => {
      const error = new Error('Share failed');
      vi.spyOn(LocationService.prototype, 'shareLocation').mockRejectedValue(error);

      await expect(locationStore.shareLocation(request)).rejects.toThrow('Share failed');

      const state = getState();
      expect(state.error).toBe('Share failed');
      expect(state.loading).toBe(false);
    });

    it('sets loading during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(LocationService.prototype, 'shareLocation').mockReturnValue(p as any);

      const loadPromise = locationStore.shareLocation(request);
      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise({ id: '1' });
      await loadPromise;
      expect(getState().loading).toBe(false);
    });
  });

  describe('getSharedLocations', () => {
    it('loads shared locations successfully', async () => {
      const locations = [mockSharedLocation()];
      vi.spyOn(LocationService.prototype, 'getSharedLocations').mockResolvedValue(locations);

      await locationStore.getSharedLocations();

      const state = getState();
      expect(state.sharedLocations).toEqual(locations);
      expect(state.loading).toBe(false); // unchanged
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch');
      vi.spyOn(LocationService.prototype, 'getSharedLocations').mockRejectedValue(error);

      await locationStore.getSharedLocations();

      const state = getState();
      expect(state.error).toBe('Failed to fetch');
      expect(state.sharedLocations).toEqual([]); // unchanged
    });
  });

  describe('getDirections', () => {
    const request: DirectionsRequest = {
      origin: mockLocation(),
      destination: mockLocation({ latitude: 30, longitude: 60 })
    };

    it('gets route successfully', async () => {
      const route = mockRoute();
      vi.spyOn(LocationService.prototype, 'getDirections').mockResolvedValue(route);

      const res = await locationStore.getDirections(request);

      expect(res).toEqual(route);
      const state = getState();
      expect(state.currentRoute).toEqual(route);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      const error = new Error('Directions failed');
      vi.spyOn(LocationService.prototype, 'getDirections').mockRejectedValue(error);

      await expect(locationStore.getDirections(request)).rejects.toThrow('Directions failed');

      const state = getState();
      expect(state.error).toBe('Directions failed');
      expect(state.loading).toBe(false);
    });

    it('sets loading during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(LocationService.prototype, 'getDirections').mockReturnValue(p as any);

      const loadPromise = locationStore.getDirections(request);
      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(mockRoute());
      await loadPromise;
      expect(getState().loading).toBe(false);
    });
  });

  describe('selectPlace', () => {
    it('sets selected place', () => {
      const place = mockPlace();
      locationStore.selectPlace(place);
      expect(getState().selectedPlace).toEqual(place);
    });

    it('clears selected place when null', () => {
      locationStore.selectPlace(mockPlace());
      locationStore.selectPlace(null);
      expect(getState().selectedPlace).toBeNull();
    });
  });

  describe('clearRoute', () => {
    it('clears current route', async () => {
      // We'll set the route by calling getDirections successfully, then clear.
      const route = mockRoute();
      vi.spyOn(LocationService.prototype, 'getDirections').mockResolvedValue(route);

      // Set route
      await locationStore.getDirections({
        origin: mockLocation(),
        destination: mockLocation()
      });
      expect(getState().currentRoute).toEqual(route);

      // Clear
      locationStore.clearRoute();
      expect(getState().currentRoute).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      // Trigger an error to set error state
      vi.spyOn(LocationService.prototype, 'getCurrentLocation').mockRejectedValue(new Error('test error'));

      // Await the rejected promise to ensure store's catch handler has run
      await expect(locationStore.getCurrentLocation()).rejects.toThrow('test error');

      // Error should now be set
      expect(getState().error).toBe('test error');

      locationStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Mock getCurrentLocation to succeed and set currentLocation
      const loc = mockLocation();
      vi.spyOn(LocationService.prototype, 'getCurrentLocation').mockResolvedValue(loc);

      await locationStore.getCurrentLocation();
      expect(getState().currentLocation).toEqual(loc);

      // Set selectedPlace
      locationStore.selectPlace(mockPlace());
      expect(getState().selectedPlace).not.toBeNull();

      // Start tracking to set watchId and isTracking
      let watchSuccessCb: any;
      geolocationMock.watchPosition.mockImplementation((success, error) => {
        watchSuccessCb = success;
        return 789;
      });
      locationStore.trackLocation(true);
      // Simulate position update
      watchSuccessCb({
        coords: { latitude: 10, longitude: 20, accuracy: 5, altitude: 100, heading: 90, speed: 1.5 },
        timestamp: Date.now()
      });
      expect(getState().isTracking).toBe(true);

      // Now reset
      locationStore.reset();

      const state = getState();
      expect(state.currentLocation).toBeNull();
      expect(state.nearbyPlaces).toEqual([]);
      expect(state.selectedPlace).toBeNull();
      expect(state.currentRoute).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.isTracking).toBe(false);

      // Verify watch clear was called
      expect(geolocationMock.clearWatch).toHaveBeenCalledWith(789);
    });

    it('clears watch even if not tracking', () => {
      // No watch started
      locationStore.reset();
      expect(geolocationMock.clearWatch).not.toHaveBeenCalled();
    });
  });
});
