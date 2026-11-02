// Location/Maps Service - Connects to Go location module
import { getApiUrl } from '../config/api';

export interface Location {
  lat: number;
  lng: number;
}

export interface Place {
  id: string;
  name: string;
  address: string;
  location: Location;
  category: string;
  rating?: number;
}

export interface Route {
  distance: string;
  duration: string;
  polyline: string;
}

class LocationService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getCurrentLocation(): Promise<Location> {
    const url = getApiUrl('location', '/api/location/current');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to get location');
    return response.json();
  }

  async searchPlaces(query: string): Promise<Place[]> {
    const url = getApiUrl('location', `/api/location/search?q=${encodeURIComponent(query)}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to search places');
    const data = await response.json();
    return data.places || [];
  }

  async getDirections(origin: Location, destination: Location): Promise<Route> {
    const url = getApiUrl('location', '/api/location/directions');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ origin, destination }),
    });
    if (!response.ok) throw new Error('Failed to get directions');
    return response.json();
  }

  async getNearbyPlaces(location: Location, radius = 1000): Promise<Place[]> {
    const url = getApiUrl('location', `/api/location/nearby?lat=${location.lat}&lng=${location.lng}&radius=${radius}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to get nearby places');
    const data = await response.json();
    return data.places || [];
  }
}

export const locationService = new LocationService();
