import type {
  AIMessage,
  AIConversation,
  AIRequest,
  AIResponse,
  AIConfig,
  AIStreamingChunk,
  GovernmentContext,
  GovernmentQuery,
  ProcedureStep,
  LLMResponse,
} from '../contracts/aiTypes';
import { authStore } from '../../auth/state/auth-store';

// Input validation functions
const validateMessageLength = (message: string): boolean => {
  return message && message.trim().length > 0 && message.length <= 10000; // Max 10k characters
};

const validateCCCDFormat = (cccd: string): boolean => {
  // Vietnamese Citizen ID format: 12 digits
  return /^\d{12}$/.test(cccd);
};

const validateApplicationIdFormat = (applicationId: string): boolean => {
  // Assuming application ID format: alphanumeric with hyphens, max 50 chars
  return /^[a-zA-Z0-9\-]{1,50}$/.test(applicationId);
};

const validateLocationCode = (code: string | undefined): boolean => {
  if (!code) return true; // Optional
  // Assuming location codes are alphanumeric, max 20 chars
  return /^[a-zA-Z0-9]{1,20}$/.test(code);
};

const validateTemperature = (temp: number | undefined): boolean => {
  if (temp === undefined) return true; // Optional
  return !isNaN(temp) && temp >= 0 && temp <= 2; // Typical temperature range for LLMs
};

const validateMaxTokens = (tokens: number | undefined): boolean => {
  if (tokens === undefined) return true; // Optional
  return !isNaN(tokens) && tokens > 0 && tokens <= 32768; // Reasonable max tokens
};

// Response validation functions
const validateAIConfig = (data: any): data is AIConfig => {
  return data &&
    typeof data.defaultModel === 'string' &&
    Array.isArray(data.availableModels) &&
    data.availableModels.every((m: any): m is string => typeof m === 'string') &&
    typeof data.maxTokens === 'number' && data.maxTokens > 0 &&
    typeof data.temperature === 'number' && data.temperature >= 0 && data.temperature <= 2 &&
    (data.apiKey === undefined || typeof data.apiKey === 'string') &&
    (data.endpoint === undefined || typeof data.endpoint === 'string');
};

// Simple logging and metrics
interface LogEntry {
  timestamp?: string;
  level: string;
  message: string;
  service: string;
  method?: string;
  [key: string]: any;
}

const log = ({ level, message, service, method, ...meta }: LogEntry): void => {
  const entry: LogEntry = {
    timestamp: new Date().toISOString(),
    level,
    message,
    service: 'AIService',
    method,
    ...meta,
  };
  console.log(JSON.stringify(entry));
  // In a real app, we might send this to a logging endpoint
};

const metrics = {
  requestCount: 0,
  errorCount: 0,
  totalLatency: 0,
};

const recordMetrics = (latency: number, error: boolean): void => {
  metrics.requestCount++;
  if (error) metrics.errorCount++;
  metrics.totalLatency += latency;
  // Log metrics every 10 requests
  if (metrics.requestCount % 10 === 0) {
    log({
      level: 'info',
      message: 'AI service metrics',
      service: 'AIService',
      metrics: { ...metrics },
    });
  }
};

const validateAIConversation = (data: any): data is AIConversation => {
  return data &&
    typeof data.id === 'string' &&
    typeof data.title === 'string' &&
    Array.isArray(data.messages) &&
    data.messages.every((m: any): m is AIMessage => {
      return m &&
        (m.role === 'user' || m.role === 'assistant' || m.role === 'system') &&
        typeof m.content === 'string' &&
        typeof m.timestamp === 'string' &&
        (m.metadata === undefined || typeof m.metadata === 'object');
    }) &&
    typeof data.createdAt === 'string' &&
    (data.updatedAt === undefined || typeof data.updatedAt === 'string') &&
    (data.model === undefined || typeof data.model === 'string');
};

