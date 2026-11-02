export interface Flight {
  id: string;
  airline: string;
  flightNumber: string;
  departure: {
    airport: string;
    city: string;
    country: string;
    terminal?: string;
    time: string;
  };
  arrival: {
    airport: string;
    city: string;
    country: string;
    terminal?: string;
    time: string;
  };
  duration: number;
  aircraft?: string;
  status: 'scheduled' | 'boarding' | 'departed' | 'delayed' | 'cancelled' | 'landed';
  price: number;
  currency: string;
  availableSeats: number;
  class: 'economy' | 'business' | 'first';
}

export interface FlightBooking {
  id: string;
  userId: string;
  flightId: string;
  flight?: Flight;
  passengers: Array<{
    firstName: string;
    lastName: string;
    type: 'adult' | 'child' | 'infant';
    seatNumber?: string;
  }>;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
  totalAmount: number;
  currency: string;
  bookingReference: string;
  createdAt: string;
}

export interface FlightsState {
  flights: Flight[];
  bookings: FlightBooking[];
  loading: boolean;
  error: string | null;
}

export interface SearchFlightsRequest {
  origin: string;
  destination: string;
  departureDate: string;
  returnDate?: string;
  passengers: number;
  class?: Flight['class'];
}

export interface BookFlightRequest {
  flightId: string;
  passengers: FlightBooking['passengers'];
}
