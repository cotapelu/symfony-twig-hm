/**
 * Native Services - Capacitor Plugins Wrapper
 * Provides type-safe, Promise-based access to native device features
 * Usage: import { camera, geolocation, pushNotifications, ... } from '$lib/services/native';
 */

import { Capacitor } from '@capacitor/core';
import type { PluginListenerHandle } from '@capacitor/core';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import type { Photo } from '@capacitor/camera';
import { Geolocation } from '@capacitor/geolocation';
import type { Position, PositionOptions, WatchPositionCallback } from '@capacitor/geolocation';
import { PushNotifications } from '@capacitor/push-notifications';
import type { PushNotificationSchema, Token } from '@capacitor/push-notifications';
import { LocalNotifications } from '@capacitor/local-notifications';
import type {
  ActionPerformed,
  LocalNotificationSchema,
  ScheduleOptions,
} from '@capacitor/local-notifications';
import { Device } from '@capacitor/device';
import type { DeviceInfo } from '@capacitor/device';
import { Network } from '@capacitor/network';
import type { NetworkStatus } from '@capacitor/network';
import { Filesystem, Directory } from '@capacitor/filesystem';
import type { Encoding, ReadFileOptions, WriteFileOptions } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';
import type { ShareOptions } from '@capacitor/share';
import { Haptics, ImpactStyle, NotificationType } from '@capacitor/haptics';
import { Clipboard } from '@capacitor/clipboard';
import { App } from '@capacitor/app';
import type { URLOpenListenerEvent } from '@capacitor/app';
import { Browser } from '@capacitor/browser';
import type { OpenOptions } from '@capacitor/browser';
import { Preferences } from '@capacitor/preferences';

/**
 * Payload of removeDeliveredNotifications(). Derived from the installed plugin
 * API because @capacitor/push-notifications does not export this type.
 */
type DeliveredNotifications = Awaited<
  ReturnType<typeof PushNotifications.getDeliveredNotifications>
>;

// ============================================
// Type Guards & Helpers
// ============================================

const isNative = (): boolean => Capacitor.isNativePlatform();

const assertNative = (feature: string): void => {
  if (!isNative()) {
    throw new Error(
      `[Native] ${feature} is only available on native platforms (iOS/Android). ` +
        'Running in browser - use PWA fallback or mock.'
    );
  }
};

// ============================================
// Camera Service
// ============================================

export interface CameraOptions {
  quality?: number;
  allowEditing?: boolean;
  resultType?: CameraResultType;
  source?: CameraSource;
  saveToGallery?: boolean;
  width?: number;
  height?: number;
  correctOrientation?: boolean;
}

export const camera = {
  /**
   * Check if camera is available
   */
  async isAvailable(): Promise<boolean> {
    if (!isNative()) return false;
    try {
      const permissions = await Camera.checkPermissions();
      return permissions.camera === 'granted' || permissions.camera === 'prompt';
    } catch {
      return false;
    }
  },

  /**
   * Request camera permissions
   */
  async requestPermissions(): Promise<{ camera: string; photos: string }> {
    assertNative('Camera');
    return Camera.requestPermissions();
  },

  /**
   * Take a photo or select from gallery
   */
  async getPhoto(options: CameraOptions = {}): Promise<Photo | null> {
    assertNative('Camera');
    const {
      quality = 90,
      allowEditing = false,
      resultType = CameraResultType.Uri,
      source = CameraSource.Camera,
      saveToGallery = false,
      width,
      height,
      correctOrientation = true,
    } = options;

    try {
      return await Camera.getPhoto({
        quality,
        allowEditing,
        resultType,
        source,
        saveToGallery,
        width,
        height,
        correctOrientation,
      });
    } catch (error) {
      if ((error as Error).message.includes('cancelled')) return null;
      throw error;
    }
  },

  /**
   * Take photo directly (camera only)
   */
  async takePhoto(options?: Omit<CameraOptions, 'source'>): Promise<Photo | null> {
    return this.getPhoto({ ...options, source: CameraSource.Camera });
  },

  /**
   * Pick from gallery
   */
  async pickFromGallery(options?: Omit<CameraOptions, 'source'>): Promise<Photo | null> {
    return this.getPhoto({ ...options, source: CameraSource.Photos });
  },

  /**
   * Convert photo to base64 string
   */
  async photoToBase64(photo: Photo): Promise<string> {
    assertNative('Camera');
    // CameraPlugin has no readFile(): base64 is populated when the caller asks
    // for CameraResultType.Base64, so the data must already be on the Photo.
    if (!photo.base64String) {
      throw new Error(
        '[Native] Camera photoToBase64 - Photo has no base64String - ' +
          'call getPhoto with resultType = CameraResultType.Base64'
      );
    }
    return `data:image/jpeg;base64,${photo.base64String}`;
  },

  /**
   * Convert photo to blob for upload
   */
  async photoToBlob(photo: Photo): Promise<Blob> {
    // Photo exposes no blob member; the only recoverable bytes are webPath.
    if (!photo.webPath) {
      throw new Error(
        '[Native] Camera photoToBlob - Photo has no webPath - ' +
          'call getPhoto with saveToGallery or read the file via filesystem'
      );
    }
    const response = await fetch(photo.webPath);
    return response.blob();
  },
};