const validateAIResponse = (data: any): data is AIResponse => {
  return data &&
    typeof data.id === 'string' &&
    typeof data.content === 'string' &&
    typeof data.model === 'string' &&
    (data.usage === undefined || (
      typeof data.usage === 'object' &&
      typeof data.usage.promptTokens === 'number' && data.usage.promptTokens >= 0 &&
      typeof data.usage.completionTokens === 'number' && data.usage.completionTokens >= 0 &&
      typeof data.usage.totalTokens === 'number' && data.usage.totalTokens >= 0
    )) &&
    (data.finishReason === undefined || typeof data.finishReason === 'string');
};

const validateLLMResponse = (data: any): data is LLMResponse => {
  return data &&
    typeof data.content === 'string' &&
    (data.sources === undefined || (
      Array.isArray(data.sources) &&
      data.sources.every((s: any): s is string => typeof s === 'string')
    )) &&
    (data.actions === undefined || (
      Array.isArray(data.actions) &&
      data.actions.every((a: any): a is { label: string; url: string } => {
        return a &&
          typeof a.label === 'string' &&
          typeof a.url === 'string';
      })
    )) &&
    (data.procedureSteps === undefined || (
      Array.isArray(data.procedureSteps) &&
      data.procedureSteps.every((step: any): step is ProcedureStep => {
        return step &&
          typeof step.step === 'number' &&
          typeof step.description === 'string' &&
          Array.isArray(step.documents) &&
          step.documents.every((doc: any): doc is string => typeof doc === 'string') &&
          typeof step.fee === 'number' && step.fee >= 0 &&
          typeof step.processingTime === 'string';
      })
    ));
};

const validateProcedureResponse = (data: any): data is {
  procedure: string;
  requirements: string[];
  fee: number;
  processingTime: string;
  steps: ProcedureStep[];
} => {
  return data &&
    typeof data.procedure === 'string' &&
    Array.isArray(data.requirements) &&
    data.requirements.every((r: any): r is string => typeof r === 'string') &&
    typeof data.fee === 'number' && data.fee >= 0 &&
    typeof data.processingTime === 'string' &&
    Array.isArray(data.steps) &&
    data.steps.every((step: any): step is ProcedureStep => {
      return step &&
        typeof step.step === 'number' &&
        typeof step.description === 'string' &&
        Array.isArray(step.documents) &&
        step.documents.every((doc: any): doc is string => typeof doc === 'string') &&
        typeof step.fee === 'number' && step.fee >= 0 &&
        typeof step.processingTime === 'string';
    });
};

const validateCitizenInfoResponse = (data: any): data is {
  name: string;
  dob: string;
  gender: string;
  address: string;
  ward: string;
  district: string;
  province: string;
} => {
  return data &&
    typeof data.name === 'string' &&
    typeof data.dob === 'string' &&
    typeof data.gender === 'string' &&
    typeof data.address === 'string' &&
    typeof data.ward === 'string' &&
    typeof data.district === 'string' &&
    typeof data.province === 'string';
};

const validateDocumentStatusResponse = (data: any): data is {
  status: 'pending' | 'processing' | 'completed' | 'rejected';
  currentStep: string;
  estimatedTime: string;
  documents: { name: string; status: string }[];
} => {
  return data &&
    (data.status === 'pending' || data.status === 'processing' || data.status === 'completed' || data.status === 'rejected') &&
    typeof data.currentStep === 'string' &&
    typeof data.estimatedTime === 'string' &&
    Array.isArray(data.documents) &&
    data.documents.every((doc: any): doc is { name: string; status: string } => {
      return doc &&
        typeof doc.name === 'string' &&
        typeof doc.status === 'string';
    });
};

// Timeout configuration
const REQUEST_TIMEOUT = 30000; // 30 seconds

