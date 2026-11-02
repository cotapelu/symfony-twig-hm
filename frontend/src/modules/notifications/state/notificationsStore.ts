import { writable } from 'svelte/store';
import type { NotificationItem, NotificationPreferences, NotificationsState } from '../contracts/notificationsTypes';
import { NotificationsService } from '../services/notificationsService';

const initialState: NotificationsState = {
  notifications: [],
  loading: false,
  error: null,
  unreadCount: 0,
  preferences: null
};

function createNotificationsStore() {
  const { subscribe, set, update } = writable<NotificationsState>(initialState);
  const service = new NotificationsService();

  return {
    subscribe,

    async loadNotifications() {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const notifications = await service.getNotifications();
        update(state => ({
          ...state,
          notifications,
          unreadCount: notifications.filter(n => !n.read).length,
          loading: false
        }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load notifications',
          loading: false
        }));
      }
    },

    async markAsRead(id: string) {
      try {
        await service.markAsRead(id);
        update(state => {
          const notifications = state.notifications.map(n =>
            n.id === id ? { ...n, read: true } : n
          );
          return {
            ...state,
            notifications,
            unreadCount: notifications.filter(n => !n.read).length
          };
        });
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to mark as read'
        }));
      }
    },

    async markAllAsRead() {
      try {
        await service.markAllAsRead();
        update(state => ({
          ...state,
          notifications: state.notifications.map(n => ({ ...n, read: true })),
          unreadCount: 0
        }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to mark all as read'
        }));
      }
    },

    async deleteNotification(id: string) {
      try {
        await service.deleteNotification(id);
        update(state => {
          const notifications = state.notifications.filter(n => n.id !== id);
          return {
            ...state,
            notifications,
            unreadCount: notifications.filter(n => !n.read).length
          };
        });
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to delete notification'
        }));
      }
    },

    async loadPreferences() {
      try {
        const preferences = await service.getPreferences();
        update(state => ({ ...state, preferences }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load preferences'
        }));
      }
    },

    async updatePreferences(preferences: Partial<NotificationPreferences>) {
      try {
        await service.updatePreferences(preferences);
        update(state => ({
          ...state,
          preferences: { ...state.preferences!, ...preferences }
        }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to update preferences'
        }));
      }
    },

    clearAll() {
      update(state => ({
        ...state,
        notifications: [],
        unreadCount: 0
      }));
    },

    clearError() {
      update(state => ({ ...state, error: null }));
    },

    addNotification(notification: NotificationItem) {
      update(state => ({
        ...state,
        notifications: [notification, ...state.notifications],
        unreadCount: state.unreadCount + (notification.read ? 0 : 1)
      }));
    },
    reset() {
      set(initialState);
    }
  };
}

export const notificationsStore = createNotificationsStore();
export const { loadNotifications, markAsRead, markAllAsRead, deleteNotification, clearError } = notificationsStore;
