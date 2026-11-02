import { writable } from "svelte/store";
import { Python } from "../lib/api";

// Python AI - TypeScript Version
// Smart replies, Sentiment, Translation, Moderation, Summary

interface PythonAIState {
  smartReplies: string[];
  sentimentAnalysis: { sentiment: string; score: number } | null;
  translations: Record<string, { translated: string }>;
  moderationResults: Record<number, any>;
  summary: any;
  loading: boolean;
  error: string | null;
}

function createPythonAIStore() {
  const initialState: PythonAIState = {
    smartReplies: [],
    sentimentAnalysis: null,
    translations: {},
    moderationResults: {},
    summary: null,
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<PythonAIState>(initialState);

  return {
    subscribe,

    getSmartReplies: async (conversationContext: any): Promise<string[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const replies = await Python.getSmartReplies(conversationContext);
        update((prev) => ({ ...prev, smartReplies: replies, loading: false }));
        return replies;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get smart replies";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    analyzeSentiment: async (
      text: string,
    ): Promise<{ sentiment: string; score: number }> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const sentiment = await Python.analyzeSentiment(text);
        update((prev) => ({
          ...prev,
          sentimentAnalysis: sentiment,
          loading: false,
        }));
        return sentiment;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to analyze sentiment";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    translateText: async (
      text: string,
      sourceLang: string,
      targetLang: string,
    ): Promise<{ translated: string }> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const translation = await Python.translateText(
          text,
          sourceLang,
          targetLang,
        );
        update((prev) => ({
          ...prev,
          translations: {
            ...prev.translations,
            [`${text}_${targetLang}`]: translation,
          },
          loading: false,
        }));
        return translation;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to translate text";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    moderateContent: async (content: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const moderation = await Python.moderateContent(content);
        update((prev) => ({
          ...prev,
          moderationResults: {
            ...prev.moderationResults,
            [Date.now()]: moderation,
          },
          loading: false,
        }));
        return moderation;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to moderate content";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    generateSummary: async (conversationId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const summary = await Python.generateSummary(conversationId);
        update((prev) => ({ ...prev, summary, loading: false }));
        return summary;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to generate summary";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        smartReplies: [],
        sentimentAnalysis: null,
        translations: {},
        moderationResults: {},
        summary: null,
        loading: false,
        error: null,
      });
    },
  };
}

// Python Ecommerce - TypeScript Version
// Recommendations, Products, Cart

interface PythonEcommerceState {
  recommendations: any[];
  trendingProducts: any[];
  searchResults: any[];
  cart: any[];
  productDetails: Record<string, any>;
  loading: boolean;
  error: string | null;
}

function createPythonEcommerceStore() {
  const initialState: PythonEcommerceState = {
    recommendations: [],
    trendingProducts: [],
    searchResults: [],
    cart: [],
    productDetails: {},
    loading: false,
    error: null,
  };

  const { subscribe, set, update } =
    writable<PythonEcommerceState>(initialState);

  return {
    subscribe,

    getRecommendations: async (userId: string): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const products = await Python.getAIRecommendations(userId);
        update((prev) => ({
          ...prev,
          recommendations: products,
          loading: false,
        }));
        return products;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get recommendations";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getTrendingProducts: async (limit = 10): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const products = await Python.getTrendingProducts(limit);
        update((prev) => ({
          ...prev,
          trendingProducts: products,
          loading: false,
        }));
        return products;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get trending products";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    searchProducts: async (query: string): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const products = await Python.searchProducts(query);
        update((prev) => ({
          ...prev,
          searchResults: products,
          loading: false,
        }));
        return products;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to search products";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getProductDetails: async (productId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const product = await Python.getProductDetails(productId);
        update((prev) => ({
          ...prev,
          productDetails: { ...prev.productDetails, [productId]: product },
          loading: false,
        }));
        return product;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get product details";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    addToCart: async (productId: string, quantity = 1): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const cartItem = await Python.addToCart({ productId, quantity });
        update((prev) => ({
          ...prev,
          cart: [...prev.cart, cartItem],
          loading: false,
        }));
        return cartItem;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to add to cart";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getCart: async (): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const cart = await Python.getCart();
        update((prev) => ({ ...prev, cart, loading: false }));
        return cart;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get cart";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        recommendations: [],
        trendingProducts: [],
        searchResults: [],
        cart: [],
        productDetails: {},
        loading: false,
        error: null,
      });
    },
  };
}

// Python SearchML, BotAutomation, Analytics - TypeScript Version

interface PythonSearchState {
  searchResults: any[];
  suggestions: any[];
  loading: boolean;
  error: string | null;
}

