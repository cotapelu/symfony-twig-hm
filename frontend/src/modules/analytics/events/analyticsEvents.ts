export interface ReportCreatedEvent {
  type: 'analytics.report.created';
  payload: {
    reportId: string;
    userId: string;
    reportType: string;
  };
}

export interface ReportDeletedEvent {
  type: 'analytics.report.deleted';
  payload: {
    reportId: string;
    userId: string;
  };
}

export interface DashboardCreatedEvent {
  type: 'analytics.dashboard.created';
  payload: {
    dashboardId: string;
    userId: string;
    name: string;
  };
}

export interface DashboardUpdatedEvent {
  type: 'analytics.dashboard.updated';
  payload: {
    dashboardId: string;
    userId: string;
    name: string;
  };
}

export interface EventTrackedEvent {
  type: 'analytics.event.tracked';
  payload: {
    eventId: string;
    userId: string;
    eventName: string;
    eventType: string;
  };
}

export type AnalyticsEvent = 
  | ReportCreatedEvent 
  | ReportDeletedEvent 
  | DashboardCreatedEvent 
  | DashboardUpdatedEvent 
  | EventTrackedEvent;

export const analyticsEvents = {
  reportCreated: (reportId: string, userId: string, reportType: string): ReportCreatedEvent => ({
    type: 'analytics.report.created',
    payload: { reportId, userId, reportType }
  }),

  reportDeleted: (reportId: string, userId: string): ReportDeletedEvent => ({
    type: 'analytics.report.deleted',
    payload: { reportId, userId }
  }),

  dashboardCreated: (dashboardId: string, userId: string, name: string): DashboardCreatedEvent => ({
    type: 'analytics.dashboard.created',
    payload: { dashboardId, userId, name }
  }),

  dashboardUpdated: (dashboardId: string, userId: string, name: string): DashboardUpdatedEvent => ({
    type: 'analytics.dashboard.updated',
    payload: { dashboardId, userId, name }
  }),

  eventTracked: (eventId: string, userId: string, eventName: string, eventType: string): EventTrackedEvent => ({
    type: 'analytics.event.tracked',
    payload: { eventId, userId, eventName, eventType }
  })
};
