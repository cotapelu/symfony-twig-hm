import type { Hotel, HotelBooking, HotelsState, SearchHotelsRequest, BookHotelRequest } from '../contracts/hotelsTypes';

const API_BASE = '/api/hotels';

export class HotelsService {
  async searchHotels(request: SearchHotelsRequest): Promise<Hotel[]> {
    const params = new URLSearchParams({
      location: request.location,
      checkIn: request.checkIn,
      checkOut: request.checkOut,
      guests: request.guests.toString()
    });
    if (request.rooms) params.append('rooms', request.rooms.toString());

    const response = await fetch(`${API_BASE}/search?${params}`);
    if (!response.ok) throw new Error('Failed to search hotels');
    return response.json();
  }

  async getHotel(id: string): Promise<Hotel> {
    const response = await fetch(`${API_BASE}/${id}`);
    if (!response.ok) throw new Error('Failed to fetch hotel');
    return response.json();
  }

  async getBookings(): Promise<HotelBooking[]> {
    const response = await fetch(`${API_BASE}/bookings`);
    if (!response.ok) throw new Error('Failed to fetch bookings');
    return response.json();
  }

  async bookHotel(request: BookHotelRequest): Promise<HotelBooking> {
    const response = await fetch(`${API_BASE}/bookings`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to book hotel');
    return response.json();
  }

  async cancelBooking(id: string): Promise<HotelBooking> {
    const response = await fetch(`${API_BASE}/bookings/${id}/cancel`, { method: 'POST' });
    if (!response.ok) throw new Error('Failed to cancel booking');
    return response.json();
  }
}

export const hotelsService = new HotelsService();
export default hotelsService;
