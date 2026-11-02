import type { TtthAccount, TtthTransaction, TtthState, PaginatedResponse } from '../contracts/ttthTypes';

const API_BASE = '/api/ttth';

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
      console.warn(`[Ttth] Attempt ${attempt + 1} failed:`, error);
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

export class TtthService {
  async getAccounts(page: number = 1, limit: number = 20): Promise<PaginatedResponse<TtthAccount>> {
    if (!validatePageParams(page, limit)) {
      console.error('[Ttth] Invalid pagination params', { page, limit });
      throw new Error('Invalid pagination parameters');
    }

    const url = `${API_BASE}/accounts?page=${page}&limit=${limit}`;
    console.info(`[Ttth] Fetching accounts: page=${page}, limit=${limit}`);

    return retryWithBackoffFn(async () => {
      const response = await fetch(url);
      if (!response.ok) {
        const errorMsg = `Failed to fetch accounts: ${response.status}`;
        console.error(`[Ttth] ${errorMsg}`);
        throw new Error(errorMsg);
      }
      const data = await response.json();
      console.info(`[Ttth] Fetched ${data.items?.length || 0} accounts`);
      return data;
    });
  }

  async getAccount(accountId: string): Promise<TtthAccount> {
    console.info(`[Ttth] Fetching account: ${accountId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/accounts/${accountId}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch account: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[Ttth] Fetched account: ${accountId}`);
      return data;
    });
  }

  async getTransactions(accountId: string, page: number = 1, limit: number = 20): Promise<PaginatedResponse<TtthTransaction>> {
    if (!validatePageParams(page, limit)) {
      throw new Error('Invalid pagination parameters');
    }

    const url = `${API_BASE}/accounts/${accountId}/transactions?page=${page}&limit=${limit}`;
    console.info(`[Ttth] Fetching transactions: accountId=${accountId}, page=${page}`);

    return retryWithBackoffFn(async () => {
      const response = await fetch(url);
      if (!response.ok) {
        throw new Error(`Failed to fetch transactions: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[Ttth] Fetched ${data.items?.length || 0} transactions`);
      return data;
    });
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {},
  setRetryWithBackoff: (fn: typeof retryWithBackoff) => { retryWithBackoffFn = fn; },
  resetRetryWithBackoff: () => { retryWithBackoffFn = retryWithBackoff; }
};

export const ttthService = new TtthService();
export default ttthService;