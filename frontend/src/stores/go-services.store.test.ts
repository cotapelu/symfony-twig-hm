import { describe, it, expect, vi, beforeEach } from 'vitest';
import { goServicesStore } from './go-all';
import { Go } from '../lib/api';

vi.mock('../lib/api', () => ({
  Go: {
    createBooking: vi.fn(),
    getBookings: vi.fn(),
    getNearbyServices: vi.fn(),
    calculateRoute: vi.fn(),
    getServiceProviders: vi.fn(),
  }
}));

describe('GoServicesStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    goServicesStore.reset();
    goServicesStore.subscribe(s => { state = s; });
  });

  describe('createBooking', () => {
    it('should create booking and prepend to bookings', async () => {
      const booking = { id: 'b1' };
      (Go.createBooking as any).mockResolvedValue(booking);
      state.bookings = [];

      const result = await goServicesStore.createBooking({});

      expect(result).toBe(booking);
      expect(state.bookings).toContain(booking);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.createBooking as any).mockRejectedValue(new Error('Create failed'));
      await expect(goServicesStore.createBooking({})).rejects.toThrow('Create failed');
      expect(state.error).toBe('Create failed');
    });
  });

  describe('getBookings', () => {
    it('should fetch bookings', async () => {
      const bookings = [{ id: 'b1' }, { id: 'b2' }];
      (Go.getBookings as any).mockResolvedValue(bookings);

      const result = await goServicesStore.getBookings();

      expect(result).toEqual(bookings);
      expect(state.bookings).toEqual(bookings);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.getBookings as any).mockRejectedValue(new Error('Get failed'));
      await expect(goServicesStore.getBookings()).rejects.toThrow('Get failed');
      expect(state.error).toBe('Get failed');
    });
  });

  describe('getNearbyServices', () => {
    it('should fetch nearby services', async () => {
      const services = [{ id: 's1' }];
      (Go.getNearbyServices as any).mockResolvedValue(services);
      state.nearbyServices = [];

      const result = await goServicesStore.getNearbyServices(10, 20, 5);

      expect(result).toEqual(services);
      expect(state.nearbyServices).toEqual(services);
      expect(state.loading).toBe(false);
    });

    it('should use default radius', async () => {
      const services = [{ id: 's1' }];
      (Go.getNearbyServices as any).mockResolvedValue(services);

      await goServicesStore.getNearbyServices(10, 20);

      expect(Go.getNearbyServices).toHaveBeenCalledWith({ latitude: 10, longitude: 20, radius: 5 });
    });

    it('should handle error', async () => {
      (Go.getNearbyServices as any).mockRejectedValue(new Error('Nearby failed'));
      await expect(goServicesStore.getNearbyServices(1,2)).rejects.toThrow('Nearby failed');
      expect(state.error).toBe('Nearby failed');
    });
  });

  describe('calculateRoute', () => {
    it('should calculate route and store routeInfo', async () => {
      const route = { distance: 100 };
      (Go.calculateRoute as any).mockResolvedValue(route);

      const result = await goServicesStore.calculateRoute('origin', 'dest');

      expect(result).toEqual(route);
      expect(state.routeInfo).toEqual(route);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.calculateRoute as any).mockRejectedValue(new Error('Route failed'));
      await expect(goServicesStore.calculateRoute('o','d')).rejects.toThrow('Route failed');
      expect(state.error).toBe('Route failed');
    });
  });

  describe('getServiceProviders', () => {
    it('should fetch providers', async () => {
      const providers = [{ id: 'p1' }];
      (Go.getServiceProviders as any).mockResolvedValue(providers);

      const result = await goServicesStore.getServiceProviders('cleaning');

      expect(result).toEqual(providers);
      expect(state.providers).toEqual(providers);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.getServiceProviders as any).mockRejectedValue(new Error('Prov failed'));
      await expect(goServicesStore.getServiceProviders('test')).rejects.toThrow('Prov failed');
      expect(state.error).toBe('Prov failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.bookings = [{ id: 'b1' }];
      state.nearbyServices = [{ id: 's1' }];
      state.routeInfo = { distance: 10 };
      state.providers = [{ id: 'p1' }];
      state.error = 'err';
      goServicesStore.reset();
      expect(state.bookings).toEqual([]);
      expect(state.nearbyServices).toEqual([]);
      expect(state.routeInfo).toBeNull();
      expect(state.providers).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Go.createBooking as any).mockRejectedValue(new Error('test'));
      try { await goServicesStore.createBooking({}); } catch (e) {}
      expect(state.error).toBe('test');
      goServicesStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
