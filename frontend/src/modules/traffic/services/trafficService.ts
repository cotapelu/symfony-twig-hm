import type { TrafficInfo, TrafficState, PaginatedResponse } from '../contracts/trafficTypes';

const API_BASE = '/api/traffic';

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
      console.warn(`[Traffic] Attempt ${attempt + 1} failed:`, error);
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

export class TrafficService {
  async getAlerts(page: number = 1, limit: number = 20): Promise<PaginatedResponse<TrafficInfo>> {
    if (!validatePageParams(page, limit)) {
      console.error('[Traffic] Invalid pagination params', { page, limit });
      throw new Error('Invalid pagination parameters');
    }

    const url = `${API_BASE}/alerts?page=${page}&limit=${limit}`;
    console.info(`[Traffic] Fetching alerts: page=${page}, limit=${limit}`);

    return retryWithBackoffFn(async () => {
      const response = await fetch(url);
      if (!response.ok) {
        const errorMsg = `Failed to fetch alerts: ${response.status}`;
        console.error(`[Traffic] ${errorMsg}`);
        throw new Error(errorMsg);
      }
      const data = await response.json();
      console.info(`[Traffic] Fetched ${data.items?.length || 0} alerts`);
      return data;
    });
  }

  async getAlert(alertId: string): Promise<TrafficInfo> {
    console.info(`[Traffic] Fetching alert: ${alertId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/alerts/${alertId}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch alert: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[Traffic] Fetched alert: ${alertId}`);
      return data;
    });
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {},
  setRetryWithBackoff,
  resetRetryWithBackoff
};

export const trafficService = new TrafficService();
export default trafficService;