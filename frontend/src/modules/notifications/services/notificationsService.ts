import type { NotificationItem, NotificationPreferences, CreateNotificationRequest } from '../contracts/notificationsTypes';

export class NotificationsService {
  private baseUrl = '/api/notifications';

  async getNotifications(): Promise<NotificationItem[]> {
    const response = await fetch(this.baseUrl);
    if (!response.ok) throw new Error('Failed to fetch notifications');
    return response.json();
  }

  async markAsRead(id: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${id}/read`, { method: 'POST' });
    if (!response.ok) throw new Error('Failed to mark notification as read');
  }

  async markAllAsRead(): Promise<void> {
    const response = await fetch(`${this.baseUrl}/read-all`, { method: 'POST' });
    if (!response.ok) throw new Error('Failed to mark all notifications as read');
  }

  async deleteNotification(id: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${id}`, { method: 'DELETE' });
    if (!response.ok) throw new Error('Failed to delete notification');
  }

  async getPreferences(): Promise<NotificationPreferences> {
    const response = await fetch(`${this.baseUrl}/preferences`);
    if (!response.ok) throw new Error('Failed to fetch preferences');
    return response.json();
  }

  async updatePreferences(preferences: Partial<NotificationPreferences>): Promise<void> {
    const response = await fetch(`${this.baseUrl}/preferences`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(preferences)
    });
    if (!response.ok) throw new Error('Failed to update preferences');
  }

  async createNotification(request: CreateNotificationRequest): Promise<NotificationItem> {
    const response = await fetch(this.baseUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create notification');
    return response.json();
  }

  subscribeToNotifications(callback: (notification: NotificationItem) => void): () => void {
    const ws = new WebSocket(`wss://${window.location.host}/ws/notifications`);
    
    ws.onmessage = (event) => {
      const notification = JSON.parse(event.data);
      callback(notification);
    };

    ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };

    return () => ws.close();
  }
}
