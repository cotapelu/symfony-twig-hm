/**
 * Native Stores - Reactive Svelte 5 stores for native features
 * Usage: import { networkStore, geolocationStore, deviceStore } from '$lib/services/native/stores';
 */

import { derived, readable, writable } from 'svelte/store';
import { native, network, geolocation, device, app, preferences } from './index';

// ============================================
// Network Store
// ============================================

function createNetworkStore() {
  const { subscribe, set } = writable<{ connected: boolean; connectionType: string }>({
    connected: true,
    connectionType: 'unknown',
  });

  let listenerId: string | null = null;

  if (native.isNative()) {
    network.getStatus().then((status) => {
      set({ connected: status.connected, connectionType: status.connectionType });
    });

    listenerId = network.onChange((status) => {
      set({ connected: status.connected, connectionType: status.connectionType });
    });
  }

  return {
    subscribe,
    async refresh() {
      if (native.isNative()) {
        const status = await network.getStatus();
        set({ connected: status.connected, connectionType: status.connectionType });
      }
    },
    destroy() {
      if (listenerId && native.isNative()) {
        network.removeListener(listenerId);
      }
    },
  };
}

export const networkStore = createNetworkStore();

// ============================================
// Geolocation Store
// ============================================

function createGeolocationStore() {
  const { subscribe, set } = writable<{
    position: GeolocationPosition | null;
    error: string | null;
    watching: boolean;
  }>({
    position: null,
    error: null,
    watching: false,
  });

  let watchId: string | null = null;

  return {
    subscribe,
    async getCurrent() {
      if (!native.isNative()) return;
      try {
        const position = await geolocation.getCurrentPosition();
        set({ position, error: null, watching: !!watchId });
      } catch (error) {
        set({ position: null, error: (error as Error).message, watching: !!watchId });
      }
    },
    async watch() {
      if (!native.isNative() || watchId) return;
      try {
        watchId = await geolocation.watchPosition((position, error) => {
          if (error) {
            set({ position: null, error: error.message, watching: true });
          } else if (position) {
            set({ position, error: null, watching: true });
          }
        });
        set({ position: null, error: null, watching: true });
      } catch (error) {
        set({ position: null, error: (error as Error).message, watching: false });
      }
    },
    async stop() {
      if (watchId && native.isNative()) {
        await geolocation.clearWatch(watchId);
        watchId = null;
        set({ position: null, error: null, watching: false });
      }
    },
  };
}

export const geolocationStore = createGeolocationStore();

// ============================================
// Device Store
// ============================================

function createDeviceStore() {
  const { subscribe, set } = writable<{
    info: DeviceInfo | null;
    loading: boolean;
    error: string | null;
  }>({
    info: null,
    loading: true,
    error: null,
  });

  if (native.isNative()) {
    device.getInfo()
      .then((info) => set({ info, loading: false, error: null }))
      .catch((error) => set({ info: null, loading: false, error: error.message }));
  } else {
    set({ info: null, loading: false, error: 'Not on native platform' });
  }

  return {
    subscribe,
    async refresh() {
      if (!native.isNative()) return;
      set({ info: null, loading: true, error: null });
      try {
        const info = await device.getInfo();
        set({ info, loading: false, error: null });
      } catch (error) {
        set({ info: null, loading: false, error: (error as Error).message });
      }
    },
  };
}

export const deviceStore = createDeviceStore();

// ============================================
// App State Store
// ============================================

function createAppStateStore() {
  const { subscribe, set } = writable<{
    isActive: boolean;
    state: 'active' | 'background' | 'inactive';
  }>({
    isActive: true,
    state: 'active',
  });

  let listenerId: string | null = null;

  if (native.isNative()) {
    app.getState().then(({ isActive, state }) => {
      set({ isActive, state });
    });

    listenerId = app.onStateChange(({ isActive, state }) => {
      set({ isActive, state });
    });
  }

  return {
    subscribe,
    destroy() {
      if (listenerId && native.isNative()) {
        app.removeListener(listenerId);
      }
    },
  };
}

export const appStateStore = createAppStateStore();

// ============================================
// Preferences Store (Reactive Key-Value)
// ============================================

function createPreferencesStore() {
  const store = writable<Record<string, string>>({});

  return {
    subscribe: store.subscribe,
    async get(key: string): Promise<string | null> {
      if (!native.isNative()) return null;
      return preferences.get(key);
    },
    async set(key: string, value: string): Promise<void> {
      if (!native.isNative()) return;
      await preferences.set(key, value);
      store.update((s) => ({ ...s, [key]: value }));
    },
    async remove(key: string): Promise<void> {
      if (!native.isNative()) return;
      await preferences.remove(key);
      store.update((s) => {
        const { [key]: _, ...rest } = s;
        return rest;
      });
    },
    async loadAll(): Promise<void> {
      if (!native.isNative()) return;
      const keys = await preferences.keys();
      const values = await Promise.all(keys.map((k) => preferences.get(k)));
      const obj = Object.fromEntries(keys.map((k, i) => [k, values[i]]));
      store.set(obj);
    },
  };
}

export const preferencesStore = createPreferencesStore();

// ============================================
// Capabilities Store
// ============================================

function createCapabilitiesStore() {
  const { subscribe, set } = writable<Record<string, boolean>>({});

  return {
    subscribe,
    async check() {
      const caps = await native.checkCapabilities();
      set(caps);
      return caps;
    },
  };
}

export const capabilitiesStore = createCapabilitiesStore();

// ============================================
// Derived Stores
// ============================================

export const isOnline = derived(networkStore, ($network) => $network.connected);
export const isNative = derived(deviceStore, ($device) => $device.info !== null && native.isNative());
export const devicePlatform = derived(deviceStore, ($device) => $device.info?.platform || 'web');

// ============================================
// Types (for TypeScript)
// ============================================

export interface DeviceInfo {
  uuid: string;
  model: string;
  platform: string;
  osVersion: string;
  manufacturer: string;
  isVirtual: boolean;
  memUsed: number;
  diskTotal: number;
  diskFree: number;
  appVersion: string;
  appBuild: string;
  appName: string;
  appId: string;
}

export interface GeolocationPosition {
  timestamp: number;
  coords: {
    latitude: number;
    longitude: number;
    accuracy: number;
    altitude: number | null;
    altitudeAccuracy: number | null;
    heading: number | null;
    speed: number | null;
  };
}