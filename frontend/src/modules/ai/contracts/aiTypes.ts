export interface AIMessage {
  role: 'user' | 'assistant' | 'system';
  content: string;
  timestamp: string;
  metadata?: Record<string, any>;
}

export interface AIConversation {
  id: string;
  title: string;
  messages: AIMessage[];
  createdAt: string;
  updatedAt?: string;
  model?: string;
}

export interface AIRequest {
  message: string;
  model?: string;
  conversationId?: string;
  context?: string[];
  temperature?: number;
  maxTokens?: number;
  stream?: boolean;
  idempotencyKey?: string;
}

export interface AIResponse {
  id: string;
  content: string;
  model: string;
  usage?: {
    promptTokens: number;
    completionTokens: number;
    totalTokens: number;
  };
  finishReason?: string;
}

export interface AIConfig {
  defaultModel: string;
  availableModels: string[];
  maxTokens: number;
  temperature: number;
  streamResponses: boolean;
  apiKey?: string;
  endpoint?: string;
}

export interface AIState {
  conversations: AIConversation[];
  currentConversation: AIConversation | null;
  loading: boolean;
  error: string | null;
  config: AIConfig | null;
  suggestions: string[];
  streaming: boolean;
  streamingChunk: string;
}

export interface AITool {
  name: string;
  description: string;
  parameters: Record<string, any>;
  handler: (params: any) => Promise<any>;
}

export interface AIStreamingChunk {
  id: string;
  content: string;
  isComplete: boolean;
}

export type GovernmentContext = 
  | 'citizen_info'
  | 'procedure_guide'
  | 'document_req'
  | 'ward_info'
  | 'emergency'
  | 'general';

export interface GovernmentQuery {
  context: GovernmentContext;
  wardCode?: string;
  districtCode?: string;
  provinceCode?: string;
  language?: 'vi' | 'en' | 'zh';
}

export interface ProcedureStep {
  step: number;
  description: string;
  documents: string[];
  fee: number;
  processingTime: string;
}

export interface LLMResponse {
  content: string;
  sources?: string[];
  actions?: { label: string; url: string }[];
  procedureSteps?: ProcedureStep[];
}
