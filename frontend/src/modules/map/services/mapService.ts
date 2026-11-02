import type { MapLocation, MapState } from '../contracts/mapTypes';

const API_BASE = '/api/map';

export class MapService {
  async getLocations(): Promise<MapLocation[]> {
    const response = await fetch(`${API_BASE}/locations`);
    if (!response.ok) throw new Error('Failed to fetch locations');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const mapService = new MapService();
export default mapService;
