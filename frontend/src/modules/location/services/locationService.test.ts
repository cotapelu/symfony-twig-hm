import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { LocationService } from './locationService';
import { __testing__ } from './locationService';

// Suppress console logs
vi.spyOn(console, 'info').mockImplementation(() => {});
vi.spyOn(console, 'warn').mockImplementation(() => {});
vi.spyOn(console, 'error').mockImplementation(() => {});

describe('LocationService', () => {
  let service: LocationService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new LocationService();
    // Mock geolocation if needed
    (global as any).navigator = { geolocation: {} };
  });

  describe('getCurrentLocation', () => {
    it('should reject if geolocation not supported', async () => {
      delete (global as any).navigator.geolocation;
      await expect(service.getCurrentLocation()).rejects.toThrow('Geolocation is not supported');
    });

    it('should get current position', async () => {
      const position = {
        coords: {
          latitude: 10.0,
          longitude: 20.0,
          accuracy: 5,
          altitude: 10,
          heading: 90,
          speed: 1.5,
          altitudeAccuracy: null
        },
        timestamp: Date.now()
      };
      const getCurrentPosition = vi.fn().mockImplementation((success) => success(position));
      (global as any).navigator.geolocation = { getCurrentPosition };

      const result = await service.getCurrentLocation();

      expect(result).toMatchObject({
        latitude: 10.0,
        longitude: 20.0,
        accuracy: 5,
        altitude: 10,
        heading: 90,
        speed: 1.5
      });
      expect(getCurrentPosition).toHaveBeenCalledWith(expect.any(Function), expect.any(Function), { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    });

    it('should reject on geolocation error', async () => {
      const getCurrentPosition = vi.fn().mockImplementation((_success, error) => error({ message: 'Permission denied' }));
      (global as any).navigator.geolocation = { getCurrentPosition };

      await expect(service.getCurrentLocation()).rejects.toThrow('Permission denied');
    });
  });

  describe('getNearbyPlaces', () => {
    it('should fetch nearby places with default pagination radius', async () => {
      const result = { items: [{ id: 'p1', name: 'Park' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getNearbyPlaces();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/location/nearby?radius=5000');
    });

    it('should fetch with custom radius', async () => {
      const result = { items: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getNearbyPlaces(1000);

      expect(global.fetch).toHaveBeenCalledWith('/api/location/nearby?radius=1000');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getNearbyPlaces()).rejects.toThrow('Failed to fetch nearby places');
    });
  });

  describe('searchNearby', () => {
    it('should search nearby places', async () => {
      const places = [{ id: 'p1', name: 'Store' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(places) });

      const result = await service.searchNearby('coffee');

      expect(result).toEqual(places);
      const url = (global.fetch as any).mock.calls[0][0];
      expect(url).toContain('/api/location/nearby');
      expect(url).toContain('q=coffee');
      expect(url).toContain('radius=5000');
    });

    it('should encode query', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue([]) });

      await service.searchNearby('a b');

      const url = (global.fetch as any).mock.calls[0][0];
      expect(url).toContain('/api/location/nearby');
      expect(url).toContain('q=a+b');
      expect(url).toContain('radius=5000');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.searchNearby('q')).rejects.toThrow('Failed to search nearby places');
    });
  });

  describe('geocode', () => {
    it('should geocode address', async () => {
      const location = { latitude: 10, longitude: 20 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(location) });

      const result = await service.geocode('123 Main St');

      expect(result).toEqual(location);
      expect(global.fetch).toHaveBeenCalledWith('/api/location/geocode?q=123%20Main%20St');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.geocode('unknown')).rejects.toThrow('Geocoding failed');
    });
  });

  describe('reverseGeocode', () => {
    it('should reverse geocode', async () => {
      const response = { address: '123 Main St' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(response) });

      const result = await service.reverseGeocode({ latitude: 10, longitude: 20 });

      expect(result).toBe('123 Main St');
      expect(global.fetch).toHaveBeenCalledWith('/api/location/reverse-geocode?lat=10&lng=20');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.reverseGeocode({ latitude: 10, longitude: 20 })).rejects.toThrow('Reverse geocoding failed');
    });
  });

  describe('shareLocation', () => {
    it('should share location', async () => {
      const res = { id: 'share1' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(res) });

      const result = await service.shareLocation({ recipientId: 'u1', duration: 60 });

      expect(result).toEqual(res);
      expect(global.fetch).toHaveBeenCalledWith('/api/location/share', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"recipientId":"u1"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.shareLocation({ recipientId: 'u1', duration: 60 })).rejects.toThrow('Failed to share location');
    });
  });

  describe('getSharedLocations', () => {
    it('should fetch shared locations', async () => {
      const shared = [{ id: 's1', from: 'u1' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(shared) });

      const result = await service.getSharedLocations();

      expect(result).toEqual(shared);
      expect(global.fetch).toHaveBeenCalledWith('/api/location/shared');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getSharedLocations()).rejects.toThrow('Failed to fetch shared locations');
    });
  });

  describe('getDirections', () => {
    it('should get directions', async () => {
      const directions = { steps: [] };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(directions) });

      const result = await service.getDirections({
        origin: { latitude: 10, longitude: 20 },
        destination: { latitude: 30, longitude: 40 }
      });

      expect(result).toEqual(directions);
      expect(global.fetch).toHaveBeenCalledWith('/api/location/directions', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"origin":')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getDirections({
        origin: { latitude: 10, longitude: 20 },
        destination: { latitude: 30, longitude: 40 }
      })).rejects.toThrow('Failed to get directions');
    });
  });
});
