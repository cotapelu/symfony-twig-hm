export interface ConversationStartedEvent { type: 'chatbot.conversation.started'; payload: { conversationId: string }; }
export interface MessageReceivedEvent { type: 'chatbot.message.received'; payload: { conversationId: string; messageId: string }; }
export type ChatbotEvent = ConversationStartedEvent | MessageReceivedEvent;

export const chatbotEvents = {
  conversationStarted: (conversationId: string): ConversationStartedEvent => ({
    type: 'chatbot.conversation.started', payload: { conversationId }
  }),
  messageReceived: (conversationId: string, messageId: string): MessageReceivedEvent => ({
    type: 'chatbot.message.received', payload: { conversationId, messageId }
  })
};
