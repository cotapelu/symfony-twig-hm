import { describe, it, expect, vi, beforeEach } from 'vitest';
import { get } from 'svelte/store';

// Mock service before importing store
const mockGetPayments = vi.fn();
const mockGetMyPayments = vi.fn();
const mockGetPayment = vi.fn();
const mockCreatePayment = vi.fn();

vi.mock('../services/qrpaymentService', () => ({
  qrpaymentService: {
    getPayments: mockGetPayments,
    getMyPayments: mockGetMyPayments,
    getPayment: mockGetPayment,
    createPayment: mockCreatePayment
  }
}));

// Import store after mocking
const { qrpaymentStore, qrpayments, qrpaymentLoading } = await import('./qrpaymentStore');

describe('qrpaymentStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    qrpaymentStore.reset();
  });

  describe('initial state', () => {
    it('should have correct initial state', () => {
      const state = get(qrpaymentStore);
      expect(state.payments).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.currentUserId).toBeNull();
    });

    it('should expose derived stores', () => {
      expect(get(qrpayments)).toEqual([]);
      expect(get(qrpaymentLoading)).toBe(false);
    });
  });

  describe('setCurrentUser', () => {
    it('should set current user id', () => {
      qrpaymentStore.setCurrentUser('user123');
      expect(get(qrpaymentStore).currentUserId).toBe('user123');
    });
  });

  describe('loadPayments', () => {
    it('should load all payments when no user set', async () => {
      const mockPayments = [{ id: '1', amount: 100000 }];
      mockGetPayments.mockResolvedValue(mockPayments);

      await qrpaymentStore.loadPayments();

      const state = get(qrpaymentStore);
      expect(state.payments).toEqual(mockPayments);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(mockGetPayments).toHaveBeenCalled();
    });

    it('should load my payments when user is set', async () => {
      qrpaymentStore.setCurrentUser('user1');
      const mockPayments = [{ id: '1', amount: 100000, userId: 'user1' }];
      mockGetMyPayments.mockResolvedValue(mockPayments);

      await qrpaymentStore.loadPayments();

      const state = get(qrpaymentStore);
      expect(state.payments).toEqual(mockPayments);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(mockGetMyPayments).toHaveBeenCalled();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      mockGetPayments.mockReturnValue(p as any);
      const loadPromise = qrpaymentStore.loadPayments();
      await new Promise(process.nextTick);
      expect(get(qrpaymentStore).loading).toBe(true);
      resolvePromise([{ id: '1', amount: 100000 }]);
      await loadPromise;
      expect(get(qrpaymentStore).loading).toBe(false);
    });

    it('updates qrpayments derived on success', async () => {
      const mockPayments = [{ id: '1', amount: 100000 }];
      mockGetPayments.mockResolvedValue(mockPayments);
      await qrpaymentStore.loadPayments();
      let p: any[] = [];
      const unsub = qrpayments.subscribe(s => { p = s; });
      expect(p).toEqual(mockPayments);
      unsub();
    });

    it('handles empty response', async () => {
      mockGetPayments.mockResolvedValue([]);
      await qrpaymentStore.loadPayments();
      expect(get(qrpaymentStore).payments).toEqual([]);
      expect(get(qrpaymentStore).loading).toBe(false);
      expect(get(qrpaymentStore).error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      mockGetPayments.mockRejectedValue(new Error('Failed'));
      await qrpaymentStore.loadPayments();
      const state = get(qrpaymentStore);
      expect(state.error).toBe('Failed');
      expect(state.loading).toBe(false);
      expect(state.payments).toEqual([]);
    });
  });

  describe('loadPayment', () => {
    it('should load single payment', async () => {
      const mockPayment = { id: '1', amount: 100000, userId: 'user1' };
      mockGetPayment.mockResolvedValue(mockPayment);

      await qrpaymentStore.loadPayment('1');

      const state = get(qrpaymentStore);
      expect(state.payments).toEqual([mockPayment]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      mockGetPayment.mockReturnValue(p as any);
      const loadPromise = qrpaymentStore.loadPayment('1');
      await new Promise(process.nextTick);
      expect(get(qrpaymentStore).loading).toBe(true);
      resolvePromise({ id: '1', amount: 100000, userId: 'user1' });
      await loadPromise;
      expect(get(qrpaymentStore).loading).toBe(false);
    });

    it('should reject unauthorized access', async () => {
      qrpaymentStore.setCurrentUser('user1');
      const mockPayment = { id: '1', amount: 100000, userId: 'user2' };
      mockGetPayment.mockResolvedValue(mockPayment);

      await qrpaymentStore.loadPayment('1');

      const state = get(qrpaymentStore);
      expect(state.error).toContain('Unauthorized');
      expect(state.loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      mockGetPayment.mockRejectedValue(new Error('Not found'));
      try {
        await qrpaymentStore.loadPayment('missing');
      } catch {}
      const state = get(qrpaymentStore);
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
    });
  });

  describe('createPayment', () => {
    it('should create payment successfully', async () => {
      const mockPayment = { id: '1', amount: 100000, merchantId: 'm1' };
      mockCreatePayment.mockResolvedValue(mockPayment);

      const result = await qrpaymentStore.createPayment('m1', 100000);

      expect(result).toEqual(mockPayment);
      const state = get(qrpaymentStore);
      expect(state.payments).toContainEqual(mockPayment);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during create and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      mockCreatePayment.mockReturnValue(p as any);
      const createPromise = qrpaymentStore.createPayment('m1', 100000);
      await new Promise(process.nextTick);
      expect(get(qrpaymentStore).loading).toBe(true);
      resolvePromise({ id: '1', amount: 100000, merchantId: 'm1' });
      await createPromise;
      expect(get(qrpaymentStore).loading).toBe(false);
    });

    it('should handle create payment error and reset loading', async () => {
      mockCreatePayment.mockRejectedValue(new Error('Failed'));

      try {
        await qrpaymentStore.createPayment('m1', 100000);
      } catch {}
      const state = get(qrpaymentStore);
      expect(state.error).toBe('Failed');
      expect(state.loading).toBe(false);
    });
  });

  describe('canAccessPayment', () => {
    it('should allow access when no user is set', () => {
      const payment = { id: '1', userId: 'anyone' };
      expect(qrpaymentStore.canAccessPayment(payment as any)).toBe(true);
    });

    it('should allow access when user owns payment', () => {
      qrpaymentStore.setCurrentUser('user1');
      const payment = { id: '1', userId: 'user1' };
      expect(qrpaymentStore.canAccessPayment(payment as any)).toBe(true);
    });

    it('should deny access when user does not own payment', () => {
      qrpaymentStore.setCurrentUser('user1');
      const payment = { id: '1', userId: 'user2' };
      expect(qrpaymentStore.canAccessPayment(payment as any)).toBe(false);
    });
  });

  describe('clearError and reset', () => {
    it('should clear error', async () => {
      mockGetPayments.mockRejectedValue(new Error('Error'));
      await qrpaymentStore.loadPayments();
      
      qrpaymentStore.clearError();
      expect(get(qrpaymentStore).error).toBeNull();
    });

    it('should reset to initial state', async () => {
      qrpaymentStore.setCurrentUser('user1');
      qrpaymentStore.reset();
      
      const state = get(qrpaymentStore);
      expect(state.currentUserId).toBeNull();
      expect(state.payments).toEqual([]);
    });
  });
});
