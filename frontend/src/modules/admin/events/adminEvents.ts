export interface UserCreatedEvent {
  type: 'admin.user.created';
  payload: {
    userId: string;
    username: string;
    role: string;
  };
}

export interface UserStatusChangedEvent {
  type: 'admin.user.status_changed';
  payload: {
    userId: string;
    oldStatus: string;
    newStatus: string;
  };
}

export interface DocumentApprovedEvent {
  type: 'admin.document.approved';
  payload: {
    documentId: string;
    userId: string;
  };
}

export interface DocumentRejectedEvent {
  type: 'admin.document.rejected';
  payload: {
    documentId: string;
    userId: string;
    reason?: string;
  };
}

export interface SystemAlertEvent {
  type: 'admin.system.alert';
  payload: {
    level: 'info' | 'warning' | 'error' | 'critical';
    message: string;
    details?: Record<string, unknown>;
  };
}

export type AdminEvent = 
  | UserCreatedEvent 
  | UserStatusChangedEvent 
  | DocumentApprovedEvent 
  | DocumentRejectedEvent 
  | SystemAlertEvent;

export const adminEvents = {
  userCreated: (userId: string, username: string, role: string): UserCreatedEvent => ({
    type: 'admin.user.created',
    payload: { userId, username, role }
  }),

  userStatusChanged: (userId: string, oldStatus: string, newStatus: string): UserStatusChangedEvent => ({
    type: 'admin.user.status_changed',
    payload: { userId, oldStatus, newStatus }
  }),

  documentApproved: (documentId: string, userId: string): DocumentApprovedEvent => ({
    type: 'admin.document.approved',
    payload: { documentId, userId }
  }),

  documentRejected: (documentId: string, userId: string, reason?: string): DocumentRejectedEvent => ({
    type: 'admin.document.rejected',
    payload: { documentId, userId, reason }
  }),

  systemAlert: (level: 'info' | 'warning' | 'error' | 'critical', message: string, details?: Record<string, unknown>): SystemAlertEvent => ({
    type: 'admin.system.alert',
    payload: { level, message, details }
  })
};
