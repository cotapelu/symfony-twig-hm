export interface AlertCreatedEvent { type: 'emergency.alert.created'; payload: { alertId: string }; }
export type EmergencyEvent = AlertCreatedEvent;

export const emergencyEvents = {
  alertCreated: (alertId: string): AlertCreatedEvent => ({ type: 'emergency.alert.created', payload: { alertId } })
};
