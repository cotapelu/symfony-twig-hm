export interface CivilSubmittedEvent { type: 'civil.submitted'; payload: { requestId: string; serviceId: string }; }
export type CivilEvent = CivilSubmittedEvent;

export const civilEvents = {
  submitted: (requestId: string, serviceId: string): CivilSubmittedEvent => ({
    type: 'civil.submitted', payload: { requestId, serviceId }
  })
};
