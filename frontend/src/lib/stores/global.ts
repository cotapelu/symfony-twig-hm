import { writable, type Writable } from "svelte/store";

type Theme = "light" | "dark" | "cyberpunk";
type RealtimeStatus = "connected" | "connecting" | "disconnected" | "error";
export type ToastType = "info" | "success" | "error" | "warning";

interface Toast {
  id: string;
  message: string;
  type: ToastType;
  title?: string;
  timestamp: number;
}

interface RealtimeEvent {
  type: string;
  message?: {
    conversationId: string;
  };
  user?: {
    id: string;
  };
  groupId?: string;
  callId?: string;
  threadMessage?: {
    parentMessageId: string;
  };
  conversationId?: string;
}

interface ThemeStore extends Writable<Theme> {
  getAvailableThemes: () => Theme[];
  cycleTheme: () => void;
}

type RealtimeCallback = (data: unknown) => void;

// User store
export const user = writable<any>(null);

// Authentication store
export const isAuthenticated = writable<boolean>(false);

// Active section store
export const activeSection = writable<string>("dashboard");

// Data store for sections
export const sectionData = writable<Record<string, any>>({});

// Loading states
export const loading = writable<Record<string, boolean>>({});

// Error states
export const errors = writable<Record<string, any>>({});

// Toast notifications
export const toasts = writable<Toast[]>([]);

// Real-time stores
export const realtimeConnection = writable<EventSource | null>(null);
export const realtimeStatus = writable<RealtimeStatus>("disconnected");
export const realtimeData = writable<Record<string, any>>({});

// Real-time event subscribers
const realtimeSubscribers = new Map<string, Set<RealtimeCallback>>();

export function subscribeToRealtime(
  topic: string,
  callback: RealtimeCallback,
): () => void {
  if (!realtimeSubscribers.has(topic)) {
    realtimeSubscribers.set(topic, new Set());
  }
  realtimeSubscribers.get(topic)!.add(callback);

  return () => {
    const subscribers = realtimeSubscribers.get(topic);
    if (subscribers) {
      subscribers.delete(callback);
      if (subscribers.size === 0) {
        realtimeSubscribers.delete(topic);
      }
    }
  };
}

export function publishRealtimeEvent(topic: string, data: unknown): void {
  const subscribers = realtimeSubscribers.get(topic);
  if (subscribers) {
    subscribers.forEach((callback) => {
      try {
        callback(data);
      } catch (error) {
        console.error("Error in realtime subscriber:", error);
      }
    });
  }
}

export function connectRealtime(): void {
  const token =
    typeof window !== "undefined" ? localStorage.getItem("auth_token") : null;
  if (!token) {
    realtimeStatus.set("error");
    return;
  }

  realtimeStatus.set("connecting");

  try {
    const topics = ["messages", "notifications", "calls", "presence"];
    const eventSourceUrl =
      "http://localhost:3000/.well-known/mercure?topic=" + topics.join(",");
    const eventSource = new EventSource(eventSourceUrl);

    eventSource.onmessage = (event: MessageEvent) => {
      try {
        const data = JSON.parse(event.data) as RealtimeEvent;
        handleRealtimeEvent(data);
      } catch (error) {
        console.error("Failed to parse Mercure message:", error);
      }
    };

    eventSource.onerror = (error: Event) => {
      console.error("Mercure connection error:", error);
      realtimeStatus.set("error");
      realtimeConnection.set(null);
    };

    eventSource.onopen = () => {
      console.log("Connected to Mercure hub");
      realtimeStatus.set("connected");
      realtimeConnection.set(eventSource);
    };
  } catch (error) {
    console.error("Failed to connect to Mercure:", error);
    realtimeStatus.set("error");
    realtimeConnection.set(null);
  }
}

export function disconnectRealtime(): void {
  realtimeConnection.update((connection: EventSource | null) => {
    if (connection) {
      connection.close();
    }
    return null;
  });
  realtimeStatus.set("disconnected");
  realtimeSubscribers.clear();
}

