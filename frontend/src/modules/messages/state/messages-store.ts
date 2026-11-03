import { writable, derived } from 'svelte/store';

interface Message {
  id: string;
  sender: string;
  content: string;
  timestamp: Date;
  read: boolean;
  conversationId: string;
}

interface MessageState {
  messages: Message[];
  conversations: Conversation[];
  activeConversation: string | null;
  loading: boolean;
  error: string | null;
}

interface Conversation {
  id: string;
  participant: string;
  lastMessage: string;
  timestamp: Date;
  unreadCount: number;
}

function createMessageStore() {
  const initialState: MessageState = {
    messages: [],
    conversations: [],
    activeConversation: null,
    loading: false,
    error: null
  };
  const { subscribe, set, update } = writable<MessageState>(initialState);

  return {
    subscribe,

    sendMessage: async (content: string, conversationId: string) => {
      const message: Message = {
        id: Date.now().toString(),
        sender: 'me',
        content,
        timestamp: new Date(),
        read: true,
        conversationId
      };

      update(state => ({
        ...state,
        messages: [...state.messages, message]
      }));

      try {
        const response = await fetch('/api/messages/send', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ content, conversationId })
        });

        if (!response.ok) {
          throw new Error('Failed to send message');
        }
      } catch (error) {
        update(state => ({
          ...state,
          error: (error as Error).message
        }));
      }
    },

    loadConversations: async () => {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const response = await fetch('/api/messages/conversations');
        
        if (!response.ok) {
          throw new Error('Failed to load conversations');
        }

        const conversations = await response.json();
        
        update(state => ({ ...state, conversations, loading: false }));
        return conversations;
      } catch (error) {
        update(state => ({
          ...state,
          loading: false,
          error: (error as Error).message
        }));
      }
    },

    setActiveConversation: (conversationId: string) => {
      update(state => ({
        ...state,
        activeConversation: conversationId
      }));
    },

    clearError: () => {
      update(state => ({ ...state, error: null }));
    },
    reset: () => set(initialState)
  };
}

export const messageStore = createMessageStore();

export const conversations = derived(
  messageStore,
  $messageStore => $messageStore.conversations
);

export const activeMessages = derived(
  messageStore,
  $messageStore => $messageStore.messages.filter(
    msg => $messageStore.activeConversation && 
    msg.conversationId === $messageStore.activeConversation
  )
);