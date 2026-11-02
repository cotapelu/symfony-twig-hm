import { describe, it, expect, vi, beforeEach } from 'vitest';
import { foodDeliveryService } from './fooddelivery';
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

describe('FoodDeliveryService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getRestaurants', () => {
    it('fetches all restaurants', async () => {
      const restaurants = [{ id: 'r1', name: 'Burger Place', rating: 4.5, deliveryTime: '30min', deliveryFee: 5, minOrder: 20, categories: ['Fast Food'], isOpen: true }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ restaurants }) });

      const result = await foodDeliveryService.getRestaurants();

      expect(getApiUrl).toHaveBeenCalledWith('fooddelivery', '/api/fooddelivery/restaurants');
      expect(result).toEqual(restaurants);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await foodDeliveryService.getRestaurants();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(foodDeliveryService.getRestaurants()).rejects.toThrow('Failed to fetch restaurants');
    });
  });

  describe('getRestaurant', () => {
    it('fetches a specific restaurant', async () => {
      const restaurant = { id: 'r1', name: 'Place', rating: 4, deliveryTime: '20min', deliveryFee: 3, minOrder: 15, categories: [' Asian'], isOpen: false };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => restaurant });

      const result = await foodDeliveryService.getRestaurant('r1');

      expect(getApiUrl).toHaveBeenCalledWith('fooddelivery', '/api/fooddelivery/restaurants/r1');
      expect(result).toEqual(restaurant);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(foodDeliveryService.getRestaurant('r1')).rejects.toThrow('Failed to fetch restaurant');
    });
  });

  describe('getMenu', () => {
    it('fetches menu for a restaurant', async () => {
      const products = [{ id: 'p1', restaurantId: 'r1', name: 'Burger', price: 10, image: '', category: 'Main', isAvailable: true }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ products }) });

      const result = await foodDeliveryService.getMenu('r1');

      expect(getApiUrl).toHaveBeenCalledWith('fooddelivery', '/api/fooddelivery/restaurants/r1/menu');
      expect(result).toEqual(products);
    });

    it('returns empty array if no products', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await foodDeliveryService.getMenu('r1');
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(foodDeliveryService.getMenu('r1')).rejects.toThrow('Failed to fetch menu');
    });
  });

  describe('searchRestaurants', () => {
    it('searches restaurants by query', async () => {
      const restaurants = [{ id: 'r1', name: 'Sushi', rating: 5, deliveryTime: '40min', deliveryFee: 8, minOrder: 30, categories: ['Japanese'], isOpen: true }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ restaurants }) });

      const result = await foodDeliveryService.searchRestaurants('sushi');

      expect(getApiUrl).toHaveBeenCalledWith('fooddelivery', '/api/fooddelivery/restaurants/search?q=sushi');
      expect(result).toEqual(restaurants);
    });

    it('encodes query', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await foodDeliveryService.searchRestaurants('phở');
      const url = getApiUrl.mock.calls[0][1];
      expect(url).toContain('q=ph%E1%BB%9F'); // depends on encoding
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(foodDeliveryService.searchRestaurants('xyz')).rejects.toThrow('Failed to search restaurants');
    });
  });

  describe('createOrder', () => {
    it('creates an order', async () => {
      const order = { id: 'o1', restaurantId: 'r1', restaurantName: 'Burger Place', items: [], totalAmount: 50, status: 'pending', deliveryAddress: '123 St', createdAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => order });

      const result = await foodDeliveryService.createOrder({
        restaurantId: 'r1',
        items: [{ productId: 'p1', quantity: 2 }],
        deliveryAddress: '123 St',
      });

      expect(getApiUrl).toHaveBeenCalledWith('fooddelivery', '/api/fooddelivery/orders');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({
          restaurantId: 'r1',
          items: [{ productId: 'p1', quantity: 2 }],
          deliveryAddress: '123 St',
        }),
      });
      expect(result).toEqual(order);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(foodDeliveryService.createOrder({
        restaurantId: 'r1',
        items: [],
        deliveryAddress: '',
      })).rejects.toThrow('Failed to create order');
    });
  });

  describe('getMyOrders', () => {
    it('fetches user orders', async () => {
      const orders = [{ id: 'o1', status: 'confirmed', items: [], totalAmount: 20, deliveryAddress: '', restaurantId: '', restaurantName: '', createdAt: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ orders }) });

      const result = await foodDeliveryService.getMyOrders();

      expect(getApiUrl).toHaveBeenCalledWith('fooddelivery', '/api/fooddelivery/orders/my');
      expect(result).toEqual(orders);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await foodDeliveryService.getMyOrders();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(foodDeliveryService.getMyOrders()).rejects.toThrow('Failed to fetch orders');
    });
  });

  describe('getOrder', () => {
    it('fetches a specific order', async () => {
      const order = { id: 'o1', status: 'delivered', items: [], totalAmount: 30, deliveryAddress: '', restaurantId: '', restaurantName: '', createdAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => order });

      const result = await foodDeliveryService.getOrder('o1');

      expect(getApiUrl).toHaveBeenCalledWith('fooddelivery', '/api/fooddelivery/orders/o1');
      expect(result).toEqual(order);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(foodDeliveryService.getOrder('o1')).rejects.toThrow('Failed to fetch order');
    });
  });

  describe('cancelOrder', () => {
    it('cancels an order', async () => {
      const order = { id: 'o1', status: 'cancelled', items: [], totalAmount: 0, deliveryAddress: '', restaurantId: '', restaurantName: '', createdAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => order });

      const result = await foodDeliveryService.cancelOrder('o1');

      expect(getApiUrl).toHaveBeenCalledWith('fooddelivery', '/api/fooddelivery/orders/o1/cancel');
      expect(result).toEqual(order);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(foodDeliveryService.cancelOrder('o1')).rejects.toThrow('Failed to cancel order');
    });
  });
});
