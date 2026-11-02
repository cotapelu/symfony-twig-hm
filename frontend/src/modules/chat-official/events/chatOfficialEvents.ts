export interface ChatCreatedEvent { type: 'chat-official.created'; payload: { chatId: string; departmentId: string }; }
export interface MessageSentEvent { type: 'chat-official.message.sent'; payload: { chatId: string; messageId: string }; }
export interface ChatClosedEvent { type: 'chat-official.closed'; payload: { chatId: string }; }
export type ChatOfficialEvent = ChatCreatedEvent | MessageSentEvent | ChatClosedEvent;

export const chatOfficialEvents = {
  chatCreated: (chatId: string, departmentId: string): ChatCreatedEvent => ({
    type: 'chat-official.created', payload: { chatId, departmentId }
  }),
  messageSent: (chatId: string, messageId: string): MessageSentEvent => ({
    type: 'chat-official.message.sent', payload: { chatId, messageId }
  }),
  chatClosed: (chatId: string): ChatClosedEvent => ({ type: 'chat-official.closed', payload: { chatId } })
};