// ============================================
// Geolocation Service
// ============================================

export interface GeolocationOptions extends PositionOptions {
  enableHighAccuracy?: boolean;
  timeout?: number;
  maximumAge?: number;
}

export const geolocation = {
  /**
   * Check if location services are available
   */
  async isAvailable(): Promise<boolean> {
    if (!isNative()) return false;
    try {
      const permissions = await Geolocation.checkPermissions();
      return permissions.location === 'granted' || permissions.location === 'prompt';
    } catch {
      return false;
    }
  },

  /**
   * Request location permissions
   */
  async requestPermissions(): Promise<{ location: string; coarseLocation: string }> {
    assertNative('Geolocation');
    return Geolocation.requestPermissions();
  },

  /**
   * Get current position once
   */
  async getCurrentPosition(options: GeolocationOptions = {}): Promise<Position> {
    assertNative('Geolocation');
    const { enableHighAccuracy = true, timeout = 10000, maximumAge = 0 } = options;
    return Geolocation.getCurrentPosition({ enableHighAccuracy, timeout, maximumAge });
  },

  /**
   * Watch position changes (returns callback ID to clear)
   */
  async watchPosition(
    callback: WatchPositionCallback,
    options: GeolocationOptions = {}
  ): Promise<string> {
    assertNative('Geolocation');
    const { enableHighAccuracy = true, timeout = 10000, maximumAge = 0 } = options;
    return Geolocation.watchPosition({ enableHighAccuracy, timeout, maximumAge }, callback);
  },

  /**
   * Stop watching position
   */
  async clearWatch(watchId: string): Promise<void> {
    assertNative('Geolocation');
    return Geolocation.clearWatch({ id: watchId });
  },
};

// ============================================
// Push Notifications Service
// ============================================

export interface PushNotificationActionPerformed {
  actionId: string;
  inputValue?: string;
  notification: PushNotificationSchema;
}

