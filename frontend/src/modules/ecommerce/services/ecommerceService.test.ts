import { describe, it, expect, vi, beforeEach } from 'vitest';
import { EcommerceService } from './ecommerceService';
import { apiClient } from '../../../appcore/api-client';

// Mock the apiClient module
vi.mock('../../../appcore/api-client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn()
  }
}));

describe('EcommerceService', () => {
  let service: EcommerceService;

  beforeEach(() => {
    service = new EcommerceService();
    vi.clearAllMocks();
  });

  describe('getProducts', () => {
    it('should fetch products successfully', async () => {
      const products = [{ id: 'p1', name: 'Product 1' }];
      (apiClient.get as any).mockResolvedValue(products);

      const result = await service.getProducts();

      expect(result).toEqual(products);
      expect(apiClient.get).toHaveBeenCalledWith('/api/ecommerce/products');
    });

    it('should propagate errors', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Network error'));

      await expect(service.getProducts()).rejects.toThrow('Network error');
    });
  });

  describe('getProduct', () => {
    it('should fetch product by id', async () => {
      const product = { id: 'p1', name: 'Product 1' };
      (apiClient.get as any).mockResolvedValue(product);

      const result = await service.getProduct('p1');

      expect(result).toEqual(product);
      expect(apiClient.get).toHaveBeenCalledWith('/api/ecommerce/products/p1');
    });

    it('should throw when product not found', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Not found'));

      await expect(service.getProduct('invalid')).rejects.toThrow('Not found');
    });
  });

  describe('searchProducts', () => {
    it('should search products with query only', async () => {
      const results = [{ id: 'p1' }];
      (apiClient.get as any).mockResolvedValue(results);

      const result = await service.searchProducts('laptop');

      expect(result).toEqual(results);
      expect(apiClient.get).toHaveBeenCalledWith('/api/ecommerce/products/search?q=laptop');
    });

    it('should search with query and filters', async () => {
      const results = [{ id: 'p1' }];
      (apiClient.get as any).mockResolvedValue(results);

      const result = await service.searchProducts('phone', { category: 'electronics', minPrice: 100 });

      const calledWith = (apiClient.get as any).mock.calls[0][0];
      expect(calledWith).toContain('q=phone');
      expect(calledWith).toContain('category=electronics');
      expect(calledWith).toContain('minPrice=100');
    });

    it('should skip falsy filter values', async () => {
      (apiClient.get as any).mockResolvedValue([]);

      await service.searchProducts('test', { category: '', minPrice: 0 });

      const calledWith = (apiClient.get as any).mock.calls[0][0];
      expect(calledWith).not.toContain('category=');
      expect(calledWith).not.toContain('minPrice=0');
    });

    it('should propagate errors', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Search failed'));

      await expect(service.searchProducts('test')).rejects.toThrow('Search failed');
    });
  });

  describe('getCategories', () => {
    it('should fetch categories successfully', async () => {
      const categories = ['electronics', 'clothing'];
      (apiClient.get as any).mockResolvedValue(categories);

      const result = await service.getCategories();

      expect(result).toEqual(categories);
      expect(apiClient.get).toHaveBeenCalledWith('/api/ecommerce/categories');
    });

    it('should throw on failure', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Failed'));

      await expect(service.getCategories()).rejects.toThrow('Failed');
    });
  });

  describe('getOrders', () => {
    it('should fetch orders successfully', async () => {
      const orders = [{ id: 'o1', total: 200 }];
      (apiClient.get as any).mockResolvedValue(orders);

      const result = await service.getOrders();

      expect(result).toEqual(orders);
      expect(apiClient.get).toHaveBeenCalledWith('/api/ecommerce/orders');
    });

    it('should throw on failure', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('API error'));

      await expect(service.getOrders()).rejects.toThrow('API error');
    });
  });

  describe('getOrder', () => {
    it('should fetch order by id', async () => {
      const order = { id: 'o1', status: 'completed' };
      (apiClient.get as any).mockResolvedValue(order);

      const result = await service.getOrder('o1');

      expect(result).toEqual(order);
      expect(apiClient.get).toHaveBeenCalledWith('/api/ecommerce/orders/o1');
    });

    it('should throw when order not found', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Not found'));

      await expect(service.getOrder('o999')).rejects.toThrow('Not found');
    });
  });

  describe('checkout', () => {
    it('should process checkout successfully', async () => {
      const request = { items: [{ productId: 'p1', quantity: 2 }], shippingAddress: 'addr' };
      const order = { id: 'o1', status: 'pending' };
      (apiClient.post as any).mockResolvedValue(order);

      const result = await service.checkout(request);

      expect(result).toEqual(order);
      expect(apiClient.post).toHaveBeenCalledWith('/api/ecommerce/checkout', request);
    });

    it('should throw on checkout failure', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Out of stock'));

      await expect(service.checkout({ items: [] })).rejects.toThrow('Out of stock');
    });
  });

  describe('cancelOrder', () => {
    it('should cancel order successfully', async () => {
      (apiClient.post as any).mockResolvedValue(undefined);

      await expect(service.cancelOrder('o1')).resolves.toBeUndefined();
      expect(apiClient.post).toHaveBeenCalledWith('/api/ecommerce/orders/o1/cancel');
    });

    it('should throw on cancel failure', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Cannot cancel'));

      await expect(service.cancelOrder('o1')).rejects.toThrow('Cannot cancel');
    });
  });

  describe('addReview', () => {
    it('should add review successfully', async () => {
      const productId = 'p1';
      const review = { rating: 5, comment: 'Great!' };
      (apiClient.post as any).mockResolvedValue(undefined);

      await expect(service.addReview(productId, review)).resolves.toBeUndefined();
      expect(apiClient.post).toHaveBeenCalledWith('/api/ecommerce/products/p1/reviews', review);
    });

    it('should throw on failure', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Validation error'));

      await expect(service.addReview('p1', { rating: 5 })).rejects.toThrow('Validation error');
    });
  });

  describe('getReviews', () => {
    it('should fetch reviews for product', async () => {
      const reviews = [{ id: 'r1', rating: 4 }];
      (apiClient.get as any).mockResolvedValue(reviews);

      const result = await service.getReviews('p1');

      expect(result).toEqual(reviews);
      expect(apiClient.get).toHaveBeenCalledWith('/api/ecommerce/products/p1/reviews');
    });

    it('should throw on failure', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Failed'));

      await expect(service.getReviews('p1')).rejects.toThrow('Failed');
    });
  });
});
