export interface DiscoverItem {
  id: string;
  title: string;
  description: string;
  imageUrl?: string;
  category: string;
  location?: string;
  rating?: number;
}

export interface DiscoverState {
  items: DiscoverItem[];
  loading: boolean;
  error: string | null;
}
