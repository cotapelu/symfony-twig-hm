export interface SmartCityService {
  id: string;
  name: string;
  category: 'transport' | 'energy' | 'waste' | 'water' | 'security' | 'environment';
  description?: string;
  location?: string;
  status: 'operational' | 'maintenance' | 'offline';
  data: Record<string, unknown>;
  updatedAt: string;
}

export interface SmartCityAlert {
  id: string;
  serviceId: string;
  severity: 'info' | 'warning' | 'critical';
  message: string;
  acknowledged: boolean;
  createdAt: string;
}

export interface SmartCityState {
  services: SmartCityService[];
  alerts: SmartCityAlert[];
  selectedService: SmartCityService | null;
  loading: boolean;
  error: string | null;
}

export interface UpdateServiceRequest {
  name?: string;
  description?: string;
  status?: 'operational' | 'maintenance' | 'offline';
}
