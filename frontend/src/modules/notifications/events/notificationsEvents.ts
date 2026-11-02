import { createEventDispatcher } from 'svelte';

export interface NotificationEvent {
  type: 'notification_received' | 'notification_read' | 'notification_deleted' | 'preferences_updated';
  data?: any;
  timestamp: number;
}

export function createNotificationEvents() {
  const dispatch = createEventDispatcher();

  return {
    dispatchNotificationReceived(notification: any) {
      dispatch('notification_received', {
        type: 'notification_received',
        data: notification,
        timestamp: Date.now()
      });
    },

    dispatchNotificationRead(notificationId: string) {
      dispatch('notification_read', {
        type: 'notification_read',
        data: { notificationId },
        timestamp: Date.now()
      });
    },

    dispatchNotificationDeleted(notificationId: string) {
      dispatch('notification_deleted', {
        type: 'notification_deleted',
        data: { notificationId },
        timestamp: Date.now()
      });
    },

    dispatchPreferencesUpdated(preferences: any) {
      dispatch('preferences_updated', {
        type: 'preferences_updated',
        data: preferences,
        timestamp: Date.now()
      });
    }
  };
}

export const notificationEventTypes = {
  RECEIVED: 'notification_received',
  READ: 'notification_read',
  DELETED: 'notification_deleted',
  PREFERENCES_UPDATED: 'preferences_updated'
} as const;
