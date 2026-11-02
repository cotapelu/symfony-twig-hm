export interface ChatbotConversation {
  id: string;
  userId: string;
  messages: ChatbotMessage[];
  createdAt: string;
  updatedAt: string;
}

export interface ChatbotMessage {
  id: string;
  conversationId: string;
  role: 'user' | 'assistant';
  content: string;
  createdAt: string;
}

export interface ChatbotQuery {
  message: string;
  context?: Record<string, unknown>;
}

export interface ChatbotState {
  conversations: ChatbotConversation[];
  activeConversation: ChatbotConversation | null;
  loading: boolean;
  error: string | null;
  currentUserId: string | null;
}
