export interface Hotel {
  id: string;
  name: string;
  description: string;
  address: {
    street: string;
    city: string;
    state: string;
    country: string;
    zipCode: string;
    coordinates?: { lat: number; lng: number };
  };
  starRating: number;
  images: string[];
  amenities: string[];
  rooms: HotelRoom[];
  checkInTime: string;
  checkOutTime: string;
  cancellationPolicy?: string;
  contactPhone?: string;
  contactEmail?: string;
}

export interface HotelRoom {
  id: string;
  hotelId: string;
  type: string;
  description: string;
  maxOccupancy: number;
  bedType: string;
  size?: number;
  price: number;
  currency: string;
  amenities?: string[];
  images?: string[];
  available: boolean;
}

export interface HotelBooking {
  id: string;
  userId: string;
  hotelId: string;
  hotel?: Hotel;
  roomId: string;
  checkIn: string;
  checkOut: string;
  guests: number;
  guestNames: string[];
  totalAmount: number;
  currency: string;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
  specialRequests?: string;
  createdAt: string;
}

export interface HotelsState {
  hotels: Hotel[];
  bookings: HotelBooking[];
  loading: boolean;
  error: string | null;
}

export interface SearchHotelsRequest {
  location: string;
  checkIn: string;
  checkOut: string;
  guests: number;
  rooms?: number;
}

export interface BookHotelRequest {
  hotelId: string;
  roomId: string;
  checkIn: string;
  checkOut: string;
  guests: number;
  guestNames: string[];
  specialRequests?: string;
}
