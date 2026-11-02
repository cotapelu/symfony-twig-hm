import { describe, it, expect, vi, beforeEach } from 'vitest';
import { EventsService } from './eventsService';

global.fetch = vi.fn();

describe('EventsService', () => {
  let service: EventsService;

  beforeEach(() => {
    service = new EventsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getEvents', () => {
    it('should fetch all events', async () => {
      const events = [{ id: 'e1', name: 'Concert' }];
      (global.fetch as any).mockResolvedValue(mockResponse(events));
      const result = await service.getEvents();
      expect(result).toEqual(events);
      expect(fetch).toHaveBeenCalledWith('/api/events');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getEvents()).rejects.toThrow('Failed to fetch events');
    });
  });

  describe('getEvent', () => {
    it('should fetch event by id', async () => {
      const event = { id: 'e1' };
      (global.fetch as any).mockResolvedValue(mockResponse(event));
      const result = await service.getEvent('e1');
      expect(result).toEqual(event);
      expect(fetch).toHaveBeenCalledWith('/api/events/e1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getEvent('e1')).rejects.toThrow('Failed to fetch event');
    });
  });

  describe('createEvent', () => {
    it('should create event', async () => {
      const created = { id: 'new', name: 'New Event' };
      (global.fetch as any).mockResolvedValue(mockResponse(created));
      const request = { name: 'New Event', date: '2024-01-01' };
      const result = await service.createEvent(request);
      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/events', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createEvent({ name: 'X' })).rejects.toThrow('Failed to create event');
    });
  });

  describe('updateEvent', () => {
    it('should update event', async () => {
      const updated = { id: 'e1', name: 'Updated' };
      (global.fetch as any).mockResolvedValue(mockResponse(updated));
      const result = await service.updateEvent('e1', { name: 'Updated' });
      expect(result).toEqual(updated);
      expect(fetch).toHaveBeenCalledWith('/api/events/e1', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: 'Updated' })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.updateEvent('e1', {})).rejects.toThrow('Failed to update event');
    });
  });

  describe('deleteEvent', () => {
    it('should delete event', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      await service.deleteEvent('e1');
      expect(fetch).toHaveBeenCalledWith('/api/events/e1', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.deleteEvent('e1')).rejects.toThrow('Failed to delete event');
    });
  });

  describe('getTickets', () => {
    it('should fetch tickets', async () => {
      const tickets = [{ id: 't1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(tickets));
      const result = await service.getTickets();
      expect(result).toEqual(tickets);
      expect(fetch).toHaveBeenCalledWith('/api/events/tickets');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getTickets()).rejects.toThrow('Failed to fetch tickets');
    });
  });

  describe('purchaseTickets', () => {
    it('should purchase tickets', async () => {
      const ticket = { id: 'tnew' };
      (global.fetch as any).mockResolvedValue(mockResponse(ticket));
      const request = { eventId: 'e1', quantity: 2 };
      const result = await service.purchaseTickets(request);
      expect(result).toEqual(ticket);
      expect(fetch).toHaveBeenCalledWith('/api/events/tickets', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.purchaseTickets({ eventId: 'e1', quantity: 1 })).rejects.toThrow('Failed to purchase tickets');
    });
  });

  describe('cancelTicket', () => {
    it('should cancel ticket', async () => {
      const result = { id: 't1', cancelled: true };
      (global.fetch as any).mockResolvedValue(mockResponse(result));
      const res = await service.cancelTicket('t1');
      expect(res).toEqual(result);
      expect(fetch).toHaveBeenCalledWith('/api/events/tickets/t1/cancel', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.cancelTicket('t1')).rejects.toThrow('Failed to cancel ticket');
    });
  });
});
