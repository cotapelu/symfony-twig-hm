import type { Settings } from '../state/settingsStore';

const API_BASE = '/api/settings';

const DEFAULT_RETRY_COUNT = 3;
const DEFAULT_RETRY_DELAY = 1000;

// Configurable retry function for testing
let retryWithBackoffFn: typeof retryWithBackoff = retryWithBackoff;

export function setRetryWithBackoff(fn: typeof retryWithBackoff) {
  retryWithBackoffFn = fn;
}

export function resetRetryWithBackoff() {
  retryWithBackoffFn = retryWithBackoff;
}

async function retryWithBackoff<T>(
  operation: () => Promise<T>,
  maxRetries: number = DEFAULT_RETRY_COUNT,
  delay: number = DEFAULT_RETRY_DELAY
): Promise<T> {
  let lastError: Error | null = null;
  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      return await operation();
    } catch (error) {
      lastError = error as Error;
      console.warn(`[Settings] Attempt ${attempt + 1} failed:`, error);
      if (attempt < maxRetries - 1) {
        await new Promise(resolve => setTimeout(resolve, delay * Math.pow(2, attempt)));
      }
    }
  }
  throw lastError;
}

function validateSettings(settings: unknown): settings is Settings {
  if (!settings || typeof settings !== 'object') return false;
  const s = settings as Record<string, unknown>;
  if (!s.notifications || typeof s.notifications !== 'object') return false;
  if (!s.privacy || typeof s.privacy !== 'object') return false;
  if (!s.appearance || typeof s.appearance !== 'object') return false;
  return true;
}

function sanitizeString(value: unknown, allowed: string[]): string {
  const str = String(value);
  return allowed.includes(str) ? str : allowed[0];
}

function sanitizeNumber(value: unknown, min: number, max: number, def: number): number {
  const num = Number(value);
  return isNaN(num) ? def : Math.min(Math.max(num, min), max);
}

export class SettingsService {
  async getSettings(): Promise<Settings> {
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch settings: ${response.status}`);
      }
      const data = await response.json();
      if (!validateSettings(data)) {
        console.warn('[Settings] Invalid settings response, using defaults');
        throw new Error('Invalid settings format');
      }
      return data;
    });
  }

  async saveSettings(settings: Settings): Promise<Settings> {
    const validated = this.validateAndSanitize(settings);
    console.info('[Settings] Saving settings');

    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(validated)
      });
      if (!response.ok) {
        throw new Error(`Failed to save settings: ${response.status}`);
      }
      const data = await response.json();
      console.info('[Settings] Settings saved successfully');
      return data;
    });
  }

  validateAndSanitize(settings: Settings): Settings {
    return {
      notifications: {
        email: Boolean(settings.notifications?.email),
        push: Boolean(settings.notifications?.push),
        messages: Boolean(settings.notifications?.messages),
        social: Boolean(settings.notifications?.social)
      },
      privacy: {
        profileVisibility: sanitizeString(settings.privacy?.profileVisibility, ['public', 'friends', 'private']) as 'public' | 'friends' | 'private',
        showOnlineStatus: Boolean(settings.privacy?.showOnlineStatus),
        allowMessages: Boolean(settings.privacy?.allowMessages)
      },
      appearance: {
        theme: sanitizeString(settings.appearance?.theme, ['light', 'dark', 'auto']) as 'light' | 'dark' | 'auto',
        language: sanitizeString(settings.appearance?.language, ['en', 'vi', 'zh', 'ja']),
        fontSize: sanitizeString(settings.appearance?.fontSize, ['small', 'medium', 'large']) as 'small' | 'medium' | 'large'
      }
    };
  }

  exportSettings(settings: Settings): void {
    const blob = new Blob([JSON.stringify(settings, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `settings-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    console.info('[Settings] Settings exported');
  }

  importSettings(file: File): Promise<Settings> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const data = JSON.parse(e.target?.result as string);
          if (!validateSettings(data)) {
            reject(new Error('Invalid settings file format'));
            return;
          }
          console.info('[Settings] Settings imported');
          resolve(this.validateAndSanitize(data));
        } catch (err) {
          reject(new Error('Failed to parse settings file'));
        }
      };
      reader.onerror = () => reject(new Error('Failed to read file'));
      reader.readAsText(file);
    });
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {},
  retryWithBackoff
};

export const settingsService = new SettingsService();
export default settingsService;