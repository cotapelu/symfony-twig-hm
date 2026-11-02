export interface CultureSportsEvent {
  id: string;
  name: string;
  category: 'culture' | 'sports' | 'festival';
  description: string;
  location: string;
  startDate: string;
  endDate: string;
  imageUrl?: string;
  status: 'upcoming' | 'ongoing' | 'completed';
}

export interface CultureSportsState {
  events: CultureSportsEvent[];
  loading: boolean;
  error: string | null;
}
