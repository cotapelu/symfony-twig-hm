export interface InsurancePolicy {
  id: string;
  userId: string;
  type: 'life' | 'health' | 'auto' | 'home' | 'travel' | 'business';
  provider: string;
  policyNumber: string;
  coverageAmount: number;
  premium: number;
  currency: string;
  startDate: string;
  endDate: string;
  status: 'active' | 'expired' | 'cancelled' | 'pending';
  beneficiaries?: string[];
  createdAt: string;
}

export interface InsuranceClaim {
  id: string;
  policyId: string;
  userId: string;
  type: string;
  amount: number;
  currency: string;
  status: 'submitted' | 'under_review' | 'approved' | 'rejected' | 'paid';
  description: string;
  documents?: string[];
  submittedAt: string;
  processedAt?: string;
}

export interface InsuranceState {
  policies: InsurancePolicy[];
  claims: InsuranceClaim[];
  loading: boolean;
  error: string | null;
  currentUserId: string | null;
}

export interface CreatePolicyRequest {
  type: InsurancePolicy['type'];
  provider: string;
  coverageAmount: number;
  premium: number;
  startDate: string;
  endDate: string;
  beneficiaries?: string[];
}

export interface SubmitClaimRequest {
  policyId: string;
  type: string;
  amount: number;
  description: string;
  documents?: string[];
}
