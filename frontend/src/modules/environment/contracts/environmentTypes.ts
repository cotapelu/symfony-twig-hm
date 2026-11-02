export interface EnvironmentReport {
  id: string;
  type: string;
  description: string;
  location?: string;
  status: 'pending' | 'investigating' | 'resolved';
  createdAt: string;
}

export interface EnvironmentState {
  reports: EnvironmentReport[];
  loading: boolean;
  error: string | null;
}
