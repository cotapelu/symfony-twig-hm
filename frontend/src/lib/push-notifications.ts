import { writable, get } from "svelte/store";
import { browser } from "$app/environment";

export interface PushNotification {
  id: string;
  title: string;
  body: string;
  icon?: string;
  badge?: string;
  tag?: string;
  data?: any;
  timestamp: number;
  read: boolean;
  action?: string;
}

export interface NotificationPreferences {
  enabled: boolean;
  sound: boolean;
  vibration: boolean;
  urgent: boolean;
  news: boolean;
  applications: boolean;
  complaints: boolean;
  emergency: boolean;
}

const STORAGE_KEY = "push_notifications";
const PREFS_KEY = "notification_prefs";

export class PushNotificationService {
  private notifications = writable<PushNotification[]>([]);
  private preferences = writable<NotificationPreferences>({
    enabled: true,
    sound: true,
    vibration: true,
    urgent: true,
    news: true,
    applications: true,
    complaints: true,
    emergency: true,
  });

  private registration: ServiceWorkerRegistration | null = null;
  private vapidPublicKey = "BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBUYIHBQFLXYp5Nksh8U";

  constructor() {
    if (browser) {
      this.init();
    }
  }

  private async init() {
    this.loadFromStorage();
    await this.registerServiceWorker();
    await this.requestPermission();
  }

  private loadFromStorage() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        this.notifications.set(JSON.parse(stored));
      }

      const prefsStored = localStorage.getItem(PREFS_KEY);
      if (prefsStored) {
        this.preferences.set(JSON.parse(prefsStored));
      }
    } catch (e) {
      console.error("Failed to load notifications:", e);
    }
  }

  private saveToStorage() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(get(this.notifications)));
      localStorage.setItem(PREFS_KEY, JSON.stringify(get(this.preferences)));
    } catch (e) {
      console.error("Failed to save notifications:", e);
    }
  }

  private async registerServiceWorker() {
    if (!("serviceWorker" in navigator)) return;

    try {
      this.registration = await navigator.serviceWorker.register("/sw.js");
      console.log("Service Worker registered");
    } catch (error) {
      console.error("Service Worker registration failed:", error);
    }
  }

  async requestPermission(): Promise<NotificationPermission> {
    if (!("Notification" in window)) {
      return "denied";
    }

    if (Notification.permission === "granted") {
      return "granted";
    }

    if (Notification.permission !== "denied") {
      const permission = await Notification.requestPermission();
      return permission;
    }

    return Notification.permission;
  }

  async subscribe() {
    if (!this.registration) {
      console.error("Service Worker not registered");
      return null;
    }

    try {
      const subscription = await this.registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey),
      });

      console.log("Push subscription successful");
      return subscription;
    } catch (error) {
      console.error("Push subscription failed:", error);
      return null;
    }
  }

  private urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  async sendNotification(notification: Omit<PushNotification, "id" | "timestamp" | "read">) {
    const prefs = get(this.preferences);
    if (!prefs.enabled) return;

    const newNotification: PushNotification = {
      ...notification,
      id: `notif_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      timestamp: Date.now(),
      read: false,
    };

    this.notifications.update((n) => [newNotification, ...n]);
    this.saveToStorage();

    if (Notification.permission === "granted") {
      const pref = get(this.preferences);
      if (pref.sound) {
        const audio = new Audio("/notification.mp3");
        audio.play().catch(() => {});
      }

      if (pref.vibration && navigator.vibrate) {
        navigator.vibrate([200, 100, 200]);
      }

      new Notification(notification.title, {
        body: notification.body,
        icon: notification.icon || "/icon.png",
        badge: notification.badge || "/badge.png",
        tag: notification.tag,
        data: notification.data,
      });
    }

    if (this.registration && Notification.permission === "granted") {
      this.registration.showNotification(notification.title, {
        body: notification.body,
        icon: notification.icon || "/icon.png",
        badge: "/badge.png",
        tag: notification.tag,
        data: notification.data,
        actions: notification.action ? [{ action: notification.action, title: "Xem" }] : [],
      });
    }
  }

  markAsRead(id: string) {
    this.notifications.update((n) =>
      n.map((notif) => (notif.id === id ? { ...notif, read: true } : notif))
    );
    this.saveToStorage();
  }

  markAllAsRead() {
    this.notifications.update((n) => n.map((notif) => ({ ...notif, read: true })));
    this.saveToStorage();
  }

  deleteNotification(id: string) {
    this.notifications.update((n) => n.filter((notif) => notif.id !== id));
    this.saveToStorage();
  }

  clearAll() {
    this.notifications.set([]);
    this.saveToStorage();
  }

  updatePreferences(prefs: Partial<NotificationPreferences>) {
    this.preferences.update((p) => ({ ...p, ...prefs }));
    this.saveToStorage();
  }

  getNotifications() {
    return this.notifications;
  }

  getPreferences() {
    return this.preferences;
  }

  getUnreadCount() {
    return get(this.notifications).filter((n) => !n.read).length;
  }

  addTestNotifications() {
    const testNotifications = [
      {
        title: "📋 Hồ sơ hoàn thành",
        body: "Hồ sơ đăng ký khai sinh đã được xử lý",
        tag: "application",
      },
      {
        title: "📰 Tin mới",
        body: "Thông báo nghỉ Tết Nguyên đán 2026",
        tag: "news",
      },
      {
        title: "⚠️ Nhắc nhở",
        body: "Hồ sơ CCCD cần bổ sung giấy tờ",
        tag: "reminder",
      },
    ];

    testNotifications.forEach((notif, index) => {
      setTimeout(() => {
        this.sendNotification(notif);
      }, index * 1000);
    });
  }
}

export const pushNotificationService = new PushNotificationService();
export default pushNotificationService;