export const pushNotifications = {
  /**
   * Initialize push notifications (call on app start)
   */
  async initialize(): Promise<void> {
    assertNative('PushNotifications');
    await PushNotifications.requestPermissions();
    await PushNotifications.register();
  },

  /**
   * Request permissions
   */
  async requestPermissions(): Promise<{ receive: string }> {
    assertNative('PushNotifications');
    return PushNotifications.requestPermissions();
  },

  /**
   * Get FCM/APNs token
   */
  async getToken(): Promise<string> {
    assertNative('PushNotifications');
    // Capacitor exposes no getToken(): the FCM/APNs token only arrives via the
    // 'registration' event. register() re-triggers it so the await always settles.
    const token = await new Promise<Token>((resolve, reject) => {
      let settled = false;
      const finish = (done: () => void) => {
        if (!settled) {
          settled = true;
          done();
        }
      };
      void PushNotifications.addListener('registration', (t) => finish(() => resolve(t)));
      void PushNotifications.addListener('registrationError', (e) => finish(() => reject(e)));
    }).finally(() => PushNotifications.removeAllListeners());
    return token.value;
  },

  /**
   * Add listener for token registration
   */
  onRegistration(callback: (token: Token) => void): string {
    assertNative('PushNotifications');
    PushNotifications.addListener('registration', callback);
    return 'registration';
  },

  /**
   * Add listener for push received
   */
  onPushReceived(callback: (notification: PushNotificationSchema) => void): string {
    assertNative('PushNotifications');
    PushNotifications.addListener('pushNotificationReceived', callback);
    return 'pushNotificationReceived';
  },

  /**
   * Add listener for push action performed
   */
  onActionPerformed(callback: (action: PushNotificationActionPerformed) => void): string {
    assertNative('PushNotifications');
    PushNotifications.addListener('pushNotificationActionPerformed', callback);
    return 'pushNotificationActionPerformed';
  },

  /**
   * Remove all listeners
   */
  async removeAllListeners(): Promise<void> {
    assertNative('PushNotifications');
    await PushNotifications.removeAllListeners();
  },

  /**
   * Get delivered notifications
   */
  async getDelivered(): Promise<PushNotificationSchema[]> {
    assertNative('PushNotifications');
    const { notifications } = await PushNotifications.getDeliveredNotifications();
    return notifications;
  },

  /**
   * Remove delivered notifications
   */
  async removeDelivered(delivered: DeliveredNotifications): Promise<void> {
    assertNative('PushNotifications');
    // The plugin removes a full DeliveredNotifications payload, not bare ids,
    // because DeliveredNotificationSchema requires id, title and body.
    await PushNotifications.removeDeliveredNotifications(delivered);
  },
};

// ============================================
// Local Notifications Service
// ============================================

export interface LocalNotificationOptions {
  title: string;
  body: string;
  id?: number;
  schedule?: { at: Date; repeats?: boolean; every?: 'minute' | 'hour' | 'day' | 'week' };
  sound?: string;
  attachments?: { id: string; url: string }[];
  extra?: Record<string, any>;
  actionTypeId?: string;
}

export const localNotifications = {
  /**
   * Request permissions
   */
  async requestPermissions(): Promise<{ display: string }> {
    assertNative('LocalNotifications');
    return LocalNotifications.requestPermissions();
  },

  /**
   * Check permissions
   */
  async checkPermissions(): Promise<{ display: string }> {
    assertNative('LocalNotifications');
    return LocalNotifications.checkPermissions();
  },

  /**
   * Schedule a local notification
   */
  async schedule(options: LocalNotificationOptions): Promise<void> {
    assertNative('LocalNotifications');
    const { title, body, id = Date.now(), schedule, sound, attachments, extra, actionTypeId } = options;

    const notification: ScheduleOptions = {
      notifications: [
        {
          title,
          body,
          id,
          schedule: schedule ? { at: schedule.at, repeats: schedule.repeats, every: schedule.every } : undefined,
          sound,
          attachments,
          extra,
          actionTypeId,
        },
      ],
    };

    await LocalNotifications.schedule(notification);
  },

  /**
   * Show notification immediately
   */
  async showNow(title: string, body: string, options: Partial<LocalNotificationOptions> = {}): Promise<void> {
    await this.schedule({ title, body, ...options });
  },

  /**
   * Cancel scheduled notification
   */
  async cancel(id: number): Promise<void> {
    assertNative('LocalNotifications');
    await LocalNotifications.cancel({ notifications: [{ id }] });
  },

  /**
   * Cancel all scheduled notifications
   */
  async cancelAll(): Promise<void> {
    assertNative('LocalNotifications');
    const { notifications } = await LocalNotifications.getPending();
    if (notifications.length > 0) {
      await LocalNotifications.cancel({ notifications });
    }
  },

  /**
   * Get pending notifications
   */
  async getPending(): Promise<LocalNotificationSchema[]> {
    assertNative('LocalNotifications');
    const { notifications } = await LocalNotifications.getPending();
    return notifications;
  },

  /**
   * Add listener for notification action
   */
  onActionPerformed(callback: (action: ActionPerformed) => void): void {
    // The plugin emits ActionPerformed ({ actionId, notification }), not the schema.
    assertNative('LocalNotifications');
    LocalNotifications.addListener('localNotificationActionPerformed', callback);
  },
};

// ============================================
// Device Info Service
// ============================================

