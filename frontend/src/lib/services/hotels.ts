// Travel/Hotels Service - Connects to Kotlin Hotels API
import { getApiUrl } from '../config/api';

export interface Hotel {
  id: string;
  name: string;
  image: string;
  images: string[];
  rating: number;
  reviewCount: number;
  address: string;
  city: string;
  price: number;
  amenities: string[];
  roomTypes: RoomType[];
}

export interface RoomType {
  id: string;
  name: string;
  price: number;
  maxOccupancy: number;
  amenities: string[];
}

export interface HotelBooking {
  id: string;
  hotelId: string;
  roomTypeId: string;
  checkIn: string;
  checkOut: string;
  guests: number;
  totalPrice: number;
  status: 'confirmed' | 'checked_in' | 'checked_out' | 'cancelled';
}

export interface HotelSearchParams {
  city: string;
  checkIn: string;
  checkOut: string;
  guests: number;
  minPrice?: number;
  maxPrice?: number;
  rating?: number;
}

class HotelsService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async searchHotels(params: HotelSearchParams): Promise<Hotel[]> {
    const url = getApiUrl('hotels', '/api/hotels/search');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(params),
    });
    if (!response.ok) throw new Error('Failed to search hotels');
    const data = await response.json();
    return data.hotels || [];
  }

  async getHotel(id: string): Promise<Hotel> {
    const url = getApiUrl('hotels', `/api/hotels/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch hotel');
    return response.json();
  }

  async bookHotel(hotelId: string, roomTypeId: string, checkIn: string, checkOut: string, guests: number): Promise<HotelBooking> {
    const url = getApiUrl('hotels', '/api/hotels/book');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ hotelId, roomTypeId, checkIn, checkOut, guests }),
    });
    if (!response.ok) throw new Error('Failed to book hotel');
    return response.json();
  }

  async getMyBookings(): Promise<HotelBooking[]> {
    const url = getApiUrl('hotels', '/api/hotels/bookings/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch bookings');
    const data = await response.json();
    return data.bookings || [];
  }

  async cancelBooking(id: string): Promise<HotelBooking> {
    const url = getApiUrl('hotels', `/api/hotels/bookings/${id}/cancel`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to cancel booking');
    return response.json();
  }
}

export const hotelsService = new HotelsService();
