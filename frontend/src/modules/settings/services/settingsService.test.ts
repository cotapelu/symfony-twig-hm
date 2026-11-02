import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

global.fetch = vi.fn();

import { SettingsService, setRetryWithBackoff, resetRetryWithBackoff } from './settingsService';

// Mock console methods to suppress output during tests
vi.spyOn(console, 'info').mockImplementation(() => {});
vi.spyOn(console, 'warn').mockImplementation(() => {});
vi.spyOn(console, 'error').mockImplementation(() => {});

describe('SettingsService', () => {
  let service: SettingsService;

  beforeEach(() => {
    // Mock the retry function to execute immediately without delays
    setRetryWithBackoff(vi.fn(async (operation: () => Promise<any>) => {
      return operation();
    }));

    vi.clearAllMocks();
    service = new SettingsService();
  });

  afterEach(() => {
    resetRetryWithBackoff();
    vi.restoreAllMocks();
  });

  describe('getSettings', () => {
    it('should fetch and validate settings', async () => {
      const validSettings = {
        notifications: { email: true, push: false, messages: true, social: false },
        privacy: { profileVisibility: 'public', showOnlineStatus: true, allowMessages: true },
        appearance: { theme: 'dark', language: 'en', fontSize: 'medium' }
      };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(validSettings) });

      const result = await service.getSettings();

      expect(result).toEqual(validSettings);
      expect(global.fetch).toHaveBeenCalledWith('/api/settings');
    });

    it('should throw on invalid settings format', async () => {
      const invalid = { foo: 'bar' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(invalid) });

      await expect(service.getSettings()).rejects.toThrow('Invalid settings format');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500, statusText: 'Server Error' });

      await expect(service.getSettings()).rejects.toThrow('Failed to fetch settings: 500');
    });
  });

  describe('saveSettings', () => {
    const validSettings = {
      notifications: { email: true, push: false, messages: true, social: false },
      privacy: { profileVisibility: 'public', showOnlineStatus: true, allowMessages: true },
      appearance: { theme: 'dark', language: 'en', fontSize: 'medium' }
    };

    it('should sanitize and save settings successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(validSettings) });

      const result = await service.saveSettings(validSettings);

      expect(result).toEqual(validSettings);
      expect(global.fetch).toHaveBeenCalledWith('/api/settings', expect.objectContaining({
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: expect.stringContaining('"email":true')
      }));
    });

    it('should sanitize string fields to allowed values', async () => {
      const settings = {
        notifications: { email: true, push: false, messages: true, social: false },
        privacy: { profileVisibility: 'invalid' as any, showOnlineStatus: true, allowMessages: true },
        appearance: { theme: 'light', language: 'fr' as any, fontSize: 'xlarge' as any }
      };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(settings) });

      await service.saveSettings(settings);

      const body = JSON.parse((global.fetch as any).mock.calls[0][1].body);
      expect(body.privacy.profileVisibility).toBe('public');
      expect(body.appearance.language).toBe('en');
      expect(body.appearance.fontSize).toBe('small');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.saveSettings(validSettings)).rejects.toThrow('Failed to save settings: 400');
    });
  });
});