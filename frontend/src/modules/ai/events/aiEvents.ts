import { createEventDispatcher } from 'svelte';

export interface AIEvent {
  type: 'message_sent' | 'response_received' | 'conversation_created' | 'conversation_deleted' | 'error_occurred';
  data?: any;
  timestamp: number;
}

export function createAIEvents() {
  const dispatch = createEventDispatcher();

  return {
    dispatchMessageSent(message: any) {
      dispatch('message_sent', {
        type: 'message_sent',
        data: message,
        timestamp: Date.now()
      });
    },

    dispatchResponseReceived(response: any) {
      dispatch('response_received', {
        type: 'response_received',
        data: response,
        timestamp: Date.now()
      });
    },

    dispatchConversationCreated(conversation: any) {
      dispatch('conversation_created', {
        type: 'conversation_created',
        data: conversation,
        timestamp: Date.now()
      });
    },

    dispatchConversationDeleted(conversationId: string) {
      dispatch('conversation_deleted', {
        type: 'conversation_deleted',
        data: { conversationId },
        timestamp: Date.now()
      });
    },

    dispatchErrorOccurred(error: any) {
      dispatch('error_occurred', {
        type: 'error_occurred',
        data: error,
        timestamp: Date.now()
      });
    }
  };
}

export const aiEventTypes = {
  MESSAGE_SENT: 'message_sent',
  RESPONSE_RECEIVED: 'response_received',
  CONVERSATION_CREATED: 'conversation_created',
  CONVERSATION_DELETED: 'conversation_deleted',
  ERROR_OCCURRED: 'error_occurred'
} as const;
