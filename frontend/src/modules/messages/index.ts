export * from './ui/+page.svelte';
export * from './ui/MessageInput.svelte';
export * from './ui/MessageThread.svelte';
export * from './ui/ConversationList.svelte';
export * from './ui/MessageItem.svelte';
export * from './ui/MessageBubble.svelte';
export * from './ui/Messages.svelte';
export * from './ui/MessageList.svelte';

export * from './state/messagesStore';
export * from './services/messageService';
export * from './contracts/messageTypes';
export * from './events/messageEvents';

export { 
  messagesStore, 
  loadConversations, 
  loadMessages, 
  sendMessage, 
  setActiveConversation, 
  markAsRead, 
  clearError 
} from './state/messagesStore';
export { MessageService } from './services/messageService';