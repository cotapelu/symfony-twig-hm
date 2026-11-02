// Unified API Client for All Backend Services
// Supports PHP, Go, Python, Rust, Zig, and Elixir backends

import type {
  User,
  Message,
  Conversation,
  Group,
  FileItem,
  Notification,
  Poll,
  Call,
  PaginatedResponse,
} from "./types";

const API_BASE_URL = "/api";

interface RequestOptions {
  method?: string;
  body?: any;
  headers?: Record<string, string>;
  timeout?: number;
}

export type { RequestOptions };

export class ApiClient {
  private baseUrl: string;
  private defaultHeaders: Record<string, string>;

  constructor(baseUrl: string = API_BASE_URL) {
    this.baseUrl = baseUrl;
    this.defaultHeaders = {
      "Content-Type": "application/json",
    };
  }

  private getAuthHeader(): Record<string, string> {
    if (typeof localStorage !== "undefined") {
      const token = localStorage.getItem("auth_token");
      if (token) {
        return { Authorization: `Bearer ${token}` };
      }
    }
    return {};
  }

  private async request<T>(
    endpoint: string,
    options: RequestOptions = {},
  ): Promise<T> {
    const { method = "GET", body, headers = {}, timeout = 30000 } = options;

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    try {
      const response = await fetch(`${this.baseUrl}${endpoint}`, {
        method,
        headers: {
          ...(body instanceof FormData ? {} : this.defaultHeaders),
          ...this.getAuthHeader(),
          ...headers,
        },
        body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        const error = await response
          .json()
          .catch(() => ({ error: "Request failed" }));
        throw new Error(
          error.error || error.message || `HTTP ${response.status}`,
        );
      }

      return response.json();
    } catch (error) {
      clearTimeout(timeoutId);
      if (error instanceof Error && error.name === "AbortError") {
        throw new Error("Request timeout");
      }
      throw error;
    }
  }

  get<T>(endpoint: string, params?: Record<string, any>): Promise<T> {
    const query = params ? "?" + new URLSearchParams(params).toString() : "";
    return this.request<T>(endpoint + query);
  }

  post<T>(endpoint: string, data?: any): Promise<T> {
    return this.request<T>(endpoint, { method: "POST", body: data });
  }

  put<T>(endpoint: string, data?: any): Promise<T> {
    return this.request<T>(endpoint, { method: "PUT", body: data });
  }

  delete<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: "DELETE" });
  }
}

const phpApi = new ApiClient("/api");
const goApi = new ApiClient("http://localhost:8080/api");
const pythonApi = new ApiClient("http://localhost:8000/api");
const rustApi = new ApiClient("http://localhost:3000/api");
const zigApi = new ApiClient("http://localhost:4000/api");
const elixirApi = new ApiClient("http://localhost:5000/api");

