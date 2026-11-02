import { writable } from "svelte/store";
import { Go } from "../lib/api";

interface GoServicesState {
  bookings: any[];
  nearbyServices: any[];
  routeInfo: any;
  providers: any[];
  loading: boolean;
  error: string | null;
}

function createGoServicesStore() {
  const initialState: GoServicesState = {
    bookings: [],
    nearbyServices: [],
    routeInfo: null,
    providers: [],
    loading: false,
    error: null,
  };

  const { subscribe, update } = writable<GoServicesState>(initialState);

  return {
    subscribe,

    createBooking: async (bookingData: any): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const booking = await Go.createBooking(bookingData);
        update((prev) => ({
          ...prev,
          bookings: [booking, ...prev.bookings],
          loading: false,
        }));
        return booking;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create booking";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getBookings: async (): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const bookings = await Go.getBookings();
        update((prev) => ({
          ...prev,
          bookings,
          loading: false,
        }));
        return bookings;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get bookings";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getNearbyServices: async (
      lat: number,
      lng: number,
      radius = 5,
    ): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const services = await Go.getNearbyServices({
          latitude: lat,
          longitude: lng,
          radius,
        });
        update((prev) => ({
          ...prev,
          nearbyServices: services,
          loading: false,
        }));
        return services;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get nearby services";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    calculateRoute: async (
      from: { lat: number; lng: number },
      to: { lat: number; lng: number },
      mode = "driving",
    ): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const route = await Go.calculateRoute(from, to, mode);
        update((prev) => ({
          ...prev,
          routeInfo: route,
          loading: false,
        }));
        return route;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to calculate route";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getServiceProviders: async (serviceType: string): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const providers = await Go.getServiceProviders(serviceType);
        update((prev) => ({
          ...prev,
          providers,
          loading: false,
        }));
        return providers;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get service providers";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      update(() => ({ ...initialState }));
    },
  };
}

interface GoLocationState {
  nearbyUsers: any[];
  userLocation: { lat: number; lng: number; updatedAt: number } | null;
  locationSharingEnabled: boolean;
  geofence: any | null;
  geofences: any[];
  loading: boolean;
  error: string | null;
}

function createGoLocationStore() {
  const initialState: GoLocationState = {
    nearbyUsers: [],
    userLocation: null,
    locationSharingEnabled: false,
    geofence: null,
    geofences: [],
    loading: false,
    error: null,
  };

  const { subscribe, update } = writable<GoLocationState>(initialState);

  return {
    subscribe,

    updateLocation: async (
      userId: string,
      lat: number,
      lng: number,
    ): Promise<void> => {
      try {
        await Go.updateLocation({ latitude: lat, longitude: lng });
        update((prev) => ({
          ...prev,
          userLocation: { lat, lng, updatedAt: Date.now() },
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to update location";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    getNearbyUsers: async (
      lat: number,
      lng: number,
      radius = 5,
    ): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const users = await Go.getNearbyUsers(lat, lng, radius);
        update((prev) => ({
          ...prev,
          nearbyUsers: users,
          loading: false,
        }));
        return users;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get nearby users";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    setLocationSharing: async (enabled: boolean): Promise<void> => {
      try {
        await Go.setLocationSharing(enabled);
        update((prev) => ({ ...prev, locationSharingEnabled: enabled }));
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to set location sharing";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    getGeofence: async (userId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const geofence = await Go.getGeofence(userId);
        update((prev) => ({
          ...prev,
          geofence,
          loading: false,
        }));
        return geofence;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get geofence";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getGeofences: async (): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const geofence = await Go.getGeofence("current-user-id");
        const geofences = geofence ? [geofence] : [];
        update((prev) => ({
          ...prev,
          geofences,
          loading: false,
        }));
        return geofences;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get geofences";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        return [];
      }
    },

    shareLocation: async (userId: string): Promise<void> => {
      await goLocationStore.setLocationSharing(true);
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      update(() => ({ ...initialState }));
    },
  };
}

interface GoPaymentsState {
  wallet: any | null;
  transactions: any[];
  loading: boolean;
  error: string | null;
}

function createGoPaymentsStore() {
  const initialState: GoPaymentsState = {
    wallet: null,
    transactions: [],
    loading: false,
    error: null,
  };

  const { subscribe, update } = writable<GoPaymentsState>(initialState);

  return {
    subscribe,

    loadWallet: async (userId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const wallet = await Go.getWallet(userId);
        update((prev) => ({
          ...prev,
          wallet,
          loading: false,
        }));
        return wallet;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to load wallet";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    createWallet: async (userId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const wallet = await Go.createWallet(userId);
        update((prev) => ({
          ...prev,
          wallet,
          loading: false,
        }));
        return wallet;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create wallet";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    loadTransactions: async (walletId: string): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const transactions = await Go.getTransactions(walletId);
        update((prev) => ({
          ...prev,
          transactions,
          loading: false,
        }));
        return transactions;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to load transactions";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    processPayment: async (paymentData: any): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const result = await Go.processPayment(paymentData);
        update((prev) => ({
          ...prev,
          transactions: [result, ...prev.transactions],
          loading: false,
        }));
        return result;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to process payment";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    addFunds: async (
      walletId: string,
      amount: number,
      currency = "USD",
    ): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const result = await Go.addFunds(walletId, amount, currency);
        update((prev) => ({
          ...prev,
          wallet: { ...prev.wallet, ...result },
          loading: false,
        }));
        return result;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to add funds";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    sendPayment: async (paymentData: {
      to: string;
      amount: number;
      currency: string;
    }): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const result = await Go.sendPayment(paymentData);
        update((prev) => ({
          ...prev,
          transactions: [result, ...prev.transactions],
          loading: false,
        }));
        return result;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to send payment";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    requestPayment: async (paymentData: {
      from: string;
      amount: number;
      currency: string;
    }): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const result = await Go.requestPayment(paymentData);
        update((prev) => ({
          ...prev,
          transactions: [result, ...prev.transactions],
          loading: false,
        }));
        return result;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to request payment";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      update(() => ({ ...initialState }));
    },
  };
}

