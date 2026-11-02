// Typing Handler Utility - TypeScript Version
// Manages typing indicators with debouncing and WebSocket integration

export interface TypingHandlerOptions {
  conversationId: string;
  userId: string;
  typingTimeout?: number;
  debounceDelay?: number;
  onTypingStart?: (data: TypingEventData) => void;
  onTypingStop?: (data: TypingStopData) => void;
}

export interface TypingEventData {
  conversationId: string;
  userId: string;
  timestamp: number;
}

export interface TypingStopData {
  conversationId: string;
  userId: string;
}

export interface RemoteTypingEvent {
  type: "typing_start" | "typing_stop";
  conversationId: string;
  userId: string;
  timestamp?: number;
}

export interface UserInfo {
  name: string;
  avatar?: string;
}

// ============================================================================
// TYPING HANDLER CLASS
// ============================================================================

export class TypingHandler {
  conversationId: string;
  userId: string;
  typingTimeout: number;
  debounceDelay: number;

  private isTyping: boolean = false;
  private typingTimer: ReturnType<typeof setTimeout> | null = null;
  private debounceTimer: ReturnType<typeof setTimeout> | null = null;
  private onTypingStart: (data: TypingEventData) => void;
  private onTypingStop: (data: TypingStopData) => void;

  constructor(options: TypingHandlerOptions) {
    this.conversationId = options.conversationId;
    this.userId = options.userId;
    this.typingTimeout = options.typingTimeout ?? 3000; // 3 seconds
    this.debounceDelay = options.debounceDelay ?? 500; // 500ms debounce
    this.onTypingStart = options.onTypingStart ?? (() => {});
    this.onTypingStop = options.onTypingStop ?? (() => {});
  }

  // Call when user starts typing
  startTyping(): void {
    if (this.isTyping) {
      // Reset timer if already typing
      this.resetTimer();
      return;
    }

    // Debounce to prevent spam
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
    }

    this.debounceTimer = setTimeout(() => {
      this.isTyping = true;
      this.onTypingStart({
        conversationId: this.conversationId,
        userId: this.userId,
        timestamp: Date.now(),
      });
      this.resetTimer();
    }, this.debounceDelay);
  }

  // Call when user stops typing (or on input)
  stopTyping(): void {
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = null;
    }

    if (this.isTyping) {
      this.isTyping = false;
      this.onTypingStop({
        conversationId: this.conversationId,
        userId: this.userId,
      });

      if (this.typingTimer) {
        clearTimeout(this.typingTimer);
        this.typingTimer = null;
      }
    }
  }

  // Reset the typing timeout
  private resetTimer(): void {
    if (this.typingTimer) {
      clearTimeout(this.typingTimer);
    }

    this.typingTimer = setTimeout(() => {
      this.stopTyping();
    }, this.typingTimeout);
  }

  // Force stop typing (useful for form submission)
  forceStop(): void {
    this.stopTyping();
  }

  // Check if currently typing
  getIsTyping(): boolean {
    return this.isTyping;
  }

  // Update configuration
  updateConfig(options: Partial<TypingHandlerOptions>): void {
    if (options.conversationId) this.conversationId = options.conversationId;
    if (options.userId) this.userId = options.userId;
    if (options.typingTimeout) this.typingTimeout = options.typingTimeout;
    if (options.debounceDelay) this.debounceDelay = options.debounceDelay;
  }

  // Cleanup
  destroy(): void {
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = null;
    }
    if (this.typingTimer) {
      clearTimeout(this.typingTimer);
      this.typingTimer = null;
    }
    this.isTyping = false;
  }
}

// ============================================================================
// TYPING MANAGER CLASS
// ============================================================================

export interface TypingManagerOptions {
  onTypingUpdate?: (conversationId: string, typingUsers: TypingUser[]) => void;
  getUserInfo?: (userId: string) => UserInfo;
}

export interface TypingUser {
  userId: string;
  name: string;
  avatar?: string;
  timestamp: number;
}

