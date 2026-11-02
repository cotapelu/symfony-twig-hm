export interface LandConstructionPermit {
  id: string;
  userId: string;
  type: string;
  location: string;
  status: 'pending' | 'approved' | 'rejected';
  submittedAt: string;
}

export interface LandConstructionState {
  permits: LandConstructionPermit[];
  loading: boolean;
  error: string | null;
}
