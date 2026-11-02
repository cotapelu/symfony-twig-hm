import { writable } from "svelte/store";
import { user, isAuthenticated } from './global';

export interface Notification {
  id: number;
  type: "success" | "error" | "warning" | "info";
  message: string;
  duration?: number;
}

export interface User {
  id: string;
  name: string;
  email: string;
  avatar?: string;
}

export const theme = writable<"light" | "dark">("light");
export const notifications = writable<Notification[]>([]);
export { user, isAuthenticated };

export const sidebarOpen = writable(true);
export const loading = writable(false);

export function addNotification(notification: Omit<Notification, "id">) {
  notifications.update((current) => [
    ...current,
    { ...notification, id: Date.now() },
  ]);
}

export { userStore } from '../../stores/user';