// Retry mechanism with exponential backoff and timeout
const fetchWithRetry = async (
  url: string,
  options: RequestInit = {},
  maxRetries = 3
): Promise<Response> => {
  let lastError: Error;
  
  for (let attempt = 0; attempt <= maxRetries; attempt++) {
    // Create abort controller for timeout
    const abortController = new AbortController();
    const timeoutId = setTimeout(() => abortController.abort(), REQUEST_TIMEOUT);
    
    try {
      // Add signal to options
      const opts = { ...options, signal: abortController.signal };
      
      const response = await fetch(url, opts);
      
      // Clear timeout
      clearTimeout(timeoutId);
      
      // If successful or client error (4xx), don't retry
      if (response.ok || (response.status >= 400 && response.status < 500)) {
        return response;
      }
      
      // Server error (5xx) or network error, retry if attempts left
      lastError = new Error(`HTTP ${response.status}: ${response.statusText}`);
      
      // If we've exhausted retries, throw the error
      if (attempt === maxRetries) {
        throw lastError;
      }
      
      // Wait before retrying (exponential backoff: 1s, 2s, 4s)
      await new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt) * 1000));
    } catch (error) {
      // Clear timeout
      clearTimeout(timeoutId);
      
      lastError = error as Error;
      
      // If we've exhausted retries, throw the error
      if (attempt === maxRetries) {
        throw lastError;
      }
      
      // Wait before retrying (exponential backoff: 1s, 2s, 4s)
      await new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt) * 1000));
    }
  }
  
  // This should never be reached, but TypeScript requires it
  throw lastError;
};

// Simple in-memory cache with expiry
interface CacheEntry<T> {
  data: T;
  expiry: number;
}

const cache = new Map<string, CacheEntry<any>>();
const CACHE_TTL = 5 * 60 * 1000; // 5 minutes

const getFromCache = <T>(key: string): T | null => {
  const entry = cache.get(key);
  if (!entry) return null;
  
  if (Date.now() > entry.expiry) {
    cache.delete(key);
    return null;
  }
  
  return entry.data;
};

const setInCache = <T>(key: string, data: T, ttl: number = CACHE_TTL): void => {
  cache.set(key, {
    data,
    expiry: Date.now() + ttl
  });
};

// (GovernmentContext/GovernmentQuery/ProcedureStep/LLMResponse are imported from
// ../contracts/aiTypes above — contracts own the DTOs; the local duplicates that
// made this module's star-export ambiguous were removed.)

export class AIService {
  private baseUrl = '/api/ai';

  private getCurrentUserId(): string | null {
    let token: string | null = null;
    authStore.subscribe(state => {
      token = state.token;
    })();
    return token ? 'authenticated' : null;
  }

  private requireAuth(): void {
    if (!this.getCurrentUserId()) {
      throw new Error('User not authenticated');
    }
  }

  async getConfig(): Promise<AIConfig> {
    const startTime = Date.now();
    try {
      // Create cache key
      const cacheKey = 'ai:config';
      
      // Try to get from cache
      const cached = getFromCache<AIConfig>(cacheKey);
      if (cached) {
        recordMetrics(Date.now() - startTime, false);
        return cached;
      }
      
      // If not in cache, fetch from API
      const response = await fetchWithRetry(`${this.baseUrl}/config`);
      if (!response.ok) throw new Error('Failed to fetch AI config');
      const data = await response.json();
      if (!validateAIConfig(data)) {
        throw new Error('Invalid AI config response format');
      }
      
      // Store in cache
      setInCache(cacheKey, data);
      
      recordMetrics(Date.now() - startTime, false);
      log({
        level: 'info',
        message: 'Fetched AI config',
        service: 'AIService',
        method: 'getConfig'
      });
      return data;
    } catch (error) {
      recordMetrics(Date.now() - startTime, true);
      log({
        level: 'error',
        message: `Failed to get AI config: ${error instanceof Error ? error.message : String(error)}`,
        service: 'AIService',
        method: 'getConfig'
      });
      throw error;
    }
  }

