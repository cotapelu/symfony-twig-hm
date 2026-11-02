import type { WishlistItem, WishlistState } from '../contracts/wishlistTypes';

const API_BASE = '/api/wishlist';

export class WishlistService {
  async getItems(): Promise<WishlistItem[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch wishlist');
    return response.json();
  }

  async addItem(itemId: string, itemType: string): Promise<WishlistItem> {
    const response = await fetch(`${API_BASE}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ itemId, itemType })
    });
    if (!response.ok) throw new Error('Failed to add item');
    return response.json();
  }

  async removeItem(itemId: string): Promise<void> {
    const response = await fetch(`${API_BASE}/${itemId}`, { method: 'DELETE' });
    if (!response.ok) throw new Error('Failed to remove item');
  }
}

export const wishlistService = new WishlistService();
export default wishlistService;
