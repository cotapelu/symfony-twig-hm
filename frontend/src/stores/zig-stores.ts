// Zig Stores - TypeScript Version
// VoiceMail, ContentModeration

import { writable } from 'svelte/store';
import { Zig } from '../lib/api';

interface ZigVoiceMailState {
  voiceMails: any[];
  transcriptions: Record<string, { text: string }>;
  listenedCount: number;
  totalDuration: number;
  loading: boolean;
  recording: boolean;
  error: string | null;
}

function createZigVoiceMailStore() {
  const initialState: ZigVoiceMailState = {
    voiceMails: [],
    transcriptions: {},
    listenedCount: 0,
    totalDuration: 0,
    loading: false,
    recording: false,
    error: null,
  };

  const { subscribe, set, update } = writable<ZigVoiceMailState>(initialState);

  return {
    subscribe,
    
    recordVoiceMail: async (audioFile: File): Promise<any> => {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const voiceMail = await Zig.recordVoiceMail(audioFile);
        update(prev => ({ ...prev, voiceMails: [voiceMail, ...prev.voiceMails], loading: false }));
        return voiceMail;
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Failed to record voice mail';
        update(state => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },
    
    transcribeVoiceMail: async (voiceMailId: string): Promise<{ text: string }> => {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const transcription = await Zig.transcribeVoiceMail(voiceMailId);
        update(prev => ({
          ...prev,
          transcriptions: { ...prev.transcriptions, [voiceMailId]: transcription },
          loading: false,
        }));
        return transcription;
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Failed to transcribe voice mail';
        update(state => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },
    
    getVoiceMails: async (userId: string): Promise<any[]> => {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const voiceMails = await Zig.getVoiceMails(userId);
        const listenedCount = voiceMails.filter(vm => !vm.isNew).length;
        const totalDuration = voiceMails.reduce((sum, vm) => sum + (vm.duration || 0), 0);
        update(prev => ({ ...prev, voiceMails, listenedCount, totalDuration, loading: false }));
        return voiceMails;
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Failed to get voice mails';
        update(state => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },
    
    deleteVoiceMail: async (voiceMailId: string): Promise<void> => {
      try {
        await Zig.deleteVoiceMail(voiceMailId);
        update(prev => ({
          ...prev,
          voiceMails: prev.voiceMails.filter(vm => vm.id !== voiceMailId),
        }));
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Failed to delete voice mail';
        update(state => ({ ...state, error: errorMessage }));
        throw error;
      }
    },
    
    setRecording: (recording: boolean): void => {
      update(state => ({ ...state, recording }));
    },
    
    clearError: (): void => {
      update(state => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        voiceMails: [],
        transcriptions: {},
        listenedCount: 0,
        totalDuration: 0,
        loading: false,
        recording: false,
        error: null,
      });
    },
  };
}

interface ZigContentModerationState {
  moderationResults: Record<string, any>;
  loading: boolean;
  error: string | null;
}

function createZigContentModerationStore() {
  const initialState: ZigContentModerationState = {
    moderationResults: {},
    loading: false,
    error: null,
  };

  const { subscribe, set, update } = writable<ZigContentModerationState>(initialState);

  return {
    subscribe,
    
    moderateText: async (text: string): Promise<any> => {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const result = await Zig.moderateText(text);
        update(prev => ({
          ...prev,
          moderationResults: { ...prev.moderationResults, [`text_${Date.now()}`]: result },
          loading: false,
        }));
        return result;
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Failed to moderate text';
        update(state => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },
    
    moderateImage: async (imageFile: File): Promise<any> => {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const result = await Zig.moderateImage(imageFile);
        update(prev => ({
          ...prev,
          moderationResults: { ...prev.moderationResults, [`image_${Date.now()}`]: result },
          loading: false,
        }));
        return result;
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Failed to moderate image';
        update(state => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },
    
    moderateVideo: async (videoFile: File): Promise<any> => {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const result = await Zig.moderateVideo(videoFile);
        update(prev => ({
          ...prev,
          moderationResults: { ...prev.moderationResults, [`video_${Date.now()}`]: result },
          loading: false,
        }));
        return result;
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Failed to moderate video';
        update(state => ({ ...state, loading: false, error: errorMessage }));
        throw error;
      }
    },
    
    clearError: (): void => {
      update(state => ({ ...state, error: null }));
    },
    // Test-only reset
    reset: (): void => {
      set({
        moderationResults: {},
        loading: false,
        error: null,
      });
    },
  };
}

export const zigVoiceMailStore = createZigVoiceMailStore();
export const zigContentModerationStore = createZigContentModerationStore();

export const {
  recordVoiceMail,
  transcribeVoiceMail,
  getVoiceMails,
  deleteVoiceMail,
  setRecording,
  clearError: clearVoiceMailError,
} = zigVoiceMailStore;

export const {
  moderateText,
  moderateImage,
  moderateVideo,
  clearError: clearModerationError,
} = zigContentModerationStore;
