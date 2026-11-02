import { describe, it, expect, vi, beforeEach } from 'vitest';
import { goLocationStore } from './go-all';
import { Go } from '../lib/api';

vi.mock('../lib/api', () => ({
  Go: {
    getNearbyUsers: vi.fn(),
    updateLocation: vi.fn(),
    setLocationSharing: vi.fn(),
    getGeofence: vi.fn(),
    getGeofences: vi.fn(), // not used but kept for compatibility
  }
}));

describe('GoLocationStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    goLocationStore.reset();
    goLocationStore.subscribe(s => { state = s; });
  });

  describe('updateLocation', () => {
    it('should update user location', async () => {
      (Go.updateLocation as any).mockResolvedValue(undefined);
      state.userLocation = null;

      await goLocationStore.updateLocation('u1', 10, 20);

      expect(Go.updateLocation).toHaveBeenCalledWith({ latitude: 10, longitude: 20 });
      expect(state.userLocation).toEqual({ lat: 10, lng: 20, updatedAt: expect.any(Number) });
    });

    it('should handle error', async () => {
      (Go.updateLocation as any).mockRejectedValue(new Error('Update failed'));
      await expect(goLocationStore.updateLocation('u1',1,2)).rejects.toThrow('Update failed');
      expect(state.error).toBe('Update failed');
    });
  });

  describe('getNearbyUsers', () => {
    it('should fetch nearby users', async () => {
      const users = [{ id: 'u1' }];
      (Go.getNearbyUsers as any).mockResolvedValue(users);

      const result = await goLocationStore.getNearbyUsers(10, 20, 5);

      expect(result).toEqual(users);
      expect(state.nearbyUsers).toEqual(users);
      expect(state.loading).toBe(false);
    });

    it('should use default radius', async () => {
      const users = [{ id: 'u1' }];
      (Go.getNearbyUsers as any).mockResolvedValue(users);

      await goLocationStore.getNearbyUsers(10, 20);

      expect(Go.getNearbyUsers).toHaveBeenCalledWith(10, 20, 5);
    });

    it('should handle error', async () => {
      (Go.getNearbyUsers as any).mockRejectedValue(new Error('Fetch failed'));
      await expect(goLocationStore.getNearbyUsers(1,2)).rejects.toThrow('Fetch failed');
      expect(state.error).toBe('Fetch failed');
    });
  });

  describe('setLocationSharing', () => {
    it('should enable location sharing', async () => {
      (Go.setLocationSharing as any).mockResolvedValue(undefined);

      await goLocationStore.setLocationSharing(true);

      expect(Go.setLocationSharing).toHaveBeenCalledWith(true);
      expect(state.locationSharingEnabled).toBe(true);
    });

    it('should disable location sharing', async () => {
      (Go.setLocationSharing as any).mockResolvedValue(undefined);
      state.locationSharingEnabled = true;

      await goLocationStore.setLocationSharing(false);

      expect(state.locationSharingEnabled).toBe(false);
    });

    it('should handle error', async () => {
      (Go.setLocationSharing as any).mockRejectedValue(new Error('Set failed'));
      await expect(goLocationStore.setLocationSharing(true)).rejects.toThrow('Set failed');
      expect(state.error).toBe('Set failed');
    });
  });

  describe('getGeofence', () => {
    it('should fetch geofence for user', async () => {
      const geofence = { radius: 100 };
      (Go.getGeofence as any).mockResolvedValue(geofence);

      const result = await goLocationStore.getGeofence('u1');

      expect(result).toEqual(geofence);
      expect(state.geofence).toEqual(geofence);
    });

    it('should handle error', async () => {
      (Go.getGeofence as any).mockRejectedValue(new Error('Geo failed'));
      await expect(goLocationStore.getGeofence('u1')).rejects.toThrow('Geo failed');
      expect(state.error).toBe('Geo failed');
    });
  });

  describe('getGeofences', () => {
    it('should fetch all geofences for current user', async () => {
      const geofence = { id: 'g1' };
      (Go.getGeofence as any).mockResolvedValue(geofence);

      const result = await goLocationStore.getGeofences();

      expect(result).toEqual([geofence]);
      expect(state.geofences).toEqual([geofence]);
      expect(Go.getGeofence).toHaveBeenCalledWith('current-user-id');
    });

    it('should return empty array if no geofence', async () => {
      (Go.getGeofence as any).mockResolvedValue(undefined);

      const result = await goLocationStore.getGeofences();

      expect(result).toEqual([]);
      expect(state.geofences).toEqual([]);
    });

    it('should handle error', async () => {
      (Go.getGeofence as any).mockRejectedValue(new Error('Geo list failed'));
      const result = await goLocationStore.getGeofences();
      expect(result).toEqual([]);
      expect(state.error).toBe('Geo list failed');
    });
  });

  describe('shareLocation', () => {
    it('should enable location sharing via setLocationSharing', async () => {
      (Go.setLocationSharing as any).mockResolvedValue(undefined);
      state.locationSharingEnabled = false;

      await goLocationStore.shareLocation('u1');

      expect(Go.setLocationSharing).toHaveBeenCalledWith(true);
      expect(state.locationSharingEnabled).toBe(true);
    });

    it('should handle error from setLocationSharing', async () => {
      (Go.setLocationSharing as any).mockRejectedValue(new Error('Share failed'));
      await expect(goLocationStore.shareLocation('u1')).rejects.toThrow('Share failed');
      expect(state.error).toBe('Share failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.nearbyUsers = [{ id: 'u1' }];
      state.userLocation = { lat: 1, lng: 2, updatedAt: Date.now() };
      state.locationSharingEnabled = true;
      state.geofence = { radius: 100 };
      state.geofences = [{ id: 'g1' }];
      state.error = 'err';
      goLocationStore.reset();
      expect(state.nearbyUsers).toEqual([]);
      expect(state.userLocation).toBeNull();
      expect(state.locationSharingEnabled).toBe(false);
      expect(state.geofence).toBeNull();
      expect(state.geofences).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Go.updateLocation as any).mockRejectedValue(new Error('test'));
      try { await goLocationStore.updateLocation('u1',1,2); } catch (e) {}
      expect(state.error).toBe('test');
      goLocationStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
