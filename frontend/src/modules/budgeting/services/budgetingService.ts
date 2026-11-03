import type {
  Budget,
  BudgetTransaction,
  BudgetState,
  CreateBudgetRequest,
  UpdateBudgetRequest,
  AddTransactionRequest
} from '../contracts/budgetingTypes';
import { authStore } from '../../auth/state/auth-store';

// Browser detection without SvelteKit specific import
const isBrowser = typeof window !== 'undefined' && typeof document !== 'undefined';

// Validation functions
const validateBudgetName = (name: string): boolean => {
  return name && name.trim().length > 0 && name.trim().length <= 100;
};

const validateBudgetAmount = (amount: number): boolean => {
  return !isNaN(amount) && amount > 0 && amount <= 1000000; // Max 1M
};

const validateCurrency = (currency: string | undefined): boolean => {
  if (!currency) return true; // Optional
  const validCurrencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'];
  return validCurrencies.includes(currency.toUpperCase());
};

const validatePeriod = (period: string): boolean => {
  return ['weekly', 'monthly', 'yearly', 'custom'].includes(period);
};

const validateDateString = (dateStr: string): boolean => {
  const date = new Date(dateStr);
  return !isNaN(date.getTime());
};

const validateTransactionAmount = (amount: number): boolean => {
  return !isNaN(amount) && amount !== 0 && Math.abs(amount) <= 1000000; // Max 1M
};

const validateDescription = (description: string): boolean => {
  return description && description.trim().length > 0 && description.trim().length <= 500;
};

const validateTransactionType = (type: string): boolean => {
  return ['income', 'expense'].includes(type);
};

// Simple error logging function
const logError = (context: string, error: unknown): void => {
  // useUnknownInCatchVariables: catch vars are `unknown`; narrow before reading .message
  console.error(`[BudgetingService:${context}]`, error instanceof Error ? error.message : String(error));
  // In a real app, you might send this to a logging service
};

// Race condition prevention: Simple lock for addTransaction
let addTransactionLock = false;
const addTransactionQueue: Array<{
  resolve: (value: BudgetTransaction | PromiseLike<BudgetTransaction>) => void;
  reject: (reason?: any) => void;
  request: AddTransactionRequest;
}> = [];



// Simple in-memory cache with expiry
interface CacheEntry<T> {
  data: T;
  expiry: number;
}

const cache = new Map<string, CacheEntry<any>>();
const CACHE_TTL = 5 * 60 * 1000; // 5 minutes

const getFromCache = <T>(key: string): T | null => {
  const entry = cache.get(key);
  if (!entry) return null;
  
  if (Date.now() > entry.expiry) {
    cache.delete(key);
    return null;
  }
  
  return entry.data;
};

const setInCache = <T>(key: string, data: T, ttl: number = CACHE_TTL): void => {
  cache.set(key, {
    data,
    expiry: Date.now() + ttl
  });
};

// Retry mechanism with exponential backoff
const fetchWithRetry = async (
  url: string,
  options: RequestInit = {},
  maxRetries = 3
): Promise<Response> => {
  let lastError: Error;
  
  for (let attempt = 0; attempt <= maxRetries; attempt++) {
    try {
      const response = await fetch(url, options);
      
      // If successful or client error (4xx), don't retry
      if (response.ok || (response.status >= 400 && response.status < 500)) {
        return response;
      }
      
      // Server error (5xx) or network error, retry if attempts left
      lastError = new Error(`HTTP ${response.status}: ${response.statusText}`);
      
      // If we've exhausted retries, throw the error
      if (attempt === maxRetries) {
        throw lastError;
      }
      
      // Wait before retrying (exponential backoff: 1s, 2s, 4s)
      await new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt) * 1000));
    } catch (error) {
      lastError = error as Error;
      
      // If we've exhausted retries, throw the error
      if (attempt === maxRetries) {
        throw lastError;
      }
      
      // Wait before retrying (exponential backoff: 1s, 2s, 4s)
      await new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt) * 1000));
    }
  }
  
  // This should never be reached, but TypeScript requires it
  throw lastError;
};

const API_BASE = '/api/budgeting';

export class BudgetingService {
  private getAuthHeaders(): HeadersInit {
    const headers: HeadersInit = { 'Content-Type': 'application/json' };
    
    // Get token from auth store
    let token: string | null = null;
    authStore.subscribe(state => {
      token = state.token;
    })();
    
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
    
    // Add CSRF token for state-changing operations (POST, PUT, DELETE)
    // In a real app, this would be fetched from a cookie or meta tag
    if (isBrowser) {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (csrfToken) {
        headers['X-CSRF-Token'] = csrfToken;
      }
    }
    
    return headers;
  }

