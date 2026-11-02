import { writable, derived } from 'svelte/store';
import type { Car, CarRental, CarState, CreateCarRequest, CreateRentalRequest } from '../contracts/carsTypes';
import { carsService } from '../services/carsService';

const initialState: CarState = {
  cars: [],
  rentals: [],
  loading: false,
  error: null
};

function createCarsStore() {
  const { subscribe, set, update } = writable<CarState>(initialState);

  return {
    subscribe,

    async loadCars() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const cars = await carsService.getCars();
        update(s => ({ ...s, cars, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadCar(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const car = await carsService.getCar(id);
        update(s => {
          const exists = s.cars.find(c => c.id === id);
          return {
            ...s,
            cars: exists ? s.cars.map(c => c.id === id ? car : c) : [...s.cars, car],
            loading: false
          };
        });
        return car;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async createCar(request: CreateCarRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const car = await carsService.createCar(request);
        update(s => ({ ...s, cars: [...s.cars, car], loading: false }));
        return car;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async updateCar(id: string, request: Partial<CreateCarRequest>) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const car = await carsService.updateCar(id, request);
        update(s => ({ ...s, cars: s.cars.map(c => c.id === id ? car : c), loading: false }));
        return car;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async deleteCar(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        await carsService.deleteCar(id);
        update(s => ({ ...s, cars: s.cars.filter(c => c.id !== id), loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadRentals() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const rentals = await carsService.getRentals();
        update(s => ({ ...s, rentals, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async createRental(request: CreateRentalRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const rental = await carsService.createRental(request);
        update(s => ({ ...s, rentals: [...s.rentals, rental], loading: false }));
        return rental;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async cancelRental(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const rental = await carsService.cancelRental(id);
        update(s => ({ ...s, rentals: s.rentals.map(r => r.id === id ? rental : r), loading: false }));
        return rental;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const carsStore = createCarsStore();

export const cars = derived(carsStore, $s => $s.cars);
export const rentals = derived(carsStore, $s => $s.rentals);
export const availableCars = derived(carsStore, $s => $s.cars.filter(c => c.status === 'available'));
export const carsLoading = derived(carsStore, $s => $s.loading);
export const carsError = derived(carsStore, $s => $s.error);
