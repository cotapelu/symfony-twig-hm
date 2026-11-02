import type { SupportTicket, SupportState, PaginatedResponse } from '../contracts/supportTypes';

const API_BASE = '/api/support';

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
      console.warn(`[Support] Attempt ${attempt + 1} failed:`, error);
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

export class SupportService {
  async getTickets(page: number = 1, limit: number = 20): Promise<PaginatedResponse<SupportTicket>> {
    if (!validatePageParams(page, limit)) {
      console.error('[Support] Invalid pagination params', { page, limit });
      throw new Error('Invalid pagination parameters');
    }

    const url = `${API_BASE}/tickets?page=${page}&limit=${limit}`;
    console.info(`[Support] Fetching tickets: page=${page}, limit=${limit}`);

    return retryWithBackoffFn(async () => {
      const response = await fetch(url);
      if (!response.ok) {
        const errorMsg = `Failed to fetch tickets: ${response.status}`;
        console.error(`[Support] ${errorMsg}`);
        throw new Error(errorMsg);
      }
      const data = await response.json();
      console.info(`[Support] Fetched ${data.items?.length || 0} tickets`);
      return data;
    });
  }

  async getTicket(ticketId: string): Promise<SupportTicket> {
    console.info(`[Support] Fetching ticket: ${ticketId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/tickets/${ticketId}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch ticket: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[Support] Fetched ticket: ${ticketId}`);
      return data;
    });
  }

  async createTicket(ticket: { subject: string; description: string; category: string }): Promise<SupportTicket> {
    console.info('[Support] Creating ticket');
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/tickets`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(ticket)
      });
      if (!response.ok) {
        throw new Error(`Failed to create ticket: ${response.status}`);
      }
      const data = await response.json();
      console.info('[Support] Ticket created');
      return data;
    });
  }

  async updateTicket(ticketId: string, updates: Partial<SupportTicket>): Promise<SupportTicket> {
    console.info(`[Support] Updating ticket: ${ticketId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/tickets/${ticketId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updates)
      });
      if (!response.ok) {
        throw new Error(`Failed to update ticket: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[Support] Ticket updated: ${ticketId}`);
      return data;
    });
  }
}

export const supportService = new SupportService();
export default supportService;

// Exported for testing only
export const __testing__ = {
  clearCache: () => {},
  setRetryWithBackoff,
  resetRetryWithBackoff
};