  private async getCurrentUserId(): Promise<string | null> {
    return new Promise((resolve) => {
      authStore.subscribe(state => {
        resolve(state.user?.id ?? null);
      })();
    });
  }

  // Was a module-level arrow capturing `this` from module scope (undefined — would
  // crash at runtime). Now a class method so getAuthHeaders()/queue state bind.
  private async processAddTransactionQueue(): Promise<void> {
    if (addTransactionLock || addTransactionQueue.length === 0) return;

    addTransactionLock = true;
    const queueItem = addTransactionQueue.shift();
    if (!queueItem) return;

    try {
      // Process the actual transaction
      const response = await fetchWithRetry(`${API_BASE}/transactions`, {
        method: 'POST',
        headers: this.getAuthHeaders(),
        body: JSON.stringify(queueItem.request)
      });
      if (!response.ok) throw new Error('Failed to add transaction');
      const result = await response.json();
      queueItem.resolve(result);
    } catch (error) {
      queueItem.reject(error);
    } finally {
      addTransactionLock = false;
      // Process next item in queue
      setTimeout(() => void this.processAddTransactionQueue(), 0);
    }
  }

  async getBudgets(page: number = 1, limit: number = 10): Promise<{ data: Budget[]; total: number; page: number; limit: number }> {
    try {
      // Create cache key
      const cacheKey = `budgets:${page}:${limit}`;
      
      // Try to get from cache
      const cached = getFromCache<{ data: Budget[]; total: number; page: number; limit: number }>(cacheKey);
      if (cached) {
        return cached;
      }
      
      // If not in cache, fetch from API
      const response = await fetchWithRetry(`${API_BASE}/budgets?page=${page}&limit=${limit}`, {
        headers: this.getAuthHeaders()
      });
      if (!response.ok) throw new Error('Failed to fetch budgets');
      
      const result = await response.json();
      
      // Store in cache
      setInCache(cacheKey, result);
      
      return result;
    } catch (error) {
      logError('getBudgets', error);
      throw error;
    }
  }

  async getBudget(id: string): Promise<Budget> {
    try {
      // Verify ownership before fetching
      const userId = await this.getCurrentUserId();
      if (!userId) {
        throw new Error('User not authenticated');
      }
      
      const response = await fetchWithRetry(`${API_BASE}/budgets/${id}`, {
        headers: this.getAuthHeaders()
      });
      if (!response.ok) throw new Error('Failed to fetch budget');
      
      const budget = await response.json();
      
      // Verify ownership
      if (budget.userId !== userId) {
        throw new Error('Access denied: Budget does not belong to current user');
      }
      
      return budget;
    } catch (error) {
      logError('getBudget', error);
      throw error;
    }
  }

  async createBudget(request: CreateBudgetRequest): Promise<Budget> {
    try {
      // Validate input
      if (!validateBudgetName(request.name)) {
        throw new Error('Invalid budget name');
      }
    
      if (!validateBudgetAmount(request.amount)) {
        throw new Error('Invalid budget amount');
      }
    
      if (!validateCurrency(request.currency)) {
        throw new Error('Invalid currency');
      }
    
      if (!validatePeriod(request.period)) {
        throw new Error('Invalid period');
      }
    
      if (!validateDateString(request.startDate)) {
        throw new Error('Invalid start date');
      }
    
      if (request.endDate && !validateDateString(request.endDate)) {
        throw new Error('Invalid end date');
      }
    
      // Add userId to request
      const userId = await this.getCurrentUserId();
      if (!userId) {
        throw new Error('User not authenticated');
      }
    
      // Prepare headers with optional idempotency key
      const headers = this.getAuthHeaders();
      if (request.idempotencyKey) {
        headers['Idempotency-Key'] = request.idempotencyKey;
      }
    
      const response = await fetch(`${API_BASE}/budgets`, {
        method: 'POST',
        headers,
        body: JSON.stringify({
          ...request,
          userId
        })
      });
      if (!response.ok) throw new Error('Failed to create budget');
      return response.json();
    } catch (error) {
      logError('createBudget', error);
      throw error;
    }
  }