export const device = {
  /**
   * Get device information
   */
  async getInfo(): Promise<DeviceInfo> {
    assertNative('Device');
    return Device.getInfo();
  },

  /**
   * Get device ID
   */
  async getId(): Promise<string> {
    const { identifier } = await Device.getId();
    return identifier;
  },

  /**
   * Get platform (ios/android/web)
   */
  getPlatform(): string {
    return Capacitor.getPlatform();
  },

  /**
   * Check if running on native
   */
  isNative(): boolean {
    return isNative();
  },

  /**
   * Get app version
   */
  async getAppVersion(): Promise<string> {
    assertNative('App');
    // AppInfo.version comes from @capacitor/app; DeviceInfo carries no version.
    const { version } = await App.getInfo();
    return version;
  },
};

// ============================================
// Network Service
// ============================================

export const network = {
  /**
   * Get current network status
   */
  async getStatus(): Promise<NetworkStatus> {
    assertNative('Network');
    return Network.getStatus();
  },

  /**
   * Check if online
   */
  async isOnline(): Promise<boolean> {
    const status = await this.getStatus();
    return status.connected;
  },

  /**
   * Add listener for network changes
   */
  onChange(callback: (status: NetworkStatus) => void): string {
    assertNative('Network');
    Network.addListener('networkStatusChange', callback);
    return 'networkStatusChange';
  },

  /**
   * Remove network listener
   */
  async removeListener(_listenerId: string): Promise<void> {
    assertNative('Network');
    // NetworkPlugin exposes no per-listener removeListener() and a handle cannot
    // be recovered from an event name, so the only supported cleanup clears all.
    await Network.removeAllListeners();
  },
};

// ============================================
// Filesystem Service
// ============================================

export interface FileOptions {
  path: string;
  directory?: Directory;
  encoding?: Encoding;
  recursive?: boolean;
}

export const filesystem = {
  /**
   * Write file
   */
  async writeFile(options: WriteFileOptions): Promise<void> {
    assertNative('Filesystem');
    await Filesystem.writeFile(options);
  },

  /**
   * Read file as string
   */
  async readFile(options: ReadFileOptions): Promise<string> {
    assertNative('Filesystem');
    const result = await Filesystem.readFile(options);
    return result.data as string;
  },

  /**
   * Read file as base64
   */
  async readFileBase64(path: string, directory: Directory = Directory.Data): Promise<string> {
    assertNative('Filesystem');
    // Encoding has no Base64 member; native readFile without an encoding option
    // returns base64 content, which is exactly this method's contract.
    const result = await Filesystem.readFile({ path, directory });
    return result.data as string;
  },

  /**
   * Delete file
   */
  async deleteFile(path: string, directory: Directory = Directory.Data): Promise<void> {
    assertNative('Filesystem');
    await Filesystem.deleteFile({ path, directory });
  },

  /**
   * Check if file exists
   */
  async exists(path: string, directory: Directory = Directory.Data): Promise<boolean> {
    assertNative('Filesystem');
    try {
      await Filesystem.stat({ path, directory });
      return true;
    } catch {
      return false;
    }
  },

  /**
   * List files in directory
   */
  async listDir(path: string, directory: Directory = Directory.Data): Promise<string[]> {
    assertNative('Filesystem');
    const result = await Filesystem.readdir({ path, directory });
    return result.files.map((f) => f.name);
  },

  /**
   * Create directory
   */
  async mkdir(path: string, directory: Directory = Directory.Data, recursive = true): Promise<void> {
    assertNative('Filesystem');
    await Filesystem.mkdir({ path, directory, recursive });
  },

  /**
   * Get URI for file
   */
  async getUri(path: string, directory: Directory = Directory.Data): Promise<string> {
    assertNative('Filesystem');
    const result = await Filesystem.getUri({ path, directory });
    return result.uri;
  },
};

// ============================================
// Share Service
// ============================================

export const share = {
  /**
   * Check if share is supported
   */
  async canShare(): Promise<boolean> {
    if (!isNative()) return false;
    try {
      // Share.canShare is not available on all platforms
      return true;
    } catch {
      return false;
    }
  },

  /**
   * Share content
   */
  async share(options: ShareOptions): Promise<void> {
    assertNative('Share');
    await Share.share(options);
  },

  /**
   * Share text
   */
  async shareText(text: string, title?: string): Promise<void> {
    await this.share({ text, title });
  },

  /**
   * Share URL
   */
  async shareUrl(url: string, title?: string, text?: string): Promise<void> {
    await this.share({ url, title, text });
  },
};

