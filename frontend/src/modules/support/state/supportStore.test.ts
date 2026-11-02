import { describe, it, expect, vi, beforeEach } from 'vitest';
import { supportStore, supportTickets, supportLoading, supportPagination } from './supportStore';
import { supportService } from '../services/supportService';
import type { SupportTicket } from '../contracts/supportTypes';

// Helper to get current store state
function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factory
const mockTicket = (overrides: Partial<SupportTicket> = {}): SupportTicket => ({
  id: `ticket-${Math.random().toString(36).substr(2, 5)}`,
  subject: 'Test Ticket',
  description: 'A test ticket',
  category: 'general',
  status: 'open' as const,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  ...overrides
});

const mockPaginatedResponse = (items: SupportTicket[], page: number, limit: number, total: number) => ({
  items,
  page,
  limit,
  total,
  totalPages: Math.ceil(total / limit)
});

describe('SupportStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    supportStore.reset();
  });

  describe('initial state', () => {
    it('has empty tickets and default pagination', () => {
      const state = getCurrentState(supportStore);
      expect(state.tickets).toEqual([]);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('loadTickets', () => {
    it('loads tickets and updates pagination', async () => {
      const tickets = [mockTicket(), mockTicket()];
      vi.spyOn(supportService, 'getTickets').mockResolvedValue(mockPaginatedResponse(tickets, 1, 20, 2));
      await supportStore.loadTickets();
      const state = getCurrentState(supportStore);
      expect(state.tickets).toEqual(tickets);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 2, totalPages: 1 });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads specific page and limit', async () => {
      const tickets = [mockTicket()];
      vi.spyOn(supportService, 'getTickets').mockResolvedValue(mockPaginatedResponse(tickets, 2, 10, 15));
      await supportStore.loadTickets(2, 10);
      expect(supportService.getTickets).toHaveBeenCalledWith(2, 10);
      const state = getCurrentState(supportStore);
      expect(state.pagination.page).toBe(2);
      expect(state.pagination.limit).toBe(10);
      expect(state.pagination.total).toBe(15);
      expect(state.pagination.totalPages).toBe(2);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(supportService, 'getTickets').mockReturnValue(p as any);
      const loadPromise = supportStore.loadTickets();
      await new Promise(process.nextTick);
      expect(getCurrentState(supportStore).loading).toBe(true);
      resolvePromise(mockPaginatedResponse([mockTicket()], 1, 20, 1));
      await loadPromise;
      expect(getCurrentState(supportStore).loading).toBe(false);
    });

    it('updates supportTickets derived on success', async () => {
      const tickets = [mockTicket()];
      vi.spyOn(supportService, 'getTickets').mockResolvedValue(mockPaginatedResponse(tickets, 1, 20, 1));
      await supportStore.loadTickets();
      let t: SupportTicket[] = [];
      const unsub = supportTickets.subscribe(s => { t = s; });
      expect(t).toEqual(tickets);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(supportService, 'getTickets').mockResolvedValue(mockPaginatedResponse([], 1, 20, 0));
      await supportStore.loadTickets();
      expect(getCurrentState(supportStore).tickets).toEqual([]);
      expect(getCurrentState(supportStore).pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
      expect(getCurrentState(supportStore).loading).toBe(false);
      expect(getCurrentState(supportStore).error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(supportService, 'getTickets').mockRejectedValue(new Error('Failed to fetch'));
      await supportStore.loadTickets();
      const state = getCurrentState(supportStore);
      expect(state.error).toBe('Failed to fetch');
      expect(state.loading).toBe(false);
      expect(state.tickets).toEqual([]);
    });
  });

  describe('loadMore', () => {


    it('does not load more if on last page', async () => {
      const tickets = [mockTicket()];
      vi.spyOn(supportService, 'getTickets').mockResolvedValue(mockPaginatedResponse(tickets, 1, 20, 1)); // only 1 page

      await supportStore.loadTickets(1, 20);
      // Now loadMore should not call service again because page=1, totalPages=1
      await supportStore.loadMore();

      expect(supportService.getTickets).toHaveBeenCalledTimes(1); // only initial
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(supportService, 'getTickets').mockRejectedValue(new Error('test'));
      try {
        await supportStore.loadTickets();
      } catch (e) {}
      let state = getCurrentState(supportStore);
      expect(state.error).toBe('test');

      supportStore.clearError();
      state = getCurrentState(supportStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      // Load some data
      const tickets = [mockTicket()];
      vi.spyOn(supportService, 'getTickets').mockResolvedValue(mockPaginatedResponse(tickets, 1, 20, 1));
      await supportStore.loadTickets();
      let state = getCurrentState(supportStore);
      expect(state.tickets.length).toBeGreaterThan(0);

      supportStore.reset();
      state = getCurrentState(supportStore);
      expect(state.tickets).toEqual([]);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('derived stores', () => {
    it('supportTickets reflects state.tickets', async () => {
      const tickets = [mockTicket(), mockTicket()];
      vi.spyOn(supportService, 'getTickets').mockResolvedValue(mockPaginatedResponse(tickets, 1, 20, 2));
      await supportStore.loadTickets();
      let t: SupportTicket[] = [];
      const unsub = supportTickets.subscribe(s => { t = s; });
      expect(t).toEqual(tickets);
      unsub();
    });

    it('supportLoading reflects state.loading during fetch', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(supportService, 'getTickets').mockReturnValue(p as any);
      const loadPromise = supportStore.loadTickets();
      await new Promise(process.nextTick);
      let l = false;
      const unsub = supportLoading.subscribe(s => { l = s; });
      expect(l).toBe(true);
      resolvePromise(mockPaginatedResponse([mockTicket()], 1, 20, 1));
      await loadPromise;
      expect(l).toBe(false);
      unsub();
    });

    it('supportPagination reflects state.pagination', async () => {
      const tickets = [mockTicket()];
      vi.spyOn(supportService, 'getTickets').mockResolvedValue(mockPaginatedResponse(tickets, 2, 10, 25));
      await supportStore.loadTickets(2, 10);
      let p = { page: 0, limit: 0, total: 0, totalPages: 0 };
      const unsub = supportPagination.subscribe(s => { p = s; });
      expect(p).toEqual({ page: 2, limit: 10, total: 25, totalPages: 3 });
      unsub();
    });

    it('supportTickets updates on loadMore (replaces with new page)', async () => {
      const page1 = [mockTicket()];
      const page2 = [mockTicket(), mockTicket()];
      // Setup: total 40 items, limit 20 -> 2 pages
      vi.spyOn(supportService, 'getTickets')
        .mockResolvedValueOnce(mockPaginatedResponse(page1, 1, 20, 40))
        .mockResolvedValueOnce(mockPaginatedResponse(page2, 2, 20, 40));
      await supportStore.loadTickets(); // defaults: page=1, limit=20
      let t: SupportTicket[] = [];
      const unsub = supportTickets.subscribe(s => { t = s; });
      expect(t).toEqual(page1);
      await supportStore.loadMore(); // loads page 2, replaces tickets
      expect(t).toEqual(page2);
      // pagination should reflect page 2
      let p = { page: 0, limit: 0, total: 0, totalPages: 0 };
      const unsubP = supportPagination.subscribe(s => { p = s; });
      expect(p.page).toBe(2);
      expect(p.totalPages).toBe(2);
      unsubP();
      unsub();
    });
  });
});
