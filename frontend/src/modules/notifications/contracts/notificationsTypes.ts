export interface NotificationItem {
  id: string;
  type: 'message' | 'call' | 'payment' | 'system' | 'social' | 'security' | 'other';
  title: string;
  message: string;
  read: boolean;
  createdAt: string;
  userId?: string;
  action?: {
    type: string;
    label: string;
    url?: string;
  };
  metadata?: Record<string, any>;
}

export interface NotificationPreferences {
  email: boolean;
  push: boolean;
  sms: boolean;
  types: {
    message: boolean;
    call: boolean;
    payment: boolean;
    system: boolean;
    social: boolean;
    security: boolean;
  };
}

export interface NotificationsState {
  notifications: NotificationItem[];
  loading: boolean;
  error: string | null;
  unreadCount: number;
  preferences: NotificationPreferences | null;
}

export interface CreateNotificationRequest {
  type: string;
  title: string;
  message: string;
  userId?: string;
  action?: {
    type: string;
    label: string;
    url?: string;
  };
  metadata?: Record<string, any>;
}
