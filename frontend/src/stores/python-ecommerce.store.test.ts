import { describe, it, expect, vi, beforeEach } from 'vitest';
import { pythonEcommerceStore } from './python-all';
import { Python } from '../lib/api';

vi.mock('../lib/api', () => ({
  Python: {
    getAIRecommendations: vi.fn(),
    getTrendingProducts: vi.fn(),
    searchProducts: vi.fn(),
    getProductDetails: vi.fn(),
    addToCart: vi.fn(),
    getCart: vi.fn(),
  }
}));

describe('PythonEcommerceStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    pythonEcommerceStore.reset();
    pythonEcommerceStore.subscribe(s => { state = s; });
  });

  describe('getRecommendations', () => {
    it('should fetch recommendations', async () => {
      const recs = [{ id: 'p1' }];
      (Python.getAIRecommendations as any).mockResolvedValue(recs);

      const result = await pythonEcommerceStore.getRecommendations('user1');

      expect(result).toEqual(recs);
      expect(state.recommendations).toEqual(recs);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.getAIRecommendations as any).mockRejectedValue(new Error('Rec failed'));
      await expect(pythonEcommerceStore.getRecommendations('u1')).rejects.toThrow('Rec failed');
      expect(state.error).toBe('Rec failed');
    });
  });

  describe('getTrendingProducts', () => {
    it('should fetch trending products with limit', async () => {
      const products = [{ id: 'p2' }];
      (Python.getTrendingProducts as any).mockResolvedValue(products);

      const result = await pythonEcommerceStore.getTrendingProducts(5);

      expect(result).toEqual(products);
      expect(state.trendingProducts).toEqual(products);
      expect(state.loading).toBe(false);
    });

    it('should use default limit', async () => {
      const products = [{ id: 'p3' }];
      (Python.getTrendingProducts as any).mockResolvedValue(products);

      await pythonEcommerceStore.getTrendingProducts();

      expect(Python.getTrendingProducts).toHaveBeenCalledWith(10);
    });

    it('should handle error', async () => {
      (Python.getTrendingProducts as any).mockRejectedValue(new Error('Trend failed'));
      await expect(pythonEcommerceStore.getTrendingProducts()).rejects.toThrow('Trend failed');
      expect(state.error).toBe('Trend failed');
    });
  });

  describe('searchProducts', () => {
    it('should search and store results', async () => {
      const results = [{ id: 'p4' }];
      (Python.searchProducts as any).mockResolvedValue(results);

      const result = await pythonEcommerceStore.searchProducts('book');

      expect(result).toEqual(results);
      expect(state.searchResults).toEqual(results);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.searchProducts as any).mockRejectedValue(new Error('Search failed'));
      await expect(pythonEcommerceStore.searchProducts('q')).rejects.toThrow('Search failed');
      expect(state.error).toBe('Search failed');
    });
  });

  describe('getProductDetails', () => {
    it('should store product details by id', async () => {
      const product = { id: 'p5', name: 'Widget' };
      (Python.getProductDetails as any).mockResolvedValue(product);

      const result = await pythonEcommerceStore.getProductDetails('p5');

      expect(result).toEqual(product);
      expect(state.productDetails['p5']).toEqual(product);
    });

    it('should handle error', async () => {
      (Python.getProductDetails as any).mockRejectedValue(new Error('Details failed'));
      await expect(pythonEcommerceStore.getProductDetails('p5')).rejects.toThrow('Details failed');
      expect(state.error).toBe('Details failed');
    });
  });

  describe('addToCart', () => {
    it('should add item to cart', async () => {
      const cartItem = { productId: 'p6', quantity: 2 };
      (Python.addToCart as any).mockResolvedValue(cartItem);
      state.cart = [];

      const result = await pythonEcommerceStore.addToCart('p6', 2);

      expect(result).toEqual(cartItem);
      expect(state.cart).toContain(cartItem);
    });

    it('should handle error', async () => {
      (Python.addToCart as any).mockRejectedValue(new Error('Add failed'));
      await expect(pythonEcommerceStore.addToCart('p6')).rejects.toThrow('Add failed');
      expect(state.error).toBe('Add failed');
    });
  });

  describe('getCart', () => {
    it('should fetch cart', async () => {
      const cart = [{ productId: 'p1', quantity: 1 }];
      (Python.getCart as any).mockResolvedValue(cart);

      const result = await pythonEcommerceStore.getCart();

      expect(result).toEqual(cart);
      expect(state.cart).toEqual(cart);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Python.getCart as any).mockRejectedValue(new Error('GetCart failed'));
      await expect(pythonEcommerceStore.getCart()).rejects.toThrow('GetCart failed');
      expect(state.error).toBe('GetCart failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.recommendations = [{ id: 'p1' }];
      state.trendingProducts = [{ id: 'p2' }];
      state.searchResults = [{ id: 'p3' }];
      state.cart = [{ productId: 'p4' }];
      state.productDetails = { p5: { id: 'p5' } };
      state.error = 'err';
      pythonEcommerceStore.reset();
      expect(state.recommendations).toEqual([]);
      expect(state.trendingProducts).toEqual([]);
      expect(state.searchResults).toEqual([]);
      expect(state.cart).toEqual([]);
      expect(state.productDetails).toEqual({});
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Python.getAIRecommendations as any).mockRejectedValue(new Error('test'));
      try { await pythonEcommerceStore.getRecommendations('u1'); } catch (e) {}
      expect(state.error).toBe('test');
      pythonEcommerceStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
