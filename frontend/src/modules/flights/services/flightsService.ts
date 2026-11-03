import type { Flight, FlightWithPrice, FlightBooking, FlightsState, SearchFlightsRequest, BookFlightRequest } from '../contracts/flightsTypes';

const API_BASE = '/api/flights';

export class FlightsService {
  async searchFlights(request: SearchFlightsRequest): Promise<FlightWithPrice[]> {
    const params = new URLSearchParams({
      origin: request.origin,
      destination: request.destination,
      departureDate: request.departureDate,
      passengers: request.passengers.toString()
    });
    if (request.returnDate) params.append('returnDate', request.returnDate);
    if (request.class) params.append('class', request.class);

    const response = await fetch(`${API_BASE}/search?${params}`);
    if (!response.ok) throw new Error('Failed to search flights');
    return response.json();
  }

  async getFlight(id: string): Promise<Flight> {
    const response = await fetch(`${API_BASE}/${id}`);
    if (!response.ok) throw new Error('Failed to fetch flight');
    return response.json();
  }

  async getBookings(): Promise<FlightBooking[]> {
    const response = await fetch(`${API_BASE}/bookings`);
    if (!response.ok) throw new Error('Failed to fetch bookings');
    return response.json();
  }

  async getBooking(id: string): Promise<FlightBooking> {
    const response = await fetch(`${API_BASE}/bookings/${id}`);
    if (!response.ok) throw new Error('Failed to fetch booking');
    return response.json();
  }

  async bookFlight(request: BookFlightRequest): Promise<FlightBooking> {
    const response = await fetch(`${API_BASE}/bookings`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to book flight');
    return response.json();
  }

  async cancelBooking(id: string): Promise<FlightBooking> {
    const response = await fetch(`${API_BASE}/bookings/${id}/cancel`, { method: 'POST' });
    if (!response.ok) throw new Error('Failed to cancel booking');
    return response.json();
  }
}

export const flightsService = new FlightsService();
export default flightsService;
