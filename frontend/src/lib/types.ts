// Unified Type Definitions for the Application
// All type definitions consolidated here

// ============================================================================
// USER TYPES
// ============================================================================

export interface User {
  id: string;
  name: string;
  email: string;
  bio?: string;
  avatar?: string;
  avatarUrl?: string;
  phone?: string;
  role?: string;
  isActive?: boolean;
  isMFAEnabled?: boolean;
  createdAt?: string;
  created_at?: string;
  updatedAt?: string;
  lastLoginAt?: string;
}

export interface UserProfile {
  user: User;
  stats: {
    messagesCount: number;
    groupsCount: number;
    filesCount: number;
    callsCount: number;
  };
}

export interface AuthToken {
  token: string;
  refreshToken?: string;
  expiresIn: number;
  tokenType: string;
}

// ============================================================================
// MESSAGE TYPES
// ============================================================================

export interface Message {
  id: string;
  conversationId: string;
  content: string;
  senderId: string;
  type: "text" | "image" | "file" | "audio" | "video";
  createdAt: string;
  created_at?: string;
  timestamp?: number;
  files?: FileItem[];
  reactions?: MessageReaction[];
  attachments?: FileItem[];
  threadId?: string;
  replyToId?: string;
  read?: boolean;
  readAt?: string;
}

export interface MessageReaction {
  id: string;
  emoji: string;
  userId: string;
  createdAt: string;
}

export interface TypingUser {
  userId: string;
  name: string;
  avatar?: string;
  timestamp: number;
}

export interface Conversation {
  id: string;
  title: string;
  type: "direct" | "group";
  participants: string[];
  participantDetails?: User[];
  lastMessage?: Message;
  unreadCount?: number;
  unread?: number;
  createdAt?: string;
  updatedAt?: string;
  avatar?: string;
  isMuted?: boolean;
  isPinned?: boolean;
}

export interface ConversationWithMessages {
  conversation: Conversation;
  messages: Message[];
}

// ============================================================================
// GROUP TYPES
// ============================================================================

export interface Group {
  id: string;
  name: string;
  title?: string;
  description?: string;
  avatarUrl?: string;
  avatar?: string;
  members: string[];
  participants?: string[];
  participantCount?: number;
  memberCount?: number;
  membersCount?: number;
  adminIds?: string[];
  createdBy?: string;
  createdAt?: string;
  updatedAt?: string;
  isPublic?: boolean;
  allowMemberInvite?: boolean;
}

export interface GroupMember {
  userId: string;
  user: User;
  role: "admin" | "member";
  joinedAt: string;
}

// ============================================================================
// FILE TYPES
// ============================================================================

export interface FileItem {
  id: string;
  name: string;
  filename: string;
  size: number;
  url: string;
  type?: string;
  mimeType: string;
  mime_type: string;
  uploadedAt: number;
  uploaded_at?: string;
  uploadedBy: string;
  uploadedByUser?: User;
  createdAt?: string;
  conversationId?: string;
  messageId?: string;
  isEncrypted?: boolean;
  storageUsed?: number;
  storageTotal?: number;
}

export interface UploadProgress {
  fileId: string;
  progress: number;
  status: "pending" | "uploading" | "completed" | "error";
  error?: string;
}

// ============================================================================
// CALL TYPES
// ============================================================================

export interface Call {
  id: string;
  callId: string;
  type: "audio" | "video";
  participants: string[];
  participantDetails?: User[];
  status: "initiated" | "ringing" | "in-progress" | "ended" | "missed";
  startedAt: string;
  started_at?: string;
  endedAt?: string;
  duration?: number;
  direction?: "outgoing" | "incoming";
  title?: string;
  name?: string;
  participantCount?: number;
}

export interface WebRTCCall {
  callId: string;
  localStream: MediaStream | null;
  remoteStream: MediaStream | null;
  peerConnection: RTCPeerConnection | null;
  configuration: RTCConfiguration;
}

export interface CallParticipant {
  odId: string;
  user: User;
  isMuted: boolean;
  isVideoEnabled: boolean;
  isScreenSharing: boolean;
  joinedAt: string;
}

// ============================================================================
// NOTIFICATION TYPES
// ============================================================================

export interface Notification {
  id: string;
  type: "message" | "call" | "group" | "system" | "friend_request";
  title: string;
  body?: string;
  message?: string;
  data?: Record<string, any>;
  createdAt: string;
  created_at?: string;
  readAt?: string;
  read?: boolean;
  read_at?: string;
}

export interface PushNotification {
  title: string;
  body: string;
  icon?: string;
  data?: Record<string, string>;
  actions?: NotificationAction[];
}

