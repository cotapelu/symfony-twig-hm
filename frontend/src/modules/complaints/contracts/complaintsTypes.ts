export interface Complaint {
  id: string;
  userId: string;
  category: string;
  subject: string;
  description: string;
  status: 'submitted' | 'processing' | 'resolved' | 'rejected';
  submittedAt: string;
  resolvedAt?: string;
  resolution?: string;
}

export interface ComplaintsState {
  complaints: Complaint[];
  loading: boolean;
  error: string | null;
}
