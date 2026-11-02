import type { VolunteerOpportunity, VolunteerState, PaginatedResponse } from '../contracts/volunteerTypes';

const API_BASE = '/api/volunteer';

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
      console.warn(`[Volunteer] Attempt ${attempt + 1} failed:`, error);
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

export class VolunteerService {
  async getOpportunities(page: number = 1, limit: number = 20): Promise<PaginatedResponse<VolunteerOpportunity>> {
    if (!validatePageParams(page, limit)) {
      console.error('[Volunteer] Invalid pagination params', { page, limit });
      throw new Error('Invalid pagination parameters');
    }

    const url = `${API_BASE}/opportunities?page=${page}&limit=${limit}`;
    console.info(`[Volunteer] Fetching opportunities: page=${page}, limit=${limit}`);

    return retryWithBackoffFn(async () => {
      const response = await fetch(url);
      if (!response.ok) {
        const errorMsg = `Failed to fetch opportunities: ${response.status}`;
        console.error(`[Volunteer] ${errorMsg}`);
        throw new Error(errorMsg);
      }
      const data = await response.json();
      console.info(`[Volunteer] Fetched ${data.items?.length || 0} opportunities`);
      return data;
    });
  }

  async getOpportunity(opportunityId: string): Promise<VolunteerOpportunity> {
    console.info(`[Volunteer] Fetching opportunity: ${opportunityId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/opportunities/${opportunityId}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch opportunity: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[Volunteer] Fetched opportunity: ${opportunityId}`);
      return data;
    });
  }

  async apply(opportunityId: string): Promise<{ success: boolean }> {
    console.info(`[Volunteer] Applying to opportunity: ${opportunityId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/opportunities/${opportunityId}/apply`, {
        method: 'POST'
      });
      if (!response.ok) {
        throw new Error(`Failed to apply: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[Volunteer] Applied to opportunity: ${opportunityId}`);
      return data;
    });
  }
}

export const volunteerService = new VolunteerService();
export default volunteerService;

// Exported for testing only
export const __testing__ = {
  clearCache: () => {},
  setRetryWithBackoff: (fn: typeof retryWithBackoff) => { retryWithBackoffFn = fn; },
  resetRetryWithBackoff: () => { retryWithBackoffFn = retryWithBackoff; }
};