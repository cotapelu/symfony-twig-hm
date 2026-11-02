export interface RideRequest {
  id: string;
  userId: string;
  pickupLocation: { address: string; coordinates: { lat: number; lng: number } };
  dropoffLocation: { address: string; coordinates: { lat: number; lng: number } };
  status: 'pending' | 'accepted' | 'arriving' | 'in_progress' | 'completed' | 'cancelled';
  driver?: { id: string; name: string; phone: string; vehicle: string; rating: number };
  estimatedPrice: number;
  actualPrice?: number;
  currency: string;
  distance?: number;
  duration?: number;
  requestedAt: string;
  startedAt?: string;
  completedAt?: string;
}

export interface RideState {
  rides: RideRequest[];
  currentRide: RideRequest | null;
  loading: boolean;
  error: string | null;
}

export interface CreateRideRequest {
  pickupLocation: RideRequest['pickupLocation'];
  dropoffLocation: RideRequest['dropoffLocation'];
}

export interface RideRatingRequest {
  rideId: string;
  rating: number;
  comment?: string;
}