function handleRealtimeEvent(data: RealtimeEvent): void {
  const { type } = data;

  realtimeData.update((current) => ({ ...current, [type]: data }));

  switch (type) {
    case "new_message":
      publishRealtimeEvent(
        `conversation/${data.message!.conversationId}`,
        data,
      );
      publishRealtimeEvent("messages", data);
      break;
    case "profile_updated":
      publishRealtimeEvent(`user/${data.user!.id}/profile`, data);
      publishRealtimeEvent("profile", data);
      break;
    case "group_created":
      publishRealtimeEvent("groups", data);
      break;
    case "user_joined":
      publishRealtimeEvent(`group/${data.groupId}`, data);
      publishRealtimeEvent("groups", data);
      break;
    case "file_uploaded":
      publishRealtimeEvent("files", data);
      break;
    case "file_deleted":
      publishRealtimeEvent("files", data);
      break;
    case "user_online":
      publishRealtimeEvent("presence", data);
      break;
    case "new_notification":
      publishRealtimeEvent("notifications", data);
      break;
    case "call_started":
      publishRealtimeEvent("calls", data);
      break;
    case "user_joined_call":
      publishRealtimeEvent("call/" + data.callId, data);
      break;
    case "typing_start":
      publishRealtimeEvent(
        "conversation/" + data.conversationId + "/typing",
        data,
      );
      break;
    case "typing_stop":
      publishRealtimeEvent(
        "conversation/" + data.conversationId + "/typing",
        data,
      );
      break;
    case "thread_created":
      publishRealtimeEvent(
        "thread/" + data.threadMessage!.parentMessageId,
        data,
      );
      break;
    case "poll_created":
      publishRealtimeEvent("polls", data);
      break;
    case "message_read":
      publishRealtimeEvent("read_receipts", data);
      break;
  }
}

function createThemeStore(): ThemeStore {
  const { subscribe, set, update } = writable<Theme>("light");

  const themes: Theme[] = ["light", "dark", "cyberpunk"];

  if (typeof window !== "undefined") {
    const savedTheme = localStorage.getItem("theme") as Theme | null;
    let initialTheme: Theme = "light";

    if (savedTheme && themes.includes(savedTheme)) {
      initialTheme = savedTheme;
    } else {
      const systemPrefersDark = window.matchMedia(
        "(prefers-color-scheme: dark)",
      ).matches;
      initialTheme = systemPrefersDark ? "dark" : "light";
    }

    set(initialTheme);
    document.documentElement.setAttribute("data-theme", initialTheme);

    window
      .matchMedia("(prefers-color-scheme: dark)")
      .addEventListener("change", (e) => {
        if (!localStorage.getItem("theme")) {
          const newTheme = e.matches ? "dark" : "light";
          set(newTheme);
          document.documentElement.setAttribute("data-theme", newTheme);
        }
      });
  }

  return {
    subscribe,
    set: (value: Theme) => {
      if (!themes.includes(value)) {
        console.warn(`Invalid theme: ${value}. Available themes:`, themes);
        return;
      }
      set(value);
      if (typeof window !== "undefined") {
        localStorage.setItem("theme", value);
        document.documentElement.setAttribute("data-theme", value);
      }
    },
    update: (fn: (current: Theme) => Theme) => {
      update((current) => {
        const newTheme = fn(current);
        if (!themes.includes(newTheme)) {
          console.warn(`Invalid theme: ${newTheme}. Available themes:`, themes);
          return current;
        }
        if (typeof window !== "undefined") {
          localStorage.setItem("theme", newTheme);
          document.documentElement.setAttribute("data-theme", newTheme);
        }
        return newTheme;
      });
    },
    getAvailableThemes: () => themes,
    cycleTheme: () => {
      update((current) => {
        const currentIndex = themes.indexOf(current);
        const nextIndex = (currentIndex + 1) % themes.length;
        const nextTheme = themes[nextIndex];
        if (typeof window !== "undefined") {
          localStorage.setItem("theme", nextTheme);
          document.documentElement.setAttribute("data-theme", nextTheme);
        }
        return nextTheme;
      });
    },
  };
}

export const theme = createThemeStore();

export function setUser(newUser: any): void {
  user.set(newUser);
}

export function showToast(message: string, type: ToastType = "info"): string {
  const id = String(Date.now());
  const toast: Toast = { id, message, type, timestamp: Number(id) };

  toasts.update((current) => [...current, toast]);

  setTimeout(() => {
    removeToast(id);
  }, 5000);

  return id;
}

export function removeToast(id: string): void {
  toasts.update((current) => current.filter((t) => t.id !== id));
}

export function logout(): void {
  user.set(null);
  isAuthenticated.set(false);
  activeSection.set("dashboard");
}

export function setActiveSection(section: string): void {
  activeSection.set(section);
}

export function setSectionData(section: string, data: any): void {
  sectionData.update((current) => ({ ...current, [section]: data }));
}

export function setLoading(section: string, state: boolean): void {
  loading.update((current) => ({ ...current, [section]: state }));
}

export function setError(section: string, error: any): void {
  errors.update((current) => ({ ...current, [section]: error }));
}

export function getSystemTheme(): Theme {
  if (typeof window === "undefined") return "light";
  return window.matchMedia("(prefers-color-scheme: dark)").matches
    ? "dark"
    : "light";
}

export function resetToSystemTheme(): void {
  if (typeof window !== "undefined") {
    localStorage.removeItem("theme");
    const systemTheme = getSystemTheme();
    theme.set(systemTheme);
  }
}
