export interface MessageEvent {
  type: 'MESSAGE_SENT' | 'MESSAGE_RECEIVED' | 'MESSAGE_READ' | 'CONVERSATION_STARTED' | 'USER_TYPING' | 'USER_STOPPED_TYPING';
  payload: any;
  timestamp: Date;
}

export interface MessageSentEvent {
  type: 'MESSAGE_SENT';
  payload: {
    conversationId: string;
    messageId: string;
    content: string;
    receiverId: string;
  };
  timestamp: Date;
}

export interface MessageReceivedEvent {
  type: 'MESSAGE_RECEIVED';
  payload: {
    conversationId: string;
    messageId: string;
    senderId: string;
    content: string;
    type: 'text' | 'image' | 'file' | 'voice';
  };
  timestamp: Date;
}

export interface MessageReadEvent {
  type: 'MESSAGE_READ';
  payload: {
    conversationId: string;
    messageId: string;
    readerId: string;
  };
  timestamp: Date;
}

export interface ConversationStartedEvent {
  type: 'CONVERSATION_STARTED';
  payload: {
    conversationId: string;
    participants: string[];
    initiatedBy: string;
  };
  timestamp: Date;
}

export interface UserTypingEvent {
  type: 'USER_TYPING';
  payload: {
    conversationId: string;
    userId: string;
  };
  timestamp: Date;
}

export interface UserStoppedTypingEvent {
  type: 'USER_STOPPED_TYPING';
  payload: {
    conversationId: string;
    userId: string;
  };
  timestamp: Date;
}