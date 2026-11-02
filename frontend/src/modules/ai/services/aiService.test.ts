import { describe, it, expect, vi, beforeEach } from 'vitest';
import type { AIConfig, AIRequest, AIResponse, GovernmentQuery } from '../contracts/aiTypes';

// Mock SvelteKit environment
vi.mock('$app/environment', () => ({ browser: true }));

// Mock authStore
let mockAuthState = { token: null };
vi.mock('../../auth/state/auth-store', () => ({
  authStore: {
    subscribe: vi.fn().mockImplementation(cb => {
      cb(mockAuthState);
      return () => {};
    })
  }
}));

global.fetch = vi.fn();

// Import after mocks
import { AIService } from './aiService';
import { __testing__ } from './aiService';

describe('AIService', () => {
  let service: AIService;

  beforeEach(async () => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new AIService();
    mockAuthState = { token: null };
  });

  describe('getConfig', () => {
    it('should fetch and cache config', async () => {
      const config: AIConfig = {
        defaultModel: 'gpt-4',
        availableModels: ['gpt-3.5', 'gpt-4'],
        maxTokens: 4096,
        temperature: 0.7,
        streamResponses: true
      };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(config) });

      const first = await service.getConfig();
      const second = await service.getConfig();

      expect(first).toEqual(config);
      expect(second).toEqual(config);
      expect(global.fetch).toHaveBeenCalledTimes(1);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/ai/config'), expect.any(Object));
    });

    it('should throw on HTTP error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.getConfig()).rejects.toThrow('Failed to fetch AI config');
    });
  });

  describe('getConversations', () => {
    it('should fetch with default pagination', async () => {
      const data = { data: [{ id: 'c1', title: 'Conv', messages: [], createdAt: 'now' }], total: 1, page: 1, limit: 10 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(data) });

      const result = await service.getConversations();

      expect(result).toEqual(data);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/ai/conversations?page=1&limit=10'), expect.any(Object));
    });

    it('should fetch with custom pagination', async () => {
      const data = { data: [], total: 0, page: 5, limit: 20 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(data) });

      const result = await service.getConversations(5, 20);

      expect(result).toEqual(data);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/ai/conversations?page=5&limit=20'), expect.any(Object));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.getConversations()).rejects.toThrow('Failed to fetch conversations');
    });
  });

  describe('getConversation', () => {
    it('should fetch a specific conversation', async () => {
      const conversation = { id: 'c1', title: 'Test', messages: [], createdAt: 'now' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(conversation) });

      const result = await service.getConversation('c1');

      expect(result).toEqual(conversation);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/ai/conversations/c1'), expect.any(Object));
    });

    it('should throw when not found', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getConversation('nonexistent')).rejects.toThrow('Failed to fetch conversation');
    });
  });

  describe('createConversation', () => {
    it('should create with title', async () => {
      const conv = { id: 'c1', title: 'New', messages: [], createdAt: 'now' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(conv) });

      const result = await service.createConversation('New');

      expect(result).toEqual(conv);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/ai/conversations'), expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"title":"New"')
      }));
    });

    it('should create without title', async () => {
      const conv = { id: 'c2', title: '', messages: [], createdAt: 'now' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(conv) });

      const result = await service.createConversation();

      expect(result).toEqual(conv);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createConversation('')).rejects.toThrow('Failed to create conversation');
    });
  });

  describe('deleteConversation', () => {
    it('should delete successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.deleteConversation('c1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/ai/conversations/c1'), expect.objectContaining({ method: 'DELETE' }));
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.deleteConversation('c1')).rejects.toThrow('Failed to delete conversation');
    });
  });

  describe('sendMessage', () => {
    const validRequest: AIRequest = { message: 'Hello', model: 'gpt-4' };
    const validResponse: AIResponse = { id: 'r1', content: 'Hi', model: 'gpt-4' };

    it('should send message successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(validResponse) });

      const result = await service.sendMessage(validRequest);

      expect(result).toEqual(validResponse);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/ai/chat'), expect.objectContaining({
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      }));
    });

    it('should throw if message is empty', async () => {
      await expect(service.sendMessage({ ...validRequest, message: '' })).rejects.toThrow('Message must be between 1 and 10,000 characters');
    });

    it('should throw if message too long', async () => {
      const long = 'a'.repeat(10001);
      await expect(service.sendMessage({ ...validRequest, message: long })).rejects.toThrow('Message must be between 1 and 10,000 characters');
    });

    it('should throw if temperature invalid', async () => {
      await expect(service.sendMessage({ ...validRequest, temperature: -1 })).rejects.toThrow('Temperature must be between 0 and 2');
    });

    it('should throw if maxTokens invalid', async () => {
      await expect(service.sendMessage({ ...validRequest, maxTokens: 0 })).rejects.toThrow('Max tokens must be between 1 and 32,768');
    });

    it('should throw if context item invalid', async () => {
      await expect(service.sendMessage({ ...validRequest, context: ['valid', 'x'.repeat(101)] })).rejects.toThrow('Invalid context item');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400, json: vi.fn().mockResolvedValue({}) });

      await expect(service.sendMessage(validRequest)).rejects.toThrow('AI request failed');
    });

    it('should throw on invalid response format', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue({ id: 123 }) });

      await expect(service.sendMessage(validRequest)).rejects.toThrow('Invalid AI response format');
    });
  });

  describe('streamMessage', () => {
    const validRequest: AIRequest = { message: 'Hello stream' };

    it('should throw if message empty', async () => {
      const onChunk = vi.fn();
      await expect(service.streamMessage({ ...validRequest, message: '' }, onChunk)).rejects.toThrow('Message must be between 1 and 10,000 characters');
    });

    it('should stream response successfully', async () => {
      const onChunk = vi.fn();
      const encoder = new TextEncoder();
      const chunkData = 'data: {"content":"Hello"}\n\ndata: [DONE]\n\n';
      const stream = new ReadableStream({
        start(controller) {
          controller.enqueue(encoder.encode(chunkData));
          controller.close();
        }
      });
      (global.fetch as any).mockResolvedValue({ ok: true, body: stream });

      await service.streamMessage(validRequest, onChunk);

      expect(onChunk).toHaveBeenCalledWith('Hello');
    });

    it('should throw on API error', async () => {
      const onChunk = vi.fn();
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.streamMessage(validRequest, onChunk)).rejects.toThrow('Streaming request failed');
    });
  });

  describe('generateSuggestions', () => {
    it('should generate suggestions', async () => {
      const suggestions = ['Suggestion 1', 'Suggestion 2'];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue({ suggestions }) });

      const result = await service.generateSuggestions('ctx');

      expect(result).toEqual(suggestions);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/ai/suggestions'), expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"context":"ctx"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.generateSuggestions('ctx')).rejects.toThrow('Failed to generate suggestions');
    });
  });

  describe('transcribeAudio', () => {
    it('should transcribe audio', async () => {
      const fakeBlob = new Blob(['audio'], { type: 'audio/wav' });
      const text = 'Transcribed text';
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue({ text }) });

      const result = await service.transcribeAudio(fakeBlob);

      expect(result).toBe(text);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/ai/transcribe'), expect.objectContaining({
        method: 'POST',
        body: expect.any(FormData)
      }));
    });

    it('should throw on API error', async () => {
      const fakeBlob = new Blob(['audio'], { type: 'audio/wav' });
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.transcribeAudio(fakeBlob)).rejects.toThrow('Transcription failed');
    });
  });

  describe('askGovernmentBot', () => {
    const context: GovernmentQuery = { context: 'citizen_info', wardCode: 'W001', districtCode: 'D001', provinceCode: 'P001' };
    const response = { content: 'Answer' };

    it('should require authentication', async () => {
      mockAuthState = { token: null };
      await expect(service.askGovernmentBot('query', context)).rejects.toThrow('User not authenticated');
    });

    it('should throw if query empty', async () => {
      mockAuthState = { token: 'test' };
      await expect(service.askGovernmentBot('', context)).rejects.toThrow('Query must be between 1 and 10,000 characters');
    });

    it('should throw if query too long', async () => {
      mockAuthState = { token: 'test' };
      const long = 'a'.repeat(10001);
      await expect(service.askGovernmentBot(long, context)).rejects.toThrow('Query must be between 1 and 10,000 characters');
    });

    it('should throw if wardCode invalid', async () => {
      mockAuthState = { token: 'test' };
      await expect(service.askGovernmentBot('q', { ...context, wardCode: 'INVALID!' })).rejects.toThrow('Invalid ward code');
    });

    it('should call API successfully when authenticated', async () => {
      mockAuthState = { token: 'test' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(response) });

      const result = await service.askGovernmentBot('query', context);

      expect(result).toEqual(response);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/ai/government/chat'), expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"query":"query"')
      }));
    });

    it('should throw on API error', async () => {
      mockAuthState = { token: 'test' };
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400, json: vi.fn().mockResolvedValue({}) });

      await expect(service.askGovernmentBot('query', context)).rejects.toThrow('Government bot request failed');
    });
  });

  describe('streamGovernmentBot', () => {
    const context: GovernmentQuery = { context: 'general' };

    it('should require authentication', async () => {
      mockAuthState = { token: null };
      await expect(service.streamGovernmentBot('query', context, vi.fn())).rejects.toThrow('User not authenticated');
    });

    it('should stream response', async () => {
      mockAuthState = { token: 'test' };
      const onChunk = vi.fn();
      const encoder = new TextEncoder();
      const chunkData = 'data: {"content":"Step1"}\n\ndata: [DONE]\n\n';
      const stream = new ReadableStream({
        start(controller) {
          controller.enqueue(encoder.encode(chunkData));
          controller.close();
        }
      });
      (global.fetch as any).mockResolvedValue({ ok: true, body: stream });

      await service.streamGovernmentBot('query', context, onChunk);

      expect(onChunk).toHaveBeenCalledWith('Step1');
    });

    it('should throw on API error', async () => {
      mockAuthState = { token: 'test' };
      const onChunk = vi.fn();
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.streamGovernmentBot('query', context, onChunk)).rejects.toThrow('Streaming request failed');
    });
  });

  describe('getCitizenInfo', () => {
    const cccd = '123456789012';
    const citizenData = { name: 'John', dob: '1990-01-01', gender: 'M', address: '123 St', ward: 'W1', district: 'D1', province: 'P1' };

    it('should require auth', async () => {
      mockAuthState = { token: null };
      await expect(service.getCitizenInfo(cccd)).rejects.toThrow('User not authenticated');
    });

    it('should reject invalid CCCD', async () => {
      mockAuthState = { token: 'test' };
      await expect(service.getCitizenInfo('123')).rejects.toThrow('Invalid CCCD format. Must be 12 digits.');
    });

    it('should fetch citizen info', async () => {
      mockAuthState = { token: 'test' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(citizenData) });

      const result = await service.getCitizenInfo(cccd);

      expect(result).toEqual(citizenData);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining(`/api/ai/government/citizen/${cccd}`), expect.any(Object));
    });

    it('should throw on API error', async () => {
      mockAuthState = { token: 'test' };
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });
      await expect(service.getCitizenInfo(cccd)).rejects.toThrow('Failed to fetch citizen info');
    });
  });

  describe('getProcedureRequirements', () => {
    it('should fetch procedure requirements', async () => {
      const procCode = 'CODE1';
      const procData = {
        procedure: 'Proc1',
        requirements: ['Req1'],
        fee: 100,
        processingTime: '1d',
        steps: [{ step: 1, description: 'Do', documents: ['Doc1'], fee: 0, processingTime: '' }]
      };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(procData) });

      const result = await service.getProcedureRequirements(procCode);

      expect(result).toEqual(procData);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining(`/api/ai/government/procedure/${procCode}`), expect.any(Object));
    });

    it('should validate procedure code format', async () => {
      await expect(service.getProcedureRequirements('')).rejects.toThrow('Procedure code is required');
      await expect(service.getProcedureRequirements('a'.repeat(51))).rejects.toThrow('Invalid procedure code format');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });
      await expect(service.getProcedureRequirements('CODE')).rejects.toThrow('Failed to fetch procedure requirements');
    });
  });

  describe('checkDocumentStatus', () => {
    const appId = 'APP-001';
    const statusData = { status: 'completed', currentStep: 'Done', estimatedTime: '1d', documents: [{ name: 'Doc1', status: 'ok' }] };

    it('should reject invalid applicationId format', async () => {
      await expect(service.checkDocumentStatus('invalid!')).rejects.toThrow('Invalid application ID format');
    });

    it('should fetch document status', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(statusData) });

      const result = await service.checkDocumentStatus(appId);

      expect(result).toEqual(statusData);
      expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining(`/api/ai/government/application/${appId}/status`), expect.any(Object));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });
      await expect(service.checkDocumentStatus(appId)).rejects.toThrow('Failed to check document status');
    });
  });
});
