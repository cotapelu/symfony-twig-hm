import { describe, it, expect, vi, beforeEach } from 'vitest';
import { CarsService } from './carsService';

global.fetch = vi.fn();

describe('CarsService', () => {
  let service: CarsService;

  beforeEach(() => {
    service = new CarsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getCars', () => {
    it('should fetch all cars', async () => {
      const cars = [{ id: 'c1', make: 'Toyota' }];
      (global.fetch as any).mockResolvedValue(mockResponse(cars));
      const result = await service.getCars();
      expect(result).toEqual(cars);
      expect(fetch).toHaveBeenCalledWith('/api/cars');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getCars()).rejects.toThrow('Failed to fetch cars');
    });
  });

  describe('getCar', () => {
    it('should fetch car by id', async () => {
      const car = { id: 'c1', make: 'Honda' };
      (global.fetch as any).mockResolvedValue(mockResponse(car));
      const result = await service.getCar('c1');
      expect(result).toEqual(car);
      expect(fetch).toHaveBeenCalledWith('/api/cars/c1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getCar('c1')).rejects.toThrow('Failed to fetch car');
    });
  });

  describe('createCar', () => {
    it('should create a car', async () => {
      const created = { id: 'new', make: 'Ford' };
      (global.fetch as any).mockResolvedValue(mockResponse(created));
      const request = { make: 'Ford', model: 'Focus' };
      const result = await service.createCar(request);
      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/cars', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createCar({ make: 'X' })).rejects.toThrow('Failed to create car');
    });
  });

  describe('updateCar', () => {
    it('should update a car', async () => {
      const updated = { id: 'c1', make: 'Tesla' };
      (global.fetch as any).mockResolvedValue(mockResponse(updated));
      const request = { make: 'Tesla' };
      const result = await service.updateCar('c1', request);
      expect(result).toEqual(updated);
      expect(fetch).toHaveBeenCalledWith('/api/cars/c1', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.updateCar('c1', {})).rejects.toThrow('Failed to update car');
    });
  });

  describe('deleteCar', () => {
    it('should delete a car', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.deleteCar('c1');
      expect(fetch).toHaveBeenCalledWith('/api/cars/c1', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.deleteCar('c1')).rejects.toThrow('Failed to delete car');
    });
  });
});
