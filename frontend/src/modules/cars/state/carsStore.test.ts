import { describe, it, expect, vi, beforeEach } from 'vitest';
import { carsStore, cars, rentals, availableCars, carsLoading, carsError } from './carsStore';
import { carsService } from '../services/carsService';
import type { Car, Rental, CreateCarRequest, CreateRentalRequest } from '../contracts/carsTypes';

function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date().toISOString();

const mockCar = (overrides: Partial<Car> = {}): Car => ({
  id: `car-${Math.random().toString(36).substr(2, 5)}`,
  make: 'Toyota',
  model: 'Camry',
  year: 2024,
  licensePlate: `ABC${Math.floor(Math.random() * 10000)}`,
  color: 'White',
  mileage: 0,
  dailyRate: 50,
  status: 'available' as const,
  features: ['GPS', 'Bluetooth'],
  createdAt: now,
  ...overrides
});

const mockRental = (overrides: Partial<Rental> = {}): Rental => ({
  id: `rent-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  carId: 'car-1',
  car: {} as Car,
  startDate: now,
  endDate: now,
  totalAmount: 200,
  status: 'active' as const,
  createdAt: now,
  ...overrides
});

describe('CarsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    carsStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and defaults', () => {
      const state = getCurrentState(carsStore);
      expect(state.cars).toEqual([]);
      expect(state.rentals).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived cars matches state.cars', () => expect(getCurrentState(cars)).toEqual([]));
    it('derived rentals matches state.rentals', () => expect(getCurrentState(rentals)).toEqual([]));
    it('derived carsLoading matches state.loading', () => expect(getCurrentState(carsLoading)).toBe(false));
    it('derived carsError matches state.error', () => expect(getCurrentState(carsError)).toBeNull());

    it('derived availableCars filters by status', async () => {
      const available = mockCar({ status: 'available' as const });
      const rented = mockCar({ status: 'rented' as const });
      vi.spyOn(carsService, 'getCars').mockResolvedValue([available, rented]);
      await carsStore.loadCars();
      const availList = getCurrentState(availableCars);
      expect(availList).toContainEqual(available);
      expect(availList.length).toBe(1);
    });
  });

  describe('loadCars', () => {
    it('loads cars', async () => {
      const crs = [mockCar(), mockCar()];
      vi.spyOn(carsService, 'getCars').mockResolvedValue(crs);
      await carsStore.loadCars();
      const state = getCurrentState(carsStore);
      expect(state.cars).toEqual(crs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(carsService, 'getCars').mockReturnValue(p as any);
      const loadPromise = carsStore.loadCars();
      await new Promise(process.nextTick);
      expect(getCurrentState(carsStore).loading).toBe(true);
      resolvePromise([mockCar()]);
      await loadPromise;
      expect(getCurrentState(carsStore).loading).toBe(false);
    });

    it('updates cars derived on success', async () => {
      const crs = [mockCar()];
      vi.spyOn(carsService, 'getCars').mockResolvedValue(crs);
      await carsStore.loadCars();
      let c: Car[] = [];
      const unsub = cars.subscribe(s => { c = s; });
      expect(c).toEqual(crs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(carsService, 'getCars').mockResolvedValue([]);
      await carsStore.loadCars();
      expect(getCurrentState(carsStore).cars).toEqual([]);
      expect(getCurrentState(carsStore).loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(carsService, 'getCars').mockRejectedValue(new Error('Fail'));
      await carsStore.loadCars();
      const state = getCurrentState(carsStore);
      expect(state.error).toBe('Fail');
      expect(state.loading).toBe(false);
      expect(state.cars).toEqual([]);
    });
  });

  describe('loadCar', () => {
    it('loads car and adds to list', async () => {
      const car = mockCar({ id: 'car-x' });
      vi.spyOn(carsService, 'getCar').mockResolvedValue(car);
      const result = await carsStore.loadCar('car-x');
      expect(result).toEqual(car);
      let state = getCurrentState(carsStore);
      expect(state.cars.some(c => c.id === 'car-x')).toBe(true);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('updates existing car', async () => {
      const orig = mockCar({ id: 'c1', make: 'Old' });
      const upd = mockCar({ id: 'c1', make: 'New' });
      vi.spyOn(carsService, 'getCar').mockResolvedValueOnce(orig);
      await carsStore.loadCar('c1');
      vi.spyOn(carsService, 'getCar').mockResolvedValueOnce(upd);
      await carsStore.loadCar('c1');
      let state = getCurrentState(carsStore);
      expect(state.cars.find(c => c.id === 'c1')?.make).toBe('New');
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(carsService, 'getCar').mockReturnValue(p as any);
      const loadPromise = carsStore.loadCar('car-x');
      await new Promise(process.nextTick);
      expect(getCurrentState(carsStore).loading).toBe(true);
      resolvePromise(mockCar({ id: 'car-x' }));
      await loadPromise;
      expect(getCurrentState(carsStore).loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(carsService, 'getCar').mockRejectedValue(new Error('Missing'));
      try {
        await carsStore.loadCar('missing');
      } catch {}
      const state = getCurrentState(carsStore);
      expect(state.error).toBe('Missing');
      expect(state.loading).toBe(false);
    });
  });

  describe('createCar', () => {
    it('adds car', async () => {
      const car = mockCar();
      vi.spyOn(carsService, 'createCar').mockResolvedValue(car);
      const request: CreateCarRequest = { make: 'Honda', model: 'Civic', year: 2024, licensePlate: 'XYZ123', color: 'Blue', dailyRate: 40, status: 'available', features: [] };
      const result = await carsStore.createCar(request);
      expect(result).toEqual(car);
      let state = getCurrentState(carsStore);
      expect(state.cars[0]).toEqual(car);
    });

    it('handles error', async () => {
      vi.spyOn(carsService, 'createCar').mockRejectedValue(new Error('Bad request'));
      try {
        await carsStore.createCar({ make: '', model: '', year: 2024, licensePlate: '', dailyRate: 0, status: 'available', features: [] });
      } catch {}
      const state = getCurrentState(carsStore);
      expect(state.error).toBe('Bad request');
    });
  });

  describe('updateCar', () => {
    it('updates car in list', async () => {
      const orig = mockCar({ id: 'c1', make: 'Old' });
      const upd = mockCar({ id: 'c1', make: 'New' });
      // Load original car into store
      vi.spyOn(carsService, 'getCar').mockResolvedValue(orig);
      await carsStore.loadCar('c1');
      // Now mock updateCar and call update
      vi.spyOn(carsService, 'updateCar').mockResolvedValue(upd);
      const result = await carsStore.updateCar('c1', { make: 'New' });
      expect(result).toEqual(upd);
      let state = getCurrentState(carsStore);
      expect(state.cars.find(c => c.id === 'c1')?.make).toBe('New');
    });

    it('handles error', async () => {
      vi.spyOn(carsService, 'updateCar').mockRejectedValue(new Error('Update failed'));
      try {
        await carsStore.updateCar('c1', {});
      } catch {}
      const state = getCurrentState(carsStore);
      expect(state.error).toBe('Update failed');
    });
  });

  describe('deleteCar', () => {
    it('removes car', async () => {
      const car = mockCar({ id: 'c1' });
      vi.spyOn(carsService, 'deleteCar').mockResolvedValue(undefined);
      // Add car first via createCar
      vi.spyOn(carsService, 'createCar').mockResolvedValue(car);
      await carsStore.createCar({ make: 'Honda', model: 'Civic', year: 2024, licensePlate: 'XYZ', dailyRate: 40, status: 'available', features: [] });
      // Verify it's there
      let state = getCurrentState(carsStore);
      expect(state.cars.some(c => c.id === 'c1')).toBe(true);
      // Now delete
      await carsStore.deleteCar('c1');
      state = getCurrentState(carsStore);
      expect(state.cars.find(c => c.id === 'c1')).toBeUndefined();
    });

    it('handles error', async () => {
      vi.spyOn(carsService, 'deleteCar').mockRejectedValue(new Error('Delete failed'));
      try {
        await carsStore.deleteCar('c1');
      } catch {}
      const state = getCurrentState(carsStore);
      expect(state.error).toBe('Delete failed');
    });
  });

  describe('loadRentals', () => {
    it('loads rentals', async () => {
      const rnts = [mockRental(), mockRental()];
      vi.spyOn(carsService, 'getRentals').mockResolvedValue(rnts);
      await carsStore.loadRentals();
      const state = getCurrentState(carsStore);
      expect(state.rentals).toEqual(rnts);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(carsService, 'getRentals').mockRejectedValue(new Error('Fail'));
      await carsStore.loadRentals();
      const state = getCurrentState(carsStore);
      expect(state.error).toBe('Fail');
      expect(state.rentals).toEqual([]);
    });
  });

  describe('createRental', () => {
    it('creates rental and adds to list', async () => {
      const rental = mockRental();
      vi.spyOn(carsService, 'createRental').mockResolvedValue(rental);
      const request: CreateRentalRequest = { carId: 'car-1', startDate: now, endDate: now, totalAmount: 200 };
      const result = await carsStore.createRental(request);
      expect(result).toEqual(rental);
      let state = getCurrentState(carsStore);
      expect(state.rentals).toContainEqual(rental);
    });

    it('handles error', async () => {
      vi.spyOn(carsService, 'createRental').mockRejectedValue(new Error('Unavailable'));
      try {
        await carsStore.createRental({ carId: 'car-1', startDate: now, endDate: now, totalAmount: 200 });
      } catch {}
      const state = getCurrentState(carsStore);
      expect(state.error).toBe('Unavailable');
    });
  });

  describe('cancelRental', () => {
    it('cancels rental and updates list', async () => {
      const original = mockRental({ id: 'r1', status: 'active' as const });
      const cancelled = { ...original, status: 'cancelled' as const };
      // Add a rental first
      vi.spyOn(carsService, 'createRental').mockResolvedValue(original);
      await carsStore.createRental({ carId: 'car-1', startDate: now, endDate: now, totalAmount: 200 });
      let state = getCurrentState(carsStore);
      expect(state.rentals.some(r => r.id === 'r1')).toBe(true);
      // Mock cancel
      vi.spyOn(carsService, 'cancelRental').mockResolvedValue(cancelled);
      const result = await carsStore.cancelRental('r1');
      expect(result).toEqual(cancelled);
      state = getCurrentState(carsStore);
      expect(state.rentals.find(r => r.id === 'r1')?.status).toBe('cancelled');
    });

    it('handles error', async () => {
      vi.spyOn(carsService, 'cancelRental').mockRejectedValue(new Error('Cancel failed'));
      try {
        await carsStore.cancelRental('r1');
      } catch {}
      const state = getCurrentState(carsStore);
      expect(state.error).toBe('Cancel failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(carsService, 'getCars').mockRejectedValue(new Error('test'));
      try { await carsStore.loadCars(); } catch {}
      let state = getCurrentState(carsStore);
      expect(state.error).toBe('test');
      carsStore.clearError();
      state = getCurrentState(carsStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const crs = [mockCar()];
      vi.spyOn(carsService, 'getCars').mockResolvedValue(crs);
      await carsStore.loadCars();
      let state = getCurrentState(carsStore);
      expect(state.cars.length).toBeGreaterThan(0);
      carsStore.reset();
      state = getCurrentState(carsStore);
      expect(state.cars).toEqual([]);
      expect(state.rentals).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
