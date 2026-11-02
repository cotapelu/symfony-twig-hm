import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ecommerceService } from './ecommerce';
import { getApiUrl } from '../config/api';

vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('EcommerceService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getProducts', () => {
    it('fetches products with pagination', async () => {
      const products = [{ id: 'p1', name: 'Product1', price: 100, images: [], category: 'cat', stock: 10, rating: 4, reviewCount: 5, isAvailable: true }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ products }) });

      const result = await ecommerceService.getProducts(2, 50);

      expect(getApiUrl).toHaveBeenCalledWith('ecommerce', '/api/ecommerce/products?page=2&limit=50');
      expect(result).toEqual(products);
    });

    it('uses default pagination', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await ecommerceService.getProducts();
      const calledUrl = getApiUrl.mock.calls[0][1];
      expect(calledUrl).toBe('/api/ecommerce/products?page=1&limit=20');
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await ecommerceService.getProducts();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(ecommerceService.getProducts()).rejects.toThrow('Failed to fetch products');
    });
  });

  describe('getProduct', () => {
    it('fetches a product by id', async () => {
      const product = { id: 'p1', name: 'Detail', price: 200, images: [], category: 'cat', stock: 5, rating: 5, reviewCount: 1, isAvailable: true };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => product });

      const result = await ecommerceService.getProduct('p1');

      expect(getApiUrl).toHaveBeenCalledWith('ecommerce', '/api/ecommerce/products/p1');
      expect(result).toEqual(product);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(ecommerceService.getProduct('p1')).rejects.toThrow('Failed to fetch product');
    });
  });

  describe('getCategories', () => {
    it('fetches categories', async () => {
      const categories = [{ id: 'c1', name: 'Electronics' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ categories }) });

      const result = await ecommerceService.getCategories();

      expect(getApiUrl).toHaveBeenCalledWith('ecommerce', '/api/ecommerce/categories');
      expect(result).toEqual(categories);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await ecommerceService.getCategories();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(ecommerceService.getCategories()).rejects.toThrow('Failed to fetch categories');
    });
  });

  describe('searchProducts', () => {
    it('searches with query', async () => {
      const products = [{ id: 'p1', name: 'Match', price: 50, images: [], category: 'cat', stock: 2, rating: 3, reviewCount: 2, isAvailable: true }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ products }) });

      const result = await ecommerceService.searchProducts('phone');

      expect(getApiUrl).toHaveBeenCalledWith('ecommerce', '/api/ecommerce/products/search?q=phone');
      expect(result).toEqual(products);
    });

    it('encodes query', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await ecommerceService.searchProducts('a b');
      const url = getApiUrl.mock.calls[0][1];
      expect(url).toContain('q=a%20b');
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await ecommerceService.searchProducts('xyz');
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(ecommerceService.searchProducts('test')).rejects.toThrow('Failed to search products');
    });
  });

  describe('createOrder', () => {
    it('creates an order with items and shipping', async () => {
      const items = [{ productId: 'p1', quantity: 2, price: 100, name: 'Product1' }];
      const order = { id: 'o1', items, totalAmount: 200, status: 'pending', shippingAddress: '123 Main St', createdAt: '2024-01-01' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => order });

      const result = await ecommerceService.createOrder(items, '123 Main St');

      expect(getApiUrl).toHaveBeenCalledWith('ecommerce', '/api/ecommerce/orders');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ items, shippingAddress: '123 Main St' }),
      });
      expect(result).toEqual(order);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(ecommerceService.createOrder([], 'addr')).rejects.toThrow('Failed to create order');
    });
  });

  describe('getMyOrders', () => {
    it('fetches user orders', async () => {
      const orders = [{ id: 'o1', items: [], totalAmount: 100, status: 'pending', shippingAddress: '', createdAt: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ orders }) });

      const result = await ecommerceService.getMyOrders();

      expect(getApiUrl).toHaveBeenCalledWith('ecommerce', '/api/ecommerce/orders/my');
      expect(result).toEqual(orders);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await ecommerceService.getMyOrders();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(ecommerceService.getMyOrders()).rejects.toThrow('Failed to fetch orders');
    });
  });

  describe('cancelOrder', () => {
    it('cancels an order', async () => {
      const order = { id: 'o1', status: 'cancelled', items: [], totalAmount: 0, shippingAddress: '', createdAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => order });

      const result = await ecommerceService.cancelOrder('o1');

      expect(getApiUrl).toHaveBeenCalledWith('ecommerce', '/api/ecommerce/orders/o1/cancel');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
      expect(result).toEqual(order);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(ecommerceService.cancelOrder('o1')).rejects.toThrow('Failed to cancel order');
    });
  });
});