export const PHP = {
  login: (email: string, password: string) =>
    phpApi.post<{ token: string; user: User }>("/auth/login", {
      email,
      password,
    }),

  register: (email: string, password: string, name: string) =>
    phpApi.post<{ token: string; user: User }>("/auth/register", {
      email,
      password,
      name,
    }),

  logout: () => phpApi.post("/auth/logout"),

  getProfile: () => phpApi.get<User>("/user/profile"),

  updateProfile: (data: Partial<User>) =>
    phpApi.put<User>("/user/profile", data),

  sendSMSVerification: (token: string) =>
    phpApi.post("/user/verify-sms", { token }),

  forgotPassword: (email: string) =>
    phpApi.post<{ success: boolean; message?: string }>(
      "/auth/forgot-password",
      { email },
    ),

  getMessages: () => phpApi.get<{ conversations: Conversation[] }>("/messages"),

  getConversationMessages: (conversationId: string) =>
    phpApi.get<{ messages: Message[] }>(
      `/conversations/${conversationId}/messages`,
    ),

  sendMessage: (data: {
    conversationId: string;
    content: string;
    files?: FileItem[];
  }) => phpApi.post<Message>("/messages", data),

  createThread: (
    messageId: string,
    data: { content: string; conversationId: string },
  ) => phpApi.post<Message>(`/messages/${messageId}/thread`, data),

  markMessageRead: (messageId: string) =>
    phpApi.post(`/messages/${messageId}/read`),

  addReaction: (messageId: string, reaction: string) =>
    phpApi.post(`/messages/${messageId}/reaction`, { reaction }),

  uploadMessageFile: (file: File, conversationId: string) => {
    const formData = new FormData();
    formData.append("file", file);
    formData.append("conversationId", conversationId);
    return phpApi.post<FileItem>("/messages/upload", formData);
  },

  getConversations: () => phpApi.get<Conversation[]>("/conversations"),

  getGroups: () => phpApi.get<Group[]>("/groups"),

  createGroup: (data: Partial<Group>) => phpApi.post<Group>("/groups", data),

  updateGroup: (groupId: string, data: Partial<Group>) =>
    phpApi.put<Group>(`/groups/${groupId}`, data),

  joinGroup: (groupId: string) => phpApi.post<Group>(`/groups/${groupId}/join`),

  leaveGroup: (groupId: string) => phpApi.delete(`/groups/${groupId}/leave`),

  addGroupParticipant: (
    groupId: string,
    userId: string,
    role: string = "member",
  ) => phpApi.post(`/groups/${groupId}/participants`, { userId, role }),

  removeGroupParticipant: (groupId: string, userId: string) =>
    phpApi.delete(`/groups/${groupId}/participants/${userId}`),

  getNotifications: () => phpApi.get<Notification[]>("/notifications"),

  markNotificationRead: (notificationId: string) =>
    phpApi.post(`/notifications/${notificationId}/read`),

  getPolls: () => phpApi.get<Poll[]>("/polls"),

  createPoll: (data: {
    conversationId: string;
    question: string;
    options: string[];
  }) => phpApi.post<Poll>("/polls", data),

  votePoll: (pollId: string, optionIds: string[]) =>
    phpApi.post(`/polls/${pollId}/vote`, { optionIds }),

  deletePoll: (pollId: string) => phpApi.delete(`/polls/${pollId}`),

  getFiles: () => phpApi.get<FileItem[]>("/files"),

  uploadFile: (file: File, conversationId?: string) => {
    const formData = new FormData();
    formData.append("file", file);
    if (conversationId) formData.append("conversationId", conversationId);
    return phpApi.post<FileItem>("/files/upload", formData);
  },

  deleteFile: (fileId: string) => phpApi.delete(`/files/${fileId}`),
};

