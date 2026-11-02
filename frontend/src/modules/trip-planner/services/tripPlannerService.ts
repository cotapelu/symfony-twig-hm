import type { TripPlan, TripPlannerState, PaginatedResponse } from '../contracts/tripPlannerTypes';

const API_BASE = '/api/trip-planner';

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
      console.warn(`[TripPlanner] Attempt ${attempt + 1} failed:`, error);
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

export class TripPlannerService {
  async getTrips(page: number = 1, limit: number = 20): Promise<PaginatedResponse<TripPlan>> {
    if (!validatePageParams(page, limit)) {
      console.error('[TripPlanner] Invalid pagination params', { page, limit });
      throw new Error('Invalid pagination parameters');
    }

    const url = `${API_BASE}?page=${page}&limit=${limit}`;
    console.info(`[TripPlanner] Fetching trips: page=${page}, limit=${limit}`);

    return retryWithBackoffFn(async () => {
      const response = await fetch(url);
      if (!response.ok) {
        const errorMsg = `Failed to fetch trips: ${response.status}`;
        console.error(`[TripPlanner] ${errorMsg}`);
        throw new Error(errorMsg);
      }
      const data = await response.json();
      console.info(`[TripPlanner] Fetched ${data.items?.length || 0} trips`);
      return data;
    });
  }

  async getTrip(tripId: string): Promise<TripPlan> {
    console.info(`[TripPlanner] Fetching trip: ${tripId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/${tripId}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch trip: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[TripPlanner] Fetched trip: ${tripId}`);
      return data;
    });
  }

  async createTrip(trip: { destination: string; startDate: string; endDate: string; activities: string[] }): Promise<TripPlan> {
    console.info('[TripPlanner] Creating trip');
    return retryWithBackoffFn(async () => {
      const response = await fetch(API_BASE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(trip)
      });
      if (!response.ok) {
        throw new Error(`Failed to create trip: ${response.status}`);
      }
      const data = await response.json();
      console.info('[TripPlanner] Trip created');
      return data;
    });
  }

  async updateTrip(tripId: string, updates: Partial<TripPlan>): Promise<TripPlan> {
    console.info(`[TripPlanner] Updating trip: ${tripId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/${tripId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updates)
      });
      if (!response.ok) {
        throw new Error(`Failed to update trip: ${response.status}`);
      }
      const data = await response.json();
      console.info(`[TripPlanner] Trip updated: ${tripId}`);
      return data;
    });
  }

  async deleteTrip(tripId: string): Promise<void> {
    console.info(`[TripPlanner] Deleting trip: ${tripId}`);
    return retryWithBackoffFn(async () => {
      const response = await fetch(`${API_BASE}/${tripId}`, {
        method: 'DELETE'
      });
      if (!response.ok) {
        throw new Error(`Failed to delete trip: ${response.status}`);
      }
      console.info(`[TripPlanner] Trip deleted: ${tripId}`);
    });
  }
}

export const tripPlannerService = new TripPlannerService();
export default tripPlannerService;

// Exported for testing only
export const __testing__ = {
  clearCache: () => {},
  setRetryWithBackoff,
  resetRetryWithBackoff
};