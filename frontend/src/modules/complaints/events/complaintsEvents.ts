export interface ComplaintSubmittedEvent { type: 'complaint.submitted'; payload: { complaintId: string }; }
export type ComplaintsEvent = ComplaintSubmittedEvent;

export const complaintsEvents = {
  submitted: (complaintId: string): ComplaintSubmittedEvent => ({ type: 'complaint.submitted', payload: { complaintId } })
};