export const Go = {
  createBooking: (bookingData: any) =>
    goApi.post<any>("/bookings", bookingData),

  getBookings: () => goApi.get<any[]>("/bookings"),

  getBooking: (bookingId: string) => goApi.get<any>(`/bookings/${bookingId}`),

  updateBooking: (bookingId: string, data: any) =>
    goApi.put<any>(`/bookings/${bookingId}`, data),

  cancelBooking: (bookingId: string) =>
    goApi.post(`/bookings/${bookingId}/cancel`),

  getLocation: (userId: string) =>
    goApi.get<{ latitude: number; longitude: number }>(`/location/${userId}`),

  updateLocation: (data: {
    latitude: number;
    longitude: number;
    address?: string;
  }) => goApi.post("/location", data),

  getNearbyUsers: (lat: number, lng: number, radius: number = 5) =>
    goApi.get<any[]>("/location/nearby", { lat, lng, radius }),

  setLocationSharing: (enabled: boolean) =>
    goApi.post("/location/sharing", { enabled }),

  getNearbyServices: (params: {
    latitude: number;
    longitude: number;
    radius?: number;
    type?: string;
  }) => goApi.get<any[]>("/location/nearby", params),

  calculateRoute: (
    from: { lat: number; lng: number },
    to: { lat: number; lng: number },
    mode: string = "driving",
  ) => goApi.post<any>("/location/route", { from, to, mode }),

  getServiceProviders: (serviceType: string) =>
    goApi.get<any[]>(`/providers/${serviceType}`),

  getGeofence: (userId: string) => goApi.get<any>(`/geofences/${userId}`),

  createGeofence: (data: any) => goApi.post("/geofences", data),

  getWallet: (userId: string) => goApi.get<any>(`/wallet/${userId}`),

  createWallet: (userId: string) => goApi.post<any>("/wallet", { userId }),

  getTransactions: (walletId: string) =>
    goApi.get<any[]>(`/wallet/${walletId}/transactions`),

  processPayment: (paymentData: any) =>
    goApi.post<any>("/payments/process", paymentData),

  addFunds: (walletId: string, amount: number, currency: string = "USD") =>
    goApi.post<any>("/wallet/${walletId}/add", { amount, currency }),

  getPayments: () => goApi.get<any[]>("/payments"),

  createPayment: (data: any) => goApi.post("/payments", data),

  getPaymentMethods: () => goApi.get<any[]>("/payment-methods"),

  addPaymentMethod: (data: any) => goApi.post("/payment-methods", data),

  sendBroadcast: (data: any) => goApi.post<any>("/broadcasts", data),

  getBroadcastStatus: (broadcastId: string) =>
    goApi.get<any>(`/broadcasts/${broadcastId}/status`),

  getBroadcasts: () => goApi.get<any[]>("/broadcasts"),

  sendBroadcastMessage: (broadcastId: string) =>
    goApi.post(`/broadcasts/${broadcastId}/send`),

  createRoom: (data: any) => goApi.post<any>("/video/rooms", data),

  joinRoom: (roomId: string) => goApi.post<any>(`/video/rooms/${roomId}/join`),

  leaveRoom: (roomId: string) => goApi.post(`/video/rooms/${roomId}/leave`),

  toggleScreenShare: (roomId: string, enabled: boolean) =>
    goApi.post(`/video/rooms/${roomId}/screen-share`, { enabled }),

  getRoomParticipants: (roomId: string) =>
    goApi.get<any[]>(`/video/rooms/${roomId}/participants`),

  createWebhook: (webhookData: any) =>
    goApi.post<any>("/webhooks", webhookData),

  getWebhooks: () => goApi.get<any[]>("/webhooks"),

  triggerWebhook: (webhookId: string, payload: any) =>
    goApi.post(`/webhooks/${webhookId}/trigger`, payload),

  getAnalytics: (period?: string) => goApi.get<any>("/analytics", { period }),

  getIntegrations: () => goApi.get<any[]>("/integrations"),

  connectIntegration: (integrationType: string) =>
    goApi.post<any>("/integrations/connect", { type: integrationType }),

  disconnectIntegration: (integrationId: string) =>
    goApi.delete(`/integrations/${integrationId}`),

  sendPayment: (paymentData: any) =>
    goApi.post<any>("/payments/send", paymentData),

  requestPayment: (paymentData: any) =>
    goApi.post<any>("/payments/request", paymentData),

  getConferences: () => goApi.get<any[]>("/video/conferences"),

  createConference: (data: any) => goApi.post<any>("/video/conferences", data),

  joinConference: (conferenceId: string) =>
    goApi.post<any>(`/video/conferences/${conferenceId}/join`),
};

