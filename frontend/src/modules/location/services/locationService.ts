import type { Location, Place, SharedLocation, Route, ShareLocationRequest, DirectionsRequest } from '../contracts/locationTypes';

export class LocationService {
  private baseUrl = '/api/location';

  async getCurrentLocation(): Promise<Location> {
    return new Promise((resolve, reject) => {
      if (!('geolocation' in navigator)) {
        reject(new Error('Geolocation is not supported'));
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          resolve({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            altitude: position.coords.altitude || undefined,
            heading: position.coords.heading || undefined,
            speed: position.coords.speed || undefined,
            timestamp: position.timestamp
          });
        },
        (error) => {
          reject(new Error(error.message));
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    });
  }

  async getNearbyPlaces(radius: number = 5000): Promise<Place[]> {
    const response = await fetch(`${this.baseUrl}/nearby?radius=${radius}`);
    if (!response.ok) throw new Error('Failed to fetch nearby places');
    return response.json();
  }

  async searchNearby(query: string, radius: number = 5000): Promise<Place[]> {
    const params = new URLSearchParams({ q: query, radius: radius.toString() });
    const response = await fetch(`${this.baseUrl}/nearby?${params}`);
    if (!response.ok) throw new Error('Failed to search nearby places');
    return response.json();
  }

  async geocode(address: string): Promise<Location> {
    const response = await fetch(`${this.baseUrl}/geocode?q=${encodeURIComponent(address)}`);
    if (!response.ok) throw new Error('Geocoding failed');
    return response.json();
  }

  async reverseGeocode(location: Location): Promise<string> {
    const response = await fetch(`${this.baseUrl}/reverse-geocode?lat=${location.latitude}&lng=${location.longitude}`);
    if (!response.ok) throw new Error('Reverse geocoding failed');
    const data = await response.json();
    return data.address;
  }

  async shareLocation(request: ShareLocationRequest): Promise<{ id: string }> {
    const response = await fetch(`${this.baseUrl}/share`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to share location');
    return response.json();
  }

  async getSharedLocations(): Promise<SharedLocation[]> {
    const response = await fetch(`${this.baseUrl}/shared`);
    if (!response.ok) throw new Error('Failed to fetch shared locations');
    return response.json();
  }

  async getDirections(request: DirectionsRequest): Promise<Route> {
    const response = await fetch(`${this.baseUrl}/directions`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to get directions');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};
