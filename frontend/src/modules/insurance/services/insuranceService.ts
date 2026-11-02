import type { InsurancePolicy, InsuranceClaim, CreatePolicyRequest, SubmitClaimRequest } from '../contracts/insuranceTypes';

const API_BASE = '/api/insurance';

export class InsuranceService {
  async getPolicies(): Promise<InsurancePolicy[]> {
    const response = await fetch(`${API_BASE}/policies`);
    if (!response.ok) throw new Error('Failed to fetch policies');
    return response.json();
  }

  async getMyPolicies(): Promise<InsurancePolicy[]> {
    const response = await fetch(`${API_BASE}/my-policies`);
    if (!response.ok) throw new Error('Failed to fetch my policies');
    return response.json();
  }

  async getPolicy(id: string): Promise<InsurancePolicy> {
    const response = await fetch(`${API_BASE}/policies/${id}`);
    if (!response.ok) throw new Error('Failed to fetch policy');
    return response.json();
  }

  async createPolicy(request: CreatePolicyRequest): Promise<InsurancePolicy> {
    const response = await fetch(`${API_BASE}/policies`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create policy');
    return response.json();
  }

  async updatePolicy(id: string, request: Partial<CreatePolicyRequest>): Promise<InsurancePolicy> {
    const response = await fetch(`${API_BASE}/policies/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to update policy');
    return response.json();
  }

  async getClaims(): Promise<InsuranceClaim[]> {
    const response = await fetch(`${API_BASE}/claims`);
    if (!response.ok) throw new Error('Failed to fetch claims');
    return response.json();
  }

  async getMyClaims(): Promise<InsuranceClaim[]> {
    const response = await fetch(`${API_BASE}/my-claims`);
    if (!response.ok) throw new Error('Failed to fetch my claims');
    return response.json();
  }

  async submitClaim(request: SubmitClaimRequest): Promise<InsuranceClaim> {
    const response = await fetch(`${API_BASE}/claims`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to submit claim');
    return response.json();
  }
}

export const insuranceService = new InsuranceService();
export default insuranceService;
