import type { Voicemail, PaginatedResponse } from '../contracts/voicemailTypes';

const API_BASE = '/api/voicemail';

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
      console.warn(`[Voicemail] Attempt ${attempt + 1} failed:`, error);
      if (attempt < maxRetries - 1) {
        await new Promise(resolve => setTimeout(resolve, delay * Math.pow(2, attempt)));
      }
    }
  }
  throw lastError;
}

function validatePageParams(page: number, limit: number): boolean {
  return page > 0 && limit > 0 && limit <= 100;
}

export class VoicemailService {
  async getVoicemails(page: number = 1, limit: number = 20): Promise<PaginatedResponse<Voicemail>> {
    if (!validatePageParams(page, limit)) {
      console.error('[Voicemail] Invalid pagination params', { page, limit });
      throw new Error('Invalid pagination parameters');
    }

    const url = `${API_BASE}?page=${page}&limit=${limit}`;
    console.info(`[Voicemail] Fetching voicemails: page=${page}, limit=${limit}`);

    return retryWithBackoffFn(async () => {
      const response = await fetch(url);
      if (!response.ok) {
        const errorMsg = `Failed to fetch voicemails: ${response.status}`;
        console.error(`[Voicemail] ${errorMsg}`);
        throw new Error(errorMsg);
      }
      const data = await response.json();
      console.info(`[Voicemail] Fetched ${data.items?.length || 0} voicemails`);
      return data;
    });
  }

  async getVoicemail(voicemailId: string): Promise<Voicemail> {
    console.info(`[Voicemail] Fetching voicemail: ${voicemailId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/${voicemailId}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch voicemail: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[Voicemail] Fetched voicemail: ${voicemailId}`);
      return data;
    });
  }

  async markAsListened(voicemailId: string): Promise<void> {
    console.info(`[Voicemail] Marking voicemail as listened: ${voicemailId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/${voicemailId}/listen`, {
        method: 'POST'
      });
      if (!response.ok) {
        throw new Error(`Failed to mark voicemail as listened: ${response.status}`);
      }
      console.info(`[Voicemail] Voicemail marked as listened: ${voicemailId}`);
    });
  }

  async deleteVoicemail(voicemailId: string): Promise<void> {
    console.info(`[Voicemail] Deleting voicemail: ${voicemailId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/${voicemailId}`, {
        method: 'DELETE'
      });
      if (!response.ok) {
        throw new Error(`Failed to delete voicemail: ${response.status}`);
      }
      console.info(`[Voicemail] Voicemail deleted: ${voicemailId}`);
    });
  }
}

export const voicemailService = new VoicemailService();
export default voicemailService;

// Exported for testing only
export const __testing__ = {
  clearCache: () => {},
  setRetryWithBackoff: (fn: typeof retryWithBackoff) => { retryWithBackoffFn = fn; },
  resetRetryWithBackoff: () => { retryWithBackoffFn = retryWithBackoff; }
};