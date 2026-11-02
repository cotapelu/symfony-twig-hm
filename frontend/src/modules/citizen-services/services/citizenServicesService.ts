import type { CitizenService, CitizenServiceRequest, CitizenServicesState } from '../contracts/citizenServicesTypes';
import { apiClient } from '../../../appcore/api-client';

const API_BASE = '/api/citizen-services';

export class CitizenServicesService {
  async getServices(): Promise<CitizenService[]> {
    return apiClient.get<CitizenService[]>(`${API_BASE}/services`);
  }

  async getRequests(): Promise<CitizenServiceRequest[]> {
    return apiClient.get<CitizenServiceRequest[]>(`${API_BASE}/requests`);
  }

  async submitRequest(serviceId: string, documents: string[]): Promise<CitizenServiceRequest> {
    return apiClient.post<CitizenServiceRequest>(`${API_BASE}/requests`, { serviceId, documents });
  }
}

export const citizenServicesService = new CitizenServicesService();
export default citizenServicesService;