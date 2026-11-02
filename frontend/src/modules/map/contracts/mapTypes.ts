export interface MapLocation {
  id: string;
  name: string;
  latitude: number;
  longitude: number;
  category: string;
  description?: string;
}

export interface MapState {
  locations: MapLocation[];
  loading: boolean;
  error: string | null;
}