// ============================================
// Haptics Service
// ============================================

export const haptics = {
  /**
   * Impact feedback (light/medium/heavy)
   */
  async impact(style: ImpactStyle = ImpactStyle.Medium): Promise<void> {
    assertNative('Haptics');
    await Haptics.impact({ style });
  },

  /**
   * Notification feedback (success/warning/error)
   */
  async notify(type: NotificationType = NotificationType.Success): Promise<void> {
    assertNative('Haptics');
    await Haptics.notification({ type });
  },

  /**
   * Selection feedback (for pickers, toggles)
   */
  async selection(): Promise<void> {
    assertNative('Haptics');
    await Haptics.selectionStart();
    await Haptics.selectionChanged();
    await Haptics.selectionEnd();
  },

  /**
   * Light impact
   */
  async light(): Promise<void> {
    await this.impact(ImpactStyle.Light);
  },

  /**
   * Medium impact
   */
  async medium(): Promise<void> {
    await this.impact(ImpactStyle.Medium);
  },

  /**
   * Heavy impact
   */
  async heavy(): Promise<void> {
    await this.impact(ImpactStyle.Heavy);
  },

  /**
   * Success notification
   */
  async success(): Promise<void> {
    await this.notify(NotificationType.Success);
  },

  /**
   * Warning notification
   */
  async warning(): Promise<void> {
    await this.notify(NotificationType.Warning);
  },

  /**
   * Error notification
   */
  async error(): Promise<void> {
    await this.notify(NotificationType.Error);
  },
};

// ============================================
// Clipboard Service
// ============================================

export const clipboard = {
  /**
   * Write to clipboard
   */
  async write(text: string): Promise<void> {
    assertNative('Clipboard');
    await Clipboard.write({ string: text });
  },

  /**
   * Read from clipboard
   */
  async read(): Promise<string> {
    assertNative('Clipboard');
    const { value } = await Clipboard.read();
    return value;
  },
};

// ============================================
// App Lifecycle Service
// ============================================

export interface AppStateInfo {
  isActive: boolean;
  /**
   * Capacitor's AppState only exposes isActive: active vs background.
   * 'inactive' stays in the union for the terminated state consumers model.
   */
  state: 'active' | 'background' | 'inactive';
}

/**
 * Registration handles for App listeners, keyed by the event-name string the
 * onXxx() methods return. addListener resolves a handle asynchronously, so the
 * pending promise is stored (not the resolved value) and removeListener awaits
 * it — a listener destroyed before registration settled is still removed.
 */
const appListenerHandles = new Map<string, Promise<PluginListenerHandle>>();

export const app = {
  /**
   * Get current app state
   */
  async getState(): Promise<AppStateInfo> {
    assertNative('App');
    const { isActive } = await App.getState();
    return { isActive, state: isActive ? 'active' : 'background' };
  },

  /**
   * Add listener for app state changes
   */
  onStateChange(callback: (state: AppStateInfo) => void): string {
    assertNative('App');
    appListenerHandles.set(
      'appStateChange',
      App.addListener('appStateChange', ({ isActive }) =>
        callback({ isActive, state: isActive ? 'active' : 'background' })
      )
    );
    return 'appStateChange';
  },

  /**
   * Remove a previously registered app listener (by its event-name id).
   */
  removeListener(eventName: string): Promise<void> {
    const pending = appListenerHandles.get(eventName);
    if (!pending) return Promise.resolve();
    appListenerHandles.delete(eventName);
    return pending.then((handle) => handle.remove());
  },

  /**
   * Add listener for app URL open (deep links)
   */
  onUrlOpen(callback: (data: URLOpenListenerEvent) => void): string {
    assertNative('App');
    appListenerHandles.set('appUrlOpen', App.addListener('appUrlOpen', callback));
    return 'appUrlOpen';
  },

  /**
   * Add listener for back button (Android)
   */
  onBackButton(callback: () => void): string {
    assertNative('App');
    appListenerHandles.set('backButton', App.addListener('backButton', callback));
    return 'backButton';
  },

  /**
   * Exit app
   */
  async exit(): Promise<void> {
    assertNative('App');
    await App.exitApp();
  },

  /**
   * Minimize app (Android)
   */
  async minimize(): Promise<void> {
    assertNative('App');
    await App.minimizeApp();
  },
};

