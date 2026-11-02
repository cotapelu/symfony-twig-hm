export interface Message {
  id: string;
  sender: string;
  content: string;
  timestamp: Date;
  read: boolean;
  conversationId?: string;
}

export interface Conversation {
  id: string;
  participant: string;
  lastMessage: string;
  timestamp: Date;
  unreadCount: number;
}

export interface SendMessageRequest {
  content: string;
  conversationId: string;
}

export interface MessageResponse {
  success: boolean;
  data?: Message;
  message?: string;
}