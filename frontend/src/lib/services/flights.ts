// Travel/Flights Service - Connects to Kotlin Flights API
import { getApiUrl } from '../config/api';

export interface Flight {
  id: string;
  airline: string;
  flightNumber: string;
  origin: string;
  originCode: string;
  destination: string;
  destinationCode: string;
  departureTime: string;
  arrivalTime: string;
  duration: string;
  price: number;
  availableSeats: number;
  class: 'economy' | 'business' | 'first';
  // routes read nested airport objects (Kotlin GET /api/flights/{id} shape) — added additively
  departureAirport?: { city?: string; code?: string };
  arrivalAirport?: { city?: string; code?: string };
}

/**
 * Seat entity of the Kotlin FlightTickets module (GET /api/flights/{id}/seats).
 */
export interface FlightSeat {
  id: string;
  flightId: string;
  seatNumber: string;
  seatClass: string;
  price: number;
  isAvailable: boolean;
  features?: string[];
}

export interface FlightSearchParams {
  origin: string;
  destination: string;
  departureDate: string;
  returnDate?: string;
  passengers: number;
  class?: string;
}

export interface FlightBooking {
  id: string;
  flightId: string;
  passengerId: string;
  seatNumber?: string;
  status: 'confirmed' | 'checked_in' | 'boarded' | 'cancelled';
}

class FlightsService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async searchFlights(params: FlightSearchParams): Promise<Flight[]> {
    const url = getApiUrl('flights', '/api/flights/search');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(params),
    });
    if (!response.ok) throw new Error('Failed to search flights');
    const data = await response.json();
    return data.flights || [];
  }

  async getFlight(id: string): Promise<Flight> {
    const url = getApiUrl('flights', `/api/flights/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch flight');
    return response.json();
  }

  async bookFlight(flightId: string, passengerId: string): Promise<FlightBooking> {
    const url = getApiUrl('flights', '/api/flights/book');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ flightId, passengerId }),
    });
    if (!response.ok) throw new Error('Failed to book flight');
    return response.json();
  }

  async getSeats(id: string, seatClass: string): Promise<FlightSeat[]> {
    const url = getApiUrl('flights', `/api/flights/${id}/seats?seatClass=${encodeURIComponent(seatClass)}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch seats');
    const data = await response.json();
    return data.seats || [];
  }

  async getMyBookings(): Promise<FlightBooking[]> {
    const url = getApiUrl('flights', '/api/flights/bookings/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch bookings');
    const data = await response.json();
    return data.bookings || [];
  }
}

export const flightsService = new FlightsService();