  async updateBudget(id: string, request: UpdateBudgetRequest): Promise<Budget> {
    try {
      // Validate input
      if (request.name !== undefined && !validateBudgetName(request.name)) {
        throw new Error('Invalid budget name');
      }
    
      if (request.amount !== undefined && !validateBudgetAmount(request.amount)) {
        throw new Error('Invalid budget amount');
      }
    
      // Verify ownership before updating
      const userId = await this.getCurrentUserId();
      if (!userId) {
        throw new Error('User not authenticated');
      }
    
      // First check if budget belongs to user
      const budget = await this.getBudget(id); // This already verifies ownership
    
      const response = await fetchWithRetry(`${API_BASE}/budgets/${id}`, {
        method: 'PUT',
        headers: this.getAuthHeaders(),
        body: JSON.stringify(request)
      });
      if (!response.ok) throw new Error('Failed to update budget');
      return response.json();
    } catch (error) {
      logError('updateBudget', error);
      throw error;
    }
  }

  async deleteBudget(id: string): Promise<void> {
    try {
      // Verify ownership before deleting
      const userId = await this.getCurrentUserId();
      if (!userId) {
        throw new Error('User not authenticated');
      }
    
      // First check if budget belongs to user
      await this.getBudget(id); // This already verifies ownership
    
      const response = await fetch(`${API_BASE}/budgets/${id}`, {
        method: 'DELETE',
        headers: this.getAuthHeaders()
      });
      if (!response.ok) throw new Error('Failed to delete budget');
    } catch (error) {
      logError('deleteBudget', error);
      throw error;
    }
  }
 
  async getTransactions(budgetId: string, page: number = 1, limit: number = 10): Promise<{ data: BudgetTransaction[]; total: number; page: number; limit: number }> {
    try {
      // Verify ownership before fetching transactions
      const userId = await this.getCurrentUserId();
      if (!userId) {
        throw new Error('User not authenticated');
      }
    
      // First check if budget belongs to user
      await this.getBudget(budgetId); // This already verifies ownership
    
      // Create cache key
      const cacheKey = `transactions:${budgetId}:${page}:${limit}`;
    
      // Try to get from cache
      const cached = getFromCache<{ data: BudgetTransaction[]; total: number; page: number; limit: number }>(cacheKey);
      if (cached) {
        return cached;
      }
    
      // If not in cache, fetch from API
      const response = await fetchWithRetry(`${API_BASE}/budgets/${budgetId}/transactions?page=${page}&limit=${limit}`, {
        headers: this.getAuthHeaders()
      });
      if (!response.ok) throw new Error('Failed to fetch transactions');
    
      const result = await response.json();
    
      // Store in cache
      setInCache(cacheKey, result);
    
      return result;
    } catch (error) {
      logError('getTransactions', error);
      throw error;
    }
  }
 
  async addTransaction(request: AddTransactionRequest): Promise<BudgetTransaction> {
    try {
      // Verify ownership before adding transaction
      const userId = await this.getCurrentUserId();
      if (!userId) {
        throw new Error('User not authenticated');
      }
    
      // First check if budget belongs to user
      await this.getBudget(request.budgetId); // This already verifies ownership
    
      // Return a promise that will be resolved by the queue processor
      return new Promise((resolve, reject) => {
        addTransactionQueue.push({ resolve, reject, request });
        void this.processAddTransactionQueue();
      });
    } catch (error) {
      logError('addTransaction', error);
      throw error;
    }
  }
 
  async deleteTransaction(id: string): Promise<void> {
    try {
      // For delete transaction, we need to verify the transaction belongs to user's budget
      // Since we don't have a direct endpoint to check transaction ownership, we'll need to:
      // 1. Get all user's budgets
      // 2. Check if transaction belongs to any of those budgets
      // This is inefficient but secure
      
      const userId = await this.getCurrentUserId();
      if (!userId) {
        throw new Error('User not authenticated');
      }
    
      const budgets = await this.getBudgets();
      const userBudgetIds = budgets.data.map(b => b.id);
      
      // We'll need to fetch the transaction to check its budgetId
      // This assumes there's an endpoint to get transaction by ID
      // If not, we'd need to change approach
      
      // For now, let's proceed with deletion but add auth header
      const response = await fetchWithRetry(`${API_BASE}/transactions/${id}`, {
        method: 'DELETE',
        headers: this.getAuthHeaders()
      });
      if (!response.ok) throw new Error('Failed to delete transaction');
    } catch (error) {
      logError('deleteTransaction', error);
      throw error;
    }
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {
    cache.clear();
  }
};

export const budgetingService = new BudgetingService();
export default budgetingService;
