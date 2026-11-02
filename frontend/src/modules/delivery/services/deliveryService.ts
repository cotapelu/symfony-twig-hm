import type { Delivery, DeliveryState, CreateDeliveryRequest, UpdateDeliveryStatusRequest } from '../contracts/deliveryTypes';
import { apiClient } from '../../../appcore/api-client';

const API_BASE = '/api/delivery';

export class DeliveryService {
  async getDeliveries(): Promise<Delivery[]> {
    return apiClient.get<Delivery[]>(API_BASE);
  }

  async getDelivery(id: string): Promise<Delivery> {
    return apiClient.get<Delivery>(`${API_BASE}/${id}`);
  }

  async createDelivery(request: CreateDeliveryRequest): Promise<Delivery> {
    return apiClient.post<Delivery>(API_BASE, request);
  }

  async updateDeliveryStatus(id: string, request: UpdateDeliveryStatusRequest): Promise<Delivery> {
    return apiClient.put<Delivery>(`${API_BASE}/${id}/status`, request);
  }

  async cancelDelivery(id: string): Promise<Delivery> {
    return apiClient.post<Delivery>(`${API_BASE}/${id}/cancel`, {});
  }

  async getActiveDeliveries(): Promise<Delivery[]> {
    return apiClient.get<Delivery[]>(`${API_BASE}?status=assigned,picked_up,in_transit`);
  }
}

export const deliveryService = new DeliveryService();
export default deliveryService;