export const Python = {
  getAIChatbotResponse: (message: string, context?: Record<string, any>) =>
    pythonApi.post<{ response: string }>("/ai/chat", { message, context }),

  getAIRecommendations: (userId: string) =>
    pythonApi.get<any[]>(`/ai/recommendations/${userId}`),

  analyzeSentiment: (text: string) =>
    pythonApi.post<{ sentiment: string; score: number }>("/ai/sentiment", {
      text,
    }),

  getSmartReplies: (conversationContext: any) =>
    pythonApi.post<any[]>("/ai/smart-replies", {
      context: conversationContext,
    }),

  translateText: (text: string, sourceLang: string, targetLang: string) =>
    pythonApi.post<{ translated: string }>("/ai/translate", {
      text,
      source: sourceLang,
      target: targetLang,
    }),

  moderateContent: (content: string) =>
    pythonApi.post<any>("/ai/moderate", { content }),

  generateSummary: (conversationId: string) =>
    pythonApi.get<any>(`/ai/summary/${conversationId}`),

  getProducts: (params?: {
    category?: string;
    limit?: number;
    offset?: number;
  }) => pythonApi.get<any[]>("/ecommerce/products", params),

  getProduct: (productId: string) =>
    pythonApi.get<any>(`/ecommerce/products/${productId}`),

  getProductDetails: (productId: string) =>
    pythonApi.get<any>(`/ecommerce/products/${productId}`),

  getTrendingProducts: (limit: number = 10) =>
    pythonApi.get<any[]>("/ecommerce/trending", { limit }),

  searchProducts: (query: string) =>
    pythonApi.get<any[]>("/ecommerce/search", { q: query }),

  addToCart: (data: { productId: string; quantity: number }) =>
    pythonApi.post<any>("/ecommerce/cart", data),

  getCart: () => pythonApi.get<any[]>("/ecommerce/cart"),

  checkout: (data: any) => pythonApi.post("/ecommerce/checkout", data),

  getOrders: () => pythonApi.get<any[]>("/ecommerce/orders"),

  getOrder: (orderId: string) =>
    pythonApi.get<any>(`/ecommerce/orders/${orderId}`),

  getEvents: (params?: { start?: string; end?: string }) =>
    pythonApi.get<any[]>("/events", params),

  createEvent: (data: any) => pythonApi.post("/events", data),

  updateEvent: (eventId: string, data: any) =>
    pythonApi.put(`/events/${eventId}`, data),

  deleteEvent: (eventId: string) => pythonApi.delete(`/events/${eventId}`),

  search: (query: string, filters?: Record<string, any>) =>
    pythonApi.post<any[]>("/search", { query, filters }),

  getSuggestions: (partialQuery: string) =>
    pythonApi.get<any[]>(`/search/suggestions`, { q: partialQuery }),

  createBot: (data: any) => pythonApi.post("/bots", data),

  getBots: () => pythonApi.get<any[]>("/bots"),

  getBotResponse: (botId: string, message: string) =>
    pythonApi.post<{ response: string }>(`/bots/${botId}/chat`, { message }),

  getUserAnalytics: (userId: string) =>
    pythonApi.get<any>(`/analytics/user/${userId}`),

  getConversationAnalytics: (conversationId: string) =>
    pythonApi.get<any>(`/analytics/conversation/${conversationId}`),

  getEngagementMetrics: (userId: string, days: number = 7) =>
    pythonApi.get<any>(`/analytics/engagement/${userId}`, { days }),

  getRetentionData: (startDate: string, endDate: string) =>
    pythonApi.get<any>("/analytics/retention", {
      start: startDate,
      end: endDate,
    }),

  getChurnPrediction: (userId: string) =>
    pythonApi.get<any>(`/analytics/churn/${userId}`),

  getMetrics: () => pythonApi.get<any>("/analytics/metrics"),
};

export const Rust = {
  generateKeyPair: () =>
    rustApi.post<{ publicKey: string; privateKey: string }>(
      "/crypto/generate-keypair",
    ),

  encryptMessage: (message: string, publicKey: string) =>
    rustApi.post<{ encrypted: string }>("/crypto/encrypt", {
      message,
      publicKey,
    }),

  decryptMessage: (encryptedMessage: string, privateKey: string) =>
    rustApi.post<{ decrypted: string }>("/crypto/decrypt", {
      encrypted: privateKey,
    }),

  uploadFile: (file: File, encryptionEnabled: boolean = false) => {
    const formData = new FormData();
    formData.append("file", file);
    formData.append("encryptionEnabled", String(encryptionEnabled));
    return rustApi.post<any>("/files/upload", formData);
  },

  downloadFile: (fileId: string) =>
    rustApi.get<Blob>(`/files/${fileId}/download`),

  shareFile: (
    fileId: string,
    recipientEmail: string,
    permissions: string = "read",
  ) =>
    rustApi.post<any>("/files/share", { fileId, recipientEmail, permissions }),

  getSharedFiles: () => rustApi.get<any[]>("/files/shared"),

  listProducts: (filters?: Record<string, any>) =>
    rustApi.get<any[]>("/marketplace/products", filters),

  createProduct: (data: any) =>
    rustApi.post<any>("/marketplace/products", data),

  getProduct: (productId: string) =>
    rustApi.get<any>(`/marketplace/products/${productId}`),

  placeBid: (productId: string, amount: number) =>
    rustApi.post<any>("/marketplace/products/${productId}/bid", { amount }),

  buyProduct: (productId: string) =>
    rustApi.post<any>("/marketplace/products/${productId}/buy"),

  getVideoRooms: () => rustApi.get<any[]>("/video/rooms"),

  createVideoRoom: (data: { topic: string; maxParticipants?: number }) =>
    rustApi.post<any>("/video/rooms", data),

  joinVideoRoom: (roomId: string) =>
    rustApi.post(`/video/rooms/${roomId}/join`),

  leaveVideoRoom: (roomId: string) =>
    rustApi.post(`/video/rooms/${roomId}/leave`),

  startRecording: (roomId: string) =>
    rustApi.post(`/video/rooms/${roomId}/recording/start`),

  stopRecording: (roomId: string) =>
    rustApi.post(`/video/rooms/${roomId}/recording/stop`),

  getRecordings: () => rustApi.get<any[]>("/video/recordings"),

  deleteRecording: (recordingId: string) =>
    rustApi.delete(`/video/recordings/${recordingId}`),
};

