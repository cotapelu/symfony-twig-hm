export interface CompareItem {
  id: string;
  type: 'product' | 'service' | 'provider';
  name: string;
  image?: string;
  price?: number;
  rating?: number;
  features: string[];
}

export interface CompareList {
  id: string;
  userId: string;
  items: CompareItem[];
  createdAt: string;
}

export interface CompareState {
  currentCompare: CompareItem[];
  history: CompareList[];
  loading: boolean;
  error: string | null;
}
