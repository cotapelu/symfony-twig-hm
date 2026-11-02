export interface OfficialChat {
  id: string;
  userId: string;
  departmentId: string;
  departmentName: string;
  messages: ChatMessage[];
  status: 'active' | 'closed';
  createdAt: string;
  updatedAt: string;
}

export interface ChatMessage {
  id: string;
  chatId: string;
  senderId: string;
  senderType: 'user' | 'staff';
  content: string;
  attachments?: string[];
  createdAt: string;
}

export interface SendMessageRequest {
  chatId: string;
  content: string;
  attachments?: string[];
}

export interface ChatOfficialState {
  chats: OfficialChat[];
  activeChat: OfficialChat | null;
  loading: boolean;
  error: string | null;
}
