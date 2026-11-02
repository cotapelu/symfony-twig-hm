import { describe, it, expect, vi, beforeEach } from 'vitest';
import { WishlistService } from './wishlistService';

global.fetch = vi.fn();

describe('WishlistService', () => {
  let service: WishlistService;

  beforeEach(() => {
    service = new WishlistService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getItems', () => {
    it('should fetch wishlist items', async () => {
      const items = [{ id: 'i1', itemId: '123' }];
      (global.fetch as any).mockResolvedValue(mockResponse(items));
      const result = await service.getItems();
      expect(result).toEqual(items);
      expect(fetch).toHaveBeenCalledWith('/api/wishlist');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getItems()).rejects.toThrow('Failed to fetch wishlist');
    });
  });

  describe('addItem', () => {
    it('should add item to wishlist', async () => {
      const added = { id: 'new', itemId: '123', itemType: 'product' };
      (global.fetch as any).mockResolvedValue(mockResponse(added));
      const result = await service.addItem('123', 'product');
      expect(result).toEqual(added);
      expect(fetch).toHaveBeenCalledWith('/api/wishlist', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ itemId: '123', itemType: 'product' })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.addItem('123', 'product')).rejects.toThrow('Failed to add item');
    });
  });

  describe('removeItem', () => {
    it('should remove item from wishlist', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.removeItem('123');
      expect(fetch).toHaveBeenCalledWith('/api/wishlist/123', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.removeItem('123')).rejects.toThrow('Failed to remove item');
    });
  });
});
