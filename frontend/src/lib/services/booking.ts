// Booking Service - Connects to Go coreservices
import { getApiUrl } from '../config/api';

export interface Booking {
  id: string;
  userId: string;
  serviceId: string;
  serviceName: string;
  serviceType: string;
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled';
  bookingStatus?: string;
  checkInDate?: string;
  checkOutDate?: string;
  hotelId?: string;
  roomTypeId?: string;
  roomType?: string;
  guestCount?: number;
  finalPrice?: number;
  totalAmount: number;
  createdAt: string;
  updatedAt?: string;
  specialRequests?: string;
}

export interface BookingRequest {
  serviceId: string;
  serviceType: string;
  checkInDate?: string;
  checkOutDate?: string;
  notes?: string;
  guestCount?: number;
  roomTypeId?: string;
}

export interface BookingListResponse {
  bookings: Booking[];
  total: number;
  page: number;
  limit: number;
}

class BookingService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getMyBookings(page = 1, limit = 20): Promise<BookingListResponse> {
    const url = getApiUrl('coreservices', `/api/bookings/my?page=${page}&limit=${limit}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch bookings');
    return response.json();
  }

  async getBookingById(id: string): Promise<Booking> {
    const url = getApiUrl('coreservices', `/api/bookings/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch booking');
    return response.json();
  }

  async createBooking(request: BookingRequest): Promise<Booking> {
    const url = getApiUrl('coreservices', '/api/bookings');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(request),
    });
    if (!response.ok) throw new Error('Failed to create booking');
    return response.json();
  }

  async cancelBooking(id: string): Promise<Booking> {
    const url = getApiUrl('coreservices', `/api/bookings/${id}/cancel`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to cancel booking');
    return response.json();
  }

  async confirmBooking(id: string): Promise<Booking> {
    const url = getApiUrl('coreservices', `/api/bookings/${id}/confirm`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to confirm booking');
    return response.json();
  }
}

export const bookingService = new BookingService();
