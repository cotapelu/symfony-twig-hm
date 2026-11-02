export interface Restaurant {
  id: string;
  name: string;
  description: string;
  cuisine: string[];
  address: { street: string; city: string; country: string; coordinates?: { lat: number; lng: number } };
  rating: number;
  priceRange: string;
  images: string[];
  amenities: string[];
  hours: { day: string; open: string; close: string }[];
  contactPhone?: string;
  contactEmail?: string;
}

export interface Table {
  id: string;
  restaurantId: string;
  number: number;
  capacity: number;
  location?: string;
  available: boolean;
}

export interface Reservation {
  id: string;
  userId: string;
  restaurantId: string;
  restaurant?: Restaurant;
  tableId: string;
  date: string;
  time: string;
  partySize: number;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
  specialRequests?: string;
  createdAt: string;
}

export interface RestaurantsState {
  restaurants: Restaurant[];
  reservations: Reservation[];
  loading: boolean;
  error: string | null;
}

export interface SearchRestaurantsRequest {
  location: string;
  cuisine?: string;
  date?: string;
  time?: string;
  partySize: number;
}

export interface CreateReservationRequest {
  restaurantId: string;
  tableId: string;
  date: string;
  time: string;
  partySize: number;
  specialRequests?: string;
}
