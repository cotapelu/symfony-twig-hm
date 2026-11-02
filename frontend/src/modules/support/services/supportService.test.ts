import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

global.fetch = vi.fn();

import { SupportService, setRetryWithBackoff, resetRetryWithBackoff } from './supportService';

// Suppress console logs
vi.spyOn(console, 'info').mockImplementation(() => {});
vi.spyOn(console, 'warn').mockImplementation(() => {});
vi.spyOn(console, 'error').mockImplementation(() => {});

describe('SupportService', () => {
  let service: SupportService;

  beforeEach(() => {
    // Mock the retry function to execute immediately without delays
    setRetryWithBackoff(vi.fn(async (operation: () => Promise<any>) => {
      return operation();
    }));

    vi.clearAllMocks();
    service = new SupportService();
  });

  afterEach(() => {
    resetRetryWithBackoff();
    vi.restoreAllMocks();
  });

  describe('getTickets', () => {
    it('should fetch tickets with default pagination', async () => {
      const result = { items: [{ id: 't1', subject: 'Issue' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getTickets();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/support/tickets?page=1&limit=20');
    });

    it('should fetch with custom pagination', async () => {
      const result = { items: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getTickets(2, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/support/tickets?page=2&limit=50');
    });

    it('should throw on invalid pagination', async () => {
      await expect(service.getTickets(0, 10)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getTickets(1, 0)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getTickets(1, 150)).rejects.toThrow('Invalid pagination parameters');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getTickets()).rejects.toThrow('Failed to fetch tickets');
    });
  });

  describe('getTicket', () => {
    it('should fetch ticket by id', async () => {
      const ticket = { id: 't1', subject: 'Issue' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(ticket) });

      const result = await service.getTicket('t1');

      expect(result).toEqual(ticket);
      expect(global.fetch).toHaveBeenCalledWith('/api/support/tickets/t1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getTicket('t1')).rejects.toThrow('Failed to fetch ticket');
    });
  });

  describe('createTicket', () => {
    const ticket = { subject: 'Problem', description: 'Details', category: 'technical' };

    it('should create ticket successfully', async () => {
      const created = { id: 't1', ...ticket };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(created) });

      const result = await service.createTicket(ticket);

      expect(result).toEqual(created);
      expect(global.fetch).toHaveBeenCalledWith('/api/support/tickets', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"subject":"Problem"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createTicket(ticket)).rejects.toThrow('Failed to create ticket');
    });
  });

  describe('updateTicket', () => {
    const updates = { status: 'closed' };
    const updated = { id: 't1', subject: 'Issue', status: 'closed' };

    it('should update ticket successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(updated) });

      const result = await service.updateTicket('t1', updates);

      expect(result).toEqual(updated);
      expect(global.fetch).toHaveBeenCalledWith('/api/support/tickets/t1', expect.objectContaining({
        method: 'PATCH',
        body: expect.stringContaining('"status":"closed"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.updateTicket('t1', updates)).rejects.toThrow('Failed to update ticket');
    });
  });
});