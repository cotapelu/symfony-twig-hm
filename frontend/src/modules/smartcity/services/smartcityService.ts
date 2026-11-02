import type { SmartCityService, SmartCityAlert, SmartCityState, UpdateServiceRequest } from '../contracts/smartcityTypes';

const API_BASE = '/api/smartcity';

export class SmartcityService {
  async getServices(): Promise<SmartCityService[]> {
    const response = await fetch(`${API_BASE}/services`);
    if (!response.ok) throw new Error('Failed to fetch services');
    return response.json();
  }

  async getService(id: string): Promise<SmartCityService> {
    const response = await fetch(`${API_BASE}/services/${id}`);
    if (!response.ok) throw new Error('Failed to fetch service');
    return response.json();
  }

  async getServicesByCategory(category: string): Promise<SmartCityService[]> {
    const response = await fetch(`${API_BASE}/services?category=${encodeURIComponent(category)}`);
    if (!response.ok) throw new Error('Failed to fetch services by category');
    return response.json();
  }

  async updateService(id: string, request: UpdateServiceRequest): Promise<SmartCityService> {
    const response = await fetch(`${API_BASE}/services/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to update service');
    return response.json();
  }

  async getAlerts(): Promise<SmartCityAlert[]> {
    const response = await fetch(`${API_BASE}/alerts`);
    if (!response.ok) throw new Error('Failed to fetch alerts');
    return response.json();
  }

  async acknowledgeAlert(id: string): Promise<SmartCityAlert> {
    const response = await fetch(`${API_BASE}/alerts/${id}/acknowledge`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to acknowledge alert');
    return response.json();
  }

  async getServiceData(serviceId: string): Promise<Record<string, unknown>> {
    const response = await fetch(`${API_BASE}/services/${serviceId}/data`);
    if (!response.ok) throw new Error('Failed to fetch service data');
    return response.json();
  }
}

export const smartcityService = new SmartcityService();
export default smartcityService;
