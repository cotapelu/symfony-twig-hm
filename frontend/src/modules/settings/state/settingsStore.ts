import { writable, derived, get } from 'svelte/store';
import { settingsService } from '../services/settingsService';

export interface Settings {
  notifications: {
    email: boolean;
    push: boolean;
    messages: boolean;
    social: boolean;
  };
  privacy: {
    profileVisibility: 'public' | 'friends' | 'private';
    showOnlineStatus: boolean;
    allowMessages: boolean;
  };
  appearance: {
    theme: 'light' | 'dark' | 'auto';
    language: string;
    fontSize: 'small' | 'medium' | 'large';
  };
}

export interface SettingsState {
  settings: Settings;
  loading: boolean;
  error: string | null;
  saving: boolean;
}

const defaultSettings: Settings = {
  notifications: {
    email: true,
    push: true,
    messages: true,
    social: false
  },
  privacy: {
    profileVisibility: 'public',
    showOnlineStatus: true,
    allowMessages: true
  },
  appearance: {
    theme: 'auto',
    language: 'en',
    fontSize: 'medium'
  }
};

function loadFromLocalStorage(): Settings {
  try {
    const saved = localStorage.getItem('app_settings');
    if (saved) {
      const parsed = JSON.parse(saved);
      if (settingsService.validateAndSanitize(parsed)) {
        return settingsService.validateAndSanitize(parsed);
      }
    }
  } catch (e) {
    console.warn('[SettingsStore] Failed to load from localStorage:', e);
  }
  return defaultSettings;
}

function saveToLocalStorage(settings: Settings): void {
  try {
    localStorage.setItem('app_settings', JSON.stringify(settings));
  } catch (e) {
    console.error('[SettingsStore] Failed to save to localStorage:', e);
  }
}

const initialState: SettingsState = {
  settings: loadFromLocalStorage(),
  loading: false,
  error: null,
  saving: false
};

function createSettingsStore() {
  const { subscribe, set, update } = writable<SettingsState>(initialState);

  return {
    subscribe,
    async loadSettings() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info('[SettingsStore] Loading settings from API');
        const settings = await settingsService.getSettings();
        const validated = settingsService.validateAndSanitize(settings);
        saveToLocalStorage(validated);
        update(s => ({ ...s, settings: validated, loading: false }));
        console.info('[SettingsStore] Settings loaded successfully');
      } catch (e) {
        console.warn('[SettingsStore] API failed, using localStorage:', e);
        const local = loadFromLocalStorage();
        update(s => ({ ...s, settings: local, loading: false, error: null }));
      }
    },
    async saveSettings(settings: Settings) {
      update(s => ({ ...s, saving: true, error: null }));
      try {
        console.info('[SettingsStore] Saving settings');
        const validated = settingsService.validateAndSanitize(settings);
        const saved = await settingsService.saveSettings(validated);
        saveToLocalStorage(saved);
        update(s => ({ ...s, settings: saved, saving: false }));
        console.info('[SettingsStore] Settings saved successfully');
      } catch (e) {
        console.warn('[SettingsStore] API save failed, saving locally:', e);
        saveToLocalStorage(settings);
        update(s => ({ ...s, saving: false, error: null }));
      }
    },
    async saveSettingsLocal() {
      const current = get({ subscribe });
      await this.saveSettings(current.settings);
    },
    resetSettings() {
      saveToLocalStorage(defaultSettings);
      update(s => ({ ...s, settings: defaultSettings, error: null }));
      console.info('[SettingsStore] Settings reset to defaults');
    },
    exportSettings() {
      const current = get({ subscribe });
      settingsService.exportSettings(current.settings);
    },
    async importSettings(file: File) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const imported = await settingsService.importSettings(file);
        saveToLocalStorage(imported);
        update(s => ({ ...s, settings: imported, loading: false }));
        console.info('[SettingsStore] Settings imported successfully');
      } catch (e) {
        update(s => ({ ...s, loading: false, error: (e as Error).message }));
        throw e;
      }
    },
    clearError() {
      update(s => ({ ...s, error: null }));
    }
  };
}

export const settingsStore = createSettingsStore();
export const settings = derived(settingsStore, $state => $state.settings);
export const loading = derived(settingsStore, $state => $state.loading);
export const saving = derived(settingsStore, $state => $state.saving);
export const error = derived(settingsStore, $state => $state.error);