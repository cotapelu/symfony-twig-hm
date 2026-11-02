// Restaurant Service - Connects to Kotlin RestaurantBooking API
import { getApiUrl } from '../config/api';

export interface Restaurant {
  id: string;
  name: string;
  image: string;
  images: string[];
  rating: number;
  reviewCount: number;
  address: string;
  city: string;
  cuisine: string[];
  priceRange: 'low' | 'medium' | 'high';
  openingHours: string;
  features: string[];
  availableTables: number;
}

export interface Table {
  id: string;
  restaurantId: string;
  name: string;
  capacity: number;
  location: 'indoor' | 'outdoor' | 'vip';
  available: boolean;
}

export interface TimeSlot {
  id: string;
  time: string;
  available: boolean;
}

export interface RestaurantSearchParams {
  city: string;
  cuisine?: string;
  priceRange?: string;
  date?: string;
  time?: string;
  guests: number;
}

export interface RestaurantBooking {
  id: string;
  restaurantId: string;
  tableId: string;
  userId: string;
  date: string;
  time: string;
  guests: number;
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled';
  specialRequests?: string;
}

class RestaurantsService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async searchRestaurants(params: RestaurantSearchParams): Promise<Restaurant[]> {
    const url = getApiUrl('restaurants', '/api/restaurants/search');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(params),
    });
    if (!response.ok) throw new Error('Failed to search restaurants');
    const data = await response.json();
    return data.restaurants || [];
  }

  async getRestaurant(id: string): Promise<Restaurant> {
    const url = getApiUrl('restaurants', `/api/restaurants/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch restaurant');
    return response.json();
  }

  async getAvailableTables(restaurantId: string, date: string, time: string, guests: number): Promise<Table[]> {
    const url = getApiUrl('restaurants', `/api/restaurants/${restaurantId}/tables?date=${date}&time=${time}&guests=${guests}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch tables');
    const data = await response.json();
    return data.tables || [];
  }

  async getTimeSlots(restaurantId: string, date: string, guests: number): Promise<TimeSlot[]> {
    const url = getApiUrl('restaurants', `/api/restaurants/${restaurantId}/timeslots?date=${date}&guests=${guests}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch time slots');
    const data = await response.json();
    return data.slots || [];
  }

  async bookTable(restaurantId: string, tableId: string, date: string, time: string, guests: number, specialRequests?: string): Promise<RestaurantBooking> {
    const url = getApiUrl('restaurants', '/api/restaurants/book');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ restaurantId, tableId, date, time, guests, specialRequests }),
    });
    if (!response.ok) throw new Error('Failed to book table');
    return response.json();
  }

  async getMyBookings(): Promise<RestaurantBooking[]> {
    const url = getApiUrl('restaurants', '/api/restaurants/bookings/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch bookings');
    const data = await response.json();
    return data.bookings || [];
  }

  async cancelBooking(id: string): Promise<RestaurantBooking> {
    const url = getApiUrl('restaurants', `/api/restaurants/bookings/${id}/cancel`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to cancel booking');
    return response.json();
  }
}

export const restaurantsService = new RestaurantsService();
