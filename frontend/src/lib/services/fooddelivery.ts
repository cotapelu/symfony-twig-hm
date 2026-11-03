// Food Delivery Service - Connects to PHP FoodDelivery module
import { getApiUrl } from '../config/api';

export interface Restaurant {
  id: string;
  name: string;
  image: string;
  // routes read a features tag list — added additively
  features?: string[];
  rating: number;
  // routes read priceRange as a number indicator — added additively
  priceRange?: number;
  deliveryTime: string;
  deliveryFee: number;
  minOrder: number;
  categories: string[];
  isOpen: boolean;
  // routes read these fields on the detail/listing pages — added additively
  images?: string[];
  cuisine?: string | string[];
  reviewCount?: number;
  address?: string;
  city?: string;
  description?: string;
  openingHours?: Record<string, { open: string; close: string }>;
  ambiance?: string;
}

export interface Product {
  id: string;
  restaurantId: string;
  name: string;
  description: string;
  price: number;
  image: string;
  category: string;
  isAvailable: boolean;
}

export interface CartItem {
  productId: string;
  quantity: number;
  price: number;
  name: string;
}

export interface Order {
  id: string;
  restaurantId: string;
  restaurantName: string;
  items: CartItem[];
  totalAmount: number;
  status: 'pending' | 'confirmed' | 'preparing' | 'delivering' | 'delivered' | 'cancelled';
  deliveryAddress: string;
  createdAt: string;
}

export interface OrderRequest {
  restaurantId: string;
  items: { productId: string; quantity: number }[];
  deliveryAddress: string;
  notes?: string;
}

class FoodDeliveryService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getRestaurants(): Promise<Restaurant[]> {
    const url = getApiUrl('fooddelivery', '/api/fooddelivery/restaurants');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch restaurants');
    const data = await response.json();
    return data.restaurants || [];
  }

  async getRestaurant(id: string): Promise<Restaurant> {
    const url = getApiUrl('fooddelivery', `/api/fooddelivery/restaurants/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch restaurant');
    return response.json();
  }

  async getMenu(restaurantId: string): Promise<Product[]> {
    const url = getApiUrl('fooddelivery', `/api/fooddelivery/restaurants/${restaurantId}/menu`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch menu');
    const data = await response.json();
    return data.products || [];
  }

  async searchRestaurants(query: string): Promise<Restaurant[]> {
    const url = getApiUrl('fooddelivery', `/api/fooddelivery/restaurants/search?q=${encodeURIComponent(query)}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to search restaurants');
    const data = await response.json();
    return data.restaurants || [];
  }

  async createOrder(request: OrderRequest): Promise<Order> {
    const url = getApiUrl('fooddelivery', '/api/fooddelivery/orders');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(request),
    });
    if (!response.ok) throw new Error('Failed to create order');
    return response.json();
  }

  async getMyOrders(): Promise<Order[]> {
    const url = getApiUrl('fooddelivery', '/api/fooddelivery/orders/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch orders');
    const data = await response.json();
    return data.orders || [];
  }

  async getOrder(id: string): Promise<Order> {
    const url = getApiUrl('fooddelivery', `/api/fooddelivery/orders/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch order');
    return response.json();
  }

  async cancelOrder(id: string): Promise<Order> {
    const url = getApiUrl('fooddelivery', `/api/fooddelivery/orders/${id}/cancel`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to cancel order');
    return response.json();
  }
}

export const foodDeliveryService = new FoodDeliveryService();
