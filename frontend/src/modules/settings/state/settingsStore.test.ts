// Mock settingsService BEFORE importing settingsStore
vi.mock('../services/settingsService', () => {
  const getSettings = vi.fn();
  const saveSettings = vi.fn();
  const validateAndSanitize = vi.fn((s: any) => s);
  const importSettings = vi.fn();
  const exportSettings = vi.fn();
  return {
    settingsService: {
      getSettings,
      saveSettings,
      validateAndSanitize,
      importSettings,
      exportSettings
    }
  };
});

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { get } from 'svelte/store';
import {
  settingsStore,
  settings,
  loading,
  saving,
  error
} from './settingsStore';
import { settingsService } from '../services/settingsService';

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: vi.fn((key: string) => store[key] ?? null),
    setItem: vi.fn((key: string, value: string) => { store[key] = value; }),
    clear: vi.fn(() => { store = {}; }),
    removeItem: vi.fn((key: string) => { delete store[key]; })
  };
})();

vi.stubGlobal('localStorage', localStorageMock);

const defaultSettings = {
  notifications: { email: true, push: true, messages: true, social: false },
  privacy: { profileVisibility: 'public', showOnlineStatus: true, allowMessages: true },
  appearance: { theme: 'auto', language: 'en', fontSize: 'medium' }
};

const otherSettings = {
  notifications: { email: false, push: false, messages: false, social: true },
  privacy: { profileVisibility: 'private', showOnlineStatus: false, allowMessages: false },
  appearance: { theme: 'dark', language: 'vi', fontSize: 'large' }
};

