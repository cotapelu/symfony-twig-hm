// Notifications Store - TypeScript Version
// PHP Notification Module with type safety

import { writable } from "svelte/store";
import { PHP } from "../lib/api";
import type { Notification } from "../lib/types";

interface NotificationsState {
  notifications: Notification[];
  unreadCount: number;
  loading: boolean;
  error: string | null;
}

function createNotificationsStore() {
  const initialState: NotificationsState = {
    notifications: [],
    unreadCount: 0,
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<NotificationsState>(initialState);

  return {
    subscribe,

    loadNotifications: async (): Promise<Notification[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const notifications = await PHP.getNotifications();
        update((prev) => ({
          ...prev,
          notifications,
          unreadCount: notifications.filter((n) => !n.readAt).length,
          loading: false,
        }));
        return notifications;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to load notifications";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    markAsRead: async (notificationId: string): Promise<void> => {
      try {
        await PHP.markNotificationRead(notificationId);
        update((prev) => ({
          ...prev,
          notifications: prev.notifications.map((n) =>
            n.id === notificationId
              ? { ...n, readAt: new Date().toISOString() }
              : n,
          ),
          unreadCount: Math.max(0, prev.unreadCount - 1),
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to mark notification as read";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    markAllAsRead: async (): Promise<void> => {
      try {
        const unreadNotifications = (await PHP.getNotifications()).filter(
          (n: Notification) => !n.readAt,
        );
        await Promise.all(
          unreadNotifications.map((n) => PHP.markNotificationRead(n.id)),
        );
        const refreshedNotifications = await PHP.getNotifications();
        update((prev) => ({
          ...prev,
          notifications: refreshedNotifications,
          unreadCount: 0,
          loading: false,
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to mark all as read";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    addNotification: (notification: Notification): void => {
      update((prev) => ({
        ...prev,
        notifications: [notification, ...prev.notifications],
        unreadCount: prev.unreadCount + 1,
      }));
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        notifications: [],
        unreadCount: 0,
        loading: false,
        error: null,
      });
    },
  };
}

export const notificationsStore = createNotificationsStore();

export const {
  loadNotifications,
  markAsRead,
  markAllAsRead,
  addNotification,
  clearError,
} = notificationsStore;
