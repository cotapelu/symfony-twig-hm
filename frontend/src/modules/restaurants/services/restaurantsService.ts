import type { Restaurant, Reservation, RestaurantsState, SearchRestaurantsRequest, CreateReservationRequest } from '../contracts/restaurantsTypes';

const API_BASE = '/api/restaurants';

export class RestaurantsService {
  async search(request: SearchRestaurantsRequest): Promise<Restaurant[]> {
    const params = new URLSearchParams({ location: request.location, partySize: request.partySize.toString() });
    if (request.cuisine) params.append('cuisine', request.cuisine);
    if (request.date) params.append('date', request.date);
    if (request.time) params.append('time', request.time);
    const r = await fetch(`${API_BASE}?${params}`); if (!r.ok) throw new Error('Failed to search'); return r.json();
  }
  async get(id: string): Promise<Restaurant> { const r = await fetch(`${API_BASE}/${id}`); if (!r.ok) throw new Error('Failed to fetch'); return r.json(); }
  async getReservations(): Promise<Reservation[]> { const r = await fetch(`${API_BASE}/reservations`); if (!r.ok) throw new Error('Failed to fetch'); return r.json(); }
  async reserve(request: CreateReservationRequest): Promise<Reservation> {
    const r = await fetch(`${API_BASE}/reservations`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(request) });
    if (!r.ok) throw new Error('Failed to reserve'); return r.json();
  }
  async cancel(id: string): Promise<Reservation> { const r = await fetch(`${API_BASE}/reservations/${id}/cancel`, { method: 'POST' }); if (!r.ok) throw new Error('Failed to cancel'); return r.json(); }
}

export const restaurantsService = new RestaurantsService();
export default restaurantsService;
