import { describe, it, expect, vi, beforeEach } from 'vitest';
import { QueueService } from './queueService';

// Mock the queueStore module
vi.mock('../state/queueStore', () => ({
  queueStore: {
    setLoading: vi.fn(),
    setError: vi.fn(),
    setQueues: vi.fn(),
    setCurrentQueue: vi.fn(),
    addTicket: vi.fn(),
    updateTicket: vi.fn(),
    setWaitingTickets: vi.fn(),
    setCalledTickets: vi.fn(),
    setStatistics: vi.fn(),
    setMyTickets: vi.fn()
  }
}));

// Import the mocked store after vi.mock
import { queueStore } from '../state/queueStore';

global.fetch = vi.fn();

describe('QueueService', () => {
  let service: QueueService;

  beforeEach(() => {
    service = new QueueService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue({ success: true, data })
  });

  // Mock API error response
  const mockApiError = (errorMsg: string) => ({
    ok: true,
    json: vi.fn().mockResolvedValue({ success: false, error: errorMsg })
  });

  describe('loadActiveQueues', () => {
    it('should fetch active queues and update store', async () => {
      const queues = [{ id: 'q1', name: 'Queue 1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(queues));

      const result = await service.loadActiveQueues();

      expect(result).toEqual(queues);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/active', { headers: { 'Content-Type': 'application/json' } });
      expect(queueStore.setQueues).toHaveBeenCalledWith(queues);
    });

    it('should throw when API returns error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('No active queues'));

      await expect(service.loadActiveQueues()).rejects.toThrow('No active queues');
    });

    it('should throw on fetch failure', async () => {
      (global.fetch as any).mockRejectedValue(new Error('Network error'));

      await expect(service.loadActiveQueues()).rejects.toThrow('Network error');
    });
  });

  describe('loadOpenQueues', () => {
    it('should fetch open queues and update store', async () => {
      const queues = [{ id: 'q2' }];
      (global.fetch as any).mockResolvedValue(mockResponse(queues));

      const result = await service.loadOpenQueues();

      expect(result).toEqual(queues);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/open', { headers: { 'Content-Type': 'application/json' } });
      expect(queueStore.setQueues).toHaveBeenCalledWith(queues);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('No open queues'));

      await expect(service.loadOpenQueues()).rejects.toThrow('No open queues');
    });
  });

  describe('loadQueuesByWard', () => {
    it('should fetch queues by ward code', async () => {
      const queues = [{ id: 'q3' }];
      (global.fetch as any).mockResolvedValue(mockResponse(queues));

      const result = await service.loadQueuesByWard('W001');

      expect(result).toEqual(queues);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/ward/W001', { headers: { 'Content-Type': 'application/json' } });
      expect(queueStore.setQueues).toHaveBeenCalledWith(queues);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Ward not found'));

      await expect(service.loadQueuesByWard('W999')).rejects.toThrow('Ward not found');
    });
  });

  describe('loadQueue', () => {
    it('should fetch a specific queue by id', async () => {
      const queue = { id: 'q1', name: 'Queue 1' };
      (global.fetch as any).mockResolvedValue(mockResponse(queue));

      const result = await service.loadQueue('q1');

      expect(result).toEqual(queue);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1', { headers: { 'Content-Type': 'application/json' } });
      expect(queueStore.setCurrentQueue).toHaveBeenCalledWith(queue);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Queue not found'));

      await expect(service.loadQueue('nonexistent')).rejects.toThrow('Queue not found');
    });
  });

  describe('createQueue', () => {
    it('should create a new queue', async () => {
      const request = { name: 'New Queue', wardCode: 'W001' };
      const created = { id: 'newq', ...request };
      (global.fetch as any).mockResolvedValue(mockResponse(created));

      const result = await service.createQueue(request);

      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Invalid data'));

      await expect(service.createQueue({ name: 'Test' } as any)).rejects.toThrow('Invalid data');
    });
  });

  describe('updateQueue', () => {
    it('should update a queue', async () => {
      const updates = { name: 'Updated Name' };
      const updated = { id: 'q1', ...updates };
      (global.fetch as any).mockResolvedValue(mockResponse(updated));

      const result = await service.updateQueue('q1', updates);

      expect(result).toEqual(updated);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updates)
      });
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Update failed'));

      await expect(service.updateQueue('q1', { name: 'x' })).rejects.toThrow('Update failed');
    });
  });

  describe('activateQueue', () => {
    it('should activate a queue', async () => {
      const queue = { id: 'q1', active: true };
      (global.fetch as any).mockResolvedValue(mockResponse(queue));

      const result = await service.activateQueue('q1');

      expect(result).toEqual(queue);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1/activate', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Cannot activate'));

      await expect(service.activateQueue('q1')).rejects.toThrow('Cannot activate');
    });
  });

  describe('deactivateQueue', () => {
    it('should deactivate a queue', async () => {
      const queue = { id: 'q1', active: false };
      (global.fetch as any).mockResolvedValue(mockResponse(queue));

      const result = await service.deactivateQueue('q1');

      expect(result).toEqual(queue);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1/deactivate', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Cannot deactivate'));

      await expect(service.deactivateQueue('q1')).rejects.toThrow('Cannot deactivate');
    });
  });

  describe('joinQueue', () => {
    it('should join a queue', async () => {
      const request = { queueId: 'q1', userId: 'u1' };
      const ticket = { id: 't1', queueId: 'q1', userId: 'u1', status: 'waiting' };
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));

      const result = await service.joinQueue(request);

      expect(result).toEqual(ticket);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1/join', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
      expect(queueStore.addTicket).toHaveBeenCalledWith(ticket);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Join failed'));

      await expect(service.joinQueue({ queueId: 'q1', userId: 'u1' } as any)).rejects.toThrow('Join failed');
    });
  });

  describe('callNextTicket', () => {
    it('should call next ticket and update store when ticket returned', async () => {
      const request = { queueId: 'q1', priority: false };
      const ticket = { id: 't1', queueId: 'q1', status: 'called' };
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));

      // Spy on the service's other methods and mock them
      const loadWaitingSpy = vi.spyOn(service, 'loadWaitingTickets').mockResolvedValue([]);
      const loadCalledSpy = vi.spyOn(service, 'loadCalledTickets').mockResolvedValue([]);

      const result = await service.callNextTicket(request);

      expect(result).toEqual(ticket);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1/next', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
      expect(queueStore.updateTicket).toHaveBeenCalledWith(ticket.id, ticket);
      expect(loadWaitingSpy).toHaveBeenCalledWith('q1');
      expect(loadCalledSpy).toHaveBeenCalledWith('q1');
    });

    it('should return null and not update store when no ticket', async () => {
      const request = { queueId: 'q1' };
      const ticket = null;
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));
      const loadWaitingSpy = vi.spyOn(service, 'loadWaitingTickets');
      const loadCalledSpy = vi.spyOn(service, 'loadCalledTickets');

      const result = await service.callNextTicket(request);

      expect(result).toBeNull();
      expect(queueStore.updateTicket).not.toHaveBeenCalled();
      expect(loadWaitingSpy).not.toHaveBeenCalled();
      expect(loadCalledSpy).not.toHaveBeenCalled();
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('No tickets'));

      await expect(service.callNextTicket({ queueId: 'q1' } as any)).rejects.toThrow('No tickets');
    });
  });

  describe('startTicket', () => {
    it('should start a ticket', async () => {
      const ticketId = 't1';
      const ticket = { id: ticketId, status: 'in_progress' };
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));

      const result = await service.startTicket(ticketId);

      expect(result).toEqual(ticket);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/tickets/t1/start', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
      expect(queueStore.updateTicket).toHaveBeenCalledWith(ticketId, ticket);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Cannot start'));

      await expect(service.startTicket('t1')).rejects.toThrow('Cannot start');
    });
  });

  describe('completeTicket', () => {
    it('should complete a ticket with notes', async () => {
      const ticketId = 't1';
      const ticket = { id: ticketId, status: 'completed', notes: 'Done' };
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));

      const result = await service.completeTicket(ticketId, 'Done');

      expect(result).toEqual(ticket);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/tickets/t1/complete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notes: 'Done' })
      });
      expect(queueStore.updateTicket).toHaveBeenCalledWith(ticketId, ticket);
    });

    it('should complete without notes', async () => {
      const ticketId = 't1';
      const ticket = { id: ticketId, status: 'completed' };
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));

      const result = await service.completeTicket(ticketId);

      expect(result).toEqual(ticket);
      // The body will be JSON.stringify({}) when notes is undefined
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/tickets/t1/complete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({})
      });
      expect(queueStore.updateTicket).toHaveBeenCalledWith(ticketId, ticket);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Cannot complete'));

      await expect(service.completeTicket('t1')).rejects.toThrow('Cannot complete');
    });
  });

  describe('cancelTicket', () => {
    it('should cancel a ticket with reason', async () => {
      const ticketId = 't1';
      const ticket = { id: ticketId, status: 'cancelled', reason: 'Changed mind' };
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));

      const result = await service.cancelTicket(ticketId, 'Changed mind');

      expect(result).toEqual(ticket);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/tickets/t1/cancel', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reason: 'Changed mind' })
      });
      expect(queueStore.updateTicket).toHaveBeenCalledWith(ticketId, ticket);
    });

    it('should cancel without reason', async () => {
      const ticketId = 't1';
      const ticket = { id: ticketId, status: 'cancelled' };
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));

      const result = await service.cancelTicket(ticketId);

      expect(result).toEqual(ticket);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/tickets/t1/cancel', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({})
      });
      expect(queueStore.updateTicket).toHaveBeenCalledWith(ticketId, ticket);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Cannot cancel'));

      await expect(service.cancelTicket('t1')).rejects.toThrow('Cannot cancel');
    });
  });

  describe('skipTicket', () => {
    it('should skip a ticket', async () => {
      const ticketId = 't1';
      const ticket = { id: ticketId, status: 'skipped' };
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));

      const result = await service.skipTicket(ticketId);

      expect(result).toEqual(ticket);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/tickets/t1/skip', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
      expect(queueStore.updateTicket).toHaveBeenCalledWith(ticketId, ticket);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Cannot skip'));

      await expect(service.skipTicket('t1')).rejects.toThrow('Cannot skip');
    });
  });

  describe('loadTicket', () => {
    it('should load a ticket by id', async () => {
      const ticket = { id: 't1' };
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));

      const result = await service.loadTicket('t1');

      expect(result).toEqual(ticket);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/tickets/t1', { headers: { 'Content-Type': 'application/json' } });
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Ticket not found'));

      await expect(service.loadTicket('t1')).rejects.toThrow('Ticket not found');
    });
  });

  describe('loadTicketsByQueue', () => {
    it('should load tickets for a queue with default params', async () => {
      const tickets = [{ id: 't1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(tickets));

      const result = await service.loadTicketsByQueue('q1');

      expect(result).toEqual(tickets);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1/tickets?limit=50&offset=0', { headers: { 'Content-Type': 'application/json' } });
    });

    it('should load tickets with status', async () => {
      const tickets = [{ id: 't2' }];
      (global.fetch as any).mockResolvedValue(mockResponse(tickets));

      const result = await service.loadTicketsByQueue('q1', 'waiting');

      expect(result).toEqual(tickets);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1/tickets?limit=50&offset=0&status=waiting', { headers: { 'Content-Type': 'application/json' } });
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('No tickets'));

      await expect(service.loadTicketsByQueue('q1')).rejects.toThrow('No tickets');
    });
  });

  describe('loadWaitingTickets', () => {
    it('should load waiting tickets and update store', async () => {
      const tickets = [{ id: 't1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(tickets));

      const result = await service.loadWaitingTickets('q1');

      expect(result).toEqual(tickets);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1/waiting', { headers: { 'Content-Type': 'application/json' } });
      expect(queueStore.setWaitingTickets).toHaveBeenCalledWith(tickets);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Failed'));

      await expect(service.loadWaitingTickets('q1')).rejects.toThrow('Failed');
    });
  });

  describe('loadCalledTickets', () => {
    it('should load called tickets and update store', async () => {
      const tickets = [{ id: 't2' }];
      (global.fetch as any).mockResolvedValue(mockResponse(tickets));

      const result = await service.loadCalledTickets('q1');

      expect(result).toEqual(tickets);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1/called', { headers: { 'Content-Type': 'application/json' } });
      expect(queueStore.setCalledTickets).toHaveBeenCalledWith(tickets);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Failed'));

      await expect(service.loadCalledTickets('q1')).rejects.toThrow('Failed');
    });
  });

  describe('loadQueueStatistics', () => {
    it('should load statistics and update store', async () => {
      const stats = { queueId: 'q1', totalTickets: 100, averageWait: 5 };
      (global.fetch as any).mockResolvedValue(mockResponse(stats));

      const result = await service.loadQueueStatistics('q1');

      expect(result).toEqual(stats);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/q1/statistics', { headers: { 'Content-Type': 'application/json' } });
      expect(queueStore.setStatistics).toHaveBeenCalledWith(stats);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('Failed'));

      await expect(service.loadQueueStatistics('q1')).rejects.toThrow('Failed');
    });
  });

  describe('loadTicketsByPhone', () => {
    it('should load tickets by phone and update store', async () => {
      const tickets = [{ id: 't3' }];
      (global.fetch as any).mockResolvedValue(mockResponse(tickets));

      const result = await service.loadTicketsByPhone('1234567890');

      expect(result).toEqual(tickets);
      expect(fetch).toHaveBeenCalledWith('/api/v1/queues/tickets/by-phone/1234567890', { headers: { 'Content-Type': 'application/json' } });
      expect(queueStore.setMyTickets).toHaveBeenCalledWith(tickets);
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue(mockApiError('No tickets'));

      await expect(service.loadTicketsByPhone('1234567890')).rejects.toThrow('No tickets');
    });
  });
});