export interface NotificationAction {
  action: string;
  title: string;
  icon?: string;
}

// ============================================================================
// POLL TYPES
// ============================================================================

export interface Poll {
  id: string;
  question: string;
  description?: string;
  options: PollOption[];
  votes: number[];
  totalVotes: number;
  createdBy: string;
  createdAt: string;
  expiresAt?: string;
  isAnonymous?: boolean;
  allowMultiple?: boolean;
  status: "active" | "closed" | "draft";
  active?: boolean;
}

export interface PollOption {
  id: string;
  text: string;
  voteCount?: number;
  votes?: number;
  percentage?: number;
  isVoted?: boolean;
}

export interface PollVote {
  pollId: string;
  optionIds: string[];
}

// ============================================================================
// PAYMENT/WALLET TYPES
// ============================================================================

export interface Wallet {
  id: string;
  userId: string;
  balance: number;
  currency: string;
  createdAt: string;
  updatedAt: string;
}

export interface Transaction {
  id: string;
  walletId: string;
  type: "credit" | "debit";
  amount: number;
  currency: string;
  description: string;
  status: "pending" | "completed" | "failed";
  createdAt: string;
  metadata?: Record<string, any>;
}

export interface PaymentMethod {
  id: string;
  type: "card" | "bank" | "wallet";
  last4?: string;
  brand?: string;
  expiryMonth?: number;
  expiryYear?: number;
  isDefault?: boolean;
}

// ============================================================================
// LOCATION TYPES
// ============================================================================

export interface Location {
  userId: string;
  latitude: number;
  longitude: number;
  accuracy?: number;
  timestamp: string;
  address?: string;
}

export interface Geofence {
  id: string;
  userId: string;
  name: string;
  latitude: number;
  longitude: number;
  radius: number;
  enabled: boolean;
  enterAction?: string;
  exitAction?: string;
}

export interface NearbyService {
  id: string;
  name: string;
  type: string;
  latitude: number;
  longitude: number;
  distance: number;
  rating?: number;
  reviewsCount?: number;
  priceRange?: string;
  isOpen?: boolean;
  imageUrl?: string;
}

// ============================================================================
// E-COMMERCE TYPES
// ============================================================================

export interface Product {
  id: string;
  name: string;
  description: string;
  price: number;
  currency: string;
  images: string[];
  category: string;
  sellerId: string;
  seller?: User;
  stock: number;
  rating: number;
  reviewsCount: number;
  createdAt: string;
}

export interface CartItem {
  productId: string;
  product: Product;
  quantity: number;
  price: number;
}

export interface Order {
  id: string;
  items: CartItem[];
  total: number;
  currency: string;
  status: "pending" | "processing" | "shipped" | "delivered" | "cancelled";
  shippingAddress: Address;
  createdAt: string;
}

export interface Address {
  id: string;
  userId: string;
  name: string;
  street: string;
  city: string;
  state: string;
  country: string;
  postalCode: string;
  phone: string;
  isDefault: boolean;
}

// ============================================================================
// ANALYTICS TYPES
// ============================================================================

export interface Analytics {
  userId: string;
  period: string;
  metrics: {
    messagesSent: number;
    messagesReceived: number;
    callsDuration: number;
    groupsJoined: number;
    filesShared: number;
    activeDays: number;
  };
  engagement: {
    dailyActiveUsers: number;
    retention: number;
    churnRisk: number;
  };
}

export interface EngagementMetrics {
  date: string;
  value: number;
  label?: string;
}

export interface RetentionData {
  cohort: string;
  retention: number[];
}

// ============================================================================
// BROADCAST TYPES
// ============================================================================

export interface Broadcast {
  id: string;
  message: string;
  senderId: string;
  channels: string[];
  recipients: string[];
  sentAt: string;
  status: "pending" | "sent" | "failed" | "cancelled";
  stats?: {
    sent: number;
    delivered: number;
    read: number;
    failed: number;
  };
}

// ============================================================================
// EVENT TYPES
// ============================================================================

export interface CalendarEvent {
  id: string;
  title: string;
  description?: string;
  startTime: string;
  endTime: string;
  location?: string;
  attendees: string[];
  attendeeDetails?: User[];
  reminders?: number[];
  createdBy: string;
  createdAt: string;
  status: "confirmed" | "tentative" | "cancelled";
  isAllDay?: boolean;
  recurrence?: {
    frequency: "daily" | "weekly" | "monthly" | "yearly";
    interval: number;
    until?: string;
  };
}

