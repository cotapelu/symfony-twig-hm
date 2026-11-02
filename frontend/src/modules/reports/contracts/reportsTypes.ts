export interface Report {
  id: string;
  title: string;
  type: string;
  description: string;
  status: 'draft' | 'submitted' | 'processed';
  createdAt: string;
}

export interface ReportsState {
  reports: Report[];
  loading: boolean;
  error: string | null;
}
