import type { Product, Order, CheckoutRequest } from '../contracts/ecommerceTypes';
import { apiClient } from '../../../appcore/api-client';

export class EcommerceService {
  private baseUrl = '/api/ecommerce';

  async getProducts(): Promise<Product[]> {
    return apiClient.get<Product[]>(`${this.baseUrl}/products`);
  }

  async getProduct(id: string): Promise<Product> {
    return apiClient.get<Product>(`${this.baseUrl}/products/${id}`);
  }

  async searchProducts(query: string, filters?: Record<string, any>): Promise<Product[]> {
    const params = new URLSearchParams({ q: query });
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value) params.append(key, value.toString());
      });
    }
    return apiClient.get<Product[]>(`${this.baseUrl}/products/search?${params}`);
  }

  async getCategories(): Promise<string[]> {
    return apiClient.get<string[]>(`${this.baseUrl}/categories`);
  }

  async getOrders(): Promise<Order[]> {
    return apiClient.get<Order[]>(`${this.baseUrl}/orders`);
  }

  async getOrder(id: string): Promise<Order> {
    return apiClient.get<Order>(`${this.baseUrl}/orders/${id}`);
  }

  async checkout(request: CheckoutRequest): Promise<Order> {
    return apiClient.post<Order>(`${this.baseUrl}/checkout`, request);
  }

  async cancelOrder(orderId: string): Promise<void> {
    return apiClient.post<void>(`${this.baseUrl}/orders/${orderId}/cancel`);
  }

  async addReview(productId: string, review: { rating: number; comment: string }): Promise<void> {
    return apiClient.post<void>(`${this.baseUrl}/products/${productId}/reviews`, review);
  }

  async getReviews(productId: string): Promise<any[]> {
    return apiClient.get<any[]>(`${this.baseUrl}/products/${productId}/reviews`);
  }
}