// ============================================================================
// CHAT BOT TYPES
// ============================================================================

export interface ChatBot {
  id: string;
  name: string;
  description: string;
  avatar?: string;
  greeting?: string;
  isActive: boolean;
  createdAt: string;
  capabilities: string[];
}

export interface BotConversation {
  botId: string;
  messages: Message[];
  context?: Record<string, any>;
  startedAt: string;
}

// ============================================================================
// VIDEO CONFERENCE TYPES
// ============================================================================

export interface VideoRoom {
  id: string;
  topic: string;
  hostId: string;
  participants: string[];
  status: "waiting" | "in-progress" | "ended";
  startedAt: string;
  endedAt?: string;
  settings: {
    maxParticipants: number;
    isRecording: boolean;
    isMuted: boolean;
    isVideoOff: boolean;
    waitingRoomEnabled: boolean;
  };
}

export interface Recording {
  id: string;
  roomId: string;
  hostId: string;
  url: string;
  duration: number;
  size: number;
  status: "processing" | "ready" | "deleted";
  createdAt: string;
}

// ============================================================================
// TYPING INDICATOR TYPES
// ============================================================================

export interface TypingEvent {
  conversationId: string;
  userId: string;
  timestamp: number;
}

export interface TypingIndicator {
  users: TypingUser[];
  lastUpdated: number;
}

// ============================================================================
// MERCURE REAL-TIME TYPES
// ============================================================================

export interface MercureMessage {
  event: string;
  data: any;
  id?: string;
  type?: string;
}

export interface MercureSubscription {
  topic: string;
  callback: (data: any) => void;
  eventSource: EventSource;
}

// ============================================================================
// ERROR TYPES
// ============================================================================

export interface ApiError {
  error: string;
  code?: string;
  message?: string;
  details?: Record<string, any>;
}

export interface ValidationError {
  field: string;
  message: string;
}

export interface AppError extends Error {
  code?: string;
  statusCode?: number;
  details?: Record<string, any>;
}

// ============================================================================
// COMMON TYPES
// ============================================================================

export interface Pagination {
  limit: number;
  offset: number;
  total: number;
  hasMore: boolean;
}

export interface PaginatedResponse<T> {
  data: T[];
  pagination: Pagination;
}

export interface SearchResult {
  id: string;
  type: "message" | "user" | "group" | "file";
  title: string;
  subtitle?: string;
  url?: string;
  score: number;
  highlights?: string[];
}

export interface SearchFilters {
  query: string;
  types?: string[];
  dateRange?: {
    start: string;
    end: string;
  };
  userIds?: string[];
  limit?: number;
  offset?: number;
}

export interface Toast {
  id: number;
  message: string;
  type: "success" | "error" | "warning" | "info";
  duration?: number;
  action?: {
    label: string;
    onClick: () => void;
  };
}

export interface LoadingState {
  isLoading: boolean;
  error: string | null;
  data: any;
}

export interface EmptyState {
  title: string;
  description?: string;
  icon?: string;
  action?: {
    label: string;
    onClick: () => void;
  };
}

export interface Theme {
  id: string;
  name: string;
  colors: {
    primary: string;
    secondary: string;
    background: string;
    surface: string;
    text: string;
    textSecondary: string;
    border: string;
    error: string;
    success: string;
    warning: string;
  };
}

export interface Settings {
  theme: "light" | "dark" | "system";
  language: string;
  notifications: {
    push: boolean;
    email: boolean;
    sound: boolean;
    vibration: boolean;
    quietHours?: {
      enabled: boolean;
      start: string;
      end: string;
    };
  };
  privacy: {
    showOnline: boolean;
    readReceipts: boolean;
    lastSeen: "everyone" | "contacts" | "nobody";
  };
  accessibility: {
    fontSize: "small" | "medium" | "large";
    highContrast: boolean;
    reducedMotion: boolean;
  };
}

// ============================================================================
// EXPORT DEFAULT TYPES MAP (for backward compatibility)
// ============================================================================

export type EntityTypes =
  | User
  | Message
  | Conversation
  | Group
  | FileItem
  | Call
  | Notification
  | Poll
  | Wallet
  | Transaction
  | CalendarEvent
  | VideoRoom
  | Product
  | Broadcast;

export type ApiEntityMap = {
  user: User;
  message: Message;
  conversation: Conversation;
  group: Group;
  file: FileItem;
  call: Call;
  notification: Notification;
  poll: Poll;
  wallet: Wallet;
  transaction: Transaction;
  event: CalendarEvent;
  room: VideoRoom;
  product: Product;
  broadcast: Broadcast;
};
