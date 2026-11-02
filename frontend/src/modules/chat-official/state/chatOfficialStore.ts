import { writable, derived } from 'svelte/store';
import type { OfficialChat, ChatOfficialState } from '../contracts/chatOfficialTypes';
import { chatOfficialService } from '../services/chatOfficialService';

const initialState: ChatOfficialState = {
  chats: [],
  activeChat: null,
  loading: false,
  error: null
};

function createChatOfficialStore() {
  const { subscribe, set, update } = writable<ChatOfficialState>(initialState);
  return {
    subscribe,
    async loadChats() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const chats = await chatOfficialService.getChats();
        update(s => ({ ...s, chats, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async loadChat(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const chat = await chatOfficialService.getChat(id);
        update(s => ({ ...s, activeChat: chat, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async createChat(departmentId: string) {
      const chat = await chatOfficialService.createChat(departmentId);
      update(s => ({ ...s, chats: [...s.chats, chat] }));
      return chat;
    },
    async sendMessage(chatId: string, content: string, attachments?: string[]) {
      const message = await chatOfficialService.sendMessage({ chatId, content, attachments });
      update(s => {
        if (s.activeChat?.id === chatId) {
          return { ...s, activeChat: { ...s.activeChat, messages: [...s.activeChat.messages, message] } };
        }
        return s;
      });
      return message;
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const chatOfficialStore = createChatOfficialStore();
export const officialChats = derived(chatOfficialStore, $s => $s.chats);
export const activeOfficialChat = derived(chatOfficialStore, $s => $s.activeChat);
export const chatOfficialLoading = derived(chatOfficialStore, $s => $s.loading);