function createPythonSearchStore() {
  const initialState: PythonSearchState = {
    searchResults: [],
    suggestions: [],
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<PythonSearchState>(initialState);

  return {
    subscribe,

    search: async (
      query: string,
      filters: Record<string, any> = {},
    ): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const results = await Python.search(query, filters);
        update((prev) => ({ ...prev, searchResults: results, loading: false }));
        return results;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Search failed";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getSuggestions: async (partialQuery: string): Promise<any[]> => {
      try {
        const suggestions = await Python.getSuggestions(partialQuery);
        update((prev) => ({ ...prev, suggestions }));
        return suggestions;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get suggestions";
        update((state) => ({ ...state, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        searchResults: [],
        suggestions: [],
        loading: false,
        error: null,
      });
    },
  };
}

interface PythonBotState {
  bots: any[];
  activeBot: any | null;
  chatHistory: Record<string, Array<{ message: string; response: string }>>;
  loading: boolean;
  error: string | null;
}

function createPythonBotStore() {
  const initialState: PythonBotState = {
    bots: [],
    activeBot: null,
    chatHistory: {},
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<PythonBotState>(initialState);

  return {
    subscribe,

    createBot: async (data: any): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const bot = await Python.createBot(data);
        update((prev) => ({
          ...prev,
          bots: [...prev.bots, bot],
          loading: false,
        }));
        return bot;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to create bot";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getBots: async (): Promise<any[]> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const bots = await Python.getBots();
        update((prev) => ({ ...prev, bots, loading: false }));
        return bots;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get bots";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getBotResponse: async (botId: string, message: string): Promise<string> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const responseData = await Python.getBotResponse(botId, message);
        const responseStr =
          typeof responseData === "string"
            ? responseData
            : responseData.response;
        update((prev) => ({
          ...prev,
          chatHistory: {
            ...prev.chatHistory,
            [botId]: [
              ...(prev.chatHistory[botId] || []),
              { message, response: responseStr },
            ],
          },
          loading: false,
        }));
        return responseStr;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get bot response";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    toggleBot: async (botId: string, isActive: boolean): Promise<void> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        await Python.toggleBot(botId, isActive);
        update((prev) => ({
          ...prev,
          bots: prev.bots.map((bot) =>
            bot.id === botId ? { ...bot, isActive } : bot,
          ),
          loading: false,
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to toggle bot";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    deleteBot: async (botId: string): Promise<void> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        await Python.deleteBot(botId);
        update((prev) => ({
          ...prev,
          bots: prev.bots.filter((bot) => bot.id !== botId),
          loading: false,
        }));
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to delete bot";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        bots: [],
        activeBot: null,
        chatHistory: {},
        loading: false,
        error: null,
      });
    },
  };
}

interface PythonAnalyticsState {
  userAnalytics: any;
  conversationAnalytics: Record<string, any>;
  engagementMetrics: any;
  retentionData: any;
  churnPrediction: any;
  totalConversations: number;
  totalMessages: number;
  loading: boolean;
  error: string | null;
}

function createPythonAnalyticsStore() {
  const initialState: PythonAnalyticsState = {
    userAnalytics: null,
    conversationAnalytics: {},
    engagementMetrics: null,
    retentionData: null,
    churnPrediction: null,
    totalConversations: 0,
    totalMessages: 0,
    loading: false,
    error: null,
  };

  const { subscribe, set, update } =
    writable<PythonAnalyticsState>(initialState);

  return {
    subscribe,

    getUserAnalytics: async (userId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const analytics = await Python.getUserAnalytics(userId);
        update((prev) => ({
          ...prev,
          userAnalytics: analytics,
          loading: false,
        }));
        return analytics;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get user analytics";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getConversationAnalytics: async (conversationId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const analytics = await Python.getConversationAnalytics(conversationId);
        update((prev) => ({
          ...prev,
          conversationAnalytics: {
            ...prev.conversationAnalytics,
            [conversationId]: analytics,
          },
          loading: false,
        }));
        return analytics;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get conversation analytics";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getEngagementMetrics: async (userId: string, days = 7): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const metrics = await Python.getEngagementMetrics(userId, days);
        update((prev) => ({
          ...prev,
          engagementMetrics: metrics,
          loading: false,
        }));
        return metrics;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get engagement metrics";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getRetentionData: async (
      startDate: string,
      endDate: string,
    ): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const data = await Python.getRetentionData(startDate, endDate);
        update((prev) => ({ ...prev, retentionData: data, loading: false }));
        return data;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get retention data";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getChurnPrediction: async (userId: string): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const prediction = await Python.getChurnPrediction(userId);
        update((prev) => ({
          ...prev,
          churnPrediction: prediction,
          loading: false,
        }));
        return prediction;
      } catch (error) {
        const errorMessage =
          error instanceof Error
            ? error.message
            : "Failed to get churn prediction";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    getMetrics: async (): Promise<any> => {
      update((state) => ({ ...state, loading: true, error: null }));
      try {
        const metrics = await Python.getMetrics();
        update((prev) => ({
          ...prev,
          userAnalytics: metrics,
          loading: false,
        }));
        return metrics;
      } catch (error) {
        const errorMessage =
          error instanceof Error ? error.message : "Failed to get metrics";
        update((state) => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },

    clearError: (): void => {
      update((state) => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        userAnalytics: null,
        conversationAnalytics: {},
        engagementMetrics: null,
        retentionData: null,
        churnPrediction: null,
        totalConversations: 0,
        totalMessages: 0,
        loading: false,
        error: null,
      });
    },
  };
}

export const pythonAIStore = createPythonAIStore();
export const pythonEcommerceStore = createPythonEcommerceStore();
export const pythonSearchStore = createPythonSearchStore();
export const pythonBotStore = createPythonBotStore();
export const pythonAnalyticsStore = createPythonAnalyticsStore();

export const {
  getSmartReplies,
  analyzeSentiment,
  translateText,
  moderateContent,
  generateSummary,
  clearError: clearAIError,
} = pythonAIStore;

export const {
  getRecommendations,
  getTrendingProducts,
  searchProducts,
  getProductDetails,
  addToCart,
  getCart,
  clearError: clearEcommerceError,
} = pythonEcommerceStore;

export const {
  search: pythonSearch,
  getSuggestions,
  clearError: clearSearchError,
} = pythonSearchStore;

export const {
  createBot,
  getBots,
  getBotResponse,
  toggleBot,
  deleteBot,
  clearError: clearBotError,
} = pythonBotStore;

export const {
  getUserAnalytics,
  getConversationAnalytics,
  getEngagementMetrics,
  getRetentionData,
  getChurnPrediction,
  getMetrics,
  clearError: clearAnalyticsError,
} = pythonAnalyticsStore;
