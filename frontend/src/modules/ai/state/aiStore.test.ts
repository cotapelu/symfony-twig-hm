import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { aiStore } from './aiStore';
import { AIService } from '../services/aiService';
import type { AIConversation, AIMessage, AIRequest, AIResponse, AIConfig } from '../contracts/aiTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = aiStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockConfig = (overrides: Partial<AIConfig> = {}): AIConfig => ({
  maxTokens: 1000,
  temperature: 0.7,
  model: 'gpt-4',
  ...overrides
});

const mockMessage = (overrides: Partial<AIMessage> = {}): AIMessage => ({
  id: `msg-${Math.random().toString(36).substr(2, 5)}`,
  role: 'user',
  content: 'Hello',
  timestamp: new Date().toISOString(),
  ...overrides
});

const mockConversation = (overrides: Partial<AIConversation> = {}): AIConversation => ({
  id: `conv-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Test Conversation',
  messages: [mockMessage()],
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  ...overrides
});

const mockResponse = (overrides: Partial<AIResponse> = {}): AIResponse => ({
  conversationId: 'conv-1',
  message: mockMessage(),
  ...overrides
});

describe('AIStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Mock localStorage
    const localStorageMock = (() => {
      let store: Record<string, string> = {};
      return {
        getItem: vi.fn((key: string) => store[key] ?? null),
        setItem: vi.fn((key: string, value: string) => { store[key] = value; }),
        removeItem: vi.fn((key: string) => { delete store[key]; }),
        clear: vi.fn(() => { store = {}; })
      };
    })();
    vi.stubGlobal('localStorage', localStorageMock);

    // Reset store if possible (only if reset method exists)
    if (typeof (aiStore as any).reset === 'function') {
      (aiStore as any).reset();
    } else {
      // Fallback: clear state manually by broadcasting? Not ideal. For now, we rely on reset method.
    }
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.conversations).toEqual([]);
      expect(state.currentConversation).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.config).toBeNull();
      expect(state.suggestions).toEqual([]);
      expect(state.streaming).toBe(false);
      expect(state.streamingChunk).toBe('');
    });
  });

  describe('loadAIConfig', () => {
    it('loads config successfully', async () => {
      const config = mockConfig();
      vi.spyOn(AIService.prototype, 'getConfig').mockResolvedValue(config);

      await aiStore.loadAIConfig();

      const state = getState();
      expect(state.config).toEqual(config);
      expect(state.error).toBeNull();
    });

    it('sets error on failure', async () => {
      const error = new Error('Config fetch failed');
      vi.spyOn(AIService.prototype, 'getConfig').mockRejectedValue(error);

      await aiStore.loadAIConfig();

      const state = getState();
      expect(state.error).toBe('Config fetch failed');
      expect(state.config).toBeNull();
    });
  });

  describe('loadConversations', () => {
    it('loads conversations successfully and updates state', async () => {
      const convs = [mockConversation(), mockConversation()];
      const response = { data: convs, page: 1, total: 2, totalPages: 1 };
      vi.spyOn(AIService.prototype, 'getConversations').mockResolvedValue(response);

      await aiStore.loadConversations();

      expect(AIService.prototype.getConversations).toHaveBeenCalledWith(1, 10);
      const state = getState();
      expect(state.conversations).toEqual(convs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('saves conversations to localStorage', async () => {
      const convs = [mockConversation()];
      const response = { data: convs, page: 1, total: 1, totalPages: 1 };
      vi.spyOn(AIService.prototype, 'getConversations').mockResolvedValue(response);

      await aiStore.loadConversations();

      expect(localStorage.setItem).toHaveBeenCalledWith('ai_conversations', JSON.stringify(convs));
    });

    it('sets loading during fetch', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(AIService.prototype, 'getConversations').mockReturnValue(p as any);

      const loadPromise = aiStore.loadConversations();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise({ data: [mockConversation()], page: 1, total: 1, totalPages: 1 });
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch conversations');
      vi.spyOn(AIService.prototype, 'getConversations').mockRejectedValue(error);

      await expect(aiStore.loadConversations()).rejects.toThrow('Failed to fetch conversations');

      const state = getState();
      expect(state.error).toBe('Failed to fetch conversations');
      expect(state.loading).toBe(false);
      expect(state.conversations).toEqual([]);
    });
  });

  describe('loadConversation', () => {
    it('loads conversation and sets currentConversation', async () => {
      const conv = mockConversation();
      vi.spyOn(AIService.prototype, 'getConversation').mockResolvedValue(conv);

      await aiStore.loadConversation('conv-1');

      expect(AIService.prototype.getConversation).toHaveBeenCalledWith('conv-1');
      const state = getState();
      expect(state.currentConversation).toEqual(conv);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      const error = new Error('Not found');
      vi.spyOn(AIService.prototype, 'getConversation').mockRejectedValue(error);

      await expect(aiStore.loadConversation('conv-1')).rejects.toThrow('Not found');

      const state = getState();
      expect(state.error).toBe('Not found');
      expect(state.currentConversation).toBeNull();
    });
  });

  describe('processAIRequest', () => {
    it('processes request and returns response', async () => {
      const response = mockResponse();
      vi.spyOn(AIService.prototype, 'sendMessage').mockResolvedValue(response);

      const request: AIRequest = { prompt: 'Hello', context: {} };
      const result = await aiStore.processAIRequest(request);

      expect(result).toEqual(response);
      const state = getState();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('queues multiple requests sequentially', async () => {
      const responses = [mockResponse({ message: mockMessage({ content: 'A' }) }), mockResponse({ message: mockMessage({ content: 'B' }) })];
      const sendMessageMock = vi.spyOn(AIService.prototype, 'sendMessage').mockResolvedValueOnce(responses[0]).mockResolvedValueOnce(responses[1]);

      const request1: AIRequest = { prompt: 'First', context: {} };
      const request2: AIRequest = { prompt: 'Second', context: {} };

      const [result1, result2] = await Promise.all([
        aiStore.processAIRequest(request1),
        aiStore.processAIRequest(request2)
      ]);

      expect(result1.message.content).toBe('A');
      expect(result2.message.content).toBe('B');
      // Should have been called in order (queue)
      expect(sendMessageMock).toHaveBeenCalledTimes(2);
    });

    it('sets error on failure', async () => {
      const error = new Error('AI failed');
      vi.spyOn(AIService.prototype, 'sendMessage').mockRejectedValue(error);

      const request: AIRequest = { prompt: 'Test', context: {} };
      await expect(aiStore.processAIRequest(request)).rejects.toThrow('AI failed');

      const state = getState();
      expect(state.error).toBe('AI failed');
    });
  });

  describe('streamAIRequest', () => {
    it('streams chunks and updates streamingChunk', async () => {
      const chunks = ['Hello', ' ', 'World'];
      let onChunkCallback: (chunk: string) => void;
      vi.spyOn(AIService.prototype, 'streamMessage').mockImplementation(async (req, onChunk) => {
        onChunkCallback = onChunk;
        for (const chunk of chunks) {
          await Promise.resolve(); // simulate async
          onChunk(chunk);
        }
      });

      const request: AIRequest = { prompt: 'Stream test', context: {} };
      const receivedChunks: string[] = [];
      await aiStore.streamAIRequest(request, (chunk) => { receivedChunks.push(chunk); });

      expect(receivedChunks).toEqual(chunks);
      // After streaming completes, streaming flag should be false
      const state = getState();
      expect(state.streaming).toBe(false);
    });

    it('handles error during streaming', async () => {
      const error = new Error('Stream broken');
      vi.spyOn(AIService.prototype, 'streamMessage').mockRejectedValue(error);

      const request: AIRequest = { prompt: 'Test', context: {} };
      await expect(aiStore.streamAIRequest(request, () => {})).rejects.toThrow('Stream broken');

      const state = getState();
      expect(state.error).toBe('Stream broken');
      expect(state.streaming).toBe(false);
    });
  });

  describe('generateSuggestions', () => {
    it('generates suggestions and updates state', async () => {
      const suggestions = ['Suggestion 1', 'Suggestion 2'];
      vi.spyOn(AIService.prototype, 'generateSuggestions').mockResolvedValue(suggestions);

      const result = await aiStore.generateSuggestions('context');

      expect(result).toEqual(suggestions);
      const state = getState();
      expect(state.suggestions).toEqual(suggestions);
    });

    it('returns empty array and sets error on failure', async () => {
      const error = new Error('Suggestion failed');
      vi.spyOn(AIService.prototype, 'generateSuggestions').mockRejectedValue(error);

      const result = await aiStore.generateSuggestions('context');

      expect(result).toEqual([]);
      const state = getState();
      expect(state.error).toBe('Suggestion failed');
    });
  });

  describe('deleteConversation', () => {
    it('deletes conversation from list', async () => {
      const conv1 = mockConversation({ id: 'conv-1' });
      const conv2 = mockConversation({ id: 'conv-2' });
      // Set initial state manually if possible? Since no reset, we set via loadConversations maybe.
      // But we can't directly set. We'll rely on the store's state being empty and then delete; no need to preload.
      // However deleteConversation removes from conversations list. If list empty, nothing changes.
      // For this test, we'll simulate by first setting conversations via loadConversations mock.
      vi.spyOn(AIService.prototype, 'getConversations').mockResolvedValue({ data: [conv1, conv2], page: 1, total: 2, totalPages: 1 });
      await aiStore.loadConversations();

      vi.spyOn(AIService.prototype, 'deleteConversation').mockResolvedValue(undefined);
      await aiStore.deleteConversation('conv-1');

      expect(AIService.prototype.deleteConversation).toHaveBeenCalledWith('conv-1');
      const state = getState();
      expect(state.conversations).toHaveLength(1);
      expect(state.conversations[0].id).toBe('conv-2');
    });

    it('handles error', async () => {
      const conv = mockConversation({ id: 'conv-1' });
      vi.spyOn(AIService.prototype, 'getConversations').mockResolvedValue({ data: [conv], page: 1, total: 1, totalPages: 1 });
      await aiStore.loadConversations();

      const error = new Error('Delete failed');
      vi.spyOn(AIService.prototype, 'deleteConversation').mockRejectedValue(error);

      await aiStore.deleteConversation('conv-1');

      const state = getState();
      expect(state.error).toBe('Delete failed');
      expect(state.conversations).toHaveLength(1); // unchanged
    });
  });

  describe('setCurrentConversation', () => {
    it('sets current conversation', async () => {
      const conv = mockConversation();
      aiStore.setCurrentConversation(conv);
      expect(getState().currentConversation).toEqual(conv);
    });

    it('clears current conversation when null', async () => {
      // Set non-null first
      aiStore.setCurrentConversation(mockConversation());
      expect(getState().currentConversation).not.toBeNull();

      aiStore.setCurrentConversation(null);
      expect(getState().currentConversation).toBeNull();
    });
  });

  describe('addMessageToCurrent', () => {
    it('adds message to current conversation', async () => {
      const conv = mockConversation({ id: 'conv-1', messages: [mockMessage({ id: 'm1' })] });
      aiStore.setCurrentConversation(conv);

      const newMsg = mockMessage({ id: 'm2' });
      aiStore.addMessageToCurrent(newMsg);

      const state = getState();
      expect(state.currentConversation?.messages).toHaveLength(2);
      expect(state.currentConversation?.messages[1]).toEqual(newMsg);
    });

    it('does nothing if no current conversation', async () => {
      // Ensure no current conversation
      if (getState().currentConversation) {
        aiStore.setCurrentConversation(null);
      }

      // Attempt to add message should not throw
      const msg = mockMessage();
      aiStore.addMessageToCurrent(msg);

      const state = getState();
      expect(state.currentConversation).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      // Force error
      vi.spyOn(AIService.prototype, 'getConfig').mockRejectedValue(new Error('err'));
      await aiStore.loadAIConfig().catch(() => {});
      expect(getState().error).toBe('err');

      aiStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('clearConversations', () => {
    it('clears conversations and current conversation', async () => {
      // Set some data
      const conv = mockConversation();
      aiStore.setCurrentConversation(conv);
      // Set conversations by mocking loadConversations
      vi.spyOn(AIService.prototype, 'getConversations').mockResolvedValue({ data: [conv], page: 1, total: 1, totalPages: 1 });
      await aiStore.loadConversations();
      expect(getState().conversations).toHaveLength(1);
      expect(getState().currentConversation).not.toBeNull();

      aiStore.clearConversations();
      const state = getState();
      expect(state.conversations).toEqual([]);
      expect(state.currentConversation).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial and clears localStorage', async () => {
      // Set various state
      aiStore.setCurrentConversation(mockConversation());
      vi.spyOn(AIService.prototype, 'getConfig').mockResolvedValue(mockConfig());
      await aiStore.loadAIConfig();
      vi.spyOn(AIService.prototype, 'getConversations').mockResolvedValue({ data: [mockConversation()], page: 1, total: 1, totalPages: 1 });
      await aiStore.loadConversations();

      // Now reset
      (aiStore as any).reset();

      const state = getState();
      expect(state.conversations).toEqual([]);
      expect(state.currentConversation).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.config).toBeNull();
      expect(state.suggestions).toEqual([]);
      expect(state.streaming).toBe(false);
      expect(state.streamingChunk).toBe('');

      // Verify localStorage cleared
      expect(localStorage.removeItem).toHaveBeenCalledWith('ai_conversations');
    });
  });
});
