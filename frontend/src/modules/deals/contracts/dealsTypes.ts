export interface Deal {
  id: string;
  title: string;
  description: string;
  imageUrl?: string;
  discount: number;
  originalPrice: number;
  discountedPrice: number;
  validUntil: string;
  providerId: string;
  providerName: string;
  category: string;
}

export interface DealsState {
  deals: Deal[];
  loading: boolean;
  error: string | null;
}
