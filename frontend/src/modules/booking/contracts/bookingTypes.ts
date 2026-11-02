export interface Booking {
  id: string;
  userId: string;
  type: 'hotel' | 'flight' | 'restaurant' | 'car' | 'experience' | 'event' | 'package';
  referenceId: string;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed' | 'failed';
  startDate: string;
  endDate?: string;
  guestCount?: number;
  totalAmount: number;
  currency: string;
  metadata?: Record<string, unknown>;
  createdAt: string;
  updatedAt: string;
  confirmedAt?: string;
  cancelledAt?: string;
}

export interface HotelBooking {
  id: string;
  bookingId: string;
  hotelId: string;
  hotelName: string;
  roomType: string;
  checkIn: string;
  checkOut: string;
  guestCount: number;
  guestNames: string[];
  specialRequests?: string;
}

export interface FlightBooking {
  id: string;
  bookingId: string;
  flightId: string;
  airline: string;
  flightNumber: string;
  departure: {
    airport: string;
    city: string;
    time: string;
  };
  arrival: {
    airport: string;
    city: string;
    time: string;
  };
  passengerCount: number;
  passengers: Array<{
    name: string;
    type: 'adult' | 'child' | 'infant';
  }>;
  class: 'economy' | 'business' | 'first';
}

export interface RestaurantBooking {
  id: string;
  bookingId: string;
  restaurantId: string;
  restaurantName: string;
  date: string;
  time: string;
  partySize: number;
  tableNumber?: string;
  specialRequests?: string;
}

export interface CarBooking {
  id: string;
  bookingId: string;
  carId: string;
  carName: string;
  pickupLocation: string;
  dropoffLocation: string;
  pickupDate: string;
  dropoffDate: string;
  driverName?: string;
  driverLicense?: string;
}

export interface ExperienceBooking {
  id: string;
  bookingId: string;
  experienceId: string;
  experienceName: string;
  date: string;
  time: string;
  participantCount: number;
  participants: string[];
}

export interface EventBooking {
  id: string;
  bookingId: string;
  eventId: string;
  eventName: string;
  eventDate: string;
  ticketCount: number;
  ticketType?: string;
}

export interface BookingState {
  bookings: Booking[];
  currentBooking: Booking | null;
  loading: boolean;
  error: string | null;
}

export interface CreateBookingRequest {
  type: Booking['type'];
  referenceId: string;
  startDate: string;
  endDate?: string;
  guestCount?: number;
  metadata?: Record<string, unknown>;
}

export interface CancelBookingRequest {
  bookingId: string;
  reason?: string;
}