describe('SettingsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.clear();
    settingsStore.resetSettings();
  });

  describe('initial state', () => {
    it('has default settings', () => {
      expect(get(settings)).toEqual(defaultSettings);
      expect(get(loading)).toBe(false);
      expect(get(saving)).toBe(false);
      expect(get(error)).toBeNull();
    });
  });

  describe('loadSettings', () => {
    it('loads from API successfully and updates state', async () => {
      settingsService.getSettings.mockResolvedValue(otherSettings);
      await settingsStore.loadSettings();
      expect(settingsService.getSettings).toHaveBeenCalledTimes(1);
      expect(get(settings)).toEqual(otherSettings);
      expect(get(loading)).toBe(false);
      expect(get(error)).toBeNull();
      expect(localStorageMock.setItem).toHaveBeenCalledWith('app_settings', JSON.stringify(otherSettings));
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      settingsService.getSettings.mockReturnValue(p as any);
      const loadPromise = settingsStore.loadSettings();
      await new Promise(process.nextTick);
      expect(get(loading)).toBe(true);
      resolvePromise(otherSettings);
      await loadPromise;
      expect(get(loading)).toBe(false);
    });

    it('falls back to localStorage on API error', async () => {
      settingsService.getSettings.mockRejectedValue(new Error('API down'));
      const localSettings = { ...otherSettings };
      localStorageMock.getItem.mockReturnValueOnce(JSON.stringify(localSettings));
      await settingsStore.loadSettings();
      expect(get(error)).toBeNull();
      expect(get(settings)).toEqual(localSettings);
      expect(get(loading)).toBe(false);
    });

    it('uses default settings when localStorage empty on API error', async () => {
      settingsService.getSettings.mockRejectedValue(new Error('API down'));
      localStorageMock.getItem.mockReturnValueOnce(null);
      await settingsStore.loadSettings();
      expect(get(settings)).toEqual(defaultSettings);
      expect(get(loading)).toBe(false);
    });
  });

  describe('saveSettings', () => {
    it('saves to API successfully and updates state', async () => {
      settingsService.saveSettings.mockResolvedValue(otherSettings);
      await settingsStore.saveSettings(otherSettings);
      expect(settingsService.saveSettings).toHaveBeenCalledWith(otherSettings);
      expect(get(settings)).toEqual(otherSettings);
      expect(get(saving)).toBe(false);
      expect(get(error)).toBeNull();
      expect(localStorageMock.setItem).toHaveBeenCalledWith('app_settings', JSON.stringify(otherSettings));
    });

    it('sets saving true during save and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      settingsService.saveSettings.mockReturnValue(p as any);
      const savePromise = settingsStore.saveSettings(otherSettings);
      await new Promise(process.nextTick);
      expect(get(saving)).toBe(true);
      resolvePromise(otherSettings);
      await savePromise;
      expect(get(saving)).toBe(false);
    });

    it('falls back to localStorage on API error', async () => {
      settingsService.saveSettings.mockRejectedValue(new Error('API down'));
      const previousSettings = get(settings);
      await settingsStore.saveSettings(otherSettings);
      expect(get(saving)).toBe(false);
      expect(get(error)).toBeNull();
      expect(localStorageMock.setItem).toHaveBeenCalledWith('app_settings', JSON.stringify(otherSettings));
      // State should not have changed
      expect(get(settings)).toEqual(previousSettings);
    });
  });

  describe('resetSettings', () => {
    it('resets to default settings and saves to localStorage', async () => {
      // Ensure saveSettings resolves to otherSettings
      settingsService.saveSettings.mockResolvedValue(otherSettings);
      await settingsStore.saveSettings(otherSettings);
      expect(get(settings)).toEqual(otherSettings);
      await settingsStore.resetSettings();
      expect(get(settings)).toEqual(defaultSettings);
      expect(localStorageMock.setItem).toHaveBeenCalledWith('app_settings', JSON.stringify(defaultSettings));
    });
  });

  describe('clearError', () => {
    it('clears error state after a failure', async () => {
      const file = new File([], 'settings.json');
      settingsService.importSettings.mockRejectedValue(new Error('import failed'));
      await expect(settingsStore.importSettings(file)).rejects.toThrow('import failed');
      expect(get(error)).toBe('import failed');
      settingsStore.clearError();
      expect(get(error)).toBeNull();
    });
  });

  describe('importSettings', () => {
    it('imports from file and updates settings', async () => {
      const imported = { ...otherSettings };
      const file = new File([JSON.stringify(imported)], 'settings.json', { type: 'application/json' });
      settingsService.importSettings.mockResolvedValue(imported);
      await settingsStore.importSettings(file);
      expect(settingsService.importSettings).toHaveBeenCalledWith(file);
      expect(get(settings)).toEqual(imported);
      expect(localStorageMock.setItem).toHaveBeenCalledWith('app_settings', JSON.stringify(imported));
    });

    it('propagates import error', async () => {
      const file = new File([], 'settings.json');
      settingsService.importSettings.mockRejectedValue(new Error('Invalid file'));
      await expect(settingsStore.importSettings(file)).rejects.toThrow('Invalid file');
      expect(get(loading)).toBe(false);
    });
  });

  describe('loadSettings edge cases', () => {
    it('handles invalid JSON in localStorage', async () => {
      settingsService.getSettings.mockRejectedValue(new Error('API down'));
      localStorageMock.getItem.mockReturnValueOnce('invalid json');
      await settingsStore.loadSettings();
      expect(get(settings)).toEqual(defaultSettings);
    });

    it('handles localStorage access error', async () => {
      settingsService.getSettings.mockRejectedValue(new Error('API down'));
      localStorageMock.getItem.mockImplementation(() => { throw new Error('LS error'); });
      await settingsStore.loadSettings();
      expect(get(settings)).toEqual(defaultSettings);
    });

    it('handles localStorage parse failure', async () => {
      settingsService.getSettings.mockRejectedValue(new Error('API down'));
      localStorageMock.getItem.mockReturnValueOnce('{"invalid": json}');
      await settingsStore.loadSettings();
      expect(get(settings)).toEqual(defaultSettings);
    });

    it('handles validateAndSanitize returning null', async () => {
      settingsService.getSettings.mockRejectedValue(new Error('API down'));
      localStorageMock.getItem.mockReturnValueOnce(JSON.stringify({ notifications: { email: false } }));
      // The store will try to validateAndSanitize from service, but if it returns falsey, it falls back to default.
      // By default validateAndSanitize is mocked to return the input. So we need to adjust mock for this specific test.
      // But we can test behavior by setting the mock to return null/undefined.
      vi.mocked(settingsService.validateAndSanitize).mockReturnValue(null as any);
      localStorageMock.getItem.mockReturnValue(JSON.stringify({ notifications: { email: false } }));
      await settingsStore.loadSettings();
      expect(get(settings)).toEqual(defaultSettings);
      // Reset the mock to default for other tests.
      vi.mocked(settingsService.validateAndSanitize).mockImplementation((s: any) => s);
    });
  });
});
