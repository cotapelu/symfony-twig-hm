export interface PolicyCreatedEvent { type: 'insurance.policy.created'; payload: { policyId: string; userId: string; type: string }; }
export interface ClaimSubmittedEvent { type: 'insurance.claim.submitted'; payload: { claimId: string; policyId: string; userId: string }; }
export interface ClaimApprovedEvent { type: 'insurance.claim.approved'; payload: { claimId: string; policyId: string }; }
export type InsuranceEvent = PolicyCreatedEvent | ClaimSubmittedEvent | ClaimApprovedEvent;
export const insuranceEvents = {
  policyCreated: (policyId: string, userId: string, type: string): PolicyCreatedEvent => ({ type: 'insurance.policy.created', payload: { policyId, userId, type } }),
  claimSubmitted: (claimId: string, policyId: string, userId: string): ClaimSubmittedEvent => ({ type: 'insurance.claim.submitted', payload: { claimId, policyId, userId } }),
  claimApproved: (claimId: string, policyId: string): ClaimApprovedEvent => ({ type: 'insurance.claim.approved', payload: { claimId, policyId } })
};
