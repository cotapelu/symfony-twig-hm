export interface Event {
  id: string;
  organizerId: string;
  title: string;
  description: string;
  type: 'conference' | 'concert' | 'workshop' | 'meetup' | 'sports' | 'exhibition' | 'festival' | 'webinar' | 'other';
  venue: {
    name: string;
    address: string;
    city: string;
    country: string;
    coordinates?: { lat: number; lng: number };
  };
  startDate: string;
  endDate: string;
  timezone: string;
  capacity?: number;
  attendeesCount: number;
  price: number;
  currency: string;
  image?: string;
  tags?: string[];
  isOnline: boolean;
  onlineUrl?: string;
  status: 'draft' | 'published' | 'cancelled' | 'postponed';
  createdAt: string;
  updatedAt: string;
}

export interface EventTicket {
  id: string;
  eventId: string;
  userId: string;
  ticketType: string;
  quantity: number;
  totalAmount: number;
  status: 'pending' | 'confirmed' | 'cancelled' | 'refunded';
  purchasedAt: string;
}

export interface EventsState {
  events: Event[];
  tickets: EventTicket[];
  loading: boolean;
  error: string | null;
}

export interface CreateEventRequest {
  title: string;
  description: string;
  type: Event['type'];
  venue: Event['venue'];
  startDate: string;
  endDate: string;
  capacity?: number;
  price: number;
  isOnline: boolean;
  onlineUrl?: string;
}

export interface PurchaseTicketsRequest {
  eventId: string;
  ticketType: string;
  quantity: number;
}
