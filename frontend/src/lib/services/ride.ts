// Ride Hailing Service - Connects to PHP RideHailing module
import { getApiUrl } from '../config/api';

export interface Driver {
  id: string;
  name: string;
  rating: number;
  vehicleType: string;
  licensePlate: string;
}

export interface Ride {
  id: string;
  driverId: string;
  driverName: string;
  pickupLocation: string;
  dropoffLocation: string;
  status: 'searching' | 'accepted' | 'en_route' | 'arrived' | 'in_progress' | 'completed' | 'cancelled';
  fare: number;
  createdAt: string;
}

export interface RideRequest {
  pickupLocation: string;
  dropoffLocation: string;
  vehicleType: string;
}

class RideHailingService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async requestRide(request: RideRequest): Promise<Ride> {
    const url = getApiUrl('ridehailing', '/api/ride/request');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(request),
    });
    if (!response.ok) throw new Error('Failed to request ride');
    return response.json();
  }

  async getCurrentRide(): Promise<Ride | null> {
    const url = getApiUrl('ridehailing', '/api/ride/current');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to get current ride');
    return response.json();
  }

  async getRideHistory(): Promise<Ride[]> {
    const url = getApiUrl('ridehailing', '/api/ride/history');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to get ride history');
    const data = await response.json();
    return data.rides || [];
  }

  async cancelRide(rideId: string): Promise<void> {
    const url = getApiUrl('ridehailing', `/api/ride/${rideId}/cancel`);
    await fetch(url, { method: 'POST', headers: this.getHeaders() });
  }
}

export const rideHailingService = new RideHailingService();