export const Zig = {
  getWebRTCConfig: () => zigApi.get<RTCConfiguration>("/webrtc/config"),

  createCall: (data: { participantId: string; type: "audio" | "video" }) =>
    zigApi.post<any>("/calls", data),

  initiateCall: (data: { participantId: string; type: "audio" | "video" }) =>
    zigApi.post<any>("/calls/initiate", data),

  joinCall: (callId: string) => zigApi.post<any>(`/calls/${callId}/join`),

  answerCall: (callId: string) => zigApi.post(`/calls/${callId}/answer`),

  declineCall: (callId: string) => zigApi.post(`/calls/${callId}/decline`),

  endCall: (callId: string) => zigApi.post(`/calls/${callId}/end`),

  getCallHistory: () => zigApi.get<Call[]>("/calls/history"),

  getOngoingCalls: () => zigApi.get<Call[]>("/calls/ongoing"),

  getActiveCalls: () => zigApi.get<any[]>("/calls/active"),

  recordVoiceMail: (audioFile: File) => {
    const formData = new FormData();
    formData.append("audio", audioFile);
    return zigApi.post<any>("/voicemail/record", formData);
  },

  transcribeVoiceMail: (voiceMailId: string) =>
    zigApi.get<{ text: string }>(`/voicemail/${voiceMailId}/transcribe`),

  getVoiceMails: (userId: string) => zigApi.get<any[]>(`/voicemail/${userId}`),

  deleteVoiceMail: (voiceMailId: string) =>
    zigApi.delete(`/voicemail/${voiceMailId}`),

  moderateText: (text: string) =>
    zigApi.post<any>("/moderation/text", { text }),

  moderateImage: (imageFile: File) => {
    const formData = new FormData();
    formData.append("image", imageFile);
    return zigApi.post<any>("/moderation/image", formData);
  },

  moderateVideo: (videoFile: File) => {
    const formData = new FormData();
    formData.append("video", videoFile);
    return zigApi.post<any>("/moderation/video", formData);
  },
};

