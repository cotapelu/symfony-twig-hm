import type { RideRequest, RideState, CreateRideRequest, RideRatingRequest } from '../contracts/rideTypes';

const API_BASE = '/api/ride';

export class RideService {
  async createRide(request: CreateRideRequest): Promise<RideRequest> {
    const r = await fetch(API_BASE, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(request) });
    if (!r.ok) throw new Error('Failed to create ride'); return r.json();
  }
  async getRide(id: string): Promise<RideRequest> { const r = await fetch(`${API_BASE}/${id}`); if (!r.ok) throw new Error('Failed to fetch'); return r.json(); }
  async getRides(): Promise<RideRequest[]> { const r = await fetch(API_BASE); if (!r.ok) throw new Error('Failed to fetch'); return r.json(); }
  async cancelRide(id: string): Promise<RideRequest> { const r = await fetch(`${API_BASE}/${id}/cancel`, { method: 'POST' }); if (!r.ok) throw new Error('Failed to cancel'); return r.json(); }
  async rateRide(request: RideRatingRequest): Promise<RideRequest> {
    const r = await fetch(`${API_BASE}/${request.rideId}/rate`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ rating: request.rating, comment: request.comment }) });
    if (!r.ok) throw new Error('Failed to rate'); return r.json();
  }
}

export const rideService = new RideService();
export default rideService;
