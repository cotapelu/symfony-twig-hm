export interface Service {
  id: string;
  name: string;
  description: string;
  price: number;
  duration: string;
  rating: number;
  image: string;
  category: string;
  provider: string;
  available: boolean;
}

export interface Booking {
  id: string;
  service: Service;
  bookedAt: Date;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
  scheduledAt?: Date;
}

export interface ServicesState {
  services: Service[];
  bookings: Booking[];
  loading: boolean;
  error: string | null;
}

export interface CreateBookingRequest {
  serviceId: string;
  scheduledAt?: Date;
}

export interface RateServiceRequest {
  bookingId: string;
  rating: number;
  comment?: string;
}