  async getConversations(page: number = 1, limit: number = 10): Promise<{ data: AIConversation[]; total: number; page: number; limit: number }> {
    const startTime = Date.now();
    try {
      const response = await fetchWithRetry(`${this.baseUrl}/conversations?page=${page}&limit=${limit}`);
      if (!response.ok) throw new Error('Failed to fetch conversations');
      const data = await response.json();
      // Validate response format
      if (!data || !Array.isArray(data.data) || typeof data.total !== 'number' || typeof data.page !== 'number' || typeof data.limit !== 'number') {
        throw new Error('Invalid conversations response format');
      }
      // Validate each conversation
      for (const conv of data.data) {
        if (!validateAIConversation(conv)) {
          throw new Error('Invalid conversation format in response');
        }
      }
      
      recordMetrics(Date.now() - startTime, false);
      log({
        level: 'info',
        message: `Fetched ${data.data.length} conversations (page ${data.page}, total ${data.total})`,
        service: 'AIService',
        method: 'getConversations'
      });
      return data;
    } catch (error) {
      recordMetrics(Date.now() - startTime, true);
      log({
        level: 'error',
        message: `Failed to get conversations: ${error instanceof Error ? error.message : String(error)}`,
        service: 'AIService',
        method: 'getConversations'
      });
      throw error;
    }
  }

  async getConversation(id: string): Promise<AIConversation> {
    const startTime = Date.now();
    try {
      const response = await fetchWithRetry(`${this.baseUrl}/conversations/${id}`);
      if (!response.ok) throw new Error('Failed to fetch conversation');
      const data = await response.json();
      if (!validateAIConversation(data)) {
        throw new Error('Invalid conversation response format');
      }
      
      recordMetrics(Date.now() - startTime, false);
      log({
        level: 'info',
        message: `Fetched conversation: ${id}`,
        service: 'AIService',
        method: 'getConversation'
      });
      return data;
    } catch (error) {
      recordMetrics(Date.now() - startTime, true);
      log({
        level: 'error',
        message: `Failed to get conversation ${id}: ${error instanceof Error ? error.message : String(error)}`,
        service: 'AIService',
        method: 'getConversation'
      });
      throw error;
    }
  }

  async createConversation(title?: string): Promise<AIConversation> {
    const startTime = Date.now();
    try {
      // Prepare headers with optional idempotency key
      const headers = { 'Content-Type': 'application/json' };
      
      // For createConversation, we'll use a generated idempotency key based on title and timestamp
      // In a real app, this might come from the client or be generated differently
      const idempotencyKey = title ? `conv-${title}-${Date.now()}` : undefined;
      if (idempotencyKey) {
        headers['Idempotency-Key'] = idempotencyKey;
      }
      
      const response = await fetchWithRetry(`${this.baseUrl}/conversations`, {
        method: 'POST',
        headers,
        body: JSON.stringify({ title })
      });
      if (!response.ok) throw new Error('Failed to create conversation');
      const data = await response.json();
      if (!validateAIConversation(data)) {
        throw new Error('Invalid conversation response format');
      }
      
      recordMetrics(Date.now() - startTime, false);
      log({
        level: 'info',
        message: 'Created conversation',
        service: 'AIService',
        method: 'createConversation'
      });
      return data;
    } catch (error) {
      recordMetrics(Date.now() - startTime, true);
      log({
        level: 'error',
        message: `Failed to create conversation: ${error instanceof Error ? error.message : String(error)}`,
        service: 'AIService',
        method: 'createConversation'
      });
      throw error;
    }
  }

  async deleteConversation(id: string): Promise<void> {
    const startTime = Date.now();
    try {
      const response = await fetchWithRetry(`${this.baseUrl}/conversations/${id}`, {
        method: 'DELETE'
      });
      if (!response.ok) throw new Error('Failed to delete conversation');
      
      recordMetrics(Date.now() - startTime, false);
      log({
        level: 'info',
        message: `Deleted conversation: ${id}`,
        service: 'AIService',
        method: 'deleteConversation'
      });
    } catch (error) {
      recordMetrics(Date.now() - startTime, true);
      log({
        level: 'error',
        message: `Failed to delete conversation ${id}: ${error instanceof Error ? error.message : String(error)}`,
        service: 'AIService',
        method: 'deleteConversation'
      });
      throw error;
    }
  }

