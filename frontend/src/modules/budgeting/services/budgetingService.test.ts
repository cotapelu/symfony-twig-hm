import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock authStore
vi.mock('../../auth/state/auth-store', () => ({
  authStore: {
    subscribe: vi.fn((cb: any) => {
      cb({ token: 'test-token', user: { id: 'u1' } });
      return () => {};
    })
  }
}));

// Mock global fetch
global.fetch = vi.fn();

// Import after mocks
import { BudgetingService } from './budgetingService';
import { __testing__ } from './budgetingService';

describe('BudgetingService', () => {
  let service: BudgetingService;

  beforeEach(() => {
    vi.useFakeTimers();
    service = new BudgetingService();
    __testing__.clearCache();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  const mockFetchResponse = (data: any, ok = true, status = 200) => {
    const response: any = { ok, status };
    response.json = async () => data;
    return response;
  };

  const runAllTimersAsync = () => vi.runAllTimersAsync() as Promise<void>;

  describe('getBudgets', () => {
    it('should fetch budgets when not cached', async () => {
      const result = { data: [], total: 0, page: 1, limit: 10 };
      (global.fetch as any).mockResolvedValue(mockFetchResponse(result));

      const res = await service.getBudgets(1, 10);

      expect(res).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/budgeting/budgets?page=1&limit=10'),
        expect.objectContaining({
          headers: expect.objectContaining({ Authorization: 'Bearer test-token' })
        })
      );
    });

    it('should return cached result on subsequent calls', async () => {
      const result = { data: [], total: 0, page: 1, limit: 10 };
      (global.fetch as any).mockResolvedValue(mockFetchResponse(result));

      await service.getBudgets(1, 10);
      await service.getBudgets(1, 10);

      expect(global.fetch).toHaveBeenCalledTimes(1);
    });

    it('should throw on fetch error after retries', async () => {
      (global.fetch as any).mockRejectedValue(new Error('Network error'));

      const promise = service.getBudgets(1, 10);
      const assertion = expect(promise).rejects.toThrow('Network error');
      await runAllTimersAsync(); // run retry backoffs
      await assertion;
    });

    it('should throw on non-ok response after retries', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500, json: async () => ({ message: 'Server error' }) });

      const promise = service.getBudgets(1, 10);
      const assertion = expect(promise).rejects.toThrow('HTTP 500: undefined');
      await runAllTimersAsync();
      await assertion;
    });
  });

  describe('getBudget', () => {
    it('should fetch budget and verify ownership', async () => {
      const budget = { id: 'b1', userId: 'u1', name: 'Test', amount: 1000 };
      (global.fetch as any).mockResolvedValue(mockFetchResponse(budget));

      const result = await service.getBudget('b1');

      expect(result).toEqual(budget);
    });

    it('should throw if budget belongs to another user', async () => {
      const budget = { id: 'b1', userId: 'u2', name: 'Test', amount: 1000 };
      (global.fetch as any).mockResolvedValue(mockFetchResponse(budget));

      await expect(service.getBudget('b1')).rejects.toThrow('Access denied');
    });

    it('should throw on not found', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getBudget('b1')).rejects.toThrow('Failed to fetch budget');
    });
  });

  describe('createBudget', () => {
    const validRequest = {
      name: 'My Budget',
      amount: 1000,
      currency: 'USD',
      period: 'monthly',
      startDate: '2024-01-01'
    };

    it('should create budget with valid data', async () => {
      const created = { id: 'b1', ...validRequest, userId: 'u1' };
      (global.fetch as any).mockResolvedValue(mockFetchResponse(created));

      const result = await service.createBudget(validRequest);

      expect(result).toEqual(created);
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/budgeting/budgets'),
        expect.objectContaining({
          method: 'POST',
          headers: expect.objectContaining({ 'Content-Type': 'application/json' }),
          body: expect.stringContaining(JSON.stringify({ ...validRequest, userId: 'u1' }))
        })
      );
    });

    it('should validate name - empty', async () => {
      const invalid = { ...validRequest, name: '' };
      await expect(service.createBudget(invalid)).rejects.toThrow('Invalid budget name');
    });

    it('should validate name - too long', async () => {
      const invalid = { ...validRequest, name: 'a'.repeat(101) };
      await expect(service.createBudget(invalid)).rejects.toThrow('Invalid budget name');
    });

    it('should validate amount - negative', async () => {
      const invalid = { ...validRequest, amount: -1 };
      await expect(service.createBudget(invalid)).rejects.toThrow('Invalid budget amount');
    });

    it('should validate amount - zero', async () => {
      const invalid = { ...validRequest, amount: 0 };
      await expect(service.createBudget(invalid)).rejects.toThrow('Invalid budget amount');
    });

    it('should validate amount - exceeds max', async () => {
      const invalid = { ...validRequest, amount: 1000001 };
      await expect(service.createBudget(invalid)).rejects.toThrow('Invalid budget amount');
    });

    it('should validate currency - invalid', async () => {
      const invalid = { ...validRequest, currency: 'XYZ' };
      await expect(service.createBudget(invalid)).rejects.toThrow('Invalid currency');
    });

    it('should validate period - invalid', async () => {
      const invalid = { ...validRequest, period: 'invalid' };
      await expect(service.createBudget(invalid)).rejects.toThrow('Invalid period');
    });

    it('should validate startDate - invalid', async () => {
      const invalid = { ...validRequest, startDate: 'not-a-date' };
      await expect(service.createBudget(invalid)).rejects.toThrow('Invalid start date');
    });
  });

  describe('getTransactions', () => {
    it('should fetch transactions for a budget with ownership check', async () => {
      const budget = { id: 'b1', userId: 'u1' };
      const transactions = { data: [], total: 0, page: 1, limit: 10 };
      (global.fetch as any).mockResolvedValueOnce(mockFetchResponse(budget)); // getBudget
      (global.fetch as any).mockResolvedValueOnce(mockFetchResponse(transactions)); // getTransactions

      const result = await service.getTransactions('b1', 1, 10);

      expect(result).toEqual(transactions);
      // Should call getBudget then fetch transactions
      expect(global.fetch).toHaveBeenCalledTimes(2);
    });

    it('should throw if budget not accessible', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getTransactions('b1')).rejects.toThrow('Failed to fetch budget');
    });
  });
});
