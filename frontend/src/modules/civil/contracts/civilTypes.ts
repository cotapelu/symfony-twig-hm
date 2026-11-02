export interface CivilService {
  id: string;
  name: string;
  category: string;
  description: string;
  requirements: string[];
  processingTime: string;
  fee: number;
}

export interface CivilRequest {
  id: string;
  userId: string;
  serviceId: string;
  status: 'pending' | 'processing' | 'completed' | 'rejected';
  submittedAt: string;
  documents: string[];
}

export interface CivilState {
  services: CivilService[];
  requests: CivilRequest[];
  loading: boolean;
  error: string | null;
  currentUserId: string | null;
}
