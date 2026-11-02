import { writable, derived, get } from 'svelte/store';
import type { ChatbotConversation, ChatbotState } from '../contracts/chatbotTypes';
import { chatbotService } from '../services/chatbotService';

const initialState: ChatbotState = {
  conversations: [],
  activeConversation: null,
  loading: false,
  error: null,
  currentUserId: null
};

function createChatbotStore() {
  const { subscribe, set, update } = writable<ChatbotState>(initialState);
  return {
    subscribe,
    
    setCurrentUser(userId: string) {
      update(s => ({ ...s, currentUserId: userId }));
    },
    
    async loadConversations() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const state = get({ subscribe });
        const conversations = state.currentUserId 
          ? await chatbotService.getMyConversations()
          : await chatbotService.getConversations();
        update(s => ({ ...s, conversations, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    
    async loadConversation(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const conversation = await chatbotService.getConversation(id);
        const state = get({ subscribe });
        
        if (state.currentUserId && conversation.userId !== state.currentUserId) {
          throw new Error('Unauthorized: You can only access your own conversations');
        }
        
        update(s => ({ ...s, activeConversation: conversation, loading: false }));
        return conversation;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },
    
    async startConversation() {
      const conversation = await chatbotService.startNewConversation();
      update(s => ({ ...s, conversations: [conversation, ...s.conversations], activeConversation: conversation }));
      return conversation;
    },
    
    async sendMessage(conversationId: string, message: string, context?: Record<string, unknown>) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const msg = await chatbotService.sendMessage(conversationId, { message, context });
        update(s => {
          if (s.activeConversation?.id === conversationId) {
            return { ...s, activeConversation: { ...s.activeConversation, messages: [...s.activeConversation.messages, msg] }, loading: false };
          }
          return { ...s, loading: false };
        });
        return msg;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },
    
    async deleteConversation(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        await chatbotService.deleteConversation(id);
        update(s => ({ 
          ...s, 
          conversations: s.conversations.filter(c => c.id !== id),
          activeConversation: s.activeConversation?.id === id ? null : s.activeConversation,
          loading: false 
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },
    
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const chatbotStore = createChatbotStore();
export const chatbotConversations = derived(chatbotStore, $s => $s.conversations);
export const activeChatbotConversation = derived(chatbotStore, $s => $s.activeConversation);
export const chatbotLoading = derived(chatbotStore, $s => $s.loading);
