import type {
  Car,
  CarRental,
  CarState,
  CreateCarRequest,
  CreateRentalRequest
} from '../contracts/carsTypes';

const API_BASE = '/api/cars';

export class CarsService {
  async getCars(): Promise<Car[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch cars');
    return response.json();
  }

  async getCar(id: string): Promise<Car> {
    const response = await fetch(`${API_BASE}/${id}`);
    if (!response.ok) throw new Error('Failed to fetch car');
    return response.json();
  }

  async createCar(request: CreateCarRequest): Promise<Car> {
    const response = await fetch(API_BASE, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create car');
    return response.json();
  }

  async updateCar(id: string, request: Partial<CreateCarRequest>): Promise<Car> {
    const response = await fetch(`${API_BASE}/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to update car');
    return response.json();
  }

  async deleteCar(id: string): Promise<void> {
    const response = await fetch(`${API_BASE}/${id}`, {
      method: 'DELETE'
    });
    if (!response.ok) throw new Error('Failed to delete car');
  }

  async getRentals(): Promise<CarRental[]> {
    const response = await fetch(`${API_BASE}/rentals`);
    if (!response.ok) throw new Error('Failed to fetch rentals');
    return response.json();
  }

  async getRental(id: string): Promise<CarRental> {
    const response = await fetch(`${API_BASE}/rentals/${id}`);
    if (!response.ok) throw new Error('Failed to fetch rental');
    return response.json();
  }

  async createRental(request: CreateRentalRequest): Promise<CarRental> {
    const response = await fetch(`${API_BASE}/rentals`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create rental');
    return response.json();
  }

  async cancelRental(id: string): Promise<CarRental> {
    const response = await fetch(`${API_BASE}/rentals/${id}/cancel`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to cancel rental');
    return response.json();
  }
}

export const carsService = new CarsService();
export default carsService;
