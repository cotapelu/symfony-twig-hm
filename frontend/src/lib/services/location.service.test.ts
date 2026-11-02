import { describe, it, expect, vi, beforeEach } from 'vitest';
import { locationService } from './location';
import { getApiUrl } from '../config/api';

vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('LocationService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getCurrentLocation', () => {
    it('fetches current location', async () => {
      const location = { lat: 10, lng: 20 };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => location });

      const result = await locationService.getCurrentLocation();

      expect(getApiUrl).toHaveBeenCalledWith('location', '/api/location/current');
      expect(result).toEqual(location);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(locationService.getCurrentLocation()).rejects.toThrow('Failed to get location');
    });
  });

  describe('searchPlaces', () => {
    it('searches places with query', async () => {
      const places = [{ id: 'p1', name: 'Cafe', location: { lat: 1, lng: 2 } }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ places }) });

      const result = await locationService.searchPlaces('cafe');

      expect(getApiUrl).toHaveBeenCalledWith('location', '/api/location/search?q=cafe');
      expect(result).toEqual(places);
    });

    it('returns empty array if no places', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await locationService.searchPlaces('xyz');
      expect(result).toEqual([]);
    });

    it('encodes query string', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await locationService.searchPlaces('a b');
      const urlParam = getApiUrl.mock.calls[0][1];
      expect(urlParam).toContain('q=a%20b');
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(locationService.searchPlaces('test')).rejects.toThrow('Failed to search places');
    });
  });

  describe('getDirections', () => {
    it('fetches route between origin and destination', async () => {
      const route = { distance: '10km', duration: '20min', polyline: 'abc' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => route });
      const origin = { lat: 1, lng: 2 };
      const destination = { lat: 3, lng: 4 };

      const result = await locationService.getDirections(origin, destination);

      expect(getApiUrl).toHaveBeenCalledWith('location', '/api/location/directions');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ origin, destination }),
      });
      expect(result).toEqual(route);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(locationService.getDirections({ lat: 1, lng: 2 }, { lat: 3, lng: 4 })).rejects.toThrow('Failed to get directions');
    });
  });

  describe('getNearbyPlaces', () => {
    it('fetches nearby places with default radius', async () => {
      const places = [{ id: 'p1' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ places }) });
      const location = { lat: 10, lng: 20 };

      const result = await locationService.getNearbyPlaces(location);

      const expectedUrl = '/api/location/nearby?lat=10&lng=20&radius=1000';
      expect(getApiUrl).toHaveBeenCalledWith('location', expectedUrl);
      expect(result).toEqual(places);
    });

    it('uses custom radius', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const location = { lat: 10, lng: 20 };
      await locationService.getNearbyPlaces(location, 500);
      const calledUrl = getApiUrl.mock.calls[0][1];
      expect(calledUrl).toBe('/api/location/nearby?lat=10&lng=20&radius=500');
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await locationService.getNearbyPlaces({ lat: 0, lng: 0 });
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(locationService.getNearbyPlaces({ lat: 0, lng: 0 })).rejects.toThrow('Failed to get nearby places');
    });
  });
});