export const Elixir = {
  getStories: () => elixirApi.get<any[]>("/stories"),

  createStory: (data: { content: string; type: "image" | "video" }) =>
    elixirApi.post<any>("/stories", data),

  viewStory: (storyId: string) => elixirApi.post(`/stories/${storyId}/view`),

  deleteStory: (storyId: string) => elixirApi.delete(`/stories/${storyId}`),

  getReels: () => elixirApi.get<any[]>("/reels"),

  createReel: (data: any) => elixirApi.post("/reels", data),

  likeReel: (reelId: string) => elixirApi.post(`/reels/${reelId}/like`),

  commentReel: (reelId: string, comment: string) =>
    elixirApi.post(`/reels/${reelId}/comment`, { comment }),

  getSocialGraph: () => elixirApi.get<any>("/social/graph"),

  getFollowers: (userId: string) =>
    elixirApi.get<any[]>(`/social/${userId}/followers`),

  getFollowing: (userId: string) =>
    elixirApi.get<any[]>(`/social/${userId}/following`),

  followUser: (userId: string) => elixirApi.post(`/social/${userId}/follow`),

  unfollowUser: (userId: string) =>
    elixirApi.post(`/social/${userId}/unfollow`),

  createEvent: (data: any) => elixirApi.post<any>("/events", data),

  getEvents: (userId: string) => elixirApi.get<any[]>(`/events/${userId}`),

  getEvent: (eventId: string) =>
    elixirApi.get<any>(`/events/single/${eventId}`),

  rsvpEvent: (eventId: string, status: string = "going") =>
    elixirApi.post<any>(`/events/${eventId}/rsvp`, { status }),

  getCalendarIntegration: (provider: string) =>
    elixirApi.get<any>(`/calendar/${provider}`),

  getBalance: (walletId: string) =>
    elixirApi.get<{ balance: number }>(`/wallet/${walletId}/balance`),

  transfer: (fromWalletId: string, toWalletId: string, amount: number) =>
    elixirApi.post<any>("/wallet/transfer", {
      from: fromWalletId,
      to: toWalletId,
      amount,
    }),

  getTransactionHistory: (walletId: string) =>
    elixirApi.get<any[]>(`/wallet/${walletId}/transactions`),

  createTransaction: (data: any) =>
    elixirApi.post<any>("/wallet/transaction", data),
};

// Legacy helper functions for backward compatibility
export const fetchCalls = () => Promise.resolve<any[]>([]);
export const startCall = (type: "audio" | "video" = "audio") =>
  Zig.initiateCall({ type, participantId: "demo" });
export const joinCall = (callId: string) => Zig.joinCall(callId);
export const subscribeToTopic = (
  topic: string,
  callback?: (data: any) => void,
) => console.log("Subscribed to:", topic);
export const connectToMercure = () => console.log("Connected to Mercure");
export const fetchConversations = () => PHP.getConversations();
export const fetchConversationMessages = () => PHP.getMessages();
export const fetchGroups = () => PHP.getGroups();
export const createGroup = (data: any) => PHP.createGroup(data);
export const joinGroup = (groupId: string) => PHP.joinGroup(groupId);
export const leaveGroup = (groupId: string) => PHP.leaveGroup(groupId);
export const fetchGroupDetails = (_groupId: string) => Promise.resolve(null);
export const addParticipant = (
  groupId: string,
  userId: string,
  role: string = "member",
) => PHP.addGroupParticipant(groupId, userId, role);
export const sendMessageAPI = (conversationId: string, content: string) =>
  PHP.sendMessage({ conversationId, content });
export const createThread = (
  messageId: string,
  data: { content: string; conversationId: string },
) => PHP.createThread(messageId, data);
export const fetchFiles = () => PHP.getFiles();
export const deleteFile = (fileId: string) => PHP.deleteFile(fileId);
export const uploadFile = (file: File, conversationId?: string) =>
  PHP.uploadFile(file, conversationId);
export const updateProfile = (data: Partial<User>) => PHP.updateProfile(data);
export const loginAPI = (credentials: { email: string; password: string }) =>
  PHP.login(credentials.email, credentials.password);
export const registerAPI = (data: {
  email: string;
  password: string;
  name: string;
}) => PHP.register(data.email, data.password, data.name);
export const fetchDashboardStats = () =>
  Promise.resolve({ messages: 0, groups: 0, files: 0, calls: 0 });
export const markMessageRead = (_messageId: string) => Promise.resolve();
export const sendSMSVerification = (phoneNumber: string, displayName: string) =>
  PHP.sendSMSVerification(phoneNumber);
export const verifyPhoneCode = (
  phoneNumber: string,
  code: string,
  displayName: string,
) =>
  Promise.resolve({
    user: { id: "1", name: displayName, email: "" },
    token: "demo-token",
  });

export default { PHP, Go, Python, Rust, Zig, Elixir };
