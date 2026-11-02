export interface MessageRequest {
  content: string;
  type: 'text' | 'image' | 'file' | 'voice';
  metadata?: {
    fileName?: string;
    fileSize?: number;
    duration?: number;
  };
}

export interface MessageResponse {
  id: string;
  senderId: string;
  receiverId: string;
  content: string;
  timestamp: string;
  read: boolean;
  type: 'text' | 'image' | 'file' | 'voice';
  metadata?: {
    fileName?: string;
    fileSize?: number;
    duration?: number;
  };
}

export interface ConversationResponse {
  id: string;
  participants: string[];
  lastMessage?: MessageResponse;
  unreadCount: number;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface CreateConversationRequest {
  participantIds: string[];
  initialMessage?: string;
}