export interface DealExpiredEvent { type: 'deal.expired'; payload: { dealId: string }; }
export type DealsEvent = DealExpiredEvent;

export const dealsEvents = {
  dealExpired: (dealId: string): DealExpiredEvent => ({ type: 'deal.expired', payload: { dealId } })
};
