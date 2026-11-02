export interface EmergencyAlert {
  id: string;
  type: string;
  title: string;
  description: string;
  location?: string;
  severity: 'low' | 'medium' | 'high' | 'critical';
  createdAt: string;
}

export interface EmergencyState {
  alerts: EmergencyAlert[];
  loading: boolean;
  error: string | null;
}
