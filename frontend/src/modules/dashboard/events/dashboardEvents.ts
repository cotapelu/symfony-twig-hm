export interface DashboardUpdatedEvent {
  type: 'dashboard.updated';
  payload: {
    userId: string;
    timestamp: string;
  };
}

export interface StatsRefreshedEvent {
  type: 'dashboard.stats.refreshed';
  payload: {
    statType: string;
    value: number;
  };
}

export interface PanelOpenedEvent {
  type: 'dashboard.panel.opened';
  payload: {
    panelId: string;
    userId: string;
  };
}

export interface PanelClosedEvent {
  type: 'dashboard.panel.closed';
  payload: {
    panelId: string;
    userId: string;
  };
}

export type DashboardEvent =
  | DashboardUpdatedEvent
  | StatsRefreshedEvent
  | PanelOpenedEvent
  | PanelClosedEvent;

export const dashboardEvents = {
  dashboardUpdated: (userId: string): DashboardUpdatedEvent => ({
    type: 'dashboard.updated',
    payload: { userId, timestamp: new Date().toISOString() }
  }),

  statsRefreshed: (statType: string, value: number): StatsRefreshedEvent => ({
    type: 'dashboard.stats.refreshed',
    payload: { statType, value }
  }),

  panelOpened: (panelId: string, userId: string): PanelOpenedEvent => ({
    type: 'dashboard.panel.opened',
    payload: { panelId, userId }
  }),

  panelClosed: (panelId: string, userId: string): PanelClosedEvent => ({
    type: 'dashboard.panel.closed',
    payload: { panelId, userId }
  })
};
