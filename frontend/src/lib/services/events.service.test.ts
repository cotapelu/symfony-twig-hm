import { describe, it, expect, vi, beforeEach } from 'vitest';
import { eventsService } from './events';
import { getApiUrl } from '../config/api';

vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('EventsService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getEvents', () => {
    it('fetches all events', async () => {
      const events = [{ id: 'e1', title: 'Concert', description: '', image: '', location: '', startDate: '', endDate: '', organizer: '', category: '', isAttending: false, attendeesCount: 0 }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ events }) });

      const result = await eventsService.getEvents();

      expect(getApiUrl).toHaveBeenCalledWith('events', '/api/events');
      expect(result).toEqual(events);
    });

    it('returns empty array if no events', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await eventsService.getEvents();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(eventsService.getEvents()).rejects.toThrow('Failed to fetch events');
    });
  });

  describe('getEvent', () => {
    it('fetches a specific event', async () => {
      const event = { id: 'e1', title: 'Event', description: '', image: '', location: '', startDate: '', endDate: '', organizer: '', category: '', isAttending: false, attendeesCount: 0 };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => event });

      const result = await eventsService.getEvent('e1');

      expect(getApiUrl).toHaveBeenCalledWith('events', '/api/events/e1');
      expect(result).toEqual(event);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(eventsService.getEvent('e1')).rejects.toThrow('Failed to fetch event');
    });
  });

  describe('attendEvent', () => {
    it('attends an event', async () => {
      const attendance = { eventId: 'e1', status: 'attending' as const };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => attendance });

      const result = await eventsService.attendEvent('e1');

      expect(getApiUrl).toHaveBeenCalledWith('events', '/api/events/e1/attend');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
      expect(result).toEqual(attendance);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(eventsService.attendEvent('e1')).rejects.toThrow('Failed to attend event');
    });
  });

  describe('getMyEvents', () => {
    it('fetches user\'s events', async () => {
      const events = [{ id: 'e1', title: 'My Event', description: '', image: '', location: '', startDate: '', endDate: '', organizer: '', category: '', isAttending: false, attendeesCount: 0 }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ events }) });

      const result = await eventsService.getMyEvents();

      expect(getApiUrl).toHaveBeenCalledWith('events', '/api/events/my');
      expect(result).toEqual(events);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await eventsService.getMyEvents();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(eventsService.getMyEvents()).rejects.toThrow('Failed to fetch my events');
    });
  });
});
