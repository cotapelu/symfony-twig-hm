export interface ServiceStatusChangedEvent {
  type: 'smartcity.service.status.changed';
  payload: {
    serviceId: string;
    serviceName: string;
    oldStatus: string;
    newStatus: string;
  };
}

export interface AlertCreatedEvent {
  type: 'smartcity.alert.created';
  payload: {
    alertId: string;
    serviceId: string;
    severity: 'info' | 'warning' | 'critical';
    message: string;
  };
}

export interface AlertAcknowledgedEvent {
  type: 'smartcity.alert.acknowledged';
  payload: {
    alertId: string;
    acknowledgedBy: string;
  };
}

export interface DataUpdatedEvent {
  type: 'smartcity.data.updated';
  payload: {
    serviceId: string;
    timestamp: string;
  };
}

export interface ServiceMaintenanceScheduledEvent {
  type: 'smartcity.service.maintenance.scheduled';
  payload: {
    serviceId: string;
    scheduledAt: string;
    duration: number;
  };
}

export type SmartcityEvent =
  | ServiceStatusChangedEvent
  | AlertCreatedEvent
  | AlertAcknowledgedEvent
  | DataUpdatedEvent
  | ServiceMaintenanceScheduledEvent;

export const smartcityEvents = {
  serviceStatusChanged: (serviceId: string, serviceName: string, oldStatus: string, newStatus: string): ServiceStatusChangedEvent => ({
    type: 'smartcity.service.status.changed',
    payload: { serviceId, serviceName, oldStatus, newStatus }
  }),

  alertCreated: (alertId: string, serviceId: string, severity: 'info' | 'warning' | 'critical', message: string): AlertCreatedEvent => ({
    type: 'smartcity.alert.created',
    payload: { alertId, serviceId, severity, message }
  }),

  alertAcknowledged: (alertId: string, acknowledgedBy: string): AlertAcknowledgedEvent => ({
    type: 'smartcity.alert.acknowledged',
    payload: { alertId, acknowledgedBy }
  }),

  dataUpdated: (serviceId: string, timestamp: string): DataUpdatedEvent => ({
    type: 'smartcity.data.updated',
    payload: { serviceId, timestamp }
  }),

  serviceMaintenanceScheduled: (serviceId: string, scheduledAt: string, duration: number): ServiceMaintenanceScheduledEvent => ({
    type: 'smartcity.service.maintenance.scheduled',
    payload: { serviceId, scheduledAt, duration }
  })
};
