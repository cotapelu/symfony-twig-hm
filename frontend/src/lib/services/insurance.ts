// Insurance Service - Connects to PHP Insurance module
import { getApiUrl } from '../config/api';

export interface Policy {
  id: string;
  type: string;
  provider: string;
  coverage: number;
  premium: number;
  startDate: string;
  endDate: string;
  status: 'active' | 'expired' | 'cancelled';
}

export interface Claim {
  id: string;
  policyId: string;
  amount: number;
  status: 'pending' | 'approved' | 'rejected';
  submittedAt: string;
}

class InsuranceService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getMyPolicies(): Promise<Policy[]> {
    const url = getApiUrl('insurance', '/api/insurance/policies/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch policies');
    const data = await response.json();
    return data.policies || [];
  }

  async submitClaim(policyId: string, amount: number): Promise<Claim> {
    const url = getApiUrl('insurance', '/api/insurance/claims');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ policyId, amount }),
    });
    if (!response.ok) throw new Error('Failed to submit claim');
    return response.json();
  }

  async getMyClaims(): Promise<Claim[]> {
    const url = getApiUrl('insurance', '/api/insurance/claims/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch claims');
    const data = await response.json();
    return data.claims || [];
  }
}

export const insuranceService = new InsuranceService();
