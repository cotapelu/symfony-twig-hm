// Notifications Service - Connects to Go realtime module
import { getApiUrl } from '../config/api';

export interface Notification {
  id: string;
  type: string;
  title: string;
  message: string;
  data?: Record<string, any>;
  read: boolean;
  createdAt: string;
}

class NotificationsService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getNotifications(): Promise<Notification[]> {
    const url = getApiUrl('notifications', '/api/notifications');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch notifications');
    const data = await response.json();
    return data.notifications || [];
  }

  async markAsRead(id: string): Promise<void> {
    const url = getApiUrl('notifications', `/api/notifications/${id}/read`);
    await fetch(url, { method: 'POST', headers: this.getHeaders() });
  }

  async markAllAsRead(): Promise<void> {
    const url = getApiUrl('notifications', '/api/notifications/read-all');
    await fetch(url, { method: 'POST', headers: this.getHeaders() });
  }

  async deleteNotification(id: string): Promise<void> {
    const url = getApiUrl('notifications', `/api/notifications/${id}`);
    await fetch(url, { method: 'DELETE', headers: this.getHeaders() });
  }

  async getUnreadCount(): Promise<number> {
    const url = getApiUrl('notifications', '/api/notifications/unread-count');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to get count');
    const data = await response.json();
    return data.count || 0;
  }
}

export const notificationsService = new NotificationsService();
