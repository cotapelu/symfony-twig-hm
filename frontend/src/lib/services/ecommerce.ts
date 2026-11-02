// Ecommerce Service - Connects to PHP Ecommerce module
import { getApiUrl } from '../config/api';

export interface Product {
  id: string;
  name: string;
  description: string;
  price: number;
  originalPrice?: number;
  images: string[];
  category: string;
  stock: number;
  rating: number;
  reviewCount: number;
  isAvailable: boolean;
}

export interface CartItem {
  productId: string;
  quantity: number;
  price: number;
  name: string;
  image?: string;
}

export interface Order {
  id: string;
  items: CartItem[];
  totalAmount: number;
  status: 'pending' | 'paid' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
  shippingAddress: string;
  createdAt: string;
}

export interface Category {
  id: string;
  name: string;
  image?: string;
}

class EcommerceService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getProducts(page = 1, limit = 20): Promise<Product[]> {
    const url = getApiUrl('ecommerce', `/api/ecommerce/products?page=${page}&limit=${limit}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch products');
    const data = await response.json();
    return data.products || [];
  }

  async getProduct(id: string): Promise<Product> {
    const url = getApiUrl('ecommerce', `/api/ecommerce/products/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch product');
    return response.json();
  }

  async getCategories(): Promise<Category[]> {
    const url = getApiUrl('ecommerce', '/api/ecommerce/categories');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch categories');
    const data = await response.json();
    return data.categories || [];
  }

  async searchProducts(query: string): Promise<Product[]> {
    const url = getApiUrl('ecommerce', `/api/ecommerce/products/search?q=${encodeURIComponent(query)}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to search products');
    const data = await response.json();
    return data.products || [];
  }

  async createOrder(items: CartItem[], shippingAddress: string): Promise<Order> {
    const url = getApiUrl('ecommerce', '/api/ecommerce/orders');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ items, shippingAddress }),
    });
    if (!response.ok) throw new Error('Failed to create order');
    return response.json();
  }

  async getMyOrders(): Promise<Order[]> {
    const url = getApiUrl('ecommerce', '/api/ecommerce/orders/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch orders');
    const data = await response.json();
    return data.orders || [];
  }

  async cancelOrder(id: string): Promise<Order> {
    const url = getApiUrl('ecommerce', `/api/ecommerce/orders/${id}/cancel`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to cancel order');
    return response.json();
  }
}

export const ecommerceService = new EcommerceService();
