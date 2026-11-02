import { writable, get } from "svelte/store";
import { browser } from "$app/environment";

export interface SyncQueueItem {
  id: string;
  type: "create" | "update" | "delete";
  entity: string;
  data: any;
  timestamp: number;
  retries: number;
}

export interface SyncStatus {
  isOnline: boolean;
  lastSyncTime: number | null;
  pendingCount: number;
  isSyncing: boolean;
  error: string | null;
}

const STORAGE_KEY = "offline_sync_queue";
const SYNC_STATUS_KEY = "offline_sync_status";

export class OfflineSyncService {
  private syncQueue = writable<SyncQueueItem[]>([]);
  private syncStatus = writable<SyncStatus>({
    isOnline: true,
    lastSyncTime: null,
    pendingCount: 0,
    isSyncing: false,
    error: null,
  });

  private syncInterval: ReturnType<typeof setInterval> | null = null;
  private eventListeners: Map<string, Set<(data: any) => void>> = new Map();

  constructor() {
    if (browser) {
      this.init();
    }
  }

  private init() {
    this.loadFromStorage();
    this.setupNetworkListeners();
    this.startSyncInterval();
  }

  private loadFromStorage() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        const queue = JSON.parse(stored);
        this.syncQueue.set(queue);
        this.updatePendingCount();
      }

      const statusStored = localStorage.getItem(SYNC_STATUS_KEY);
      if (statusStored) {
        const status = JSON.parse(statusStored);
        this.syncStatus.set(status);
      }
    } catch (e) {
      console.error("Failed to load sync queue from storage:", e);
    }
  }

  private saveToStorage() {
    if (!browser) return;
    try {
      const queue = get(this.syncQueue);
      localStorage.setItem(STORAGE_KEY, JSON.stringify(queue));

      const status = get(this.syncStatus);
      localStorage.setItem(SYNC_STATUS_KEY, JSON.stringify(status));
    } catch (e) {
      console.error("Failed to save sync queue to storage:", e);
    }
  }

  private setupNetworkListeners() {
    if (!browser) return;

    window.addEventListener("online", () => this.handleOnline());
    window.addEventListener("offline", () => this.handleOffline());

    this.syncStatus.update((s) => ({
      ...s,
      isOnline: navigator.onLine,
    }));
  }

  private handleOnline() {
    this.syncStatus.update((s) => ({ ...s, isOnline: true }));
    this.processQueue();
  }

  private handleOffline() {
    this.syncStatus.update((s) => ({ ...s, isOnline: false }));
  }

  private startSyncInterval() {
    this.syncInterval = setInterval(() => {
      if (get(this.syncStatus).isOnline && !get(this.syncStatus).isSyncing) {
        this.processQueue();
      }
    }, 30000);
  }

  private updatePendingCount() {
    const queue = get(this.syncQueue);
    this.syncStatus.update((s) => ({ ...s, pendingCount: queue.length }));
  }

  async addToQueue(type: SyncQueueItem["type"], entity: string, data: any): Promise<void> {
    const item: SyncQueueItem = {
      id: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
      type,
      entity,
      data,
      timestamp: Date.now(),
      retries: 0,
    };

    this.syncQueue.update((q) => [...q, item]);
    this.updatePendingCount();
    this.saveToStorage();

    const status = get(this.syncStatus);
    if (status.isOnline && !status.isSyncing) {
      this.processQueue();
    }
  }

  async processQueue(): Promise<void> {
    const status = get(this.syncStatus);
    if (!status.isOnline || status.isSyncing) return;

    const queue = get(this.syncQueue);
    if (queue.length === 0) return;

    this.syncStatus.update((s) => ({ ...s, isSyncing: true, error: null }));

    const failedItems: SyncQueueItem[] = [];

    for (const item of queue) {
      try {
        await this.syncItem(item);
        this.emit(`${item.entity}:synced`, item);
      } catch (error) {
        console.error(`Failed to sync item ${item.id}:`, error);
        item.retries++;

        if (item.retries < 3) {
          failedItems.push(item);
        } else {
          this.emit(`${item.entity}:sync_failed`, item);
        }
      }
    }

    this.syncQueue.set(failedItems);
    this.updatePendingCount();
    this.saveToStorage();

    this.syncStatus.update((s) => ({
      ...s,
      isSyncing: false,
      lastSyncTime: Date.now(),
    }));
  }

  private async syncItem(item: SyncQueueItem): Promise<void> {
    const endpoints: Record<string, string> = {
      citizen: "/api/government/citizens",
      officer: "/api/government/officers",
      document: "/api/government/documents",
      tthc: "/api/government/procedures",
      iot: "/api/iot/devices",
      wallet: "/api/wallet/transactions",
    };

    const endpoint = endpoints[item.entity];
    if (!endpoint) {
      throw new Error(`Unknown entity: ${item.entity}`);
    }

    let url = endpoint;
    let method = "POST";

    if (item.type === "update") {
      url = `${endpoint}/${item.data.id}`;
      method = "PUT";
    } else if (item.type === "delete") {
      url = `${endpoint}/${item.data.id}`;
      method = "DELETE";
    }

    const response = await fetch(url, {
      method,
      headers: { "Content-Type": "application/json" },
      body: item.type !== "delete" ? JSON.stringify(item.data) : undefined,
    });

    if (!response.ok) {
      throw new Error(`Sync failed with status: ${response.status}`);
    }
  }

  async forceSyncNow(): Promise<void> {
    const status = get(this.syncStatus);
    if (!status.isOnline) {
      throw new Error("Cannot sync while offline");
    }
    await this.processQueue();
  }

  clearQueue(): void {
    this.syncQueue.set([]);
    this.updatePendingCount();
    this.saveToStorage();
  }

  on(event: string, callback: (data: any) => void): () => void {
    if (!this.eventListeners.has(event)) {
      this.eventListeners.set(event, new Set());
    }
    this.eventListeners.get(event)!.add(callback);

    return () => {
      this.eventListeners.get(event)?.delete(callback);
    };
  }

  private emit(event: string, data: any): void {
    this.eventListeners.get(event)?.forEach((cb) => {
      cb(data);
    });
  }

  getStatus() {
    return this.syncStatus;
  }

  getQueue() {
    return this.syncQueue;
  }

  destroy() {
    if (this.syncInterval) {
      clearInterval(this.syncInterval);
    }
  }
}

export const offlineSync = new OfflineSyncService();
export default offlineSync;