export class TypingManager {
  private handlers: Map<string, TypingHandler> = new Map(); // conversationId -> TypingHandler
  private typingUsers: Map<string, Map<string, TypingUser>> = new Map(); // conversationId -> Map<userId, TypingUser>
  private onTypingUpdate: (
    conversationId: string,
    typingUsers: TypingUser[],
  ) => void;
  private getUserInfo: (userId: string) => UserInfo;

  constructor(options: TypingManagerOptions = {}) {
    this.onTypingUpdate = options.onTypingUpdate ?? (() => {});
    this.getUserInfo = options.getUserInfo ?? this.defaultGetUserInfo;
  }

  // Get or create typing handler for a conversation
  getHandler(conversationId: string, userId: string): TypingHandler {
    if (!this.handlers.has(conversationId)) {
      const handler = new TypingHandler({
        conversationId,
        userId,
        onTypingStart: (data) => this.handleTypingStart(data),
        onTypingStop: (data) => this.handleTypingStop(data),
      });
      this.handlers.set(conversationId, handler);
    }
    return this.handlers.get(conversationId)!;
  }

  // Handle typing start event
  private handleTypingStart(data: TypingEventData): void {
    const { conversationId, userId } = data;

    if (!this.typingUsers.has(conversationId)) {
      this.typingUsers.set(conversationId, new Map());
    }

    const conversationTyping = this.typingUsers.get(conversationId)!;
    const userInfo = this.getUserInfo(userId);

    conversationTyping.set(userId, {
      userId,
      timestamp: data.timestamp,
      ...userInfo,
    });

    this.onTypingUpdate(
      conversationId,
      Array.from(conversationTyping.values()),
    );
  }

  // Handle typing stop event
  private handleTypingStop(data: TypingStopData): void {
    const { conversationId, userId } = data;

    if (this.typingUsers.has(conversationId)) {
      const conversationTyping = this.typingUsers.get(conversationId)!;
      conversationTyping.delete(userId);

      if (conversationTyping.size === 0) {
        this.typingUsers.delete(conversationId);
      }

      this.onTypingUpdate(
        conversationId,
        Array.from(conversationTyping.values()),
      );
    }
  }

  // Get typing users for a conversation
  getTypingUsers(conversationId: string): TypingUser[] {
    if (this.typingUsers.has(conversationId)) {
      return Array.from(this.typingUsers.get(conversationId)!.values());
    }
    return [];
  }

  // Handle remote typing events (from WebSocket)
  handleRemoteTypingEvent(event: RemoteTypingEvent): void {
    const { type, conversationId, userId, timestamp } = event;

    if (type === "typing_start") {
      this.handleTypingStart({
        conversationId,
        userId,
        timestamp: timestamp ?? Date.now(),
      });
    } else if (type === "typing_stop") {
      this.handleTypingStop({ conversationId, userId });
    }
  }

  // Set callback for typing updates
  setTypingUpdateCallback(
    callback: (conversationId: string, typingUsers: TypingUser[]) => void,
  ): void {
    this.onTypingUpdate = callback;
  }

  // Get user info (mock - in real app, get from user store)
  private defaultGetUserInfo(userId: string): UserInfo {
    return {
      name: `User ${userId}`,
      avatar: undefined,
    };
  }

  // Get typing users count for a conversation
  getTypingUsersCount(conversationId: string): number {
    return this.typingUsers.get(conversationId)?.size ?? 0;
  }

  // Check if a specific user is typing
  isUserTyping(conversationId: string, userId: string): boolean {
    return this.typingUsers.get(conversationId)?.has(userId) ?? false;
  }

  // Get all conversations with typing users
  getConversationsWithTypingUsers(): string[] {
    return Array.from(this.typingUsers.keys());
  }

  // Cleanup all handlers
  destroy(): void {
    for (const handler of this.handlers.values()) {
      handler.destroy();
    }
    this.handlers.clear();
    this.typingUsers.clear();
  }
}

// ============================================================================
// SINGLETON EXPORT
// ============================================================================

export const typingManager = new TypingManager();