  async sendMessage(request: AIRequest): Promise<AIResponse> {
    const startTime = Date.now();
    try {
      // Validate input
      if (!validateMessageLength(request.message)) {
        throw new Error('Message must be between 1 and 10,000 characters');
      }
    
      if (request.temperature !== undefined && !validateTemperature(request.temperature)) {
        throw new Error('Temperature must be between 0 and 2');
      }
    
      if (request.maxTokens !== undefined && !validateMaxTokens(request.maxTokens)) {
        throw new Error('Max tokens must be between 1 and 32,768');
      }
    
      // Additional validation for conversation context if needed
      if (request.context) {
        // Validate each context item if needed
        for (const ctx of request.context) {
          if (typeof ctx !== 'string' || ctx.length > 100) {
            throw new Error('Invalid context item');
          }
        }
      }
    
      // Prepare headers with optional idempotency key
      const headers = { 'Content-Type': 'application/json' };
      if (request.idempotencyKey) {
        headers['Idempotency-Key'] = request.idempotencyKey;
      }
    
      const response = await fetchWithRetry(`${this.baseUrl}/chat`, {
        method: 'POST',
        headers,
        body: JSON.stringify(request)
      });
      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'AI request failed');
      }
      const data = await response.json();
      if (!validateAIResponse(data)) {
        throw new Error('Invalid AI response format');
      }
      