// ============================================
// Browser Service (In-App Browser)
// ============================================

export const browser = {
  /**
   * Open URL in system browser
   */
  async open(url: string, options?: OpenOptions): Promise<void> {
    assertNative('Browser');
    await Browser.open({ url, ...options });
  },

  /**
   * Open URL in in-app browser (SafariViewController / Chrome Custom Tab)
   */
  async openInApp(url: string, options?: OpenOptions): Promise<void> {
    assertNative('Browser');
    await Browser.open({ url, presentationStyle: 'popover', ...options });
  },

  /**
   * Close in-app browser
   */
  async close(): Promise<void> {
    assertNative('Browser');
    await Browser.close();
  },

  /**
   * Add listener for browser finished
   */
  onFinished(callback: () => void): string {
    assertNative('Browser');
    Browser.addListener('browserFinished', callback);
    return 'browserFinished';
  },
};

// ============================================
// Preferences Service (Key-Value Storage)
// ============================================

export const preferences = {
  /**
   * Set value
   */
  async set(key: string, value: string): Promise<void> {
    assertNative('Preferences');
    await Preferences.set({ key, value });
  },

  /**
   * Get value
   */
  async get(key: string): Promise<string | null> {
    assertNative('Preferences');
    const { value } = await Preferences.get({ key });
    return value;
  },

  /**
   * Remove key
   */
  async remove(key: string): Promise<void> {
    assertNative('Preferences');
    await Preferences.remove({ key });
  },

  /**
   * Clear all
   */
  async clear(): Promise<void> {
    assertNative('Preferences');
    await Preferences.clear();
  },

  /**
   * Get all keys
   */
  async keys(): Promise<string[]> {
    assertNative('Preferences');
    const { keys } = await Preferences.keys();
    return keys;
  },

  /**
   * Set object (JSON stringify)
   */
  async setObject(key: string, value: object): Promise<void> {
    await this.set(key, JSON.stringify(value));
  },

  /**
   * Get object (JSON parse)
   */
  async getObject<T>(key: string): Promise<T | null> {
    const value = await this.get(key);
    if (!value) return null;
    try {
      return JSON.parse(value) as T;
    } catch {
      return null;
    }
  },
};

// ============================================
// Utility: Platform Detection & Capabilities
// ============================================

export const native = {
  isNative: isNative,
  platform: Capacitor.getPlatform(),
  assertNative,
  isIOS: () => Capacitor.getPlatform() === 'ios',
  isAndroid: () => Capacitor.getPlatform() === 'android',
  isWeb: () => Capacitor.getPlatform() === 'web',

  /**
   * Check if all required native features are available
   */
  async checkCapabilities(): Promise<Record<string, boolean>> {
    if (!isNative()) {
      return {
        camera: false,
        geolocation: false,
        pushNotifications: false,
        localNotifications: false,
        biometric: false,
        share: false,
        haptics: false,
        clipboard: false,
      };
    }

    const [cameraPerm, geoPerm, pushPerm, localPerm] = await Promise.allSettled([
      Camera.checkPermissions(),
      Geolocation.checkPermissions(),
      PushNotifications.checkPermissions(),
      LocalNotifications.checkPermissions(),
    ]);

    return {
      camera: cameraPerm.status === 'fulfilled' && cameraPerm.value.camera === 'granted',
      geolocation: geoPerm.status === 'fulfilled' && geoPerm.value.location === 'granted',
      pushNotifications: pushPerm.status === 'fulfilled' && pushPerm.value.receive === 'granted',
      localNotifications: localPerm.status === 'fulfilled' && localPerm.value.display === 'granted',
      biometric: false, // Would need separate plugin
      share: true,
      haptics: true,
      clipboard: true,
    };
  },
};

// ============================================
// Export all services as default object
// ============================================

export default {
  camera,
  geolocation,
  pushNotifications,
  localNotifications,
  device,
  network,
  filesystem,
  share,
  haptics,
  clipboard,
  app,
  browser,
  preferences,
  native,
};