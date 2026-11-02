import type { Service, Booking, CreateBookingRequest, RateServiceRequest } from '../contracts/servicesTypes';

const API_BASE = '/api/services';

export class ServicesService {
  async getServices(): Promise<Service[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) {
      throw new Error('Failed to fetch services');
    }
    return response.json();
  }

  async getService(serviceId: string): Promise<Service> {
    const response = await fetch(`${API_BASE}/${serviceId}`);
    if (!response.ok) {
      throw new Error('Failed to fetch service');
    }
    return response.json();
  }

  async createBooking(request: CreateBookingRequest): Promise<Booking> {
    const response = await fetch(`${API_BASE}/bookings`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) {
      throw new Error('Failed to create booking');
    }
    return response.json();
  }

  async cancelBooking(bookingId: string): Promise<void> {
    const response = await fetch(`${API_BASE}/bookings/${bookingId}/cancel`, {
      method: 'POST'
    });
    if (!response.ok) {
      throw new Error('Failed to cancel booking');
    }
  }

  async getBookings(): Promise<Booking[]> {
    const response = await fetch(`${API_BASE}/bookings`);
    if (!response.ok) {
      throw new Error('Failed to fetch bookings');
    }
    return response.json();
  }

  async rateService(request: RateServiceRequest): Promise<Booking> {
    const response = await fetch(`${API_BASE}/bookings/${request.bookingId}/rate`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ rating: request.rating, comment: request.comment })
    });
    if (!response.ok) {
      throw new Error('Failed to rate service');
    }
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const servicesService = new ServicesService();
export default servicesService;