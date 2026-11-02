// Car Rental Service - Connects to Kotlin CarRental API
import { getApiUrl } from '../config/api';

export interface Car {
  id: string;
  name: string;
  model: string;
  brand: string;
  type: 'economy' | 'compact' | 'midsize' | 'suv' | 'luxury' | 'van';
  seats: number;
  transmission: 'auto' | 'manual';
  fuel: string;
  pricePerDay: number;
  images: string[];
  location: string;
  available: boolean;
  features: string[];
}

export interface CarSearchParams {
  location: string;
  pickupDate: string;
  returnDate: string;
  carType?: string;
  seats?: number;
  maxPrice?: number;
}

export interface CarBooking {
  id: string;
  carId: string;
  userId: string;
  pickupLocation: string;
  dropoffLocation: string;
  pickupDate: string;
  dropoffDate: string;
  totalPrice: number;
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled';
}

class CarsService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async searchCars(params: CarSearchParams): Promise<Car[]> {
    const url = getApiUrl('cars', '/api/cars/search');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(params),
    });
    if (!response.ok) throw new Error('Failed to search cars');
    const data = await response.json();
    return data.cars || [];
  }

  async getCar(id: string): Promise<Car> {
    const url = getApiUrl('cars', `/api/cars/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch car');
    return response.json();
  }

  async bookCar(carId: string, params: {
    pickupLocation: string;
    dropoffLocation: string;
    pickupDate: string;
    dropoffDate: string;
  }): Promise<CarBooking> {
    const url = getApiUrl('cars', '/api/cars/book');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ carId, ...params }),
    });
    if (!response.ok) throw new Error('Failed to book car');
    return response.json();
  }

  async getMyBookings(): Promise<CarBooking[]> {
    const url = getApiUrl('cars', '/api/cars/bookings/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch bookings');
    const data = await response.json();
    return data.bookings || [];
  }

  async cancelBooking(id: string): Promise<CarBooking> {
    const url = getApiUrl('cars', `/api/cars/bookings/${id}/cancel`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to cancel booking');
    return response.json();
  }
}

export const carsService = new CarsService();
