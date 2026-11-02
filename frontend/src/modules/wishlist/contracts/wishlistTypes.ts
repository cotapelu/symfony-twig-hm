export interface WishlistItem {
  id: string;
  userId: string;
  itemId: string;
  itemType: string;
  name: string;
  imageUrl?: string;
  addedAt: string;
}

export interface WishlistState {
  items: WishlistItem[];
  loading: boolean;
  error: string | null;
}
