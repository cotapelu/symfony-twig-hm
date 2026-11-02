import type { Booking, BookingSlot, CreateBookingRequest, BookingsState } from '../contracts/bookingsTypes';

const API_BASE = '/api/bookings';

export class BookingsService {
  async getBookings(): Promise<Booking[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch bookings');
    return response.json();
  }

  async getBooking(id: string): Promise<Booking> {
    const response = await fetch(`${API_BASE}/${id}`);
    if (!response.ok) throw new Error('Failed to fetch booking');
    return response.json();
  }

  async createBooking(request: CreateBookingRequest): Promise<Booking> {
    const response = await fetch(`${API_BASE}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create booking');
    return response.json();
  }

  async cancelBooking(id: string): Promise<Booking> {
    const response = await fetch(`${API_BASE}/${id}/cancel`, {
      method: 'PUT'
    });
    if (!response.ok) throw new Error('Failed to cancel booking');
    return response.json();
  }

  async getAvailableSlots(serviceId: string, date: string): Promise<BookingSlot[]> {
    const response = await fetch(`${API_BASE}/slots?serviceId=${serviceId}&date=${date}`);
    if (!response.ok) throw new Error('Failed to fetch slots');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const bookingsService = new BookingsService();
export default bookingsService;