interface GoBroadcastState {
  broadcasts: any[];
  loading: boolean;
  error: string | null;
}

function createGoBroadcastStore() {
  const initialState: GoBroadcastState = {
    broadcasts: [],
    loading: false,
    error: null,
  };

  const { subscribe, update } = writable<GoBroadcastState>(initialState);

  return {
    subscribe,

    sendBroadcast: async (data: any): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const broadcast = await Go.sendBroadcast(data);
        update((prev) => ({
          ...prev,
          broadcasts: [broadcast, ...prev.broadcasts],
          loading: false,
        }));
        return broadcast;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to send broadcast";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getBroadcastStatus: async (broadcastId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const status = await Go.getBroadcastStatus(broadcastId);
        update((prev) => ({
          ...prev,
          broadcasts: prev.broadcasts.map((b: any) =>
            b.id === broadcastId ? { ...b, ...status } : b,
          ),
          loading: false,
        }));
        return status;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get broadcast status";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getBroadcasts: async (): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const broadcasts = await Go.getBroadcasts();
        update((prev) => ({
          ...prev,
          broadcasts,
          loading: false,
        }));
        return broadcasts;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get broadcasts";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    createBroadcast: async (data: any): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const broadcast = await Go.sendBroadcast(data);
        update((prev) => ({
          ...prev,
          broadcasts: [broadcast, ...prev.broadcasts],
          loading: false,
        }));
        return broadcast;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create broadcast";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    deleteBroadcast: async (broadcastId: string): Promise<void> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        update((prev) => ({
          ...prev,
          broadcasts: prev.broadcasts.filter((b: any) => b.id !== broadcastId),
          loading: false,
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to delete broadcast";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      update(() => ({ ...initialState }));
    },
  };
}

interface GoVideoConferenceState {
  conferences: any[];
  rooms: any[];
  currentRoom: any | null;
  participants: any[];
  localStream: MediaStream | null;
  remoteStreams: Record<string, MediaStream>;
  screenSharingEnabled: boolean;
  loading: boolean;
  error: string | null;
}

