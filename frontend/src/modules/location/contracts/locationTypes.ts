export interface Location {
  latitude: number;
  longitude: number;
  accuracy?: number;
  altitude?: number;
  heading?: number;
  speed?: number;
  timestamp?: number;
  address?: string;
}

export interface Place {
  id: string;
  name: string;
  type: 'restaurant' | 'cafe' | 'shop' | 'hotel' | 'gas' | 'parking' | 'hospital' | 'bank' | 'other';
  address: string;
  latitude: number;
  longitude: number;
  distance: number;
  rating?: number;
  phone?: string;
  website?: string;
  openNow?: boolean;
  hours?: string[];
  photos?: string[];
}

export interface SharedLocation {
  id: string;
  userId: string;
  userName: string;
  userAvatar?: string;
  latitude: number;
  longitude: number;
  timestamp: string;
  expiresAt?: string;
  accuracy?: number;
}

export interface Route {
  origin: Location;
  destination: Location;
  distance: number; // meters
  duration: number; // seconds
  steps: RouteStep[];
  polyline?: string;
}

export interface RouteStep {
  instruction: string;
  distance: number;
  duration: number;
  startLocation: Location;
  endLocation: Location;
  maneuver?: string;
}

export interface LocationState {
  currentLocation: Location | null;
  nearbyPlaces: Place[];
  sharedLocations: SharedLocation[];
  selectedPlace: Place | null;
  currentRoute: Route | null;
  loading: boolean;
  error: string | null;
  isTracking: boolean;
}

export interface ShareLocationRequest {
  latitude: number;
  longitude: number;
  duration?: number; // seconds
  recipientIds?: string[];
}

export interface DirectionsRequest {
  origin: Location;
  destination: Location;
  mode?: 'driving' | 'walking' | 'cycling' | 'transit';
}
