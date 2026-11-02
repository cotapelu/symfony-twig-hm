import { writable } from 'svelte/store';
import type { AIConversation, AIMessage, AIRequest, AIResponse, AIConfig, AIState } from '../contracts/aiTypes';
import { AIService } from '../services/aiService';
import { browser } from '$app/environment';

const STORAGE_KEY = 'ai_conversations';

// Shared service instance
const aiService = new AIService();

// Load conversations from localStorage if available
const loadConversationsFromStorage = (): AIConversation[] => {
  if (!browser) return [];
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : [];
  } catch (error) {
    console.warn('Failed to load conversations from storage:', error);
    return [];
  }
};

// Save conversations to localStorage
const saveConversationsToStorage = (conversations: AIConversation[]): void => {
  if (!browser) return;
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(conversations));
  } catch (error) {
    console.warn('Failed to save conversations to storage:', error);
  }
};

// Operation locking to prevent concurrent requests
let isProcessingRequest = false;
const requestQueue: Array<{
  resolve: (value: AIResponse | PromiseLike<AIResponse>) => void;
  reject: (reason?: any) => void;
  request: AIRequest;
}> = [];

const processRequestQueue = async () => {
  if (isProcessingRequest || requestQueue.length === 0) return;
  
  isProcessingRequest = true;
  const queueItem = requestQueue.shift()!;
  
  try {
    // Process the request directly; the queue ensures only valid AIRequest objects are enqueued.
    queueItem.resolve(await aiService.sendMessage(queueItem.request));
  } catch (error) {
    queueItem.reject(error);
  } finally {
    isProcessingRequest = false;
    // Process next item in queue
    setTimeout(processRequestQueue, 0);
  }
};

const initialState: AIState = {
  conversations: loadConversationsFromStorage(),
  currentConversation: null,
  loading: false,
  error: null,
  config: null,
  suggestions: [],
  streaming: false,
  streamingChunk: ''
};

function createAIStore() {
  const { subscribe, set, update } = writable<AIState>(initialState);

  return {
    subscribe,

    async loadAIConfig() {
      try {
        const config = await aiService.getConfig();
        update(state => ({ ...state, config }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load AI config'
        }));
      }
    },

    async loadConversations(page: number = 1, limit: number = 10) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const response = await aiService.getConversations(page, limit);
        update(state => ({ ...state, conversations: response.data, loading: false }));
        // Save to localStorage
        saveConversationsToStorage(response.data);
        // Return pagination info if needed
        return response;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load conversations',
          loading: false
        }));
        throw error;
      }
    },

    async loadConversation(id: string) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const conversation = await aiService.getConversation(id);
        update(state => ({ ...state, currentConversation: conversation, loading: false }));
        return conversation;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load conversation',
          loading: false
        }));
        throw error;
      }
    },

    async processAIRequest(request: AIRequest): Promise<AIResponse> {
      update(state => ({ ...state, loading: true, error: null }));
      return new Promise((resolve, reject) => {
        requestQueue.push({ resolve, reject, request });
        processRequestQueue();
      }).then((response) => {
        update(state => ({ ...state, loading: false }));
        return response;
      }).catch((error) => {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'AI request failed',
          loading: false
        }));
        throw error;
      });
    },

    async streamAIRequest(request: AIRequest, onChunk: (chunk: string) => void): Promise<void> {
      update(state => ({ ...state, streaming: true, streamingChunk: '' }));
      try {
        await aiService.streamMessage(request, (chunk) => {
          update(state => ({ ...state, streamingChunk: state.streamingChunk + chunk }));
          onChunk(chunk);
        });
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Streaming failed',
          streaming: false
        }));
        throw error;
      } finally {
        update(state => ({ ...state, streaming: false }));
      }
    },

    async generateSuggestions(context: string): Promise<string[]> {
      try {
        const suggestions = await aiService.generateSuggestions(context);
        update(state => ({ ...state, suggestions }));
        return suggestions;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to generate suggestions'
        }));
        return [];
      }
    },

    async deleteConversation(id: string) {
      try {
        await aiService.deleteConversation(id);
        update(state => ({
          ...state,
          conversations: state.conversations.filter(c => c.id !== id)
        }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to delete conversation'
        }));
      }
    },

    setCurrentConversation(conversation: AIConversation | null) {
      update(state => ({ ...state, currentConversation: conversation }));
    },

    addMessageToCurrent(message: AIMessage) {
      update(state => {
        if (!state.currentConversation) return state;
        return {
          ...state,
          currentConversation: {
            ...state.currentConversation,
            messages: [...state.currentConversation.messages, message]
          }
        };
      });
    },

    clearError() {
      update(state => ({ ...state, error: null }));
    },

    clearConversations() {
      update(state => ({ ...state, conversations: [], currentConversation: null }));
    },

    reset() {
      // Reset all state to initial values
      set(initialState);
      // Also clear localStorage to avoid cross-test contamination
      if (browser) {
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
      }
    }
  };
}

export const aiStore = createAIStore();
export const { loadAIConfig, loadConversations, loadConversation, processAIRequest, clearError } = aiStore;
