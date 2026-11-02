export interface Booking {
  id: string;
  userId: string;
  serviceId: string;
  serviceName: string;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
  scheduledAt: string;
  location?: string;
  notes?: string;
  createdAt: string;
  updatedAt: string;
}

export interface BookingSlot {
  id: string;
  serviceId: string;
  availableAt: string;
  isAvailable: boolean;
}

export interface CreateBookingRequest {
  serviceId: string;
  scheduledAt: string;
  location?: string;
  notes?: string;
}

export interface BookingsState {
  bookings: Booking[];
  slots: BookingSlot[];
  loading: boolean;
  error: string | null;
}
