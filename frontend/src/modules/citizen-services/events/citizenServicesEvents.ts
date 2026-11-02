export interface ServiceSubmittedEvent { type: 'citizen-service.submitted'; payload: { requestId: string; serviceId: string }; }
export interface ServiceCompletedEvent { type: 'citizen-service.completed'; payload: { requestId: string }; }
export type CitizenServicesEvent = ServiceSubmittedEvent | ServiceCompletedEvent;

export const citizenServicesEvents = {
  serviceSubmitted: (requestId: string, serviceId: string): ServiceSubmittedEvent => ({
    type: 'citizen-service.submitted', payload: { requestId, serviceId }
  }),
  serviceCompleted: (requestId: string): ServiceCompletedEvent => ({ type: 'citizen-service.completed', payload: { requestId } })
};