function createGoVideoConferenceStore() {
  const initialState: GoVideoConferenceState = {
    conferences: [],
    rooms: [],
    currentRoom: null,
    participants: [],
    localStream: null,
    remoteStreams: {},
    screenSharingEnabled: false,
    loading: false,
    error: null,
  };

  const { subscribe, update } = writable<GoVideoConferenceState>(initialState);

  return {
    subscribe,

    getConferences: async (): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const conferences = await Go.getConferences();
        update((prev) => ({
          ...prev,
          conferences,
          loading: false,
        }));
        return conferences;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get conferences";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    createConference: async (data: {
      title: string;
      description?: string;
      scheduledTime?: string;
      maxParticipants?: number;
    }): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const conference = await Go.createConference(data);
        update((prev) => ({
          ...prev,
          conferences: [conference, ...prev.conferences],
          loading: false,
        }));
        return conference;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create conference";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    joinConference: async (conferenceId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const result = await Go.joinConference(conferenceId);
        update((state) => ({ ...state, loading: false }));
        return result;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to join conference";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    createRoom: async (data: {
      topic: string;
      maxParticipants?: number;
    }): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const room = await Go.createRoom(data);
        update((prev) => ({
          ...prev,
          rooms: [room, ...prev.rooms],
          currentRoom: room,
          loading: false,
        }));
        return room;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create room";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    joinRoom: async (roomId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const room = await Go.joinRoom(roomId);
        update((prev) => ({
          ...prev,
          currentRoom: room,
          loading: false,
        }));
        return room;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to join room";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    leaveRoom: async (roomId: string): Promise<void> => {
      try {
        await Go.leaveRoom(roomId);
        update((prev) => ({
          ...prev,
          currentRoom: null,
          participants: [],
          localStream: null,
          remoteStreams: {},
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to leave room";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    toggleScreenShare: async (
      roomId: string,
      enabled: boolean,
    ): Promise<void> => {
      try {
        await Go.toggleScreenShare(roomId, enabled);
        update((prev) => ({ ...prev, screenSharingEnabled: enabled }));
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to toggle screen share";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    getRoomParticipants: async (roomId: string): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const participants = await Go.getRoomParticipants(roomId);
        update((prev) => ({
          ...prev,
          participants,
          loading: false,
        }));
        return participants;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get room participants";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      update(() => ({ ...initialState }));
    },
  };
}

interface GoIntegrationState {
  integrations: any[];
  webhooks: any[];
  loading: boolean;
  error: string | null;
}

function createGoIntegrationStore() {
  const initialState: GoIntegrationState = {
    integrations: [],
    webhooks: [],
    loading: false,
    error: null,
  };

  const { subscribe, update } = writable<GoIntegrationState>(initialState);

  return {
    subscribe,

    getIntegrations: async (): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const integrations = await Go.getIntegrations();
        update((prev) => ({
          ...prev,
          integrations,
          loading: false,
        }));
        return integrations;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get integrations";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    connectIntegration: async (integrationType: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const integration = await Go.connectIntegration(integrationType);
        update((prev) => ({
          ...prev,
          integrations: [...prev.integrations, integration],
          loading: false,
        }));
        return integration;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to connect integration";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    disconnectIntegration: async (integrationId: string): Promise<void> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        await Go.disconnectIntegration(integrationId);
        update((prev) => ({
          ...prev,
          integrations: prev.integrations.filter((i) => i.id !== integrationId),
          loading: false,
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to disconnect integration";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    createWebhook: async (webhookData: {
      url: string;
      events: string[];
    }): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const webhook = await Go.createWebhook(webhookData);
        update((prev) => ({
          ...prev,
          webhooks: [webhook, ...prev.webhooks],
          loading: false,
        }));
        return webhook;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create webhook";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getWebhooks: async (): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const webhooks = await Go.getWebhooks();
        update((prev) => ({
          ...prev,
          webhooks,
          loading: false,
        }));
        return webhooks;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get webhooks";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    triggerWebhook: async (webhookId: string, payload: any): Promise<any> => {
      try {
        const result = await Go.triggerWebhook(webhookId, payload);
        return result;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to trigger webhook";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      update(() => ({ ...initialState }));
    },
  };
}

export const goServicesStore = createGoServicesStore();
export const goLocationStore = createGoLocationStore();
export const goPaymentsStore = createGoPaymentsStore();
export const goBroadcastStore = createGoBroadcastStore();
export const goVideoConferenceStore = createGoVideoConferenceStore();
export const goIntegrationStore = createGoIntegrationStore();

export const {
  createBooking,
  getBookings,
  getNearbyServices,
  calculateRoute,
  getServiceProviders,
  clearError: clearServicesError,
} = goServicesStore;

export const {
  updateLocation,
  getNearbyUsers,
  setLocationSharing,
  getGeofence,
  getGeofences,
  shareLocation,
  clearError: clearLocationError,
} = goLocationStore;

export const {
  loadWallet,
  createWallet,
  loadTransactions,
  processPayment,
  addFunds,
  sendPayment,
  requestPayment,
  clearError: clearPaymentsError,
} = goPaymentsStore;

export const {
  sendBroadcast: goSendBroadcast,
  getBroadcastStatus,
  getBroadcasts,
  createBroadcast,
  deleteBroadcast,
  clearError: clearBroadcastError,
} = goBroadcastStore;

export const {
  getConferences,
  createConference,
  joinConference,
  createRoom,
  joinRoom,
  leaveRoom,
  toggleScreenShare,
  getRoomParticipants,
  clearError: clearVideoError,
} = goVideoConferenceStore;

export const {
  createWebhook,
  getWebhooks,
  triggerWebhook,
  getIntegrations,
  connectIntegration,
  disconnectIntegration,
  clearError: clearIntegrationError,
} = goIntegrationStore;
