export interface CitizenService {
  id: string;
  name: string;
  category: string;
  description: string;
  requirements: string[];
  processingTime: string;
  fee: number;
  status: 'active' | 'inactive';
}

export interface CitizenServiceRequest {
  id: string;
  userId: string;
  serviceId: string;
  status: 'pending' | 'processing' | 'completed' | 'rejected';
  submittedAt: string;
  processedAt?: string;
  documents: string[];
  notes?: string;
}

export interface CitizenServicesState {
  services: CitizenService[];
  requests: CitizenServiceRequest[];
  loading: boolean;
  error: string | null;
}