      recordMetrics(Date.now() - startTime, false);
      log({
        level: 'info',
        message: 'Sent message',
        service: 'AIService',
        method: 'sendMessage',
        conversationId: request.conversationId,
        messageLength: request.message.length
      });
      return data;
    } catch (error) {
      recordMetrics(Date.now() - startTime, true);
      log({
        level: 'error',
        message: `Failed to send message: ${error instanceof Error ? error.message : String(error)}`,
        service: 'AIService',
        method: 'sendMessage',
        conversationId: request.conversationId,
        messageLength: request.message.length
      });
      throw error;
    }
  }

  async streamMessage(request: AIRequest, onChunk: (chunk: string) => void): Promise<void> {
    const startTime = Date.now();
    try {
      // Validate input
      if (!validateMessageLength(request.message)) {
        throw new Error('Message must be between 1 and 10,000 characters');
      }
    
      if (request.temperature !== undefined && !validateTemperature(request.temperature)) {
        throw new Error('Temperature must be between 0 and 2');
      }
    
      if (request.maxTokens !== undefined && !validateMaxTokens(request.maxTokens)) {
        throw new Error('Max tokens must be between 1 and 32,768');
      }
    
      // Additional validation for conversation context if needed
      if (request.context) {
        // Validate each context item if needed
        for (const ctx of request.context) {
          if (typeof ctx !== 'string' || ctx.length > 100) {
            throw new Error('Invalid context item');
          }
        }
      }
    
      const response = await fetchWithRetry(`${this.baseUrl}/chat/stream`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
      
      if (!response.ok) throw new Error('Streaming request failed');
      if (!response.body) throw new Error('No response body');

      const reader = response.body.getReader();
      const decoder = new TextDecoder();

      try {
        while (true) {
          const { done, value } = await reader.read();
          if (done) break;
          
          const chunk = decoder.decode(value, { stream: true });
          const lines = chunk.split('\n');
          
          for (const line of lines) {
            if (line.trim().startsWith('data: ')) {
              const data = line.trim().slice(6);
              if (data === '[DONE]') return;
              try {
                const parsed = JSON.parse(data);
                if (parsed.content) {
                  onChunk(parsed.content);
                }
              } catch { /* ignore parse errors */ }
            }
          }
        }
      } finally {
        reader.releaseLock();
      }
      
      recordMetrics(Date.now() - startTime, false);
      log({
        level: 'info',
        message: 'Completed streaming message',
        service: 'AIService',
        method: 'streamMessage',
        conversationId: request.conversationId
      });
    } catch (error) {
      recordMetrics(Date.now() - startTime, true);
      log({
        level: 'error',
        message: `Failed to stream message: ${error instanceof Error ? error.message : String(error)}`,
        service: 'AIService',
        method: 'streamMessage',
        conversationId: request.conversationId
      });
      throw error;
    }
  }

  async generateSuggestions(context: string): Promise<string[]> {
    try {
      // Create cache key
      const cacheKey = `ai:suggestions:${context}`;
      
      // Try to get from cache
      const cached = getFromCache<string[]>(cacheKey);
      if (cached) {
        return cached;
      }
      
      // If not in cache, fetch from API
      const response = await fetchWithRetry(`${this.baseUrl}/suggestions`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ context })
      });
      if (!response.ok) throw new Error('Failed to generate suggestions');
      const data = await response.json();
      // Validate response format
      if (!data || !Array.isArray(data.suggestions) || !data.suggestions.every((s: any): s is string => typeof s === 'string')) {
        throw new Error('Invalid suggestions response format');
      }
      
      // Store in cache
      setInCache(cacheKey, data.suggestions);
      
      return data.suggestions;
    } catch (error) {
      console.error('[AIService:generateSuggestions] Error:', error);
      throw error;
    }
  }

  async transcribeAudio(audioBlob: Blob): Promise<string> {
    try {
      const formData = new FormData();
      formData.append('audio', audioBlob);

      const response = await fetchWithRetry(`${this.baseUrl}/transcribe`, {
        method: 'POST',
        body: formData
      });
      if (!response.ok) throw new Error('Transcription failed');
      const data = await response.json();
      // Validate response format
      if (!data || typeof data.text !== 'string') {
        throw new Error('Invalid transcription response format');
      }
      return data.text;
    } catch (error) {
      console.error('[AIService:transcribeAudio] Error:', error);
      throw error;
    }
  }

  async askGovernmentBot(query: string, context: GovernmentQuery): Promise<LLMResponse> {
    // Require authentication for sensitive operations
    this.requireAuth();
    
    try {
      // Validate input
      if (!validateMessageLength(query)) {
        throw new Error('Query must be between 1 and 10,000 characters');
      }
    
      if (!validateLocationCode(context.wardCode)) {
        throw new Error('Invalid ward code');
      }
    
      if (!validateLocationCode(context.districtCode)) {
        throw new Error('Invalid district code');
      }
    
      if (!validateLocationCode(context.provinceCode)) {
        throw new Error('Invalid province code');
      }
    
      const response = await fetchWithRetry(`${this.baseUrl}/government/chat`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          query,
          context: context.context,
          location: {
            ward: context.wardCode,
            district: context.districtCode,
            province: context.provinceCode,
          },
          language: context.language || 'vi',
        })
      });
      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Government bot request failed');
      }
      const data = await response.json();
      if (!validateLLMResponse(data)) {
        throw new Error('Invalid government bot response format');
      }
      return data;
    } catch (error) {
      console.error('[AIService:askGovernmentBot] Error:', error);
      throw error;
    }
  }

  async streamGovernmentBot(
    query: string, 
    context: GovernmentQuery, 
    onChunk: (chunk: string) => void
  ): Promise<void> {
    // Require authentication for sensitive operations
    this.requireAuth();
    
    const startTime = Date.now();
    try {
      // Validate input
      if (!validateMessageLength(query)) {
        throw new Error('Query must be between 1 and 10,000 characters');
      }
    
      if (!validateLocationCode(context.wardCode)) {
        throw new Error('Invalid ward code');
      }
    
      if (!validateLocationCode(context.districtCode)) {
        throw new Error('Invalid district code');
      }
    
      if (!validateLocationCode(context.provinceCode)) {
        throw new Error('Invalid province code');
      }
    
      const response = await fetchWithRetry(`${this.baseUrl}/government/chat/stream`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          query,
          context: context.context,
          location: {
            ward: context.wardCode,
            district: context.districtCode,
            province: context.provinceCode,
          },
          language: context.language || 'vi',
        })
      });
      
      if (!response.ok) throw new Error('Streaming request failed');
      if (!response.body) throw new Error('No response body');
      
      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      
      try {
        while (true) {
          const { done, value } = await reader.read();
          if (done) break;
          
          const chunk = decoder.decode(value, { stream: true });
          const lines = chunk.split('\n');
          
          for (const line of lines) {
            if (line.trim().startsWith('data: ')) {
              const data = line.trim().slice(6);
              if (data === '[DONE]') return;
              try {
                const parsed = JSON.parse(data);
                if (parsed.content) {
                  onChunk(parsed.content);
                }
              } catch { /* ignore parse errors */ }
            }
          }
        }
      } finally {
        reader.releaseLock();
      }
      
      recordMetrics(Date.now() - startTime, false);
      log({
        level: 'info',
        message: 'Completed streaming government bot response',
        service: 'AIService',
        method: 'streamGovernmentBot',
        queryLength: query.length,
        context: context.context
      });
    } catch (error) {
      recordMetrics(Date.now() - startTime, true);
      log({
        level: 'error',
        message: `Failed to stream government bot: ${error instanceof Error ? error.message : String(error)}`,
        service: 'AIService',
        method: 'streamGovernmentBot',
        queryLength: query.length,
        context: context.context
      });
      throw error;
    }
  }

  async getProcedureRequirements(procedureCode: string): Promise<{
    procedure: string;
    requirements: string[];
    fee: number;
    processingTime: string;
    steps: ProcedureStep[];
  }> {
    // Validate input
    if (!procedureCode || procedureCode.trim().length === 0) {
      throw new Error('Procedure code is required');
    }
    
    // Assuming procedure code format: alphanumeric with underscores/hyphens, max 50 chars
    if (!/^[a-zA-Z0-9_-]{1,50}$/.test(procedureCode)) {
      throw new Error('Invalid procedure code format');
    }
    
    try {
      const response = await fetchWithRetry(`${this.baseUrl}/government/procedure/${procedureCode}`);
      if (!response.ok) throw new Error('Failed to fetch procedure requirements');
      const data = await response.json();
      if (!validateProcedureResponse(data)) {
        throw new Error('Invalid procedure requirements response format');
      }
      return data;
    } catch (error) {
      console.error('[AIService:getProcedureRequirements] Error:', error);
      throw error;
    }
  }

  async getCitizenInfo(cccd: string): Promise<{
    name: string;
    dob: string;
    gender: string;
    address: string;
    ward: string;
    district: string;
    province: string;
    }> {
      // Require authentication for sensitive operations
      this.requireAuth();
      
      // Validate input
      if (!validateCCCDFormat(cccd)) {
        throw new Error('Invalid CCCD format. Must be 12 digits.');
      }
      
      try {
        const response = await fetchWithRetry(`${this.baseUrl}/government/citizen/${cccd}`);
        if (!response.ok) throw new Error('Failed to fetch citizen info');
        const data = await response.json();
        if (!validateCitizenInfoResponse(data)) {
          throw new Error('Invalid citizen info response format');
        }
        return data;
      } catch (error) {
        console.error('[AIService:getCitizenInfo] Error:', error);
        throw error;
      }

  }

  async checkDocumentStatus(applicationId: string): Promise<{
    status: 'pending' | 'processing' | 'completed' | 'rejected';
    currentStep: string;
    estimatedTime: string;
    documents: { name: string; status: string }[];
  }> {
    // Validate input
    if (!validateApplicationIdFormat(applicationId)) {
      throw new Error('Invalid application ID format');
    }
    
    try {
      const response = await fetchWithRetry(`${this.baseUrl}/government/application/${applicationId}/status`);
      if (!response.ok) throw new Error('Failed to check document status');
      const data = await response.json();
      if (!validateDocumentStatusResponse(data)) {
        throw new Error('Invalid document status response format');
      }
      return data;
    } catch (error) {
      console.error('[AIService:checkDocumentStatus] Error:', error);
      throw error;
    }
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {
    cache.clear();
  }
};

