import type {
  Booking,
  BookingState,
  CreateBookingRequest,
  CancelBookingRequest
} from '../contracts/bookingTypes';

const API_BASE = '/api/bookings';

export class BookingService {
  async getBookings(): Promise<Booking[]> {
    const response = await fetch(API_BASE);
    if (!response.ok) throw new Error('Failed to fetch bookings');
    return response.json();
  }

  async getBooking(id: string): Promise<Booking> {
    const response = await fetch(`${API_BASE}/${id}`);
    if (!response.ok) throw new Error('Failed to fetch booking');
    return response.json();
  }

  async createBooking(request: CreateBookingRequest): Promise<Booking> {
    const response = await fetch(API_BASE, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create booking');
    return response.json();
  }

  async cancelBooking(request: CancelBookingRequest): Promise<Booking> {
    const response = await fetch(`${API_BASE}/${request.bookingId}/cancel`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reason: request.reason })
    });
    if (!response.ok) throw new Error('Failed to cancel booking');
    return response.json();
  }

  async confirmBooking(id: string): Promise<Booking> {
    const response = await fetch(`${API_BASE}/${id}/confirm`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to confirm booking');
    return response.json();
  }

  async getUpcomingBookings(): Promise<Booking[]> {
    const response = await fetch(`${API_BASE}?status=confirmed&upcoming=true`);
    if (!response.ok) throw new Error('Failed to fetch upcoming bookings');
    return response.json();
  }

  async getPastBookings(): Promise<Booking[]> {
    const response = await fetch(`${API_BASE}?status=completed,past=true`);
    if (!response.ok) throw new Error('Failed to fetch past bookings');
    return response.json();
  }
}

export const bookingService = new BookingService();
export default bookingService;
