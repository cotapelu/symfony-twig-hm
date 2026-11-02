// Messages Store - TypeScript Version
// PHP Message Module with type safety

import { writable, derived, type Readable } from "svelte/store";
import { PHP } from "../lib/api";
import type { Message, Conversation, FileItem } from "../lib/types";

interface MessagesState {
  conversations: Conversation[];
  messages: Record<string, Message[]>;
  activeConversationId: string | null;
  loading: boolean;
  sending: boolean;
  error: string | null;
}

/**
 * Creates the messages store with typed state and actions
 */
function createMessagesStore(): {
  subscribe: (
    run: (value: MessagesState) => void,
    invalidate?: (value?: MessagesState) => void,
  ) => () => void;
  loadConversations: () => Promise<Conversation[]>;
  loadMessages: (conversationId: string) => Promise<Message[]>;
  sendMessage: (conversationId: string, content: string) => Promise<Message>;
  createThread: (
    messageId: string,
    conversationId: string,
    content: string,
  ) => Promise<Message>;
  markRead: (conversationId: string) => Promise<void>;
  addReaction: (messageId: string, reaction: string) => Promise<void>;
  uploadFile: (file: File, conversationId?: string) => Promise<FileItem>;
  setActiveConversation: (conversationId: string | null) => void;
  addMessage: (message: Message) => void;
  updateConversation: (conversation: Conversation) => void;
  clearError: () => void;
} {
  const initialState: MessagesState = {
    conversations: [],
    messages: {},
    activeConversationId: null,
    loading: false,
    sending: false,
    error: null,
  };

  const { subscribe, set, update } = writable<MessagesState>(initialState);

  const baseStore = {
    subscribe,
    loadConversations: async (): Promise<Conversation[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const data = await PHP.getMessages();
        const conversations: Conversation[] = Array.isArray(data)
          ? data
          : data.conversations || [];
        update((prev) => ({
          ...prev,
          conversations,
          loading: false,
        }));
        return conversations;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to load conversations";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    loadMessages: async (conversationId: string): Promise<Message[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const data = await PHP.getConversationMessages(conversationId);
        const messages = data.messages || [];
        update((prev) => ({
          ...prev,
          messages: {
            ...prev.messages,
            [conversationId]: messages,
          },
          loading: false,
        }));
        return messages;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to load messages";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    sendMessage: async (
      conversationId: string,
      content: string,
    ): Promise<Message> => {
      update((state) => ({ ...state, sending: true, error: null }));
      try {
        const message = await PHP.sendMessage({
          conversationId,
          content,
        });
        update((prev) => ({
          ...prev,
          sending: false,
          messages: {
            ...prev.messages,
            [conversationId]: [
              ...(prev.messages[conversationId] || []),
              message,
            ],
          },
        }));
        return message;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to send message";
        update((state) => ({
          ...state,
          sending: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    createThread: async (
      messageId: string,
      conversationId: string,
      content: string,
    ): Promise<Message> => {
      update((state) => ({ ...state, sending: true, error: null }));
      try {
        const message = await PHP.createThread(messageId, {
          content,
          conversationId,
        });
        update((state) => ({ ...state, sending: false }));
        return message;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create thread";
        update((state) => ({
          ...state,
          sending: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    markRead: async (conversationId: string): Promise<void> => {
      try {
        await PHP.markMessageRead(conversationId);
        update((prev) => ({
          ...prev,
          conversations: prev.conversations.map((conv) =>
            conv.id === conversationId ? { ...conv, unreadCount: 0 } : conv,
          ),
        }));
      } catch (error) {
        console.error("Failed to mark messages as read:", error);
      }
    },

    addReaction: async (messageId: string, reaction: string): Promise<void> => {
      try {
        await PHP.addReaction(messageId, reaction);
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to add reaction";
        update((state) => ({ ...state, error: errorMessage }));
      }
    },

    uploadFile: async (
      file: File,
      conversationId?: string,
    ): Promise<FileItem> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const fileItem = await PHP.uploadFile(file, conversationId);
        if (conversationId) {
          update((prev) => ({
            ...prev,
            loading: false,
            messages: {
              ...prev.messages,
              [conversationId]: [
                ...(prev.messages[conversationId] || []),
                {
                  id: fileItem.id,
                  conversationId,
                  type: "file" as const,
                  content: fileItem.name,
                  senderId: "me",
                  createdAt: new Date().toISOString(),
                } as Message,
              ],
            },
          }));
        }
        return fileItem;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to upload file";
        update((state) => ({
          ...state,
          loading: false,
          error: errorMessage,
        }));
        throw error;
      }
    },

    setActiveConversation: (conversationId: string | null): void => {
      update((state) => ({
        ...state,
        activeConversationId: conversationId,
      }));
    },

    addMessage: (message: Message): void => {
      update((prev) => {
        const conversationId = message.conversationId;
        const existingMessages = prev.messages[conversationId] || [];
        return {
          ...prev,
          messages: {
            ...prev.messages,
            [conversationId]: [...existingMessages, message],
          },
          activeConversationId: prev.activeConversationId || conversationId,
        };
      });
    },

    updateConversation: (conversation: Conversation): void => {
      update((prev) => ({
        ...prev,
        conversations: prev.conversations.map((conv) =>
          conv.id === conversation.id ? { ...conv, ...conversation } : conv,
        ),
      }));
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        conversations: [],
        messages: {},
        activeConversationId: null,
        loading: false,
        sending: false,
        error: null,
      });
    },
  };

  return baseStore;
}

export const messagesStore = createMessagesStore();

export const conversations = derived(
  messagesStore as unknown as Readable<MessagesState>,
  ($state: MessagesState) => $state.conversations,
) as Readable<Conversation[]>;
export const selectedConversation = derived(
  messagesStore as unknown as Readable<MessagesState>,
  ($state: MessagesState) =>
    $state.activeConversationId
      ? $state.conversations.find((c) => c.id === $state.activeConversationId)
      : null,
) as Readable<Conversation | null>;
export const messages = derived(
  messagesStore as unknown as Readable<MessagesState>,
  ($state: MessagesState) => $state.messages,
) as Readable<Record<string, Message[]>>;
export const loading = derived(
  messagesStore as unknown as Readable<MessagesState>,
  ($state: MessagesState) => $state.loading,
) as Readable<boolean>;

export const activeConversation = derived(
  messagesStore as unknown as Readable<MessagesState>,
  ($state: MessagesState) =>
    $state.activeConversationId
      ? $state.messages[$state.activeConversationId] || []
      : [],
) as Readable<Message[]>;

export const unreadCount = derived(
  messagesStore as unknown as Readable<MessagesState>,
  ($state: MessagesState) =>
    $state.conversations.reduce(
      (total: number, conv: Conversation) => total + (conv.unreadCount || 0),
      0,
    ),
) as Readable<number>;

export const {
  loadConversations,
  loadMessages,
  sendMessage,
  createThread,
  markRead,
  addReaction,
  uploadFile,
  setActiveConversation,
  addMessage,
  updateConversation,
  clearError,
} = messagesStore;

export const selectConversation = setActiveConversation;
